<?php
/**
 * Renders the site homepage — full marketing page (hero, stats, experience
 * grid, how-it-works, stories, FAQ, closing CTA, footer), styled after the
 * ourmoments.live reference per the rebuild plan.
 *
 * Same pattern as the other views: intercepts template_redirect and owns
 * the full html document, bypassing the default WordPress theme entirely.
 * Experiences without a builder file yet render as "Coming Soon" instead
 * of a dead link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blush_Moments_Homepage_View {

	/** key => [label, tagline, emoji, accent, tag, badge, rating, reviews]. */
	const EXPERIENCES = array(
		'proposal'    => array(
			'label'   => 'Surprise Them',
			'tagline' => 'A romantic surprise page with a dodging "No" button, a sealed letter, and a celebration when they say yes.',
			'emoji'   => '💝',
			'accent'  => '#ff5c8a',
			'tag'     => 'ROMANCE',
			'badge'   => 'MOST POPULAR',
			'rating'  => '4.9',
			'reviews' => '2,847',
		),
		'birthday'    => array(
			'label'   => 'Birthday Surprise',
			'tagline' => 'Balloons to pop, a letter to open, and a cake-shaped closing scene — built around their reasons for being loved.',
			'emoji'   => '🎂',
			'accent'  => '#f2790b',
			'tag'     => 'CELEBRATION',
			'badge'   => 'BESTSELLER',
			'rating'  => '4.8',
			'reviews' => '1,956',
		),
		'girlfriend'  => array(
			'label'   => 'Girlfriend Surprise',
			'tagline' => 'A museum of the two of you — rooms she opens one at a time, with your photos, your reasons, and a sealed love letter.',
			'emoji'   => '🏛️',
			'accent'  => '#c86bd6',
			'tag'     => 'FOR HER',
			'badge'   => 'COMING SOON',
			'rating'  => '5.0',
			'reviews' => '—',
		),
		'bestfriend'  => array(
			'label'   => 'Best Friend Museum',
			'tagline' => 'Rooms built around your friendship — knock on the door, reveal the gallery, sign the renewal. A bestie gift for any day.',
			'emoji'   => '🤝',
			'accent'  => '#f2b705',
			'tag'     => 'FRIENDSHIP',
			'badge'   => 'COMING SOON',
			'rating'  => '5.0',
			'reviews' => '—',
		),
		'photopuzzle' => array(
			'label'   => 'Photo Puzzle',
			'tagline' => 'Turn a favorite photo into a sliding puzzle. Solving it reveals a hidden message or surprise underneath.',
			'emoji'   => '🧩',
			'accent'  => '#5b7cf2',
			'tag'     => 'INTERACTIVE',
			'badge'   => 'COMING SOON',
			'rating'  => '4.9',
			'reviews' => '—',
		),
		'anniversary' => array(
			'label'   => 'Anniversary Love',
			'tagline' => 'Celebrate your journey together with a timeline of memories, a love counter, and a toast animation.',
			'emoji'   => '🥂',
			'accent'  => '#e0a010',
			'tag'     => 'COUPLES',
			'badge'   => 'COMING SOON',
			'rating'  => '4.9',
			'reviews' => '—',
		),
		'apology'     => array(
			'label'   => 'Heartfelt Apology',
			'tagline' => 'A sincere, beautifully designed sorry page with a dodging "No" button and a forgiveness meter that fills up.',
			'emoji'   => '🙏',
			'accent'  => '#8a7ce0',
			'tag'     => 'HEARTFELT',
			'badge'   => 'COMING SOON',
			'rating'  => '4.7',
			'reviews' => '—',
		),
		'upigift'     => array(
			'label'   => 'UPI QR Gift',
			'tagline' => 'A personalized money-gift page with a UPI QR code, custom message, and festive animations.',
			'emoji'   => '💸',
			'accent'  => '#3aa66b',
			'tag'     => 'DIGITAL GIFT',
			'badge'   => 'COMING SOON',
			'rating'  => '4.8',
			'reviews' => '—',
		),
		'mothersday'  => array(
			'label'   => "Mother's Day",
			'tagline' => 'A touching tribute page for mom with flower animations, photo memories, and a virtual hug button.',
			'emoji'   => '🌸',
			'accent'  => '#ec6fa8',
			'tag'     => 'FAMILY',
			'badge'   => 'COMING SOON',
			'rating'  => '5.0',
			'reviews' => '—',
		),
	);

	const STEPS = array(
		array(
			'emoji' => '🎁',
			'title' => 'Pick a Surprise',
			'copy'  => 'Choose from our growing library of experiences — proposals, birthdays, puzzles, apologies, and more.',
		),
		array(
			'emoji' => '✍️',
			'title' => 'Add Your Magic',
			'copy'  => 'Upload photos, write your message, and personalize every detail to make it truly yours.',
		),
		array(
			'emoji' => '👁️',
			'title' => 'Preview It',
			'copy'  => 'See your surprise come to life instantly, exactly as they will, before you ever send it.',
		),
		array(
			'emoji' => '🔗',
			'title' => 'Share the Link',
			'copy'  => 'Copy your unique link and send it via WhatsApp, Instagram, or any app. Watch their reaction.',
		),
	);

	const FAQS = array(
		array(
			'q' => 'What exactly am I creating?',
			'a' => 'An interactive digital gift that lives on its own private page — not a card, not a video. They tap through it: balloons to pop, a letter that unseals itself, a puzzle to solve. You fill in the names, photos, and messages; we turn it into a page and hand you a link to send.',
		),
		array(
			'q' => 'Do I need design skills or an app?',
			'a' => "Neither. It's a short guided form — a few taps and a couple of photos, usually done in under five minutes on your phone. Nothing to install, for you or for them — it opens in any browser.",
		),
		array(
			'q' => 'Can I see it before I pay?',
			'a' => "Yes. Every experience lets you preview the whole thing first, exactly as they'll see it, with your own names and photos already in place. Nothing is charged until you're happy with it.",
		),
		array(
			'q' => 'How long until I get my link?',
			'a' => "Instantly. The moment you're done, your private link appears on the same screen — ready to copy. No queue, no waiting.",
		),
		array(
			'q' => 'How do I actually send it to them?',
			'a' => "However you already talk to them. It's an ordinary link, so it works on WhatsApp, Instagram DM, Telegram, iMessage, email — or written on a paper card if you'd rather hand it over.",
		),
	);

	const TESTIMONIALS = array(
		array(
			'quote'  => 'Put it together on my lunch break and sent it that evening. He read it twice before he replied.',
			'name'   => 'Ananya',
			'detail' => 'Birthday Surprise · Mumbai',
		),
		array(
			'quote'  => "We'd been fighting for three days and I didn't know how to start the conversation. This did it for me.",
			'name'   => 'Karan',
			'detail' => 'Heartfelt Apology · Delhi',
		),
		array(
			'quote'  => 'Used our trip photos for the anniversary page. She screenshotted the whole thing.',
			'name'   => 'Meera',
			'detail' => 'Anniversary Love · Bengaluru',
		),
	);

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ) );
	}

	public static function maybe_render() {
		if ( ! is_front_page() && ! is_home() ) {
			return;
		}
		self::render();
		exit;
	}

	private static function render() {
		$experiences = self::EXPERIENCES;
		$live_count  = count(
			array_filter(
				array_keys( $experiences ),
				function ( $key ) {
					return file_exists( BM_PLUGIN_DIR . 'builders/' . $key . '.php' );
				}
			)
		);
		?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blush Moments — Personalized Digital Gifts That Feel Real</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-32.png' ); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-180.png' ); ?>">
