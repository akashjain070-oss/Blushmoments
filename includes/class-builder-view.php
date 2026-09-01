<?php
/**
 * Renders the builder wizard at /create/{experience_type}.
 *
 * Code-driven, same as the recipient page — this is a JS state machine, not
 * content anyone would hand-edit in Elementor. Preview stays entirely
 * client-side (matches the real product's behavior and costs nothing); the
 * server is only involved once the user actually sends the surprise, at
 * which point we create the real draft via the wizard REST API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Builder_View {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ) );
	}

	public static function add_rewrite_rule() {
		add_rewrite_rule( '^create/([^/]+)/?$', 'index.php?bm_builder_type=$matches[1]', 'top' );
	}

	public static function add_query_var( $vars ) {
		$vars[] = 'bm_builder_type';
		return $vars;
	}

	public static function maybe_render() {
		$experience_type = get_query_var( 'bm_builder_type' );
		if ( empty( $experience_type ) ) {
			return;
		}

		$template = BM_PLUGIN_DIR . 'builders/' . sanitize_file_name( $experience_type ) . '.php';

		if ( ! file_exists( $template ) ) {
			status_header( 404 );
			echo '<p>' . esc_html( "There's no builder for \"{$experience_type}\" yet." ) . '</p>';
			exit;
		}

		$rest_url = esc_url_raw( rest_url( 'bm/v1/surprise' ) );
		include $template;
		exit;
	}
}
