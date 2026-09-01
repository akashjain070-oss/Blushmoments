<?php
/**
 * Plugin Name: Blush Moments
 * Description: Custom post type, wizard, and recipient-page engine for the Blush Moments gifting experiences.
 * Version: 0.1.0
 * Author: Blush Moments
 * Text Domain: blush-moments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BM_VERSION', '0.1.0' );
define( 'BM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once BM_PLUGIN_DIR . 'includes/class-post-type.php';
require_once BM_PLUGIN_DIR . 'includes/class-wizard-api.php';
require_once BM_PLUGIN_DIR . 'includes/class-recipient-view.php';

function bm_init_plugin() {
	Blush_Moments_Post_Type::init();
	Blush_Moments_Wizard_API::init();
	Blush_Moments_Recipient_View::init();
}
add_action( 'plugins_loaded', 'bm_init_plugin' );

register_activation_hook( __FILE__, function () {
	Blush_Moments_Post_Type::register();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
