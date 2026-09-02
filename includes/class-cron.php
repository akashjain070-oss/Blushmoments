<?php
/**
 * Daily cleanup: reclaims disk space from expired paid surprises' photos,
 * and deletes abandoned (never-paid) drafts after a grace period so
 * partial wizard attempts don't pile up forever.
 *
 * Note: expired *paid* surprises keep payment_status = 'paid' and are
 * NOT deleted — class-recipient-view.php's expiry check compares
 * expires_at against the current time and only accepts an exact 'paid'
 * status (see maybe_render()), so changing that value here would break
 * the existing "this surprise has expired" message instead of showing
 * it correctly. Only their photo files get reclaimed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Cron {

	const HOOK                  = 'bm_expire_surprises';
	const DRAFT_MAX_AGE_DAYS    = 14;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
	}

	/** Self-healing: this project deploys by replacing plugin files directly
	 *  (zip upload), which never re-fires register_activation_hook, so the
	 *  schedule has to be checked/created on every load instead of relying
	 *  on activation alone. */
	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::HOOK );
		}
	}

	public static function run() {
		self::reclaim_expired_photos();
		self::delete_abandoned_drafts();
	}

	private static function reclaim_expired_photos() {
		$post_ids = get_posts( array(
			'post_type'      => Blush_Moments_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'payment_status',
					'value' => 'paid',
				),
				array(
					'key'     => 'expires_at',
					'value'   => current_time( 'mysql', true ),
					'compare' => '<',
					'type'    => 'DATETIME',
				),
				array(
					'key'     => 'photos_reclaimed',
					'compare' => 'NOT EXISTS',
				),
			),
		) );

		foreach ( $post_ids as $post_id ) {
			self::delete_photos_for( $post_id );
			update_post_meta( $post_id, 'photos_reclaimed', '1' );
			Blush_Moments_Events::log( $post_id, 'photos_reclaimed' );
		}
	}

	private static function delete_abandoned_drafts() {
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . self::DRAFT_MAX_AGE_DAYS . ' days' ) );

		$post_ids = get_posts( array(
			'post_type'      => Blush_Moments_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'column'    => 'post_date_gmt',
					'before'    => $cutoff,
					'inclusive' => true,
				),
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'payment_status',
					'value' => 'draft',
				),
			),
		) );

		foreach ( $post_ids as $post_id ) {
			self::delete_photos_for( $post_id );
			Blush_Moments_Events::log( $post_id, 'draft_abandoned_deleted' );
			wp_delete_post( $post_id, true );
		}
	}

	private static function delete_photos_for( $post_id ) {
		$content = json_decode( get_post_meta( $post_id, 'content', true ), true );
		if ( empty( $content['photos'] ) || ! is_array( $content['photos'] ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();
		foreach ( $content['photos'] as $url ) {
			if ( ! is_string( $url ) || 0 !== strpos( $url, $upload_dir['baseurl'] ) ) {
				continue;
			}
			$path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
	}
}
