<?php
/**
 * Registers the "bm_surprise" custom post type — one row per created gift.
 *
 * Storage decision: WordPress's own post + postmeta tables, not a separate
 * database. See the build plan — this lets a non-technical person browse
 * every surprise ever created directly in wp-admin, with zero custom UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Post_Type {

	const POST_TYPE = 'bm_surprise';

	/** Meta keys stored per surprise. Kept in one place so the API and
	 *  admin columns stay in sync with what actually gets saved. */
	const META_KEYS = array(
		'experience_type',   // proposal | birthday | girlfriend | friendship | puzzle | anniversary | apology | upi_gift | mothers_day
		'their_name',
		'your_name',
		'relation',
		'question',
		'message',
		'content',           // JSON: selected love-card ids, or uploaded photo attachment ids
		'payment_status',    // draft | paid
		'razorpay_order_id',
		'razorpay_payment_id',
		'expires_at',        // paid_at + 90 days, as a MySQL datetime
		'opened_at',         // first time the recipient actually viewed it
		'photos_reclaimed',  // set by class-cron.php once an expired surprise's photo files are deleted
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
	}

	public static function register() {
		register_post_type( self::POST_TYPE, array(
			'label'           => 'Surprises',
			'labels'          => array(
				'name'          => 'Surprises',
				'singular_name' => 'Surprise',
				'add_new_item'  => 'Add Surprise',
				'search_items'  => 'Search Surprises',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-heart',
			'supports'        => array( 'title' ),
			'has_archive'     => false,
			'rewrite'         => false,
			'capability_type' => 'post',
		) );

		foreach ( self::META_KEYS as $key ) {
			register_post_meta( self::POST_TYPE, $key, array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => false, // exposed only through the wizard API, not the default REST route
			) );
		}
	}

	/** Slug convention: {their-name}-{your-name}-{random4}, e.g. sam-alex-7f2q.
	 *  Uses WordPress's own post_name — no separate slug column needed. */
	public static function generate_slug( $their_name, $your_name ) {
		$base   = sanitize_title( $their_name . '-' . $your_name );
		$random = substr( wp_generate_password( 6, false ), 0, 4 );
		return $base . '-' . strtolower( $random );
	}

	public static function admin_columns( $columns ) {
		$columns['experience_type'] = 'Experience';
		$columns['payment_status']  = 'Status';
		$columns['expires_at']      = 'Expires';
		return $columns;
	}

	public static function admin_column_content( $column, $post_id ) {
		if ( ! in_array( $column, self::META_KEYS, true ) ) {
			return;
		}
		echo esc_html( get_post_meta( $post_id, $column, true ) );
	}
}
