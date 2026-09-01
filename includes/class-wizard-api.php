<?php
/**
 * REST API the builder wizard talks to.
 *
 * Flow (see build plan, Phase 3):
 *   1. POST /bm/v1/surprise        -> creates a draft, returns its slug
 *   2. POST /bm/v1/surprise/{id}/order -> creates a Razorpay order (stub — Phase 3)
 *   3. Razorpay webhook (registered separately) flips payment_status to 'paid'
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
	 * Stub — Phase 3. Will call the Razorpay Orders API and return the order
	 * details the client-side checkout needs. Payment confirmation itself
	 * happens via webhook, never trusting this response alone.
	 */
	public static function create_order( WP_REST_Request $request ) {
		return new WP_Error( 'bm_not_implemented', 'Razorpay integration lands in Phase 3.', array( 'status' => 501 ) );
	}
}
