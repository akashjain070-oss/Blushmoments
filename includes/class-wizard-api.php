<?php
/**
 * REST API the builder wizard talks to.
 *
 * Flow:
 *   1. POST /bm/v1/surprise               -> creates a draft, returns its slug
 *   2. POST /bm/v1/surprise/{id}/order    -> creates a Razorpay order
 *   3. Razorpay Checkout runs client-side, then
 *      POST /bm/v1/surprise/{id}/verify  -> verifies the payment signature and
 *      flips payment_status to 'paid'
 *
 * NOTE: verification here is the client-side signature check only. A
 * Razorpay webhook (verified via X-Razorpay-Signature + a webhook secret,
 * configured in the Razorpay dashboard) is the more robust production
 * pattern and should be added as a follow-up hardening step.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Wizard_API {

	const NAMESPACE_ = 'bm/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( self::NAMESPACE_, '/surprise', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'create_draft' ),
			'permission_callback' => '__return_true', // public — anyone can start building a surprise
		) );

		register_rest_route( self::NAMESPACE_, '/surprise/(?P<id>\d+)/order', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'create_order' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE_, '/surprise/(?P<id>\d+)/verify', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'verify_payment' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Creates a draft bm_surprise post from the wizard's step 1-5 fields.
	 * Mirrors the fields the proposal-prototype's `state` object already collects.
	 */
	public static function create_draft( WP_REST_Request $request ) {
		$their_name      = sanitize_text_field( $request->get_param( 'their_name' ) );
		$your_name       = sanitize_text_field( $request->get_param( 'your_name' ) );
		$experience_type = sanitize_key( $request->get_param( 'experience_type' ) );

		if ( empty( $their_name ) || empty( $your_name ) || empty( $experience_type ) ) {
			return new WP_Error( 'bm_missing_fields', 'their_name, your_name, and experience_type are required.', array( 'status' => 400 ) );
		}

		$content = $request->get_param( 'content' );
		if ( is_array( $content ) && ! empty( $content['photos'] ) ) {
			$content['photos'] = self::save_photos( $content['photos'] );
		}

		$slug = Blush_Moments_Post_Type::generate_slug( $their_name, $your_name );

		$post_id = wp_insert_post( array(
			'post_type'   => Blush_Moments_Post_Type::POST_TYPE,
			'post_status' => 'publish', // WP-internal status; payment_status meta is the real gate on recipient access
			'post_title'  => $their_name . "'s Surprise",
			'post_name'   => $slug,
		), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$meta = array(
			'experience_type' => $experience_type,
			'their_name'      => $their_name,
			'your_name'       => $your_name,
			'relation'        => sanitize_text_field( $request->get_param( 'relation' ) ),
			'question'        => sanitize_text_field( $request->get_param( 'question' ) ),
			'message'         => sanitize_textarea_field( $request->get_param( 'message' ) ),
			// JSON_UNESCAPED_UNICODE: store emoji as raw UTF-8 bytes, not \u escapes —
			// the \u-escape round-trip through WP_REST_Request's param slashing was
			// silently dropping backslashes and corrupting every emoji in testing.
			'content'         => wp_json_encode( $content, JSON_UNESCAPED_UNICODE ),
			'payment_status'  => 'draft',
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return array(
			'id'   => $post_id,
			'slug' => $slug,
			'url'  => home_url( '/s/' . $slug ),
		);
	}

	/**
	 * Decodes the wizard's base64 data-URL photos and saves each as a real
	 * file in the uploads dir, returning their URLs. Anything that isn't a
	 * valid jpeg/png data URL, or over 5MB once decoded, is silently dropped
	 * rather than failing the whole submission over one bad photo.
	 */
	private static function save_photos( $photos ) {
		if ( ! is_array( $photos ) ) {
			return array();
		}

		$urls = array();
		foreach ( array_slice( $photos, 0, 5 ) as $data_url ) {
			if ( ! is_string( $data_url ) || ! preg_match( '/^data:image\/(jpe?g|png);base64,(.+)$/', $data_url, $matches ) ) {
				continue;
			}

			$bits = base64_decode( $matches[2], true );
			if ( false === $bits || strlen( $bits ) > 5 * 1024 * 1024 ) {
				continue;
			}

			$ext      = 'jpg' === $matches[1] ? 'jpeg' : $matches[1];
			$filename = 'bm-photo-' . wp_generate_password( 12, false ) . '.' . $ext;
			$upload   = wp_upload_bits( $filename, null, $bits );

			if ( empty( $upload['error'] ) ) {
				$urls[] = $upload['url'];
			}
		}
		return $urls;
	}

	/**
	 * Price (in paise) charged per experience, matching the amount already
	 * shown in each builder's paywall markup. Keep this in sync with the
	 * "new" price in builders/proposal.php and builders/birthday.php.
	 */
	private static function get_price_paise( $experience_type ) {
		$prices = array(
			'proposal' => 19900, // ₹199
			'birthday' => 14900, // ₹149
		);
		return isset( $prices[ $experience_type ] ) ? $prices[ $experience_type ] : 14900;
	}

	/**
	 * Calls the Razorpay Orders API to create an order for this surprise, and
	 * stores the resulting order id on the post. Returns only what the
	 * client-side Checkout needs — never the key secret.
	 */
	public static function create_order( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || Blush_Moments_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'bm_not_found', 'Surprise not found.', array( 'status' => 404 ) );
		}

		$key_id     = defined( 'RAZORPAY_KEY_ID' ) ? RAZORPAY_KEY_ID : '';
		$key_secret = defined( 'RAZORPAY_KEY_SECRET' ) ? RAZORPAY_KEY_SECRET : '';

		if ( empty( $key_id ) || empty( $key_secret ) ) {
			return new WP_Error( 'bm_razorpay_not_configured', 'Payments are not configured on this server yet.', array( 'status' => 500 ) );
		}

		$experience_type = get_post_meta( $post_id, 'experience_type', true );
		$amount          = self::get_price_paise( $experience_type );

		$response = wp_remote_post( 'https://api.razorpay.com/v1/orders', array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $key_id . ':' . $key_secret ),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'amount'   => $amount,
				'currency' => 'INR',
				'receipt'  => 'bm-' . $post_id,
			) ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'bm_razorpay_request_failed', 'Could not reach Razorpay: ' . $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['id'] ) ) {
			$message = isset( $body['error']['description'] ) ? $body['error']['description'] : 'Unknown error creating Razorpay order.';
			return new WP_Error( 'bm_razorpay_order_failed', $message, array( 'status' => 502 ) );
		}

		update_post_meta( $post_id, 'razorpay_order_id', $body['id'] );

		return array(
			'order_id' => $body['id'],
			'amount'   => $amount,
			'currency' => 'INR',
			'key_id'   => $key_id,
		);
	}

	/**
	 * Verifies a completed Razorpay Checkout payment by recomputing the HMAC
	 * signature server-side, and flips payment_status to 'paid' on match.
	 * This is the client-side verification path; a Razorpay webhook is the
	 * more robust production pattern and is a recommended follow-up.
	 */
	public static function verify_payment( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || Blush_Moments_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'bm_not_found', 'Surprise not found.', array( 'status' => 404 ) );
		}

		$key_secret = defined( 'RAZORPAY_KEY_SECRET' ) ? RAZORPAY_KEY_SECRET : '';
		if ( empty( $key_secret ) ) {
			return new WP_Error( 'bm_razorpay_not_configured', 'Payments are not configured on this server yet.', array( 'status' => 500 ) );
		}

		$order_id   = sanitize_text_field( $request->get_param( 'razorpay_order_id' ) );
		$payment_id = sanitize_text_field( $request->get_param( 'razorpay_payment_id' ) );
		$signature  = sanitize_text_field( $request->get_param( 'razorpay_signature' ) );

		if ( empty( $order_id ) || empty( $payment_id ) || empty( $signature ) ) {
			return new WP_Error( 'bm_missing_fields', 'razorpay_order_id, razorpay_payment_id, and razorpay_signature are required.', array( 'status' => 400 ) );
		}

		$stored_order_id = get_post_meta( $post_id, 'razorpay_order_id', true );
		if ( empty( $stored_order_id ) || $stored_order_id !== $order_id ) {
			return new WP_Error( 'bm_order_mismatch', 'This order does not match the surprise.', array( 'status' => 400 ) );
		}

		$expected_signature = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $key_secret );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return new WP_Error( 'bm_invalid_signature', 'Payment signature could not be verified.', array( 'status' => 400 ) );
		}

		update_post_meta( $post_id, 'payment_status', 'paid' );
		update_post_meta( $post_id, 'razorpay_payment_id', $payment_id );

		return array(
			'status' => 'paid',
			'url'    => home_url( '/s/' . $post->post_name ),
		);
	}
}
