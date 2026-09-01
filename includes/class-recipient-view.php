<?php
/**
 * Renders the recipient-facing page at /s/{slug}.
 *
 * Gate order matters: a missing surprise, an unpaid one, and an expired one
 * all need different messaging — never just a blank 404 for a real link
 * that simply hasn't been paid for yet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Recipient_View {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ) );
	}

	public static function add_rewrite_rule() {
		add_rewrite_rule( '^s/([^/]+)/?$', 'index.php?bm_surprise_slug=$matches[1]', 'top' );
	}

	public static function add_query_var( $vars ) {
		$vars[] = 'bm_surprise_slug';
		return $vars;
	}

	public static function maybe_render() {
		$slug = get_query_var( 'bm_surprise_slug' );
		if ( empty( $slug ) ) {
			return;
		}

		$post = get_page_by_path( $slug, OBJECT, Blush_Moments_Post_Type::POST_TYPE );

		if ( ! $post ) {
			self::render_state( 'not-found' );
			exit;
		}

		$payment_status = get_post_meta( $post->ID, 'payment_status', true );
		$expires_at     = get_post_meta( $post->ID, 'expires_at', true );

		if ( 'paid' !== $payment_status ) {
			self::render_state( 'not-unlocked' );
			exit;
		}

		if ( $expires_at && strtotime( $expires_at ) < time() ) {
			self::render_state( 'expired' );
			exit;
		}

		if ( ! get_post_meta( $post->ID, 'opened_at', true ) ) {
			update_post_meta( $post->ID, 'opened_at', current_time( 'mysql' ) );
		}

		self::render_experience( $post );
		exit;
	}

	/** Loads templates/{experience_type}.php with the surprise's data available as $surprise. */
	private static function render_experience( $post ) {
		$experience_type = get_post_meta( $post->ID, 'experience_type', true );
		$template         = BM_PLUGIN_DIR . 'templates/' . sanitize_file_name( $experience_type ) . '.php';

		if ( ! file_exists( $template ) ) {
			self::render_state( 'not-found' );
			return;
		}

		$surprise = array(
			'their_name' => get_post_meta( $post->ID, 'their_name', true ),
			'your_name'  => get_post_meta( $post->ID, 'your_name', true ),
			'relation'   => get_post_meta( $post->ID, 'relation', true ),
			'question'   => get_post_meta( $post->ID, 'question', true ),
			'message'    => get_post_meta( $post->ID, 'message', true ),
			'content'    => json_decode( get_post_meta( $post->ID, 'content', true ), true ),
		);

		include $template;
	}

	/** not-found | not-unlocked | expired — simple states, no template file needed yet. */
	private static function render_state( $state ) {
		status_header( 'not-found' === $state ? 404 : 200 );
		$messages = array(
			'not-found'    => "This link doesn't lead anywhere — double check it was copied in full.",
			'not-unlocked' => 'This surprise is still being finished by its creator.',
			'expired'      => 'This surprise link has expired.',
		);
		echo '<p>' . esc_html( $messages[ $state ] ?? $messages['not-found'] ) . '</p>';
	}
}