<link rel="icon" type="image/png" sizes="512x512" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-512.png' ); ?>">
<style>
  :root{
    --ink:#2c1c22; --muted:#8a7580; --cream:#fff8f5; --line:#f2e2e6;
    --grad-a:#ff5c8a; --grad-b:#f2790b;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html{scroll-behavior:smooth;}
  body{
    font-family:-apple-system,'Segoe UI',Roboto,sans-serif;
    background:var(--cream); color:var(--ink); line-height:1.55;
  }
  a{color:inherit;}
  .wrap{ max-width:1120px; margin:0 auto; padding:0 24px; }
  .grad{ background:linear-gradient(135deg,var(--grad-a),var(--grad-b)); -webkit-background-clip:text; background-clip:text; color:transparent; }
  .btn{
    display:inline-flex; align-items:center; gap:8px; font-weight:800; font-size:.95rem;
    padding:14px 26px; border-radius:999px; text-decoration:none; border:none; cursor:pointer;
  }
  .btn-primary{ background:linear-gradient(135deg,var(--grad-a),var(--grad-b)); color:#fff; box-shadow:0 14px 30px rgba(242,80,120,.3); }
  .btn-dark{ background:var(--ink); color:#fff; width:100%; justify-content:center; }
  .btn-ghost{ background:#fff; color:var(--ink); border:1.5px solid var(--line); }

  /* header */
  header{ position:sticky; top:0; z-index:20; background:rgba(255,248,245,.9); backdrop-filter:blur(8px); border-bottom:1px solid var(--line); }
  header .row{ display:flex; align-items:center; justify-content:space-between; padding:18px 0; }
  header .word{ font-weight:800; font-size:1.2rem; }
  header .word span{ color:var(--grad-a); }
  header nav{ display:flex; align-items:center; gap:28px; }
  header nav a.link{ font-weight:600; font-size:.92rem; text-decoration:none; color:var(--ink); }
  header nav .navlinks{ display:flex; gap:28px; }
  @media (max-width:820px){ header nav .navlinks{ display:none; } }

  /* hero */
  .hero{ padding:64px 0 30px; }
  @media (max-width:600px){ .hero{ padding:36px 0 12px; } }
  .hero-grid{ display:grid; grid-template-columns:1.1fr .9fr; gap:40px; align-items:center; }
  @media (max-width:900px){ .hero-grid{ grid-template-columns:1fr; gap:34px; } }
  .badges{ display:flex; flex-wrap:wrap; gap:10px; margin-bottom:22px; }
  .pill{ display:inline-flex; align-items:center; gap:6px; font-size:.8rem; font-weight:700; padding:8px 14px; border-radius:999px; }
  .pill-live{ background:#e9f9ee; color:#1c8a4b; }
  .pill-live .dot{ width:7px; height:7px; border-radius:50%; background:#1c8a4b; }
  .pill-badge{ background:#ffe9ef; color:#c23568; }
  .hero h1{ font-size:clamp(1.9rem,7vw,2.9rem); font-weight:900; line-height:1.16; text-wrap:balance; }
  .hero p.lead{ color:var(--muted); font-size:clamp(.95rem,2.6vw,1.08rem); margin:18px 0 26px; max-width:520px; }
  .hero-ctas{ display:flex; flex-wrap:wrap; gap:14px; }
  .hero-visual{ position:relative; }
  .phone{
    background:#1b1420; border-radius:34px; padding:14px; box-shadow:0 30px 60px rgba(60,20,40,.25);
    max-width:300px; margin:0 auto; position:relative;
  }
  .phone-screen{ background:linear-gradient(160deg,#fff,#fff0f4); border-radius:22px; padding:22px 16px; min-height:360px; }
  .phone-screen .step-label{ font-size:.7rem; font-weight:800; letter-spacing:.06em; color:var(--grad-a); }
  .phone-screen h3{ margin-top:10px; font-size:1.05rem; }
  .phone-screen .balloon{ display:inline-block; margin:14px 6px 0 0; padding:10px 14px; border-radius:16px; background:#fff; border:1.5px solid var(--line); font-size:.85rem; }
  .float-card{
    position:absolute; z-index:5; background:#fff; border-radius:16px; padding:12px 16px; box-shadow:0 16px 30px rgba(60,20,40,.15);
    font-size:.82rem; font-weight:700; display:flex; align-items:center; gap:8px; white-space:nowrap;
  }
  .float-1{ top:-24px; right:6px; }
  .float-2{ bottom:16%; left:-36px; }
  @media (max-width:900px){ .float-1, .float-2{ display:none; } }
  .avatar{ width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; color:#fff; font-weight:800; }

  .stats-bar{ margin:50px 0; background:#fff; border:1.5px solid var(--line); border-radius:26px; padding:30px 20px; display:grid; grid-template-columns:repeat(4,1fr); gap:20px; text-align:center; }
  @media (max-width:680px){ .stats-bar{ grid-template-columns:1fr 1fr; margin:34px 0; padding:22px 14px; gap:22px 14px; } }
  .stats-bar .num{ font-size:clamp(1.25rem,4.5vw,1.7rem); font-weight:900; }
  .stats-bar .num.grad{ }
  .stats-bar .label{ font-size:.72rem; font-weight:700; color:var(--muted); letter-spacing:.05em; margin-top:4px; }

  .section{ padding:70px 0; }
  @media (max-width:600px){ .section{ padding:44px 0; } }
  .section-head{ text-align:center; max-width:640px; margin:0 auto 44px; }
  .section-head p.lead{ color:var(--muted); margin-top:14px; font-size:clamp(.92rem,2.4vw,1.05rem); }
  .section-head h2{ font-size:clamp(1.5rem,5.5vw,2.1rem); font-weight:900; margin-top:12px; text-wrap:balance; }

  .grid-3{ display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
  @media (max-width:900px){ .grid-3{ grid-template-columns:1fr 1fr; } }
  @media (max-width:620px){ .grid-3{ grid-template-columns:1fr; } }

  .exp-card{ background:#fff; border:1.5px solid var(--line); border-radius:22px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 14px 34px rgba(180,60,90,.06); }
  .exp-banner{ height:120px; display:flex; align-items:center; justify-content:center; font-size:2.6rem; }
  .exp-body{ padding:22px; display:flex; flex-direction:column; gap:10px; flex:1; }
  .exp-tags{ display:flex; justify-content:space-between; align-items:center; }
  .exp-tag{ font-size:.68rem; font-weight:800; letter-spacing:.04em; color:var(--muted); }
  .exp-status{ font-size:.65rem; font-weight:800; letter-spacing:.03em; padding:4px 10px; border-radius:999px; color:#fff; }
  .exp-card h3{ font-size:1.2rem; }
  .exp-card p{ color:var(--muted); font-size:.9rem; flex:1; }
  .exp-rating{ font-size:.82rem; font-weight:700; color:#e0a010; }
  .exp-rating span.count{ color:var(--muted); font-weight:600; margin-left:4px; }
  .exp-card .btn{ margin-top:6px; }
  .exp-card.disabled .btn{ opacity:.45; cursor:default; }

  .steps-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:24px; }
  @media (max-width:900px){ .steps-grid{ grid-template-columns:1fr 1fr; } }
  @media (max-width:560px){ .steps-grid{ grid-template-columns:1fr; } }
  .step{ text-align:center; padding:26px 18px; }
  .step .num{ width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--grad-a),var(--grad-b)); color:#fff; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
  .step .emoji{ font-size:1.6rem; margin-bottom:8px; }
  .step h4{ font-size:1.02rem; margin-bottom:6px; }
  .step p{ color:var(--muted); font-size:.86rem; }

  .stories-section{ background:#fff5f3; }
  .story-stats{ display:flex; justify-content:center; gap:50px; margin-bottom:40px; flex-wrap:wrap; text-align:center; }
  .story-stats .num{ font-size:1.6rem; font-weight:900; }
  .story-stats .label{ font-size:.75rem; color:var(--muted); font-weight:700; }
  .story-card{ background:#fff; border-radius:20px; padding:26px; border:1.5px solid var(--line); display:flex; flex-direction:column; gap:14px; }
  .story-card .stars{ color:#e0a010; font-size:.85rem; }
  .story-card blockquote{ font-size:.95rem; }
  .story-who{ display:flex; align-items:center; gap:10px; margin-top:auto; }
  .story-who .name{ font-weight:700; font-size:.88rem; }
  .story-who .detail{ font-size:.78rem; color:var(--muted); }

  .faq-list{ max-width:760px; margin:0 auto; display:flex; flex-direction:column; gap:12px; }
  .faq-item{ background:#fff; border:1.5px solid var(--line); border-radius:16px; overflow:hidden; }
  .faq-q{ width:100%; text-align:left; background:none; border:none; padding:20px 22px; font-weight:700; font-size:.98rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; gap:16px; }
  .faq-a{ max-height:0; overflow:hidden; transition:max-height .25s ease; }
  .faq-a p{ padding:0 22px 20px; color:var(--muted); font-size:.9rem; }
  .faq-item.open .faq-a{ max-height:260px; }
  .faq-item.open .chev{ transform:rotate(180deg); }
  .chev{ transition:transform .2s ease; flex-shrink:0; }

  .cta-final{ text-align:center; background:linear-gradient(135deg,var(--grad-a),var(--grad-b)); border-radius:30px; padding:60px 30px; color:#fff; }
  @media (max-width:600px){ .cta-final{ padding:40px 20px; border-radius:22px; } }
  .cta-final h2{ font-size:clamp(1.5rem,5.5vw,2rem); font-weight:900; margin-bottom:14px; }
  .cta-final p{ opacity:.92; max-width:560px; margin:0 auto 26px; font-size:clamp(.92rem,2.4vw,1rem); }
  .cta-final .btn-primary{ background:#fff; color:var(--grad-a); box-shadow:none; }
  .cta-trust{ margin-top:26px; display:flex; justify-content:center; gap:26px; flex-wrap:wrap; font-size:.82rem; font-weight:700; opacity:.9; }

  footer{ background:#211318; color:#efd9df; padding:60px 0 30px; }
  .footer-grid{ display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:40px; }
  @media (max-width:820px){ .footer-grid{ grid-template-columns:1fr; } }
  footer .word{ font-weight:800; font-size:1.15rem; color:#fff; }
  footer .word span{ color:var(--grad-a); }
  footer p.tag{ color:#c9a3ad; font-size:.88rem; margin-top:12px; max-width:340px; }
  footer h5{ font-size:.8rem; letter-spacing:.06em; color:#fff; margin-bottom:14px; }
  footer ul{ list-style:none; display:flex; flex-direction:column; gap:10px; }
  footer ul a{ text-decoration:none; color:#c9a3ad; font-size:.88rem; }
  .footer-bottom{ border-top:1px solid rgba(255,255,255,.1); margin-top:44px; padding-top:22px; display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px; font-size:.78rem; color:#a98891; }
  .footer-bottom a{ text-decoration:none; color:#a98891; margin-left:14px; }

  /* logo */
  .logo-img{ height:38px; width:auto; display:block; }
  footer .logo-img{ height:34px; filter:brightness(0) invert(1); opacity:.95; }

  /* motion */
  @keyframes fadeInUp{ from{ opacity:0; transform:translateY(26px); } to{ opacity:1; transform:translateY(0); } }
  @keyframes floatY{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-10px); } }
  @keyframes pulseDot{ 0%,100%{ box-shadow:0 0 0 0 rgba(28,138,75,.4); } 50%{ box-shadow:0 0 0 6px rgba(28,138,75,0); } }
  @media (prefers-reduced-motion: reduce){
    .reveal{ opacity:1 !important; transform:none !important; animation:none !important; transition:none !important; }
    .phone, .float-card{ animation:none !important; }
  }
  .reveal{ opacity:0; transform:translateY(26px); transition:opacity .6s ease, transform .6s ease; }
  .reveal.in-view{ opacity:1; transform:translateY(0); }
  .grid-3 .reveal:nth-child(2), .steps-grid .reveal:nth-child(2){ transition-delay:.08s; }
  .grid-3 .reveal:nth-child(3), .steps-grid .reveal:nth-child(3){ transition-delay:.16s; }
  .steps-grid .reveal:nth-child(4){ transition-delay:.24s; }

  .pill-live .dot{ animation:pulseDot 2s ease-in-out infinite; }
  .phone{ animation:floatY 5s ease-in-out infinite; }
  .float-card{ animation:floatY 4.5s ease-in-out infinite; }
  .float-card.float-2{ animation-delay:.6s; }

  .exp-card{ transition:transform .28s ease, box-shadow .28s ease; }
  .exp-card:hover{ transform:translateY(-6px); box-shadow:0 22px 44px rgba(180,60,90,.14); }

  .step{ background:#fff; border:1.5px solid var(--line); border-radius:20px; box-shadow:0 10px 26px rgba(180,60,90,.05); transition:transform .28s ease, box-shadow .28s ease; }
  .step:hover{ transform:translateY(-5px); box-shadow:0 18px 34px rgba(180,60,90,.12); }
  .step .num{ box-shadow:0 8px 18px rgba(242,80,120,.35); }
  .step .emoji{ font-size:2rem; }

  .story-card{ transition:transform .28s ease, box-shadow .28s ease; }
  .story-card:hover{ transform:translateY(-5px); box-shadow:0 18px 34px rgba(180,60,90,.1); }
</style>
</head>
<body>

<header>
  <div class="wrap row">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Blush Moments">
      <img class="logo-img" src="<?php echo esc_url( BM_PLUGIN_URL . 'assets/logo.png' ); ?>" alt="Blush Moments">
    </a>
    <nav>
      <div class="navlinks">
        <a class="link" href="#experiences">Experiences</a>
        <a class="link" href="#how-it-works">How It Works</a>
        <a class="link" href="#stories">Stories</a>
        <a class="link" href="#faq">FAQ</a>
      </div>
      <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/create/proposal' ) ); ?>">Create Magic ✨</a>
    </nav>
  </div>
</header>

<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <div class="badges">
        <span class="pill pill-live"><span class="dot"></span> <span id="bm-live-count">42</span> people creating right now</span>
        <span class="pill pill-badge">💝 India's Newest Digital Gifting Platform</span>
      </div>
      <h1>Personalized digital gifts <span class="grad">for people you love</span></h1>
      <p class="lead">Create magical, interactive surprises in minutes — proposal pages, birthday celebrations, and more. Share instantly via WhatsApp, Instagram, or any link. No design skills needed.</p>
      <div class="hero-ctas">
        <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/create/proposal' ) ); ?>">Create Your Surprise →</a>
        <a class="btn btn-ghost" href="#how-it-works">▶ How It Works</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="float-card float-1"><span class="avatar" style="background:#ff5c8a;">A</span> <span id="bm-toast-text">Aditya unlocked Birthday</span></div>
      <div class="phone">
        <div class="phone-screen">
          <div class="step-label">STEP 3 OF 5</div>
          <h3>Fill the balloons 🎈</h3>
          <p style="color:var(--muted); font-size:.85rem; margin-top:6px;">Each balloon hides one reason they're loved.</p>
          <div class="balloon">Your laugh is my favourite sound</div>
          <div class="balloon">You make ordinary days feel special</div>
        </div>
      </div>
      <div class="float-card float-2">★★★★★ 4.9 — Loved by early creators</div>
    </div>
  </div>

  <div class="wrap stats-bar">
    <div><div class="num grad">50+</div><div class="label">SURPRISES CREATED</div></div>
    <div><div class="num grad"><?php echo (int) $live_count; ?></div><div class="label">LIVE EXPERIENCES</div></div>
    <div><div class="num grad">3+</div><div class="label">CITIES REACHED</div></div>
    <div><div class="num grad">5.0★</div><div class="label">EARLY RATING</div></div>
  </div>
</section>

<section class="section" id="experiences">
  <div class="wrap">
    <div class="section-head">
      <span class="pill pill-badge">✨ 9 Magical Experiences</span>
      <h2>Choose Your Perfect Surprise</h2>
      <p class="lead">From heartfelt surprises to fun puzzles — create unforgettable digital moments for every occasion. All gifts are shareable via link and work beautifully on any device.</p>
    </div>
    <div class="grid-3">
      <?php foreach ( $experiences as $key => $info ) :
				$is_live = file_exists( BM_PLUGIN_DIR . 'builders/' . $key . '.php' );
				?>
      <div class="exp-card reveal <?php echo $is_live ? '' : 'disabled'; ?>">
        <div class="exp-banner" style="background:<?php echo esc_attr( $info['accent'] ); ?>22;"><?php echo esc_html( $info['emoji'] ); ?></div>
        <div class="exp-body">
          <div class="exp-tags">
            <span class="exp-tag"><?php echo esc_html( $info['tag'] ); ?></span>
            <span class="exp-status" style="background:<?php echo $is_live ? esc_attr( $info['accent'] ) : '#c9b6bc'; ?>;"><?php echo esc_html( $info['badge'] ); ?></span>
          </div>
          <h3><?php echo esc_html( $info['label'] ); ?></h3>
          <p><?php echo esc_html( $info['tagline'] ); ?></p>
          <div class="exp-rating">★★★★★ <?php echo esc_html( $info['rating'] ); ?><span class="count"><?php echo $info['reviews'] !== '—' ? '(' . esc_html( $info['reviews'] ) . ' reviews)' : ''; ?></span></div>
          <?php if ( $is_live ) : ?>
          <a class="btn btn-dark" href="<?php echo esc_url( home_url( '/create/' . $key ) ); ?>">Create Yours Now →</a>
          <?php else : ?>
          <a class="btn btn-dark" href="#" onclick="return false;">Coming Soon</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="wrap">
    <div class="section-head">
      <span class="pill pill-badge">⚡ Simple &amp; Fast</span>
      <h2>Create Magic in 4 Easy Steps</h2>
      <p class="lead">No design skills needed. Build a stunning digital surprise in under five minutes and share it instantly with anyone, anywhere.</p>
    </div>
    <div class="steps-grid">
      <?php foreach ( self::STEPS as $i => $step ) : ?>
      <div class="step reveal">
        <div class="num"><?php echo esc_html( $i + 1 ); ?></div>
        <div class="emoji"><?php echo esc_html( $step['emoji'] ); ?></div>
        <h4><?php echo esc_html( $step['title'] ); ?></h4>
        <p><?php echo esc_html( $step['copy'] ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section stories-section" id="stories">
  <div class="wrap">
    <div class="section-head">
      <span class="pill pill-badge">💌 Early Stories</span>
      <h2>What Happened When They Opened It</h2>
      <p class="lead">A few of the first reactions from people who tried Blush Moments early.</p>
    </div>
    <div class="grid-3">
      <?php foreach ( self::TESTIMONIALS as $t ) : ?>
      <div class="story-card reveal">
        <div class="stars">★★★★★</div>
        <blockquote>&ldquo;<?php echo esc_html( $t['quote'] ); ?>&rdquo;</blockquote>
        <div class="story-who">
          <span class="avatar" style="background:var(--grad-a);"><?php echo esc_html( mb_substr( $t['name'], 0, 1 ) ); ?></span>
          <div>
            <div class="name"><?php echo esc_html( $t['name'] ); ?></div>
            <div class="detail"><?php echo esc_html( $t['detail'] ); ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="wrap">
    <div class="section-head">
      <span class="pill pill-badge">❓ Before You Create</span>
      <h2>Questions? We've Got Answers</h2>
      <p class="lead">Everything worth knowing before you make your first surprise.</p>
    </div>
    <div class="faq-list">
      <?php foreach ( self::FAQS as $i => $faq ) : ?>
      <div class="faq-item">
        <button class="faq-q" onclick="bmToggleFaq(this)">
          <span><?php echo esc_html( $faq['q'] ); ?></span>
          <span class="chev">▾</span>
        </button>
        <div class="faq-a"><p><?php echo esc_html( $faq['a'] ); ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="cta-final">
      <h2>Ready to Create Magic?</h2>
      <p>No signup. No design skills. Just pure magic — start creating your first surprise for free.</p>
      <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/create/proposal' ) ); ?>">Create Your Surprise →</a>
      <div class="cta-trust">
        <span>5.0★ early rating</span>
        <span>Ready in 5 min</span>
        <span>No app needed</span>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="wrap footer-grid">
    <div>
      <img class="logo-img" src="<?php echo esc_url( BM_PLUGIN_URL . 'assets/logo.png' ); ?>" alt="Blush Moments">
      <p class="tag">Crafting unforgettable digital surprises for the people you love. We turn your feelings into magical, shareable moments — instantly.</p>
    </div>
    <div>
      <h5>EXPERIENCES</h5>
      <ul>
        <?php foreach ( $experiences as $key => $info ) : ?>
        <li><a href="<?php echo esc_url( home_url( '/create/' . $key ) ); ?>"><?php echo esc_html( $info['label'] ); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h5>COMPANY</h5>
      <ul>
        <li><a href="#">About Us</a></li>
        <li><a href="#stories">Love Stories</a></li>
        <li><a href="mailto:support@blushmoments.live">Contact</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <div>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Blush Moments. All rights reserved.</div>
    <div>
      <a href="/privacy-policy">Privacy Policy</a>
      <a href="/terms-of-service">Terms of Service</a>
      <a href="/refund-policy">Refund Policy</a>
    </div>
  </div>
</footer>

<script>
(function(){
  var count = 42;
  var el = document.getElementById('bm-live-count');
  setInterval(function(){
    count += (Math.random() > 0.5 ? 1 : -1);
    if (count < 30) count = 30;
    if (count > 60) count = 60;
    if (el) el.textContent = count;
  }, 4000);

  var toasts = [
    'Aditya unlocked Birthday',
    'Priya shared a Surprise',
    'Rahul created a gift',
    'Sneha unlocked Birthday'
  ];
  var ti = 0;
  var toastEl = document.getElementById('bm-toast-text');
  setInterval(function(){
    ti = (ti + 1) % toasts.length;
    if (toastEl) toastEl.textContent = toasts[ti];
  }, 5000);
})();

function bmToggleFaq(btn) {
  var item = btn.closest('.faq-item');
  var wasOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(function(el){ el.classList.remove('open'); });
  if (!wasOpen) item.classList.add('open');
}

(function(){
  var targets = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) {
    targets.forEach(function(el){ el.classList.add('in-view'); });
    return;
  }
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
  targets.forEach(function(el){ io.observe(el); });
})();
</script>

</body>
</html>
		<?php
	}
}
