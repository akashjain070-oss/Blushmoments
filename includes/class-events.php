<?php
/**
 * Audit trail for everything that happens to a surprise: draft created,
 * each wizard step reached, order created, payment verified (client or
 * webhook), payment failures, recipient opens. One row per event in its
 * own table — not postmeta, since a surprise can accumulate many events
 * and postmeta isn't built for that.
 *
 * The table is created/upgraded on `plugins_loaded` rather than only on
 * plugin activation — this project ships updates by replacing the plugin
 * files directly (zip upload), which never re-fires the activation hook,
 * so activation-only table creation would silently never run on a live
 * site that's already active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Events {

	const DB_VERSION = '1.0';

	public static function init() {
		// Not 'plugins_loaded': this init() call itself runs from inside a
		// 'plugins_loaded' callback (bm_init_plugin), so registering on that
		// same hook here is too late to ever fire in this pass — 'init' is
		// the next hook to run and matches the pattern class-cron.php uses
		// for the same reason.
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'bm_events';
	}

	/** Not just version-gated: also confirms the table is actually there,
	 *  since a version option can end up saved even if dbDelta silently
	 *  failed to create it (that happened once — the option said "up to
	 *  date" for a table that never existed). */
	public static function maybe_upgrade() {
		global $wpdb;
		$table_name   = self::table_name();
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

		if ( $table_exists && get_option( 'bm_events_db_version' ) === self::DB_VERSION ) {
			return;
		}
		self::create_table();
		update_option( 'bm_events_db_version', self::DB_VERSION );
	}

	private static function create_table() {
		global $wpdb;
		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			event_type VARCHAR(64) NOT NULL,
			meta LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY event_type (event_type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/** Records one event. $meta, if given, is JSON-encoded before storage. */
	public static function log( $post_id, $event_type, $meta = null ) {
		global $wpdb;
		$wpdb->insert(
			self::table_name(),
			array(
				'post_id'    => (int) $post_id,
				'event_type' => sanitize_key( $event_type ),
				'meta'       => null === $meta ? null : wp_json_encode( $meta, JSON_UNESCAPED_UNICODE ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/** Every event for one post, oldest first — the timeline the admin page renders. */
	public static function for_post( $post_id ) {
		global $wpdb;
		$table_name = self::table_name();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE post_id = %d ORDER BY created_at ASC", (int) $post_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Blush_Moments_Post_Type::POST_TYPE,
			'Surprise Events',
			'Events',
			'edit_posts',
			'bm-events',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<div class="wrap"><h1>Surprise Events</h1>';

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || Blush_Moments_Post_Type::POST_TYPE !== $post->post_type ) {
				echo '<p>Surprise not found.</p></div>';
				return;
			}
			echo '<h2>' . esc_html( get_the_title( $post ) ) . ' &mdash; full timeline</h2>';
			echo '<table class="widefat striped"><thead><tr><th style="width:200px;">When (UTC)</th><th style="width:220px;">Event</th><th>Detail</th></tr></thead><tbody>';
			foreach ( self::for_post( $post_id ) as $row ) {
				echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td>' . esc_html( $row->event_type ) . '</td><td><code>' . esc_html( $row->meta ) . '</code></td></tr>';
			}
			echo '</tbody></table>';
			echo '<p style="margin-top:16px;"><a href="' . esc_url( admin_url( 'admin.php?page=bm-events' ) ) . '">&larr; All recent events</a></p>';
			echo '</div>';
			return;
		}

		global $wpdb;
		$table_name = self::table_name();
		$rows       = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		echo '<p>Most recent 200 events across all surprises. Click a surprise to see its full timeline.</p>';
		echo '<table class="widefat striped"><thead><tr><th style="width:200px;">When (UTC)</th><th>Surprise</th><th style="width:220px;">Event</th><th>Detail</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$title = get_the_title( $row->post_id );
			$link  = admin_url( 'admin.php?page=bm-events&post_id=' . $row->post_id );
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td><a href="' . esc_url( $link ) . '">' . esc_html( $title ? $title : ( 'Post #' . $row->post_id ) ) . '</a></td><td>' . esc_html( $row->event_type ) . '</td><td><code>' . esc_html( $row->meta ) . '</code></td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
