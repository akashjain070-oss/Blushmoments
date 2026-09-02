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
		$copy = array(
			'not-found'    => array(
				'emoji' => '🔗',
				'title' => "This link doesn't lead anywhere",
				'text'  => 'Double check it was copied in full — links are long and easy to clip by accident.',
			),
			'not-unlocked' => array(
				'emoji' => '🎁',
				'title' => 'Almost ready...',
				'text'  => 'This surprise is still being finished by its creator. Check back with them soon.',
			),
			'expired'      => array(
				'emoji' => '🕯️',
				'title' => 'This surprise has floated away',
				'text'  => 'Surprise links stay live for 90 days — this one has expired. The love behind it hasn’t, though.',
			),
		);
		$c = $copy[ $state ] ?? $copy['not-found'];
		?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $c['title'] ); ?> — Blush Moments</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box; margin:0; padding:0;}
  body{
    font-family:'Outfit',-apple-system,'Segoe UI',Roboto,sans-serif;
    background:linear-gradient(160deg,#faf3ec,#e6d6e0 30%,#f3ddc8 65%,#f7e6d4);
    min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;
  }
  .card{ background:#fff; border-radius:22px; padding:40px 28px; max-width:380px; width:100%; text-align:center; box-shadow:0 18px 40px rgba(200,120,20,.18); }
  .emoji{ font-size:2.6rem; margin-bottom:14px; }
  h1{ font-size:1.3rem; color:#3a2620; margin-bottom:10px; }
  p{ font-size:.9rem; color:#8a6e63; line-height:1.6; margin-bottom:22px; }
  a.cta{ display:inline-block; background:linear-gradient(135deg,#c17a3f,#e0a868); color:#fff; font-weight:700; font-size:.9rem; padding:14px 26px; border-radius:40px; text-decoration:none; }
</style>
</head>
<body>
  <div class="card">
    <div class="emoji"><?php echo esc_html( $c['emoji'] ); ?></div>
    <h1><?php echo esc_html( $c['title'] ); ?></h1>
    <p><?php echo esc_html( $c['text'] ); ?></p>
    <a class="cta" href="<?php echo esc_url( home_url( '/' ) ); ?>">Make Your Own 🎂</a>
  </div>
</body>
</html>
		<?php
	}
}
