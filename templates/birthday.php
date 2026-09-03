<?php
/**
 * Recipient-facing template for the "birthday" experience.
 *
 * Loaded by Blush_Moments_Recipient_View::render_experience() with $surprise
 * already populated from postmeta. Owns the full html document, same as
 * templates/proposal.php — bypasses the WordPress theme entirely.
 *
 * Mirrors builders/birthday.php's preview flow minus the wizard, generating
 * screen, and paywall — none of that belongs here, it already happened
 * before this link was ever sent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$their_name = $surprise['their_name'] ?: 'Them';
$your_name  = $surprise['your_name'] ?: 'Someone';
$message    = $surprise['message'] ?: "Another year of you is the best news of my year. Happy birthday.";
$cake_key   = ! empty( $surprise['content']['cake'] ) ? $surprise['content']['cake'] : 'strawberry';
$age        = ! empty( $surprise['content']['age'] ) ? $surprise['content']['age'] : '';
$balloons   = ! empty( $surprise['content']['balloons'] ) && is_array( $surprise['content']['balloons'] )
	? array_values( array_filter( $surprise['content']['balloons'] ) )
	: array( 'This whole surprise, honestly' );
// Each photo is normally {url, caption}; older surprises saved before
// captions existed stored a bare URL string, so both shapes are accepted.
$photos_raw = ! empty( $surprise['content']['photos'] ) && is_array( $surprise['content']['photos'] )
	? $surprise['content']['photos']
	: array();
$photos     = array();
foreach ( $photos_raw as $photo ) {
	if ( is_array( $photo ) && ! empty( $photo['url'] ) ) {
		$photos[] = array( 'url' => $photo['url'], 'caption' => $photo['caption'] ?? '' );
	} elseif ( is_string( $photo ) && '' !== $photo ) {
		$photos[] = array( 'url' => $photo, 'caption' => '' );
	}
}

// Only lock if the birthday is within the next 26 hours — this is a
// last-day suspense touch, not a months-ahead calendar feature, so a
// birthday set far in the future (or already past) never locks anyone out.
$birth_day    = ! empty( $surprise['content']['birth_day'] ) ? (int) $surprise['content']['birth_day'] : 0;
$birth_month  = ! empty( $surprise['content']['birth_month'] ) ? (int) $surprise['content']['birth_month'] : 0;
$lock_seconds = 0;
if ( $birth_day && $birth_month ) {
	$tz     = wp_timezone();
	$now    = new DateTime( 'now', $tz );
	$target = new DateTime( 'now', $tz );
	$target->setDate( (int) $now->format( 'Y' ), $birth_month, $birth_day );
	$target->setTime( 0, 0, 0 );
	$diff = $target->getTimestamp() - $now->getTimestamp();
	if ( $diff > 0 && $diff <= 26 * HOUR_IN_SECONDS ) {
		$lock_seconds = $diff;
	}
}

$cake_labels = array(
	'chocolate'  => 'Midnight Chocolate',
	'strawberry' => 'Strawberry Blush',
	'vanilla'    => 'Vanilla Gold',
);
$cake_label = $cake_labels[ $cake_key ] ?? 'Strawberry Blush';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Happy Birthday, <?php echo esc_html( $their_name ); ?>!</title>
<link rel="icon" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-32.png' ); ?>" sizes="32x32">
<link rel="apple-touch-icon" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-180.png' ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&family=Yellowtail&display=swap" rel="stylesheet">
<style>
  :root{
    --gold-deep:#c17a3f; --gold:#e0a868; --peach-soft:#f8ead9;
    --ink:#3a2620; --muted:#8a6e63; --night:#2a1830; --night-card:#3a2440;
    --font-display:'Outfit', -apple-system, 'Segoe UI', Roboto, sans-serif;
    --font-script:'Yellowtail', cursive;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html,body{ height:100%; }
  body{
    font-family:-apple-system, 'Segoe UI', Roboto, sans-serif;
    background:linear-gradient(180deg,var(--night),#4a2a4d);
    min-height:100vh; overflow-x:hidden; color:#fff; position:relative;
  }
  h2, .night .big, .closing-wrap .big{ font-family:var(--font-display); letter-spacing:-.01em; }
  .made-with .script{ font-family:var(--font-script); font-size:1.3rem; color:var(--gold); line-height:1; }
  .stage{ max-width:420px; margin:0 auto; min-height:100vh; position:relative; padding-bottom:40px; }
  .night{ padding:60px 24px 40px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; }
  .night .wish{ font-style:italic; opacity:.75; font-size:1rem; margin-bottom:10px; }
  .night .big{ font-size:2.4rem; font-weight:900; line-height:1.15; margin-bottom:6px; }
  .night .name{ font-size:1.8rem; font-weight:800; color:var(--gold); margin-bottom:18px; }
  .night .tap{ font-size:.75rem; opacity:.55; margin-top:20px; letter-spacing:.4px; }
  .balloon-pop{ padding:40px 22px; min-height:100vh; }
  .balloon-pop h2{ text-align:center; font-size:1.3rem; }
  .balloon-pop .sub{ text-align:center; font-size:.82rem; opacity:.7; margin:8px 0 22px; }
  .pop-field{ display:flex; flex-wrap:wrap; gap:22px; justify-content:center; margin-bottom:10px; }
  .balloon{ width:64px; height:80px; border-radius:50% 50% 50% 50% / 60% 60% 40% 40%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.4rem; box-shadow:inset -6px -8px 14px rgba(0,0,0,.15); animation:balloonBob 3.6s ease-in-out infinite; will-change:transform; -webkit-tap-highlight-color:transparent; }
  @keyframes balloonBob{ 0%,100%{ transform:translate3d(0,0,0) rotate(-3deg); } 50%{ transform:translate3d(0,-14px,0) rotate(3deg); } }
  .balloon:active{ transform:scale(.9); }
  /* Real pop: overshoot then collapse, instead of an instant disappear. */
  .balloon.popped{ animation:balloonPop .42s cubic-bezier(.2,.9,.3,1.2) forwards; pointer-events:none; }
  @keyframes balloonPop{ 0%{ transform:scale(1); opacity:1; } 28%{ transform:scale(1.28); opacity:.9; } 100%{ transform:scale(.28); opacity:0; } }
  .reasons{ display:flex; flex-direction:column; gap:10px; margin-top:18px; }
  .reason-card{ position:relative; overflow:hidden; border:1.5px solid; border-radius:14px; padding:12px 14px; background:rgba(255,255,255,.06); animation:stepIn .5s cubic-bezier(.2,.82,.3,1) both; }
  /* One-shot light sweep as each reason lands. translateX rather than left, so it
     does not invalidate layout every frame; base skew folded into both stops. */
  .reason-card::before{ content:''; position:absolute; top:0; left:-100%; width:60%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent); animation:cardSheen 1s ease .25s 1; pointer-events:none; }
  @keyframes cardSheen{ 0%{ transform:translateX(0) skewX(-20deg); } 100%{ transform:translateX(500%) skewX(-20deg); } }
  .reason-card .tag{ display:inline-block; font-size:.65rem; font-weight:800; letter-spacing:.4px; padding:3px 10px; border-radius:12px; margin-bottom:6px; background:rgba(255,255,255,.12); }
  .reason-card .txt{ font-weight:700; font-size:.92rem; }
  .and-more{ text-align:center; font-size:.85rem; opacity:.7; margin:16px 0; font-style:italic; }
  .night-btn{ position:relative; overflow:hidden; width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:700; font-size:.95rem; cursor:pointer; margin-top:6px; }
  /* The 0%-58% dead zone is the whole trick: one glint every ~3.4s, not a strobe. */
  .night-btn::after, .primary-btn::after{ content:''; position:absolute; top:0; bottom:0; left:-120%; width:45%; background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent); transform:skewX(-18deg); animation:btnShine 3.4s ease-in-out infinite; pointer-events:none; }
  @keyframes btnShine{ 0%,58%{ left:-120%; opacity:0; } 64%{ opacity:1; } 90%{ left:160%; opacity:0; } 100%{ left:160%; opacity:0; } }
  .photos-wrap{ padding:50px 22px; min-height:100vh; text-align:center; }
  .photos-wrap h2{ font-size:1.3rem; }
  .photos-wrap .sub{ font-size:.82rem; opacity:.7; margin:8px 0 30px; }
  .envelope-wrap{ padding:60px 24px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .envelope-wrap h3{ font-size:1.2rem; margin-bottom:4px; }
  .envelope-wrap .sub{ font-size:.82rem; opacity:.7; margin-bottom:26px; }
  .envelope{ width:180px; height:120px; background:linear-gradient(160deg,#ffcf7a,#f2a83c); border-radius:8px; position:relative; cursor:pointer; box-shadow:0 14px 30px rgba(0,0,0,.3); animation:float 3s ease-in-out infinite; }
  @keyframes float{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-6px); } }
  .envelope::before{ content:''; position:absolute; inset:0; background:linear-gradient(135deg,transparent 49.5%,rgba(0,0,0,.15) 50%),linear-gradient(-135deg,transparent 49.5%,rgba(0,0,0,.15) 50%); }
  /* The flap is a real element hinged at the top, so opening is a 3D rotation
     rather than a fade. .8s with a slight overshoot on the way over. */
  .env-flap{ position:absolute; top:0; left:0; right:0; height:55%; transform-origin:top center; transform-style:preserve-3d; z-index:5; border-radius:8px 8px 0 0; background:linear-gradient(160deg,#ffdca0,#eda23a); clip-path:polygon(0 0, 100% 0, 50% 100%); transition:transform .8s cubic-bezier(.175,.885,.32,1.275), z-index .3s; }
  .envelope.is-open .env-flap{ transform:rotateX(180deg); z-index:1; }
  .env-glow{ position:absolute; width:340px; height:340px; max-width:90vw; top:50%; left:50%; transform:translate(-50%,-50%); background:radial-gradient(circle,rgba(255,150,80,.34) 0,transparent 68%); filter:blur(36px); z-index:0; pointer-events:none; animation:envGlowPulse 3s ease-in-out infinite; }
  @keyframes envGlowPulse{ 0%,100%{ opacity:.55; transform:translate(-50%,-50%) scale(.95); } 50%{ opacity:.9; transform:translate(-50%,-50%) scale(1.08); } }
  /* Rise + sway are split across two elements so the transforms compose;
     the reference animated bottom/margin-left, which lays out every frame. */
  .env-float{ position:absolute; left:50%; bottom:40%; z-index:4; pointer-events:none; animation:envFloatRise linear forwards; }
  .env-float i{ display:block; font-style:normal; animation:envFloatSway 1.4s ease-in-out infinite alternate; }
  @keyframes envFloatRise{ 0%{ transform:translateY(0); opacity:0; } 15%{ opacity:.95; } 80%{ opacity:.85; } 100%{ transform:translateY(-260px); opacity:0; } }
  @keyframes envFloatSway{ 0%{ transform:translateX(-18px); } 100%{ transform:translateX(18px); } }
  .envelope .seal{ position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:44px; height:44px; border-radius:50%; background:#fff; color:var(--gold-deep); font-weight:900; display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 4px 10px rgba(0,0,0,.2); }
  .envelope-wrap .tap{ font-size:.75rem; opacity:.6; margin-top:20px; animation:hintBounce 1.8s ease-in-out infinite; }
  @keyframes hintBounce{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-9px); } }
  .letter-page{ padding:26px 22px 40px; min-height:100vh; }
  .letter-paper{ background:#fff8e8; border-radius:14px; padding:22px 20px; min-height:260px; margin-top:20px; opacity:0; animation:letterAppear .7s cubic-bezier(.34,1.56,.64,1) .12s forwards; }
  @keyframes letterAppear{ 0%{ opacity:0; transform:scale(.92); } 100%{ opacity:1; transform:scale(1); } }
  .letter-paper .dear{ font-weight:800; color:var(--gold-deep); margin-bottom:14px; font-size:1.05rem; }
  .letter-paper .msg{ line-height:1.7; font-size:.95rem; color:#3a2a10; white-space:pre-wrap; }
  /* Each character lands as a warm amber spark and cools into ink. This is
     what makes reading the letter an event rather than text appearing. */
  .magic-char{ display:inline-block; white-space:pre; opacity:0; animation:magicIn .5s ease forwards; }
  @keyframes magicIn{
    0%{ opacity:0; transform:scale(1.5) translateY(-4px); filter:blur(2px); color:#f0a83e; text-shadow:0 0 10px #ffc46b, 0 0 20px #ffc46b; }
    40%{ opacity:1; transform:scale(1.08) translateY(0); filter:blur(0); color:#f0a83e; text-shadow:0 0 12px #ffc46b; }
    100%{ opacity:1; transform:scale(1) translateY(0); filter:blur(0); color:#3a2a10; text-shadow:none; }
  }
  .letter-paper .sign{ text-align:right; margin-top:18px; font-weight:700; color:var(--gold-deep); }
  .letter-page .primary-btn{ margin-top:20px; }
  .primary-btn{ position:relative; overflow:hidden; width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:700; font-size:.95rem; cursor:pointer; box-shadow:0 10px 26px rgba(242,121,11,.35); }
  .closing-wrap{ padding:60px 24px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .closing-wrap .big{ font-size:1.7rem; font-weight:900; line-height:1.25; }
  .closing-wrap .big .name{ color:var(--gold); }
  .closing-wrap .decor{ font-size:2.4rem; margin:18px 0; opacity:0; transform:translateY(18px) scale(.5) rotate(-10deg); will-change:transform,opacity; }
  /* Pop, then hand off to an idle float. One shorthand — the .8s delay is the pop
     duration plus 100ms, so the two must be tuned together. */
  .closing-wrap .decor.is-pop{ animation:stickerPop .7s cubic-bezier(.2,.9,.3,1.4) forwards, stickerFloat 4.6s ease-in-out .8s infinite; }
  @keyframes stickerPop{ 0%{ opacity:0; transform:translateY(18px) scale(.5) rotate(-10deg); } 60%{ opacity:1; transform:translateY(-4px) scale(1.06) rotate(3deg); } 100%{ opacity:1; transform:translateY(0) scale(1) rotate(0); } }
  @keyframes stickerFloat{ 0%,100%{ transform:translateY(0) rotate(-1.5deg); } 50%{ transform:translateY(-8px) rotate(1.5deg); } }
  .closing-wrap .from{ font-size:.85rem; opacity:.75; margin-bottom:8px; }
  .closing-wrap .cake-note{ font-size:.72rem; opacity:.5; margin-bottom:24px; }
  .closing-wrap .made-with{ font-size:.75rem; opacity:.5; margin-top:10px; }
  .unwrap-wrap{ padding:60px 24px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .unwrap-wrap .kicker{ font-size:.78rem; font-weight:800; letter-spacing:.08em; opacity:.7; margin-bottom:8px; }
  .unwrap-wrap .name{ font-size:2rem; font-weight:900; color:var(--gold); margin-bottom:14px; }
  .unwrap-wrap .from{ font-size:.9rem; opacity:.75; margin-bottom:32px; }
  .unwrap-btn{ border:none; border-radius:40px; padding:16px 34px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:800; font-size:1rem; cursor:pointer; box-shadow:0 14px 30px rgba(193,122,63,.35); }
  .unwrap-wrap .sound-hint{ font-size:.75rem; opacity:.5; margin-top:16px; }
  .sound-toggle{ position:fixed; top:16px; right:16px; z-index:45; width:38px; height:38px; border-radius:50%; border:none; background:rgba(255,255,255,.15); color:#fff; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; }
  .wordmark{ position:relative; z-index:4; text-align:center; padding:14px 0 0; }
  .wordmark-pill{ display:inline-block; background:rgba(255,255,255,.92); padding:7px 16px; border-radius:20px; }
  .wordmark-pill img{ height:30px; width:auto; display:block; }
  .bg-balloons{ position:fixed; inset:0; z-index:3; overflow:hidden; pointer-events:none; }
  .bg-balloon{ position:absolute; bottom:-10vh; will-change:transform; animation:bgBalloonFloat linear infinite; }
  @keyframes bgBalloonFloat{
    0%{ transform:translateY(0) translateX(0) rotate(-4deg); }
    50%{ transform:translateY(-55vh) translateX(16px) rotate(4deg); }
    100%{ transform:translateY(-115vh) translateX(-12px) rotate(-4deg); }
  }
  @media (prefers-reduced-motion: reduce){ .bg-balloon{ animation:none; display:none; } }
  .locked-wrap{ padding:60px 24px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .locked-wrap .emoji{ display:inline-block; font-size:2.6rem; margin-bottom:14px; animation:giftRock 2.4s ease-in-out infinite; }
  @keyframes giftRock{ 0%,100%{ transform:rotate(-6deg); } 50%{ transform:rotate(6deg); } }
  .locked-wrap h3{ font-size:1.25rem; margin-bottom:8px; }
  .locked-wrap .sub{ font-size:.85rem; opacity:.7; margin-bottom:26px; }
  .count-digits{ display:flex; gap:14px; }
  .count-cell{ background:rgba(255,255,255,.08); border-radius:14px; padding:14px 18px; min-width:64px; }
  .count-num{ font-size:1.8rem; font-weight:900; }
  .count-label{ font-size:.65rem; opacity:.6; letter-spacing:.06em; margin-top:4px; }
  .teaser-wrap{ padding:60px 24px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; }
  .teaser-wrap .sub2{ font-size:.9rem; opacity:.7; margin-top:14px; }
  .photo-carousel{ display:flex; gap:16px; overflow-x:auto; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; padding:10px 24px 20px; margin:0 -22px 10px; }
  .photo-carousel::-webkit-scrollbar{ display:none; }
  .photo-carousel-item{ scroll-snap-align:center; flex:0 0 auto; width:200px; transform-origin:top center; animation:swayPhoto 4.6s ease-in-out infinite; will-change:transform; }
  .photo-carousel-item:nth-child(2n){ animation-name:swayPhotoAlt; animation-delay:-2.3s; }
  /* Transform-based with the tilt folded in — animating margin-top here would
     reflow the whole flex row every frame, once per photo. */
  @keyframes swayPhoto{ 0%,100%{ transform:translate3d(0,0,0) rotate(-2.4deg); } 50%{ transform:translate3d(0,6px,0) rotate(-2.4deg); } }
  @keyframes swayPhotoAlt{ 0%,100%{ transform:translate3d(0,0,0) rotate(2.2deg); } 50%{ transform:translate3d(0,6px,0) rotate(2.2deg); } }
  .photo-carousel-item img{ width:200px; height:240px; object-fit:cover; border-radius:14px; border:5px solid #fff; box-shadow:0 12px 26px rgba(0,0,0,.35); display:block; animation:kenburns 9s ease-in-out infinite alternate; }
  @keyframes kenburns{ from{ transform:scale(1); } to{ transform:scale(1.09); } }
  .photo-carousel-item .cap{ text-align:center; font-size:.75rem; opacity:.7; margin-top:8px; }
  .photo-dots{ display:flex; justify-content:center; gap:6px; margin-bottom:6px; }
  .photo-dots span{ width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,.25); }
  .photo-dots span.on{ background:var(--gold); }
  .swipe-hint{ position:relative; height:0; }
  .swipe-hint-icon{ position:absolute; top:-160px; left:50%; margin-left:-14px; font-size:1.6rem; animation:gSwipe 2.2s ease-in-out infinite; pointer-events:none; transition:opacity .3s ease; }
  @keyframes gSwipe{
    0%{ transform:translateX(-46px) rotate(-8deg) scale(1); opacity:0; }
    16%{ opacity:1; transform:translateX(-46px) rotate(-8deg) scale(.86); }
    80%{ opacity:1; transform:translateX(46px) rotate(-8deg) scale(.86); }
    100%{ transform:translateX(46px) rotate(-8deg) scale(1); opacity:0; }
  }
  /* Reduced motion: for anything that CARRIES STATE, force the correct END state
     rather than just switching the animation off — animation:none alone would
     park a popped balloon at full size and leave the finale decor invisible. */
  @media (prefers-reduced-motion: reduce){
    .swipe-hint-icon, .bg-balloon{ animation:none; display:none; }
    .balloon{ animation:none; }
    .balloon.popped{ animation:none; opacity:0; visibility:hidden; }
    .closing-wrap .decor,
    .closing-wrap .decor.is-pop{ animation:none; opacity:1; transform:none; }
    .letter-paper{ animation:none; opacity:1; }
    .magic-char{ animation:none; opacity:1; color:#3a2a10; filter:none; text-shadow:none; transform:none; }
    .env-flap{ transition:none; }
    .env-glow, .env-float, .env-float i{ animation:none; }
    .env-float{ display:none; }
    .reason-card{ animation:none; }
    .reason-card::before{ animation:none; opacity:0; }
    .night-btn::after, .primary-btn::after{ animation:none; opacity:0; }
    .photo-carousel-item, .photo-carousel-item img,
    .locked-wrap .emoji, .envelope-wrap .tap, .envelope{ animation:none; }
  }
  /* Phones do the least well with many blurred/animated layers at once. */
  @media (max-width:768px){
    .bg-balloon{ animation-duration:26s; }
    .photo-carousel-item{ animation:none; }
  }
  /* Scenes are stacked and cross-faded rather than display-toggled — a
     display swap has no frame where both scenes exist, which is what made
     transitions read as a slideshow. Out is fast and undelayed; in is slower
     and delayed, so the outgoing scene has cleared before the new one commits. */
  /* Blossom-tree scene. Light on purpose: it is the bright beat between
     the dark teaser and the dark title card, exactly as the reference. */
  .tree-wrap{ position:relative; min-height:100%; height:100%; overflow:hidden; cursor:pointer; background:linear-gradient(180deg,#fdf6ee 0%,#fbeadd 55%,#f7dfd0 100%); }
  .tree-canvas{ position:absolute; inset:0; width:100%; height:100%; display:block; }
  /* Sits BELOW the canopy: the heart fills the full width of our 420px stage,
     so the reference's left-of-tree placement would land straight on the blossoms. */
  .tree-copy{ position:absolute; left:0; right:0; top:63%; z-index:2; text-align:center; pointer-events:none; color:#e0447a; font-weight:800; line-height:1.15; text-shadow:0 2px 12px rgba(255,255,255,.85), 0 0 26px rgba(255,255,255,.6); }
  .tree-copy .l1, .tree-copy .l2{ font-family:var(--font-display); font-size:clamp(1.5rem,7vw,2.2rem); letter-spacing:-.01em; }
  .tree-note{ font-size:.72rem; font-weight:600; color:#b8607e; margin-top:10px; opacity:0; transition:opacity .6s ease; }
  .tree-note.is-in{ opacity:1; }
  .tree-tap{ position:absolute; left:0; right:0; bottom:7%; z-index:2; text-align:center; font-size:.75rem; letter-spacing:.4px; color:#b8607e; opacity:0; transition:opacity .6s ease; pointer-events:none; }
  .tree-tap.is-in{ opacity:1; }
  .step{ position:absolute; inset:0; overflow-y:auto; -webkit-overflow-scrolling:touch; opacity:0; pointer-events:none; transform:translateY(14px); transition:opacity .28s ease; }
  .step.active{ opacity:1; pointer-events:auto; transform:translateY(0); transition:opacity .5s ease .16s, transform .55s cubic-bezier(.2,.7,.3,1) .06s; }
  /* Every scene now stays in the render tree, so idle scenes would otherwise
     keep burning GPU on their own loops (ken burns, balloon bob, sway). */
  .step:not(.active) *{ animation-play-state:paused; }
  /* z-index 40 deliberately: below .sound-toggle (45), so a firework never paints
     over the mute button. Do NOT copy the reference's 9000. */
  #bmFx{ position:fixed; inset:0; width:100%; height:100%; pointer-events:none; z-index:40; }
  @keyframes stepIn{
    0%{ opacity:0; transform:translate3d(0,16px,0) scale(.96); }
    60%{ opacity:1; transform:translate3d(0,-2px,0) scale(1.015); }
    100%{ opacity:1; transform:translate3d(0,0,0) scale(1); }
  }
</style>
</head>
<body>

<div class="wordmark"><div class="wordmark-pill"><img src="<?php echo esc_url( BM_PLUGIN_URL . 'assets/logo.png' ); ?>" alt="Blush Moments"></div></div>
<div class="bg-balloons" id="bgBalloons" aria-hidden="true"></div>
<canvas id="bmFx" aria-hidden="true"></canvas>
<button class="sound-toggle" id="soundToggle" onclick="toggleSound()" aria-label="Toggle sound">🔊</button>

<div class="stage">

  <div class="step active" data-step="unwrap">
    <div class="unwrap-wrap">
      <div class="kicker">A SURPRISE FOR</div>
      <div class="name"><?php echo esc_html( $their_name ); ?></div>
      <div class="from"><?php echo esc_html( $your_name ); ?> made this — just for you.</div>
      <button class="unwrap-btn" onclick="handleUnwrap()">Unwrap it 🎁</button>
      <div class="sound-hint">🔊 Sound on for the full magic</div>
    </div>
  </div>

  <div class="step" data-step="locked">
    <div class="locked-wrap">
      <div class="emoji">🎈</div>
      <h3>The surprise unlocks at midnight</h3>
      <div class="sub">Some things are worth the wait. This is one of them.</div>
      <div class="count-digits">
        <div class="count-cell"><div class="count-num" id="countH">00</div><div class="count-label">HOURS</div></div>
        <div class="count-cell"><div class="count-num" id="countM">00</div><div class="count-label">MINUTES</div></div>
        <div class="count-cell"><div class="count-num" id="countS">00</div><div class="count-label">SECONDS</div></div>
      </div>
    </div>
  </div>

  <div class="step" data-step="teaser">
    <div class="teaser-wrap" onclick="toStep('tree')">
      <div class="big">Happy<br>Birthday</div>
      <div class="sub2">to someone worth celebrating</div>
      <div class="tap">tap anywhere to continue</div>
    </div>
  </div>

  <div class="step" data-step="tree">
    <div class="tree-wrap" onclick="toStep('title')">
      <canvas id="treeCanvas" class="tree-canvas" aria-label="A blossom tree grows and blooms into a heart made of petals" role="img"></canvas>
      <div class="tree-copy">
        <div class="l1">Happy</div>
        <div class="l2">Birthday, <?php echo esc_html( $their_name ); ?></div>
        <div class="tree-note" id="treeWish">it&rsquo;s officially your day</div>
      </div>
      <div class="tree-tap" id="treeTap">tap anywhere to continue</div>
    </div>
  </div>

  <div class="step" data-step="title">
    <div class="night" onclick="toStep('balloons')">
      <div class="wish">make a wish...</div>
      <div class="big">Happy<br>Birthday</div>
      <div class="name"><?php echo esc_html( $their_name ); ?>!</div>
      <div class="tap">tap anywhere to continue</div>
    </div>
  </div>

  <div class="step" data-step="balloons">
    <div class="balloon-pop">
      <h2>Pop the balloons 🎈</h2>
      <div class="sub"><?php echo count( $balloons ); ?> balloon<?php echo count( $balloons ) === 1 ? '' : 's'; ?>. Each one holds a reason you're loved. Pop them all 🎈</div>
      <div class="pop-field" id="popField"></div>
      <div class="reasons" id="reasonsList"></div>
      <div class="and-more" id="andMore" style="display:none;">...and a thousand more reasons 💛</div>
      <button class="night-btn" id="keepGoingBtn" style="display:none;" onclick="goAfterBalloons()">Keep going 💛</button>
    </div>
  </div>

  <div class="step" data-step="photos">
    <div class="photos-wrap">
      <h2>A walk down memory lane 📸</h2>
      <div class="sub">swipe through</div>
      <div class="swipe-hint"><div class="swipe-hint-icon" id="swipeHint">👉</div></div>
      <div class="photo-carousel" id="photoCarousel"></div>
      <div class="photo-dots" id="photoDots"></div>
      <button class="night-btn" onclick="toStep('envelope')">Keep going 💛</button>
    </div>
  </div>

  <div class="step" data-step="envelope">
    <div class="envelope-wrap">
      <h3>One last thing, <?php echo esc_html( $their_name ); ?>...</h3>
      <div class="sub"><?php echo esc_html( $your_name ); ?> wrote you a letter.</div>
      <div class="env-glow" aria-hidden="true"></div>
      <div class="envelope" id="envelope" onclick="openLetter()"><div class="env-flap" aria-hidden="true"></div><div class="seal">🎂</div></div>
      <div class="tap">Tap to open your letter</div>
    </div>
  </div>

  <div class="step" data-step="letter">
    <div class="letter-page">
      <div class="letter-paper">
        <div class="dear">Dear <?php echo esc_html( $their_name ); ?>,</div>
        <div class="msg" id="letterMsgOut"></div>
        <div class="sign" id="letterSign" style="display:none;">With all my love,<br>— <?php echo esc_html( $your_name ); ?></div>
      </div>
      <button class="primary-btn" id="letterContinueBtn" style="display:none;" onclick="toStep('closing')">Continue</button>
    </div>
  </div>

  <div class="step" data-step="closing">
    <div class="closing-wrap">
      <div class="big">HAPPY BIRTHDAY<br><span class="name"><?php echo esc_html( mb_strtoupper( $their_name ) ); ?>!</span></div>
      <div class="decor">🎂🎈🎉</div>
      <div class="from">Made with love, just for you — <?php echo esc_html( $your_name ); ?> 💛</div>
      <div class="cake-note"><?php echo esc_html( $cake_label ); ?><?php echo $age ? ' · turning ' . esc_html( $age ) : ''; ?></div>
      <div class="made-with">Made with <span class="script">Blush Moments</span>, just for you.</div>
    </div>
  </div>

</div>

<script>
  const BALLOONS = <?php echo wp_json_encode( $balloons, JSON_UNESCAPED_UNICODE ); ?>;
  const MESSAGE = <?php echo wp_json_encode( $message, JSON_UNESCAPED_UNICODE ); ?>;
  const PHOTOS = <?php echo wp_json_encode( $photos, JSON_UNESCAPED_UNICODE ); ?>;
  const LOCK_SECONDS = <?php echo (int) $lock_seconds; ?>;
  const BALLOON_COLORS = ['#ff8fa3','#8f7aff','#3fd6c0','#ffb347','#ff6f91'];

  // ── Particle FX canvas ──────────────────────────────────────────
  // Ported from the reference build's #om-bday-fx layer. Replaces the
  // old DOM-based confetti (40 emoji divs) with a real 2D particle
  // system: rect flakes + dots, gravity, drift, spin, and rockets that
  // shed embers and detonate at apex. The rAF loop parks itself when
  // no particles are alive, so it costs nothing between celebrations.
  const FX = { cv:null, ctx:null, parts:[], raf:0, w:0, h:0, dpr:1 };
  const FX_PALETTE = ['#F6C453','#FF7A59','#39D0C4','#FF8FB1','#A78BFA','#F3E3C3','#FFB74D'];
  const fxTimers = [];

  function fxReduced(){
    try { return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; }
    catch(e){ return false; }
  }
  function fxNarrow(){ return (window.innerWidth || 999) <= 768; }

  function fxInit(){
    if(FX.ctx) return;
    FX.cv = document.getElementById('bmFx');
    if(!FX.cv) return;
    try { FX.ctx = FX.cv.getContext('2d'); } catch(e){ FX.ctx = null; }
    if(!FX.ctx) return;
    fxResize();
  }

  function fxResize(){
    if(!FX.cv || !FX.ctx) return;
    FX.dpr = Math.min(window.devicePixelRatio || 1, 2);
    FX.w = window.innerWidth;
    FX.h = window.innerHeight;
    FX.cv.width  = Math.max(1, Math.round(FX.w * FX.dpr));
    FX.cv.height = Math.max(1, Math.round(FX.h * FX.dpr));
    try { FX.ctx.setTransform(FX.dpr, 0, 0, FX.dpr, 0, 0); } catch(e){}
  }

  function fxAdd(p){
    if(!FX.ctx) return;
    if(FX.parts.length > 320) FX.parts.splice(0, FX.parts.length - 290);
    FX.parts.push(p);
    if(!FX.raf) FX.raf = requestAnimationFrame(fxTick);
  }

  // A firework's detonation: low sine thump under two filtered noise bursts.
  function boomSfx(){
    if(!soundOn || !audioCtx) return;
    playTone(70, .35, 'sine', .18);
    for(let i = 0; i < 2; i++){
      setTimeout(() => playTone(120 + Math.random()*80, .12, 'triangle', .05), i * 60);
    }
  }

  function fxTick(){
    FX.raf = 0;
    if(!FX.ctx) return;
    const c = FX.ctx;
    c.clearRect(0, 0, FX.w, FX.h);
    const prev = FX.parts;
    FX.parts = [];
    for(let i = 0; i < prev.length; i++){
      const p = prev[i];
      p.vx *= p.decay;
      p.vy *= p.decay;
      p.vy += p.g;
      p.x  += p.vx + (p.drift || 0);
      p.y  += p.vy;
      p.rot += p.vr || 0;
      p.life--;

      if(p.kind === 'rocket'){
        if(Math.random() < 0.5) FX.parts.push({
          kind:'dot', x:p.x, y:p.y,
          vx:(Math.random()-0.5)*0.4, vy:0.4,
          g:0.01, decay:0.96, rot:0, size:1.6,
          color:'#F3E3C3', life:18, alpha:1
        });
        if(p.vy >= -0.8 || p.y < p.targetY){
          fxBurst(p.x, p.y, p.burstN, p.color);
          boomSfx();
          continue;
        }
      }

      if(p.life <= 0 || p.y > FX.h + 30) continue;

      p.alpha = Math.min(1, p.life / 26);
      c.globalAlpha = Math.max(0, p.alpha);
      c.fillStyle = p.color;
      if(p.kind === 'rect'){
        c.save();
        c.translate(p.x, p.y);
        c.rotate(p.rot);
        c.fillRect(-p.size/2, -p.size/4, p.size, p.size/2);
        c.restore();
      } else {
        c.beginPath();
        c.arc(p.x, p.y, p.size, 0, 6.2832);
        c.fill();
      }
      FX.parts.push(p);
    }
    c.globalAlpha = 1;
    if(FX.parts.length && !document.hidden){
      if(!FX.raf) FX.raf = requestAnimationFrame(fxTick);
    } else {
      c.clearRect(0, 0, FX.w, FX.h);
    }
  }

  function fxBurst(x, y, n, color){
    const count = fxReduced() ? 8 : (fxNarrow() ? Math.round(n * 0.6) : n);
    for(let i = 0; i < count; i++){
      const a = Math.random() * 6.2832;
      const sp = 2 + Math.random() * 5;
      fxAdd({
        kind: Math.random() < 0.45 ? 'rect' : 'dot',
        x:x, y:y,
        vx: Math.cos(a) * sp,
        vy: Math.sin(a) * sp - 1.5,
        g:0.11, decay:0.955,
        drift:(Math.random()-0.5)*0.3,
        rot: Math.random()*6.28,
        vr:(Math.random()-0.5)*0.25,
        size: 4 + Math.random()*5,
        color: (color && Math.random() < 0.55) ? color : FX_PALETTE[Math.random()*FX_PALETTE.length|0],
        life: 60 + Math.random()*40,
        alpha:1
      });
    }
  }

  function fxBurstMid(x, y){ fxBurst(x, y, 46); }

  function fxConfettiRain(durationMs){
    if(!FX.ctx || fxReduced()) return;
    const end = Date.now() + durationMs;
    const iv = setInterval(() => {
      if(Date.now() > end || document.hidden){ clearInterval(iv); return; }
      for(let i = 0; i < (fxNarrow() ? 3 : 6); i++) fxAdd({
        kind:'rect',
        x: Math.random()*FX.w, y:-12,
        vx:(Math.random()-0.5)*1.4,
        vy: 1.4 + Math.random()*2,
        g:0.045, decay:0.995,
        drift:(Math.random()-0.5)*0.7,
        rot: Math.random()*6.28,
        vr:(Math.random()-0.5)*0.22,
        size: 5 + Math.random()*5,
        color: FX_PALETTE[Math.random()*FX_PALETTE.length|0],
        life:240, alpha:1
      });
    }, 130);
    fxTimers.push(setTimeout(() => clearInterval(iv), durationMs + 400));
  }

  function fxFirework(){
    if(!FX.ctx) return;
    if(fxReduced()){
      fxBurst(FX.w*(0.25+Math.random()*0.5), FX.h*(0.2+Math.random()*0.25), 12);
      return;
    }
    fxAdd({
      kind:'rocket',
      x: FX.w*(0.18+Math.random()*0.64),
      y: FX.h + 8,
      vx:(Math.random()-0.5)*1.1,
      vy:-(9.5+Math.random()*3.2),
      g:0.14, decay:0.992, rot:0, size:2.4,
      color: FX_PALETTE[Math.random()*FX_PALETTE.length|0],
      targetY: FX.h*(0.16+Math.random()*0.22),
      burstN: fxNarrow() ? 44 : 78,
      life:300, alpha:1
    });
  }

  let fxResizeT = null;
  window.addEventListener('resize', () => {
    clearTimeout(fxResizeT);
    fxResizeT = setTimeout(fxResize, 160);
  });

/* =====================================================================
 * Blossom tree canvas engine — clean transcription of the obfuscated
 * ourmoments.live/birthday "tree" scene (app.js, IIFE _0x1f7a0b).
 *
 * Source region: app.deobfuscated.js chars ~83,300 – ~112,600
 * aria-label: "A blossom tree grows and blooms into a heart made of petals"
 *
 * A bare trunk grows up, branches fork recursively and are CLIPPED against
 * a heart-shaped boundary, then blossoms bloom in to fill that heart,
 * then petals detach and drift to the ground.
 *
 * Every numeric constant below is recovered verbatim from the source.
 * Anything guessed is marked with an explicit TODO(GUESS) comment.
 * (At time of writing there are NO TODO(GUESS) items in the algorithm —
 *  see the notes at the bottom of the file for the only two additions,
 *  both of which are plumbing, not maths.)
 *
 * Usage:
 *   var tree = createBlossomTree(document.getElementById('my-canvas'), {
 *     wishEl: document.getElementById('wish'),   // optional, gets .is-in
 *     tapEl:  document.getElementById('tap')     // optional, gets .is-in
 *   });
 *   tree.start();   // tree.stop();  tree.resize();
 *
 * The canvas MUST be sized by CSS (clientWidth/clientHeight drive the
 * backing store); the engine handles DPR itself.
 * ===================================================================== */

function createBlossomTree(canvas, opts) {
  'use strict';

  opts = opts || {};

  /* ------------------------------------------------------------------
   * CONSTANTS
   * ---------------------------------------------------------------- */

  // 6 blossom colour pairs: c0 = inner highlight, c1 = body colour.
  var PALETTE = [
    { c0: '#ffe1ec', c1: '#ff80aa' },
    { c0: '#ffd0e0', c1: '#f4577f' },
    { c0: '#ffc4d2', c1: '#e23b67' },
    { c0: '#ffd9c4', c1: '#ff8a5b' },
    { c0: '#ffeec2', c1: '#f6b13e' },
    { c0: '#ffd2e6', c1: '#e84d9a' }
  ];

  // Master timeline, in SECONDS since start().
  var TL = {
    trunkStart: 0.1,   // t0 of the trunk segment
    branchSpan: 1.8,   // all branch t0's are renormalised to end here
    bloomT0: 1.25,     // earliest blossom start (bottom tip of the heart)
    bloomSpan: 2,      // total spread of blossom start times
    petalT0: 2.45,     // petals start detaching and falling
    noteStart: 0.45,   // the "wish" caption fades in
    done: 4.6          // scene considered finished
  };

  var SPRITE = 168;    // 0xa8 — baked blossom sprite canvas is 168x168 px

  /* ------------------------------------------------------------------
   * SMALL MATH HELPERS
   * ---------------------------------------------------------------- */

  function rand(a, b) { return a + Math.random() * (b - a); }
  function pick(a) { return a[Math.random() * a.length | 0]; }
  function clamp(v, lo, hi) { return v < lo ? lo : (v > hi ? hi : v); }
  function sat(v) { return v < 0 ? 0 : (v > 1 ? 1 : v); }   // clamp to [0,1]
  function lerp(a, b, t) { return a + (b - a) * t; }

  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

  // easeOutBack — overshoot constant recovered exactly: c1 = 1.70158,
  // c3 = c1 + 1 = 2.70158 (the standard easings.net value).
  function easeOutBack(t) {
    var c1 = 1.70158;
    var c3 = c1 + 1;
    return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
  }

  // Additive lighten/darken of a #rrggbb string -> "rgb(r,g,b)".
  function shade(hex, amt) {
    var n = parseInt(hex.slice(1), 16);
    var r = clamp((n >> 16) + amt, 0, 255);
    var g = clamp((n >> 8 & 255) + amt, 0, 255);
    var b = clamp((n & 255) + amt, 0, 255);
    return 'rgb(' + (r | 0) + ',' + (g | 0) + ',' + (b | 0) + ')';
  }

  function prefersReducedMotion() {
    // opts.reducedMotion is an ADDITION (not in the original) so callers
    // can force either branch; the default path is the original check.
    if (opts.reducedMotion != null) return !!opts.reducedMotion;
    try {
      return typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
      return false;
    }
  }

  /* ------------------------------------------------------------------
   * STATE
   * ---------------------------------------------------------------- */

  var ctx = null;
  var wishEl = opts.wishEl || null;   // caption element toggled at noteStart
  var tapEl = opts.tapEl || null;    // "tap to continue" toggled at t >= 3
  var IN_CLASS = opts.inClass || 'is-in';

  var wired = false;
  var W = 0, H = 0, dpr = 1;

  var heartCx = 0;    // heart centre X (px)
  var heartCy = 0;    // heart centre Y (px)
  var heartRx = 0;    // heart half-width  = heartR * 1.16
  var heartR = 0;    // heart half-height
  var groundY = 0;    // where falling petals settle

  var segments = [];  // branch segments (quadratic beziers)
  var blossoms = [];
  var falling = [];   // petals in flight
  var settled = [];   // petals that have landed
  var bokeh = [];     // big soft out-of-focus blobs
  var ambient = [];   // background/foreground drifting petals
  var sparkles = [];

  var bgGrad = null, bloomGrad = null, groundGlowGrad = null;

  var sprites = { crisp: [], soft: [] };  // 6 colours x 2 variants = 12
  var bokehSprites = [];
  var sparkleSprite = null;
  var heartPoly = null;   // normalised cardioid polygon, both axes in [-1,1]

  var rafId = 0;
  var startMs = 0, lastMs = 0, lastPetalMs = 0;
  var running = false, finished = false;

  /* ------------------------------------------------------------------
   * 1. HEART GEOMETRY
   * ---------------------------------------------------------------- */

  // The 4-bezier heart used for the blossom SPRITE (not the canopy).
  function heartPath(c, x, y, w, h) {
    c.beginPath();
    c.moveTo(x, y + h * 0.28);
    c.bezierCurveTo(x, y, x - w * 0.5, y, x - w * 0.5, y + h * 0.28);
    c.bezierCurveTo(x - w * 0.5, y + h * 0.6, x - w * 0.16, y + h * 0.8, x, y + h);
    c.bezierCurveTo(x + w * 0.16, y + h * 0.8, x + w * 0.5, y + h * 0.6, x + w * 0.5, y + h * 0.28);
    c.bezierCurveTo(x + w * 0.5, y, x, y, x, y + h * 0.28);
    c.closePath();
  }

  // Parametric cardioid, sampled at 161 points (loop is i <= 160, so the
  // last sample duplicates the first — 160 distinct + 1 closing point),
  // then normalised so each axis independently spans exactly [-1, 1]:
  // centre on the midpoint of (min,max) and divide by the half-range.
  // NOTE: half-ranges are computed per-axis, so the shape is stretched
  // to fill the unit square; it is NOT aspect-preserving.
  function buildHeartPoly() {
    var pts = [];
    var minX = 1e9, maxX = -1e9, minY = 1e9, maxY = -1e9;   // 0x3b9aca00 = 1e9
    var SAMPLES = 160;   // 0xa0

    for (var i = 0; i <= SAMPLES; i++) {
      var t = i / SAMPLES * Math.PI * 2;
      var x = 16 * Math.pow(Math.sin(t), 3);
      var y = 13 * Math.cos(t) - 5 * Math.cos(2 * t) - 2 * Math.cos(3 * t) - Math.cos(4 * t);
      pts.push([x, y]);
      if (x < minX) minX = x;
      if (x > maxX) maxX = x;
      if (y < minY) minY = y;
      if (y > maxY) maxY = y;
    }

    var midX = (minX + maxX) / 2;
    var midY = (minY + maxY) / 2;
    var halfX = (maxX - minX) / 2;
    var halfY = (maxY - minY) / 2;

    heartPoly = pts.map(function (p) {
      return [(p[0] - midX) / halfX, (p[1] - midY) / halfY];
    });
  }

  /* ------------------------------------------------------------------
   * 2. POINT IN POLYGON — even-odd ray casting (horizontal ray, +x)
   * ---------------------------------------------------------------- */

  function inHeartNorm(px, py) {
    var inside = false;
    var poly = heartPoly;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      var xi = poly[i][0], yi = poly[i][1];
      var xj = poly[j][0], yj = poly[j][1];
      if ((yi > py) !== (yj > py) &&
          px < (xj - xi) * (py - yi) / (yj - yi) + xi) {
        inside = !inside;
      }
    }
    return inside;
  }

  // Screen-space test. `pad` shrinks the heart; branches use the default
  // 0.9 (so the canopy silhouette sits inside the blossom cloud).
  // Y is FLIPPED: canvas y grows down, the cardioid's y grows up.
  function inHeart(x, y, pad) {
    pad = pad == null ? 0.9 : pad;
    return inHeartNorm((x - heartCx) / (heartRx * pad),
                       (heartCy - y) / (heartR * pad));
  }

  /* ------------------------------------------------------------------
   * 7. SPRITE BAKING — 12 blossom sprites (6 colours x crisp/soft)
   * ---------------------------------------------------------------- */

  function bakeBlossom(pal, soft) {
    var c0 = pal.c0, c1 = pal.c1;
    var cv = document.createElement('canvas');
    cv.width = cv.height = SPRITE;
    var g = cv.getContext('2d');

    var pw = SPRITE * 0.62;   // petal width
    var ph = SPRITE * 0.58;   // petal height
    var cx = SPRITE / 2;
    var top = SPRITE * 0.17;

    // 1. drop shadow pass
    g.save();
    g.shadowColor = 'rgba(150,38,72,0.32)';
    g.shadowBlur = SPRITE * 0.085;
    g.shadowOffsetY = SPRITE * 0.05;
    g.fillStyle = c1;
    heartPath(g, cx, top, pw, ph);
    g.fill();
    g.restore();

    // 2. radial body gradient: highlight upper-left, darkened rim
    var rg = g.createRadialGradient(
      cx - pw * 0.2, top + ph * 0.2, ph * 0.04,
      cx,            top + ph * 0.42, ph * 0.92
    );
    rg.addColorStop(0, c0);
    rg.addColorStop(0.55, c1);
    rg.addColorStop(1, shade(c1, -26));    // -0x1a
    heartPath(g, cx, top, pw, ph);
    g.fillStyle = rg;
    g.fill();

    // 3. clipped detail pass: bottom shading + specular ellipse
    g.save();
    heartPath(g, cx, top, pw, ph);
    g.clip();
    var lg = g.createLinearGradient(0, top, 0, top + ph);
    lg.addColorStop(0, 'rgba(255,255,255,0)');
    lg.addColorStop(0.65, 'rgba(110,16,46,0)');
    lg.addColorStop(1, 'rgba(110,16,46,0.26)');
    g.fillStyle = lg;
    g.fillRect(0, 0, SPRITE, SPRITE);
    g.globalAlpha = 0.55;
    g.fillStyle = '#ffffff';
    g.beginPath();
    g.ellipse(cx - pw * 0.15, top + ph * 0.24, pw * 0.17, ph * 0.11, -0.5, 0, Math.PI * 2);
    g.fill();
    g.restore();

    if (!soft) return cv;

    // "Soft" variant = the crisp sprite blurred 2.6px, then washed with a
    // 42%-alpha cream tint clipped to the existing pixels (source-atop).
    // Used for the far/hazy blossoms and the background ambient petals.
    var cv2 = document.createElement('canvas');
    cv2.width = cv2.height = SPRITE;
    var g2 = cv2.getContext('2d');
    g2.filter = 'blur(2.6px)';
    g2.drawImage(cv, 0, 0);
    g2.filter = 'none';
    g2.globalCompositeOperation = 'source-atop';
    g2.globalAlpha = 0.42;
    g2.fillStyle = '#fff3ea';
    g2.fillRect(0, 0, SPRITE, SPRITE);
    return cv2;
  }

  // 128px radial blob used for the big out-of-focus bokeh circles.
  function bakeBokeh(rgb) {
    var S = 128;    // 0x80
    var cv = document.createElement('canvas');
    cv.width = cv.height = S;
    var g = cv.getContext('2d');
    var rg = g.createRadialGradient(S / 2, S / 2, 0, S / 2, S / 2, S / 2);
    rg.addColorStop(0, 'rgba(' + rgb + ',0.9)');
    rg.addColorStop(0.45, 'rgba(' + rgb + ',0.22)');
    rg.addColorStop(1, 'rgba(' + rgb + ',0)');
    g.fillStyle = rg;
    g.fillRect(0, 0, S, S);
    return cv;
  }

  // 64px 4-point star + glow, drawn twice (second pass rotated 45deg and
  // scaled 50%) to make an 8-point sparkle.
  function bakeSparkle() {
    var S = 64;    // 0x40
    var cv = document.createElement('canvas');
    cv.width = cv.height = S;
    var g = cv.getContext('2d');
    var h = S / 2;
    var rg = g.createRadialGradient(h, h, 0, h, h, h);
    rg.addColorStop(0, 'rgba(255,255,255,0.95)');
    rg.addColorStop(0.25, 'rgba(255,236,200,0.5)');
    rg.addColorStop(1, 'rgba(255,236,200,0)');
    g.fillStyle = rg;
    g.beginPath();
    g.arc(h, h, h, 0, 6.2832);
    g.fill();

    g.fillStyle = 'rgba(255,255,255,0.95)';
    g.translate(h, h);
    for (var i = 0; i < 2; i++) {
      g.beginPath();
      g.moveTo(0, -h);
      g.quadraticCurveTo(0, 0, h, 0);
      g.quadraticCurveTo(0, 0, 0, h);
      g.quadraticCurveTo(0, 0, -h, 0);
      g.quadraticCurveTo(0, 0, 0, -h);
      g.fill();
      g.rotate(Math.PI / 4);
      g.scale(0.5, 0.5);
    }
    return cv;
  }

  function bakeAll() {
    sprites = {
      crisp: PALETTE.map(function (p) { return bakeBlossom(p, false); }),
      soft:  PALETTE.map(function (p) { return bakeBlossom(p, true); })
    };
    bokehSprites = [
      bakeBokeh('255,224,188'),
      bakeBokeh('255,196,214'),
      bakeBokeh('255,238,210')
    ];
    sparkleSprite = bakeSparkle();
  }

  // Note the -size*0.47 vertical offset: sprites are pivoted slightly
  // above centre so blossoms hang correctly off their branch tips.
  function drawSprite(img, x, y, size, rot, alpha) {
    ctx.save();
    ctx.translate(x, y);
    if (rot) ctx.rotate(rot);
    ctx.globalAlpha = alpha;
    ctx.drawImage(img, -size * 0.5, -size * 0.47, size, size);
    ctx.restore();
  }

  /* ------------------------------------------------------------------
   * 3/4/5. BRANCH GROWTH, CLIPPING, TIMELINE RENORMALISATION
   * ---------------------------------------------------------------- */

  function qPoint(seg, t) {
    var u = 1 - t;
    var a = u * u, b = 2 * u * t, c = t * t;
    return {
      x: a * seg.x1 + b * seg.cx + c * seg.x2,
      y: a * seg.y1 + b * seg.cy + c * seg.y2
    };
  }

  // Bark gradient — darker at the base, lighter with depth.
  function barkGrad(x1, y1, x2, y2, depth) {
    var g = ctx.createLinearGradient(x1, y1, x2, y2);
    g.addColorStop(0, 'hsl(348 26% ' + (26 + depth * 3) + '%)');
    g.addColorStop(1, 'hsl(346 24% ' + (40 + depth * 5) + '%)');
    return g;
  }

  // Emit ONE branch segment. If its far end leaves the heart, binary-search
  // (12 iterations => ~1/4096 of the segment length) for the crossing point
  // and truncate there, reporting clipped:true so the caller stops growing.
  function addSegment(x, y, ang, len, w, depth, t0) {
    var ex = x + Math.cos(ang) * len;
    var ey = y + Math.sin(ang) * len;
    var clipped = false;

    if (!inHeart(ex, ey)) {
      var lo = 0, hi = 1;
      for (var i = 0; i < 12; i++) {           // 0xc iterations
        var mid = (lo + hi) / 2;
        if (inHeart(x + Math.cos(ang) * len * mid,
                    y + Math.sin(ang) * len * mid)) {
          lo = mid;
        } else {
          hi = mid;
        }
      }
      ex = x + Math.cos(ang) * len * lo;
      ey = y + Math.sin(ang) * len * lo;
      clipped = true;
    }

    // Slight sideways bow: control point pushed off the chord midpoint
    // along the perpendicular by up to +/-12% of the UNCLIPPED length.
    var mx = (x + ex) / 2;
    var my = (y + ey) / 2;
    var perp = ang + Math.PI / 2;
    var bow = rand(-1, 1) * len * 0.12;
    var w1 = w * 0.66;                          // taper per generation

    segments.push({
      x1: x, y1: y,
      cx: mx + Math.cos(perp) * bow,
      cy: my + Math.sin(perp) * bow,
      x2: ex, y2: ey,
      w0: w, w1: w1,
      t0: t0,
      dur: Math.max(0.14, 0.32 - depth * 0.03),  // deeper = quicker
      depth: depth,
      grad: barkGrad(x, y, ex, ey, depth)
    });

    return { ex: ex, ey: ey, w1: w1, clipped: clipped };
  }

  // Recursive fork. STOP CONDITIONS (exactly as in source):
  //   segment was clipped by the heart  ||  depth >= 6  ||  len < heartR*0.06
  function grow(x, y, ang, len, w, depth, t0) {
    var seg = addSegment(x, y, ang, len, w, depth, t0);
    if (seg.clipped || depth >= 6 || len < heartR * 0.06) return;

    // Children start 60% of the way through this segment's own duration.
    var childT0 = t0 + (0.32 - depth * 0.03) * 0.6;
    var n = Math.random() < 0.55 ? 2 : 3;       // 55% chance of a 2-way fork

    for (var i = 0; i < n; i++) {
      var spread = 0.6 * (i - (n - 1) / 2) + rand(-0.22, 0.22); // 0.6 rad apart
      var lean = -0.06 + rand(-0.05, 0.05);                    // slight bias
      grow(seg.ex, seg.ey,
           ang + spread + lean,
           len * rand(0.74, 0.84),               // length decay per level
           seg.w1,                               // width decay = *0.66
           depth + 1,
           childT0 + i * 0.03);                  // stagger siblings 30ms
    }
  }

  function buildTree() {
    var trunkX = heartCx;
    var trunkBaseY = H * 1;                       // trunk starts at the very bottom
    var trunkTopY = heartCy + heartR * 0.62;      // where the canopy begins
    var trunkW = Math.max(9, W * 0.024);
    var branchLen = heartR * 0.6;                 // primary branch length

    // Trunk: a single straight segment, drawn slowly (dur overridden to 0.55).
    addSegment(trunkX, trunkBaseY, -Math.PI / 2,
               trunkBaseY - trunkTopY, trunkW, 0, TL.trunkStart);
    segments[0].dur = 0.55;

    // 3 primary branches fanning from the trunk top, 0.62 rad apart.
    var primaryT0 = TL.trunkStart + 0.36;
    var PRIMARIES = 3;
    for (var i = 0; i < PRIMARIES; i++) {
      var a = -Math.PI / 2 + 0.62 * (i - (PRIMARIES - 1) / 2) + rand(-0.12, 0.12);
      grow(trunkX, trunkTopY, a, branchLen, trunkW * 0.7, 1, primaryT0 + i * 0.05);
    }

    /* --- 5. t0 RENORMALISATION -----------------------------------------
     * The recursion is randomised, so the natural finish time varies run to
     * run. Fix: find the LATEST end time over all segments (max of t0+dur),
     * then compute the single factor that maps the interval
     *     [trunkStart, latestEnd]  ->  [trunkStart, branchSpan]
     * and apply it to every t0 (pivoting on trunkStart, which stays put).
     * Durations are deliberately NOT scaled, so the true finish differs
     * from branchSpan by dur * (1 - scale): it UNDERshoots when the random
     * recursion happened to finish early (scale > 1) and OVERshoots when it
     * ran long (scale < 1). Measured spread is ~1.76-1.78s against a target
     * of 1.80 — a few tens of ms, invisible.
     * Growth therefore always *starts* at trunkStart and *lands on*
     * branchSpan regardless of how deep the random recursion went.
     * ------------------------------------------------------------------ */
    var latestEnd = segments.reduce(function (acc, s) {
      return Math.max(acc, s.t0 + s.dur);
    }, 0);
    var scale = (TL.branchSpan - TL.trunkStart) / (latestEnd - TL.trunkStart);
    for (var k = 0; k < segments.length; k++) {
      segments[k].t0 = TL.trunkStart + (segments[k].t0 - TL.trunkStart) * scale;
    }
  }

  /* ------------------------------------------------------------------
   * 6. BLOSSOM PLACEMENT
   * ---------------------------------------------------------------- */

  function buildBlossoms() {
    // Count scales with heart area, clamped to [250, 440].
    var count = Math.round(clamp(heartRx * heartR / 56, 250, 440));
    // Base sprite size on screen, clamped to [30, 74] px.
    var baseBox = clamp(Math.min(W, H) * 0.115, 30, 74);

    var guard = 0;
    // Rejection sampling inside the unit square (slightly oversized at
    // 1.06 so blossoms can spill just past the strict heart outline).
    // Guard: at most 50 attempts per requested blossom.
    while (blossoms.length < count && guard < count * 50) {
      guard++;
      var nx = rand(-1.06, 1.06);
      var ny = rand(-1.06, 1.06);
      if (!inHeartNorm(nx, ny)) continue;

      var x = heartCx + nx * heartRx;
      var y = heartCy - ny * heartR;         // flip: cardioid y is up

      // Bloom ripple: distance from the heart's BOTTOM TIP, which in
      // normalised space is (0, -1). Divided by 2.4 then clamped, so the
      // far top lobes are ~0.9 through the ramp.
      var d = sat(Math.hypot(nx, ny + 1) / 2.4);
      var t0 = TL.bloomT0 + d * (TL.bloomSpan * 0.82) + rand(0, TL.bloomSpan * 0.18);

      var soft = Math.random() < 0.42;       // 42% are the blurred variant

      blossoms.push({
        x: x, y: y,
        idx: Math.random() * PALETTE.length | 0,   // sprite colour
        soft: soft,
        box: baseBox * (soft ? rand(0.6, 0.85) : rand(0.78, 1.12)),
        rot: rand(-0.55, 0.55),
        sway: rand(0, 6.28),                 // per-blossom sway phase
        t0: t0
      });
    }

    // Paint order: all soft (hazy) blossoms first, then crisp ones;
    // within each group, top of the canvas first so lower blossoms overlap.
    blossoms.sort(function (a, b) {
      return a.soft === b.soft ? a.y - b.y : (a.soft ? -1 : 1);
    });
  }

  /* ------------------------------------------------------------------
   * SCENE BUILD (layout + background + ambient layers)
   * ---------------------------------------------------------------- */

  function build() {
    segments = [];
    blossoms = [];
    falling = [];
    settled = [];
    sparkles = [];
    bokeh = [];
    ambient = [];

    buildHeartPoly();

    var wide = W / H > 1.2;                 // landscape-ish layout switch
    heartCx = W * (wide ? 0.57 : 0.5);
    heartCy = H * (wide ? 0.37 : 0.38);
    // Verbatim from source: the ternary really does have 0.33 on BOTH
    // branches (a leftover from tuning), so heartR = min(H*0.33, W*0.34).
    heartR = Math.min(H * (wide ? 0.33 : 0.33), W * 0.34);
    heartRx = heartR * 1.16;                // heart is 16% wider than tall
    groundY = H * 0.93;

    // Warm dawn backdrop.
    bgGrad = ctx.createLinearGradient(0, 0, 0, H);
    bgGrad.addColorStop(0, '#fff3e9');
    bgGrad.addColorStop(0.46, '#ffe7d6');
    bgGrad.addColorStop(0.78, '#fcd9c4');
    bgGrad.addColorStop(1, '#f3c4b5');

    // Radial glow behind the canopy, faded in with the bloom.
    bloomGrad = ctx.createRadialGradient(heartCx, heartCy, heartR * 0.1,
                                         heartCx, heartCy, heartR * 1.55);
    bloomGrad.addColorStop(0, 'rgba(255,219,170,0.6)');
    bloomGrad.addColorStop(0.5, 'rgba(255,170,150,0.2)');
    bloomGrad.addColorStop(1, 'rgba(255,170,150,0)');

    // Ground bounce light, centred just below the bottom edge.
    groundGlowGrad = ctx.createRadialGradient(heartCx, H * 1.02, heartR * 0.2,
                                              heartCx, H * 1.02, heartR * 1.6);
    groundGlowGrad.addColorStop(0, 'rgba(255,205,165,0.5)');
    groundGlowGrad.addColorStop(1, 'rgba(255,205,165,0)');

    // 11 slow-rising bokeh blobs.
    for (var i = 0; i < 11; i++) {
      bokeh.push({
        x: rand(0, W),
        y: rand(0, H),
        r: rand(W * 0.05, W * 0.17),
        vy: rand(-6, -16),                  // negative => drifts upward
        drift: rand(-0.3, 0.3),
        phase: rand(0, 6.28),
        alpha: rand(0.05, 0.13),
        sprite: pick(bokehSprites)
      });
    }

    // Ambient drifting petals: 18 in landscape, 15 in portrait.
    // `depth` in [0,1) drives size / speed / opacity, and splits them into
    // a back layer (depth < 0.6) and a front layer (depth >= 0.6).
    var ambientCount = wide ? 18 : 15;
    for (var j = 0; j < ambientCount; j++) {
      var depth = Math.random();
      ambient.push({
        x: rand(0, W),
        y: rand(-H * 0.1, H * 1.1),
        depth: depth,
        idx: Math.random() * PALETTE.length | 0,
        box: lerp(Math.min(W, H) * 0.025, Math.min(W, H) * 0.075, depth),
        vy: lerp(7, 20, depth),
        sway: rand(8, 22),                  // 0x8 .. 0x16
        phase: rand(0, 6.28),
        rot: rand(-0.4, 0.4),
        vrot: rand(-0.5, 0.5),
        baseA: lerp(0.16, 0.5, depth),
        soft: depth < 0.45
      });
    }

    buildTree();
    buildBlossoms();
  }

  /* ------------------------------------------------------------------
   * DRAW LAYERS
   * ---------------------------------------------------------------- */

  function drawBackground() {
    ctx.globalAlpha = 1;
    ctx.fillStyle = bgGrad;
    ctx.fillRect(0, 0, W, H);
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    ctx.globalAlpha = 1;
    ctx.fillStyle = groundGlowGrad;
    ctx.fillRect(0, 0, W, H);
    ctx.restore();
  }

  // 9 god-rays fanning down from just above the heart, gently breathing.
  function drawGodRays(t, intensity) {
    if (intensity <= 0) return;
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';

    var ox = heartCx;
    var oy = heartCy - heartR * 0.35;
    var reach = Math.hypot(W, H) * 1.1;
    var RAYS = 9;
    var swing = Math.sin(t * 0.07) * 0.18;   // whole fan rocks very slowly

    for (var i = 0; i < RAYS; i++) {
      var a = -Math.PI / 2 + swing + (i - (RAYS - 1) / 2) * 0.2;  // 0.2 rad apart
      var halfW = 0.035 + 0.02 * (0.5 + 0.5 * Math.sin(t * 0.5 + i * 1.7));
      var a0 = a - halfW;
      var a1 = a + halfW;

      var g = ctx.createLinearGradient(ox, oy,
                                       ox + Math.cos(a) * reach,
                                       oy + Math.sin(a) * reach);
      g.addColorStop(0, 'rgba(255,232,190,' + 0.1 * intensity + ')');
      g.addColorStop(0.5, 'rgba(255,214,170,' + 0.05 * intensity + ')');
      g.addColorStop(1, 'rgba(255,214,170,0)');
      ctx.fillStyle = g;
      ctx.beginPath();
      ctx.moveTo(ox, oy);
      ctx.lineTo(ox + Math.cos(a0) * reach, oy + Math.sin(a0) * reach);
      ctx.lineTo(ox + Math.cos(a1) * reach, oy + Math.sin(a1) * reach);
      ctx.closePath();
      ctx.fill();
    }
    ctx.restore();
  }

  // Canopy glow ramps in over 90% of the bloom span.
  function drawBloomGlow(t) {
    var p = sat((t - TL.bloomT0) / (TL.bloomSpan * 0.9));
    if (p <= 0) return;
    ctx.save();
    ctx.globalAlpha = p;
    ctx.globalCompositeOperation = 'lighter';
    ctx.fillStyle = bloomGrad;
    ctx.fillRect(0, 0, W, H);
    ctx.restore();
  }

  function drawBokeh(t, dt) {
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    for (var i = 0; i < bokeh.length; i++) {
      var b = bokeh[i];
      b.y += b.vy * dt;
      b.x += Math.sin(t * 0.3 + b.phase) * b.drift;
      if (b.y < -b.r) { b.y = H + b.r; b.x = rand(0, W); }
      ctx.globalAlpha = b.alpha;
      ctx.drawImage(b.sprite, b.x - b.r, b.y - b.r, b.r * 2, b.r * 2);
    }
    ctx.restore();
  }

  // front=false draws the back layer (depth < 0.6), front=true the rest.
  function drawAmbient(t, dt, front) {
    var fade = sat((t - 0.2) / 1.4);         // ambient layer fades in early
    if (fade <= 0) return;
    for (var i = 0; i < ambient.length; i++) {
      var p = ambient[i];
      if ((p.depth >= 0.6) !== front) continue;
      p.y -= p.vy * dt;                      // ambient petals rise
      p.x += Math.sin(t * 0.5 + p.phase) * p.sway * dt;
      p.rot += p.vrot * dt;
      if (p.y < -p.box) { p.y = H + p.box; p.x = rand(0, W); }
      drawSprite((p.soft ? sprites.soft : sprites.crisp)[p.idx],
                 p.x, p.y, p.box, p.rot, p.baseA * fade);
    }
  }

  // Branches are stroked as a chain of up to 12 short tapered lines so the
  // width can interpolate along the bezier (canvas has no variable stroke).
  function drawBranches(t) {
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    for (var i = 0; i < segments.length; i++) {
      var s = segments[i];
      var p = sat((t - s.t0) / s.dur);
      if (p <= 0) continue;
      var e = easeOutCubic(p);
      ctx.strokeStyle = s.grad;

      var STEPS = 12;
      var n = Math.max(1, Math.ceil(STEPS * e));
      var prev = qPoint(s, 0);
      for (var k = 1; k <= n; k++) {
        var tt = Math.min(e, k / STEPS);
        var cur = qPoint(s, tt);
        ctx.lineWidth = lerp(s.w0, s.w1, tt);
        ctx.beginPath();
        ctx.moveTo(prev.x, prev.y);
        ctx.lineTo(cur.x, cur.y);
        ctx.stroke();
        prev = cur;
      }
    }
  }

  function drawBlossoms(t) {
    // Whole canopy "breathes" +/-1.2% about the heart centre.
    var breathe = 1 + Math.sin(t * 0.8) * 0.012;

    for (var i = 0; i < blossoms.length; i++) {
      var b = blossoms[i];
      var p = sat((t - b.t0) / 0.6);         // each blossom pops over 0.6s
      if (p <= 0) continue;

      var scale = Math.max(0, easeOutBack(p));   // overshoot pop
      var alpha = sat(p * 1.7);                  // fades in ~1.7x faster
      if (b.soft) alpha *= 0.8;

      // Sway only starts 0.6s AFTER the pop finishes, ramping over 0.7s.
      var swayAmt = sat((t - b.t0 - 0.6) / 0.7);
      var sway = swayAmt * Math.sin(t * 1.5 + b.sway) * (b.box * 0.05);

      // Small upward settle as the pop eases out.
      var rise = (1 - easeOutCubic(p)) * b.box * 0.45;

      var x = heartCx + (b.x - heartCx) * breathe + sway;
      var y = heartCy + (b.y - heartCy) * breathe - rise;

      drawSprite((b.soft ? sprites.soft : sprites.crisp)[b.idx],
                 x, y, b.box * scale, b.rot + sway * 0.012, alpha);
    }
  }

  // Sparkles start once the bloom is 45% through, max 9 alive at a time.
  function drawSparkles(t, dt) {
    var canSpawn = t > TL.bloomT0 + TL.bloomSpan * 0.45;
    if (canSpawn && sparkles.length < 9 && Math.random() < 0.5) {
      var host = blossoms[Math.random() * blossoms.length | 0];
      if (host) {
        sparkles.push({
          x: host.x, y: host.y,
          size: rand(0.6, 1.3) * (Math.min(W, H) * 0.05),
          age: 0,
          life: rand(0.7, 1.2),
          rot: rand(0, 6.28)
        });
      }
    }

    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    for (var i = sparkles.length - 1; i >= 0; i--) {
      var s = sparkles[i];
      s.age += dt;
      var p = s.age / s.life;
      if (p >= 1) { sparkles.splice(i, 1); continue; }
      var a = Math.sin(p * Math.PI);          // fade in then out
      drawSprite(sparkleSprite, s.x, s.y,
                 s.size * (0.6 + 0.4 * a), s.rot + p * 1.2, a);
    }
    ctx.restore();
  }

  /* ------------------------------------------------------------------
   * 8. FALLING PETALS
   * ---------------------------------------------------------------- */

  function spawnPetal() {
    var host = blossoms[Math.random() * blossoms.length | 0];
    if (!host) return;
    falling.push({
      x: host.x + rand(-8, 8),
      y: host.y + rand(-8, 8),
      vy: rand(14, 30),                // 0xe .. 0x1e, px/s downward
      vx: rand(-8, 8),
      sway: rand(0.6, 1.4),            // horizontal wobble frequency
      phase: rand(0, 6.28),
      box: host.box * rand(0.34, 0.6), // detached petals are smaller
      idx: host.idx,
      rot: rand(0, 6.28),
      vrot: rand(-1.4, 1.4),
      age: 0,
      land: groundY + rand(-6, H * 0.05)   // per-petal landing line
    });
  }

  function drawFalling(t, dt) {
    for (var i = falling.length - 1; i >= 0; i--) {
      var p = falling[i];
      p.age += dt;
      p.vy += 8 * dt;                                        // gravity
      p.x += (p.vx + Math.sin(t * p.sway + p.phase) * 16) * dt;  // 16px sway
      p.y += p.vy * dt;
      p.rot += p.vrot * dt;

      if (p.y >= p.land) {
        settled.push({
          x: clamp(p.x, 6, W - 6),
          y: p.land,
          box: p.box,
          idx: p.idx,
          rot: p.rot,
          a: rand(0.7, 0.95)
        });
        if (settled.length > 90) settled.shift();   // cap the ground litter
        falling.splice(i, 1);
        continue;
      }

      var fadeIn = p.age < 0.3 ? p.age / 0.3 : 1;
      drawSprite(sprites.crisp[p.idx], p.x, p.y, p.box, p.rot, fadeIn);
    }
  }

  function drawSettled() {
    for (var i = 0; i < settled.length; i++) {
      var s = settled[i];
      drawSprite(sprites.crisp[s.idx], s.x, s.y, s.box, s.rot, s.a);
    }
  }

  /* ------------------------------------------------------------------
   * DOM CAPTION TOGGLES
   * ---------------------------------------------------------------- */

  function showWish(on) { if (wishEl) wishEl.classList.toggle(IN_CLASS, on); }
  function showTap(on) { if (tapEl) tapEl.classList.toggle(IN_CLASS, on); }

  /* ------------------------------------------------------------------
   * 9. MAIN LOOP
   * ---------------------------------------------------------------- */

  function tick(now) {
    if (!running) return;

    if (!startMs) { startMs = now; lastMs = now; }
    var t = (now - startMs) / 1000;               // seconds on the timeline
    var dt = Math.min(0.05, (now - lastMs) / 1000); // dt capped at 50ms
    lastMs = now;

    var bloomP = sat((t - TL.bloomT0) / TL.bloomSpan);

    drawBackground();
    drawGodRays(t, bloomP);
    drawBloomGlow(t);
    drawBokeh(t, dt);
    drawAmbient(t, dt, false);      // back layer
    drawBranches(t);
    drawBlossoms(t);
    drawSparkles(t, dt);

    // Two petals released every 150ms once past petalT0.
    if (t > TL.petalT0 && now - lastPetalMs > 150) {
      spawnPetal();
      spawnPetal();
      lastPetalMs = now;
    }

    drawFalling(t, dt);
    drawSettled();
    drawAmbient(t, dt, true);       // front layer

    showWish(t >= TL.noteStart);
    if (t >= 3) showTap(true);      // "tap to continue" hint at 3s
    if (!finished && t >= TL.done) finished = true;

    // NOTE: the loop keeps running after `done` — it is only a flag.
    // Nothing cancels the rAF; stop() is called externally when the
    // scene changes.
    rafId = requestAnimationFrame(tick);
  }

  /* ------------------------------------------------------------------
   * SIZING / DPR
   * ---------------------------------------------------------------- */

  function layout() {
    if (!canvas) return;
    dpr = Math.min(window.devicePixelRatio || 1, 2);   // capped at 2x
    W = canvas.clientWidth;
    H = canvas.clientHeight;
    if (!W || !H) return;
    canvas.width = Math.round(W * dpr);
    canvas.height = Math.round(H * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);            // draw in CSS pixels
    bakeAll();
    build();
    if (prefersReducedMotion()) renderStatic();
  }

  var resizePending = 0;
  function onResize() {
    if (!running) return;
    if (resizePending) return;
    resizePending = requestAnimationFrame(function () {
      resizePending = 0;
      layout();
    });
  }

  // Single frame showing the finished scene, for prefers-reduced-motion.
  // t = 99 is simply "far past the end of the timeline".
  function renderStatic() {
    build();
    drawBackground();
    drawGodRays(0, 1);
    drawBloomGlow(TL.done);
    drawBokeh(0, 0);
    drawAmbient(99, 0, false);
    drawBranches(99);
    drawBlossoms(99);

    // Scatter 40 fallen petals on the ground.
    for (var i = 0; i < 40; i++) {
      var b = blossoms[Math.random() * blossoms.length | 0];
      if (!b) continue;
      settled.push({
        x: clamp(b.x + rand(-W * 0.3, W * 0.3), 6, W - 6),
        y: groundY + rand(-6, H * 0.05),
        box: b.box * 0.5,
        idx: b.idx,
        rot: rand(0, 6.28),
        a: 0.85
      });
    }
    drawSettled();
    drawAmbient(99, 0, true);
    showWish(true);
    showTap(true);
    finished = true;
  }

  /* ------------------------------------------------------------------
   * PUBLIC API
   * ---------------------------------------------------------------- */

  function ensure() {
    if (wired) return true;
    if (!canvas) return false;
    ctx = canvas.getContext('2d');
    if (!ctx) return false;
    window.addEventListener('resize', onResize);
    wired = true;
    return true;
  }

  function start() {
    if (!ensure()) return;
    stop();
    running = true;
    startMs = 0;
    lastMs = 0;
    lastPetalMs = 0;
    finished = false;
    showWish(false);
    showTap(false);
    layout();
    if (prefersReducedMotion()) { running = false; return; }
    if (!rafId) rafId = requestAnimationFrame(tick);
  }

  function renderAt(t) {
    if (!ensure()) return;
    stop();
    layout();
    var bloomP = sat((t - TL.bloomT0) / TL.bloomSpan);
    drawBackground();
    drawGodRays(t, bloomP);
    drawBloomGlow(t);
    drawBokeh(t, 0);
    drawAmbient(t, 0, false);
    drawBranches(t);
    drawBlossoms(t);
    drawSparkles(t, 0);
    drawFalling(t, 0);
    drawSettled();
    drawAmbient(t, 0, true);
    showWish(t >= TL.noteStart);
    showTap(t >= 3);
  }

  function stop() {
    running = false;
    if (rafId) { cancelAnimationFrame(rafId); rafId = 0; }
  }

  return {
    start: start,
    stop: stop,
    resize: layout,
    renderAt: renderAt,
    renderStatic: renderStatic,
    isRunning: function () { return running; }
  };
}

/* =====================================================================
 * TRANSCRIPTION NOTES
 * ---------------------------------------------------------------------
 * Nothing in the ALGORITHM was guessed — every constant above was read
 * directly out of the obfuscated source. Two structural changes were made
 * to satisfy the "no global collisions / pass a canvas in" requirement:
 *
 *   1. The original grabbed its DOM by id inside the IIFE:
 *        canvas  -> #om-bday-tree-canvas
 *        wishEl  -> #om-bday-tree-wish
 *        tapEl   -> #om-bday-tree-tap
 *      Here they are constructor arguments / opts instead.
 *
 *   2. `opts.reducedMotion` was added as an override for the
 *      matchMedia('(prefers-reduced-motion: reduce)') check. Pass nothing
 *      and behaviour is identical to the original.
 *
 * The original also exported `isRunning()`; it is kept.
 *
 * One genuine quirk worth flagging (faithfully reproduced, not a bug in
 * this transcription): in the animation loop nothing cancels the rAF when
 * `t >= TL.done` — `finished` is only a flag, and the host page calls
 * stop() when it switches scenes.
 * ===================================================================== */

  // The tree is a canvas engine, not CSS — it has to be started when its
  // scene opens and stopped when it closes, or its rAF keeps running.
  let bmTree = null;
  function startTree(){
    const cv = document.getElementById('treeCanvas');
    if(!cv || typeof createBlossomTree !== 'function') return;
    if(!bmTree){
      bmTree = createBlossomTree(cv, {
        wishEl: document.getElementById('treeWish'),
        tapEl: document.getElementById('treeTap')
      });
    }
    bmTree.start();
  }

  // Scene-scoped timers, flushed on every transition so a finale that is left
  // early cannot keep emitting into the next scene.
  const sceneTimers = [];

  let advancing = false;
  function toStep(n, force){
    if(advancing && !force) return;
    advancing = true;
    setTimeout(() => { advancing = false; }, 450);
    sceneTimers.forEach(t => { clearTimeout(t); clearInterval(t); });
    sceneTimers.length = 0;
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
    if(n === 'title'){
      // lands as the card settles out of its .5s stepIn
      sceneTimers.push(setTimeout(() => {
        fxBurstMid(FX.w / 2, FX.h * 0.40);
        try { navigator.vibrate && navigator.vibrate([16, 60, 24]); } catch(e){}
      }, 500));
    }
    if(n === 'tree') startTree();
    else if(bmTree) bmTree.stop();
    if(n === 'balloons') renderPopBalloons();
    if(n === 'photos') renderPhotos();
    if(n === 'closing') confettiBurst();
    window.scrollTo(0,0);
  }

  // Synthesized with the Web Audio API rather than shipped audio files —
  // the plugin has no audio assets, and a couple of tasteful oscillator
  // tones cover the interaction sounds without adding binary asset
  // management or licensing questions.
  let audioCtx = null;
  let soundOn = true;
  try { soundOn = localStorage.getItem('bm_sound_off') !== '1'; } catch(e){}

  function updateSoundIcon(){
    document.getElementById('soundToggle').textContent = soundOn ? '🔊' : '🔇';
  }
  updateSoundIcon();

  function toggleSound(){
    soundOn = !soundOn;
    try { localStorage.setItem('bm_sound_off', soundOn ? '0' : '1'); } catch(e){}
    updateSoundIcon();
    if(musicGain) musicGain.gain.setTargetAtTime(soundOn ? MUSIC_VOLUME : 0, audioCtx.currentTime, .3);
  }

  function initAudio(){
    if(!audioCtx){
      try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e){}
    }
    // Safari/iOS in particular can leave a freshly-created context
    // 'suspended' even inside a click handler — resume() is safe to call
    // unconditionally and is a no-op once the context is already running.
    if(audioCtx && audioCtx.state === 'suspended'){
      audioCtx.resume().catch(() => {});
    }
  }

  function playTone(freq, duration, type, vol){
    if(!soundOn || !audioCtx) return;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = type;
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(vol, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
    osc.connect(gain).connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + duration);
  }

  function playPop(){
    playTone(600, .15, 'triangle', .2);
    setTimeout(() => playTone(900, .1, 'triangle', .15), 40);
  }

  function playChime(){
    [523, 659, 784].forEach((f, i) => setTimeout(() => playTone(f, .4, 'sine', .12), i * 90));
  }

  // A soft music-box rendition of "Happy Birthday" looping under the whole
  // experience — actual melody notes rather than an abstract ambient pad,
  // since generic drone tones didn't read as "birthday music" at all.
  // Runs independently of step navigation (nothing here is tied to .step
  // elements) so it keeps looping across every screen until sound is muted.
  const MUSIC_VOLUME = 0.09;
  const BEAT_SEC = 0.42;
  const HAPPY_BIRTHDAY_MELODY = [
    [392.00,0.5],[392.00,0.5],[440.00,1],[392.00,1],[523.25,1],[493.88,2],
    [392.00,0.5],[392.00,0.5],[440.00,1],[392.00,1],[587.33,1],[523.25,2],
    [392.00,0.5],[392.00,0.5],[783.99,1],[659.25,1],[523.25,1],[493.88,1],[440.00,2],
    [698.46,0.5],[698.46,0.5],[659.25,1],[523.25,1],[587.33,1],[523.25,2],
  ];
  let musicGain = null;
  let musicStarted = false;

  function scheduleMelodyNote(freq, startTime, beats){
    if(!audioCtx || !musicGain) return;
    const dur = beats * BEAT_SEC;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'triangle';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0, startTime);
    gain.gain.linearRampToValueAtTime(1, startTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, startTime + dur * 0.9);
    osc.connect(gain).connect(musicGain);
    osc.start(startTime);
    osc.stop(startTime + dur);
  }

  function playMelodyLoop(){
    if(!audioCtx) return;
    let t = audioCtx.currentTime + 0.1;
    let totalBeats = 0;
    HAPPY_BIRTHDAY_MELODY.forEach(([freq, beats]) => {
      scheduleMelodyNote(freq, t, beats);
      t += beats * BEAT_SEC;
      totalBeats += beats;
    });
    setTimeout(playMelodyLoop, (totalBeats * BEAT_SEC + 1.6) * 1000);
  }

  function startBackgroundMusic(){
    if(musicStarted || !audioCtx) return;
    musicStarted = true;
    musicGain = audioCtx.createGain();
    musicGain.gain.value = soundOn ? MUSIC_VOLUME : 0;
    musicGain.connect(audioCtx.destination);
    playMelodyLoop();
  }

  function handleUnwrap(){
    initAudio();
    startBackgroundMusic();
    playChime();
    if(LOCK_SECONDS > 0){
      toStep('locked');
      startLockCountdown();
    } else {
      toStep('teaser');
    }
  }

  function startLockCountdown(){
    let remaining = LOCK_SECONDS;
    const paint = () => {
      const h = Math.floor(remaining / 3600);
      const m = Math.floor((remaining % 3600) / 60);
      const s = remaining % 60;
      document.getElementById('countH').textContent = String(h).padStart(2,'0');
      document.getElementById('countM').textContent = String(m).padStart(2,'0');
      document.getElementById('countS').textContent = String(s).padStart(2,'0');
    };
    paint();
    const timer = setInterval(() => {
      remaining--;
      if(remaining <= 0){
        clearInterval(timer);
        fxBurstMid(FX.w / 2, FX.h * 0.35);
        toStep('teaser');
        return;
      }
      paint();
    }, 1000);
  }

  function goAfterBalloons(){
    toStep(PHOTOS.length ? 'photos' : 'envelope');
  }

  function renderPhotos(){
    const track = document.getElementById('photoCarousel');
    const dots = document.getElementById('photoDots');
    if(track.dataset.rendered) return;
    track.dataset.rendered = '1';
    PHOTOS.forEach((photo,i)=>{
      const item = document.createElement('div');
      item.className = 'photo-carousel-item';
      const capHtml = photo.caption ? `<div class="cap">${photo.caption}</div>` : '';
      item.innerHTML = `<img src="${photo.url}" alt="">${capHtml}`;
      track.appendChild(item);
      const dot = document.createElement('span');
      if(i === 0) dot.classList.add('on');
      dots.appendChild(dot);
    });
    if(PHOTOS.length <= 1){
      const hint = document.getElementById('swipeHint');
      if(hint) hint.style.display = 'none';
      return;
    }
    const dotEls = dots.children;
    let hintDismissed = false;
    track.addEventListener('scroll', () => {
      const idx = Math.round(track.scrollLeft / (track.firstElementChild.offsetWidth + 16));
      Array.from(dotEls).forEach((d,i)=>d.classList.toggle('on', i === idx));
      // The hint has done its job once they've actually swiped once —
      // leaving it animating forever would be more distracting than helpful.
      if(!hintDismissed){
        hintDismissed = true;
        const hint = document.getElementById('swipeHint');
        if(hint) hint.style.opacity = '0';
      }
    }, { passive: true });
  }

  function renderPopBalloons(){
    const field = document.getElementById('popField');
    if(field.dataset.rendered) return;
    field.dataset.rendered = '1';
    field.innerHTML='';
    BALLOONS.forEach((text,idx)=>{
      const d = document.createElement('div');
      d.className='balloon';
      d.style.background = BALLOON_COLORS[idx % BALLOON_COLORS.length];
      d.onclick = () => popBalloon(d, text, idx);
      field.appendChild(d);
    });
  }

  function popBalloon(el, text, idx){
    if(el.classList.contains('popped')) return;
    el.classList.add('popped');
    initAudio();
    playPop();
    const r = el.getBoundingClientRect();
    fxBurst(r.left + r.width / 2, r.top + r.height / 2, fxNarrow() ? 12 : 22);
    const reasons = document.getElementById('reasonsList');
    const card = document.createElement('div');
    card.className='reason-card';
    card.style.borderColor = BALLOON_COLORS[idx % BALLOON_COLORS.length];
    card.innerHTML = `<span class="tag">REASON NO.${reasons.children.length+1}</span><div class="txt">${text}</div>`;
    reasons.appendChild(card);
    if(reasons.children.length >= BALLOONS.length){
      document.getElementById('andMore').style.display='block';
      document.getElementById('keepGoingBtn').style.display='block';
    }
  }

  // Short, cheap haptic patterns — a large part of felt quality on a phone,
  // and a no-op everywhere else.
  function vib(pattern){
    try { if(navigator.vibrate) navigator.vibrate(pattern || 18); } catch(e){}
  }

  // 14 emoji lift off the envelope as it opens.
  function envFloatBurst(){
    const host = document.querySelector('.envelope-wrap');
    if(!host || fxReduced()) return;
    const glyphs = ['💛','✨','🎈','💌','🎂','⭐','💖'];
    for(let i = 0; i < 14; i++){
      const el = document.createElement('div');
      el.className = 'env-float';
      el.style.left = (35 + Math.random() * 30) + '%';
      el.style.fontSize = (0.5 + Math.random() * 0.8).toFixed(2) + 'rem';
      const dur = 2 + Math.random() * 2;
      el.style.animationDuration = dur + 's';
      el.style.animationDelay = (Math.random() * 0.5).toFixed(2) + 's';
      const inner = document.createElement('i');
      inner.textContent = glyphs[Math.random() * glyphs.length | 0];
      inner.style.animationDelay = (Math.random() * 1.4).toFixed(2) + 's';
      el.appendChild(inner);
      host.appendChild(el);
      sceneTimers.push(setTimeout(() => el.remove(), (dur + 1) * 1000));
    }
  }

  function openLetter(){
    const env = document.getElementById('envelope');
    if(env) env.classList.add('is-open');
    vib([30, 40, 80]);
    envFloatBurst();
    initAudio();
    playChime();
    // let the flap actually swing before leaving the scene
    sceneTimers.push(setTimeout(revealLetter, 620));
  }

  function revealLetter(){
    toStep('letter', true);
    const out = document.getElementById('letterMsgOut');
    const sign = document.getElementById('letterSign');
    const cont = document.getElementById('letterContinueBtn');
    out.textContent = '';
    sign.style.display = 'none';
    cont.style.display = 'none';

    // Spread rather than split(''), so astral emoji stay whole instead of
    // being torn into surrogate halves (the reference has this bug).
    const chars = Array.from(String(MESSAGE || ''));
    if(fxReduced()){
      out.textContent = chars.join('');
      sign.style.display = 'block';
      cont.style.display = 'block';
      return;
    }
    // 30ms/char reads best, but our letters run to 500 characters — compress
    // the stagger so the reveal always lands in about 2.6s.
    const per = Math.min(30, 2600 / Math.max(1, chars.length));
    const frag = document.createDocumentFragment();
    chars.forEach((ch, i) => {
      const sp = document.createElement('span');
      sp.className = 'magic-char';
      sp.textContent = ch;
      sp.style.animationDelay = Math.round(i * per) + 'ms';
      frag.appendChild(sp);
    });
    out.appendChild(frag);
    sceneTimers.push(setTimeout(() => {
      sign.style.display = 'block';
      cont.style.display = 'block';
    }, chars.length * per + 620));
  }

  function confettiBurst(){
    initAudio();
    playChime();
    const decor = document.querySelector('.closing-wrap .decor');
    if(decor) decor.classList.add('is-pop');
    fxConfettiRain(4200);
    const bursts = fxReduced() ? 3 : 6;
    for(let i = 0; i < bursts; i++){
      sceneTimers.push(setTimeout(fxFirework, 300 + i * 800));
    }
    sceneTimers.push(setInterval(fxFirework, 2600));
  }


  // Ambient background decoration, independent of step navigation — sits
  // fixed over the whole page so it drifts continuously no matter which
  // .step is active, rather than being rebuilt per-screen.
  (function(){
    fxInit();
    const wrap = document.getElementById('bgBalloons');
    const count = 9;
    for(let i=0;i<count;i++){
      const b = document.createElement('div');
      b.className = 'bg-balloon';
      b.textContent = '🎈';
      b.style.left = Math.round(Math.random()*100) + '%';
      b.style.fontSize = (1.3 + Math.random()*1.5).toFixed(2) + 'rem';
      b.style.opacity = (0.12 + Math.random()*0.16).toFixed(2);
      b.style.animationDuration = (16 + Math.random()*12).toFixed(1) + 's';
      b.style.animationDelay = (-Math.random()*24).toFixed(1) + 's';
      wrap.appendChild(b);
    }
  })();
</script>
</body>
</html>
