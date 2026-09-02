<?php
/**
 * REST API the builder wizard talks to.
 *
 * Flow:
 *   1. POST /bm/v1/surprise               -> creates a draft on step 1, then the
 *      client calls this again (passing the returned id back) on every later
 *      step and photo upload so abandoned attempts and their photos survive
 *      even if the visitor never finishes — the endpoint is create-or-update.
 *   2. POST /bm/v1/surprise/{id}/order    -> creates a Razorpay order
 *   3. Razorpay Checkout runs client-side, then
 *      POST /bm/v1/surprise/{id}/verify  -> verifies the payment signature and
 *      flips payment_status to 'paid'
 *   4. POST /bm/v1/webhook/razorpay       -> Razorpay's own server-to-server
 *      callback; the authoritative payment confirmation, since step 3 alone
 *      is lost if the customer's browser closes before it fires.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Wizard_API {

	const NAMESPACE_          = 'bm/v1';
	const RATE_LIMIT_MAX      = 10;   // new drafts per IP per hour
	const RATE_LIMIT_WINDOW   = HOUR_IN_SECONDS;
	const DRAFT_EXPIRY_DAYS   = 90;   // paid surprise lifetime, matches META_KEYS comment

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

		register_rest_route( self::NAMESPACE_, '/webhook/razorpay', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_webhook' ),
			'permission_callback' => '__return_true', // authenticated via HMAC signature inside the callback, not WP auth
		) );
	}

	/**
	 * Creates a draft on the first call (end of wizard step 1), then updates
	 * the same post on every subsequent call as the wizard progresses —
	 * the client resends its full current state each time and passes back
	 * the `id` this returned the first time. This is what makes abandoned
	 * attempts (and any photos already uploaded) visible in the database
	 * instead of only existing in a browser tab that might never submit.
	 */
	public static function create_draft( WP_REST_Request $request ) {
		// Honeypot: a real visitor never fills this hidden field; a bot's
		// autofill usually does. Only checked on the very first save (no id
		// yet) — later auto-saves from steps 2-5 have no form to fill it from.
		$post_id_param = (int) $request->get_param( 'id' );
		if ( ! $post_id_param && ! empty( $request->get_param( 'website' ) ) ) {
			return new WP_Error( 'bm_rejected', 'Could not process this request.', array( 'status' => 400 ) );
		}

		$their_name      = sanitize_text_field( $request->get_param( 'their_name' ) );
		$your_name       = sanitize_text_field( $request->get_param( 'your_name' ) );
		$experience_type = sanitize_key( $request->get_param( 'experience_type' ) );

		if ( empty( $their_name ) || empty( $your_name ) || empty( $experience_type ) ) {
			return new WP_Error( 'bm_missing_fields', 'their_name, your_name, and experience_type are required.', array( 'status' => 400 ) );
		}

		// A numeric post id alone isn't authorization — ids are sequential and
		// guessable, and this route is public/unauthenticated. Updating an
		// existing draft additionally requires the random token handed back
		// when it was first created; a mismatch (or missing token) falls
		// through to creating a fresh draft instead of erroring, so a client
		// that genuinely lost its token just starts a new one rather than
		// being able to probe/overwrite someone else's in-progress draft.
		$token_param   = sanitize_text_field( (string) $request->get_param( 'token' ) );
		$existing_post = $post_id_param ? get_post( $post_id_param ) : null;
		$is_update     = $existing_post
			&& Blush_Moments_Post_Type::POST_TYPE === $existing_post->post_type
			&& 'draft' === get_post_meta( $existing_post->ID, 'payment_status', true )
			&& ! empty( $token_param )
			&& hash_equals( (string) get_post_meta( $existing_post->ID, 'draft_token', true ), $token_param );

		if ( ! $is_update ) {
			$limit = self::check_rate_limit();
			if ( is_wp_error( $limit ) ) {
				return $limit;
			}
		}

		$content = $request->get_param( 'content' );
		if ( is_array( $content ) && ! empty( $content['photos'] ) ) {
			$content['photos'] = self::save_photos( $content['photos'] );
		}

		if ( $is_update ) {
			$post_id = $existing_post->ID;
			$slug    = $existing_post->post_name;
			$token   = $token_param;
		} else {
			$slug    = Blush_Moments_Post_Type::generate_slug( $their_name, $your_name );
			$post_id = wp_insert_post( array(
				'post_type'   => Blush_Moments_Post_Type::POST_TYPE,
				'post_status' => 'publish', // WP-internal status; payment_status meta is the real gate on recipient access
				'post_title'  => $their_name . "'s Surprise",
				'post_name'   => $slug,
			), true );

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			$token = wp_generate_password( 32, false );
			update_post_meta( $post_id, 'draft_token', $token );
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
		);

		if ( ! $is_update ) {
			$meta['payment_status'] = 'draft';
		}

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$step = sanitize_key( (string) $request->get_param( 'step' ) );
		if ( $is_update ) {
			Blush_Moments_Events::log( $post_id, 'step_reached', array( 'step' => $step ) );
		} else {
			Blush_Moments_Events::log( $post_id, 'draft_created', array( 'step' => $step ) );
		}

		return array(
			'id'     => $post_id,
			'slug'   => $slug,
			'url'    => home_url( '/s/' . $slug ),
			'token'  => $token,
			// Resolved photo URLs (and captions, if any) — the client uses
			// this to replace its in-memory base64 copies so the *next*
			// autosave re-sends real URLs instead of re-uploading the same
			// image as a brand new file every time the wizard advances.
			'photos' => is_array( $content ) && ! empty( $content['photos'] ) ? $content['photos'] : array(),
		);
	}

	/** Simple transient-based rate limit on new-draft creation, keyed by IP. */
	private static function check_rate_limit() {
		$ip  = self::client_ip();
		$key = 'bm_rl_' . md5( $ip );

		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT_MAX ) {
			return new WP_Error( 'bm_rate_limited', 'Too many attempts. Please try again later.', array( 'status' => 429 ) );
		}

		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
		return true;
	}

	private static function client_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $forwarded[0] );
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * Decodes any new base64 data-URL photos and saves each as a real file
	 * in the uploads dir, returning {url, caption} pairs. Photos that are
	 * already a real uploaded file from an earlier incremental save (a plain
	 * URL, not a data URL) are passed through unchanged — the client resends
	 * its full photo list on every save, so without this a photo saved on
	 * step 2 would otherwise get silently dropped when step 3's save
	 * re-sends it. Anything that isn't a valid jpeg/png data URL, or over
	 * 5MB once decoded, is dropped (and logged) rather than failing the
	 * whole save.
	 *
	 * Accepts either a bare data-URL/already-uploaded-URL string (older
	 * callers) or {data, caption} — the birthday/proposal builders send the
	 * latter so a caption can be shown alongside the photo on the recipient
	 * page.
	 */
	private static function save_photos( $photos ) {
		if ( ! is_array( $photos ) ) {
			return array();
		}

		$upload_dir = wp_upload_dir();
		$out        = array();

		foreach ( array_slice( $photos, 0, 5 ) as $photo ) {
			$data_url = is_array( $photo ) ? ( isset( $photo['data'] ) ? $photo['data'] : '' ) : $photo;
			$caption  = is_array( $photo ) && ! empty( $photo['caption'] ) ? sanitize_text_field( $photo['caption'] ) : '';

			if ( ! is_string( $data_url ) || '' === $data_url ) {
				continue;
			}

			if ( 0 === strpos( $data_url, $upload_dir['baseurl'] ) ) {
				$out[] = array( 'url' => $data_url, 'caption' => $caption ); // already saved on an earlier step
				continue;
			}

			if ( ! preg_match( '/^data:image\/(jpe?g|png);base64,(.+)$/', $data_url, $matches ) ) {
				error_log( 'Blush Moments: dropped a photo — not a recognised image data URL.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				continue;
			}

			$bits = base64_decode( $matches[2], true );
			if ( false === $bits || strlen( $bits ) > 5 * 1024 * 1024 ) {
				error_log( 'Blush Moments: dropped a photo — decode failed or over 5MB.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				continue;
			}

			$ext      = 'jpg' === $matches[1] ? 'jpeg' : $matches[1];
			$filename = 'bm-photo-' . wp_generate_password( 12, false ) . '.' . $ext;
			$upload   = wp_upload_bits( $filename, null, $bits );

			if ( empty( $upload['error'] ) ) {
				$out[] = array( 'url' => $upload['url'], 'caption' => $caption );
			} else {
				error_log( 'Blush Moments: wp_upload_bits failed — ' . $upload['error'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
		return $out;
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
			error_log( 'Blush Moments: Razorpay order request failed — ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return new WP_Error( 'bm_razorpay_request_failed', 'Could not reach Razorpay: ' . $response->get_error_message(), array( 'status' => 502 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['id'] ) ) {
			$message = isset( $body['error']['description'] ) ? $body['error']['description'] : 'Unknown error creating Razorpay order.';
			error_log( 'Blush Moments: Razorpay order creation failed (' . $code . ') — ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return new WP_Error( 'bm_razorpay_order_failed', $message, array( 'status' => 502 ) );
		}

		update_post_meta( $post_id, 'razorpay_order_id', $body['id'] );
		Blush_Moments_Events::log( $post_id, 'order_created', array( 'order_id' => $body['id'], 'amount' => $amount ) );

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
	 * This is the client-side verification path — the instant-redirect UX.
	 * The webhook (handle_webhook, below) is the authoritative path that
	 * still confirms payment even if the browser never calls this.
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
			Blush_Moments_Events::log( $post_id, 'payment_failed', array( 'reason' => 'order_mismatch' ) );
			return new WP_Error( 'bm_order_mismatch', 'This order does not match the surprise.', array( 'status' => 400 ) );
		}

		$expected_signature = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $key_secret );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			Blush_Moments_Events::log( $post_id, 'payment_failed', array( 'reason' => 'bad_signature' ) );
			return new WP_Error( 'bm_invalid_signature', 'Payment signature could not be verified.', array( 'status' => 400 ) );
		}

		self::mark_paid( $post_id, $payment_id, 'payment_verified_client' );

		return array(
			'status' => 'paid',
			'url'    => home_url( '/s/' . $post->post_name ),
		);
	}

	/**
	 * Razorpay's server-to-server webhook — the authoritative payment
	 * confirmation. Configure this URL in the Razorpay dashboard (Settings
	 * > Webhooks) subscribed to the "payment.captured" event, with the same
	 * secret as RAZORPAY_WEBHOOK_SECRET below.
	 */
	public static function handle_webhook( WP_REST_Request $request ) {
		$webhook_secret = defined( 'RAZORPAY_WEBHOOK_SECRET' ) ? RAZORPAY_WEBHOOK_SECRET : '';
		if ( empty( $webhook_secret ) ) {
			error_log( 'Blush Moments: Razorpay webhook called but RAZORPAY_WEBHOOK_SECRET is not configured.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return new WP_Error( 'bm_webhook_not_configured', 'Webhook secret not configured.', array( 'status' => 500 ) );
		}

		$raw_body  = $request->get_body();
		$signature = $request->get_header( 'x_razorpay_signature' );

		if ( empty( $signature ) || ! hash_equals( hash_hmac( 'sha256', $raw_body, $webhook_secret ), $signature ) ) {
			Blush_Moments_Events::log( 0, 'webhook_signature_invalid' );
			return new WP_Error( 'bm_invalid_webhook_signature', 'Signature verification failed.', array( 'status' => 400 ) );
		}

		$payload = json_decode( $raw_body, true );
		$event   = isset( $payload['event'] ) ? $payload['event'] : '';

		if ( 'payment.captured' !== $event ) {
			return array( 'status' => 'ignored', 'event' => $event );
		}

		$payment_entity = isset( $payload['payload']['payment']['entity'] ) ? $payload['payload']['payment']['entity'] : array();
		$receipt        = isset( $payment_entity['receipt'] ) ? $payment_entity['receipt'] : '';
		$payment_id     = isset( $payment_entity['id'] ) ? $payment_entity['id'] : '';

		// Orders are created with receipt = "bm-{post_id}" (see create_order()).
		if ( ! preg_match( '/^bm-(\d+)$/', (string) $receipt, $matches ) ) {
			error_log( 'Blush Moments: webhook payment.captured with unrecognised receipt: ' . $receipt ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return new WP_Error( 'bm_receipt_not_found', 'Could not resolve receipt to a surprise.', array( 'status' => 404 ) );
		}

		$post_id = (int) $matches[1];
		$post    = get_post( $post_id );
		if ( ! $post || Blush_Moments_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'bm_not_found', 'Surprise not found.', array( 'status' => 404 ) );
		}

		if ( 'paid' !== get_post_meta( $post_id, 'payment_status', true ) ) {
			self::mark_paid( $post_id, $payment_id, 'payment_verified_webhook' );
		} else {
			Blush_Moments_Events::log( $post_id, 'payment_verified_webhook', array( 'payment_id' => $payment_id, 'note' => 'already paid' ) );
		}

		return array( 'status' => 'ok' );
	}

	/** Shared by both the client-side verify path and the webhook. */
	private static function mark_paid( $post_id, $payment_id, $event_type ) {
		update_post_meta( $post_id, 'payment_status', 'paid' );
		update_post_meta( $post_id, 'razorpay_payment_id', $payment_id );
		update_post_meta( $post_id, 'expires_at', gmdate( 'Y-m-d H:i:s', strtotime( '+' . self::DRAFT_EXPIRY_DAYS . ' days' ) ) );
		Blush_Moments_Events::log( $post_id, $event_type, array( 'payment_id' => $payment_id ) );
	}
}
