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
    /* the film CSS is used verbatim and reads this name */
    --bd-font-display:var(--font-display);
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
/* ===== cupid film — CSS verbatim from the reference ===== */
.om-bday-film-hero {
  position:absolute;
  inset:0;
  z-index:3;
  overflow:hidden
}

.om-bday-film-herobg {
  position:absolute;
  inset:0;
  background:radial-gradient(120% 88% at 50% 24%,#fff8f1 0,#fff6ee 38%,#f9e2d2 74%,#f2c9b8 100%)
}

.om-bday-film-herobg::after {
  content:"";
  position:absolute;
  left:50%;
  top:44%;
  width:min(78vw,720px);
  aspect-ratio:1;
  transform:translate(-50%,-50%);
  background:radial-gradient(circle,rgb(255 190 150 / .55),rgb(255 150 170 / .16) 46%,transparent 70%);
  filter:blur(6px)
}

.om-bday-film-motes {
  position:absolute;
  inset:0;
  pointer-events:none
}

.om-bday-film-mote {
  position:absolute;
  border-radius:50%;
  background:radial-gradient(circle,rgb(255 236 214 / .9),rgb(255 206 180 / .15) 55%,transparent 72%);
  will-change:transform,opacity
}

.om-bday-film-eyebrow {
  position:absolute;
  top:13%;
  left:0;
  right:0;
  text-align:center;
  font-family:var(--bd-font-display);
  font-style:italic;
  font-weight:600;
  font-size:clamp(15px, 3.6vw, 26px);
  letter-spacing:.06em;
  color:#a85069;
  text-shadow:0 1px 10px rgb(255 248 241 / .9);
  opacity:0
}

.om-bday-film-targetwrap {
  position:absolute;
  top:33%;
  left:0;
  right:0;
  display:flex;
  justify-content:center;
  transform:translateY(-50%);
  pointer-events:none
}

.om-bday-film-target {
  position:relative;
  width:clamp(120px,27vw,208px);
  aspect-ratio:100/92;
  will-change:transform;
  transform-origin:50% 60%
}

.om-bday-film-targetheart {
  position:absolute;
  inset:0;
  display:block
}

.om-bday-film-heartglow {
  position:absolute;
  left:50%;
  top:52%;
  width:230%;
  aspect-ratio:1;
  transform:translate(-50%,-50%) scale(1);
  border-radius:50%;
  background:radial-gradient(circle,rgb(255 120 150 / .55),rgb(255 90 130 / .18) 42%,transparent 68%);
  filter:blur(4px);
  pointer-events:none;
  will-change:transform,opacity
}

.om-bday-film-heartsvg {
  width:100%;
  height:100%;
  display:block;
  transform-origin:50% 60%;
  filter:drop-shadow(0 10px 22px rgb(168 15 64 / .34)) drop-shadow(0 3px 6px rgb(120 10 48 / .3));
  will-change:transform
}

.om-bday-film-archery {
  position:absolute;
  top:0;
  left:0;
  width:clamp(100px,18vw,168px);
  cursor:grab;
  -webkit-tap-highlight-color:#fff0;
  touch-action:none;
  will-change:transform
}

.om-bday-film-archery:active {
  cursor:grabbing
}

.om-bday-film-archery:focus-visible {
  outline:0
}

.om-bday-film-archery:focus-visible .om-bday-film-bow {
  filter:drop-shadow(0 0 0 3px rgb(255 255 255 / .85)) drop-shadow(0 10px 18px rgb(120 50 20 / .4))
}

.om-bday-film-bow {
  display:block;
  width:100%;
  height:auto;
  filter:drop-shadow(0 12px 18px rgb(90 40 15 / .3));
  overflow:visible
}

.om-bday-film-aim {
  position:absolute;
  left:50%;
  bottom:32%;
  width:2px;
  height:190%;
  margin-left:-1px;
  transform-origin:bottom center;
  background:linear-gradient(0deg,rgb(255 214 150 / .6) 0,rgb(255 150 170 / .22) 52%,transparent 78%);
  opacity:0;
  pointer-events:none
}

.om-bday-film-arrow {
  position:absolute;
  left:0;
  right:0;
  margin-inline:auto;
  bottom:36%;
  width:17.5%;
  height:auto;
  overflow:visible;
  will-change:transform;
  filter:drop-shadow(0 5px 7px rgb(90 40 15 / .36))
}

.om-bday-film-wings {
  transform-box:fill-box;
  transform-origin:50% 90%
}

.om-bday-film-wingL,.om-bday-film-wingR {
  transform-box:fill-box
}

.om-bday-film-wingL {
  transform-origin:88% 60%;
  animation:om-bday-film-flapL 2.6s ease-in-out infinite
}

.om-bday-film-wingR {
  transform-origin:12% 60%;
  animation:om-bday-film-flapR 2.6s ease-in-out infinite
}

@keyframes om-bday-film-flapL {
  0%,100%{transform:rotate(0)}50%{transform:rotate(-11deg)}
}

@keyframes om-bday-film-flapR {
  0%,100%{transform:rotate(0)}50%{transform:rotate(11deg)}
}

.om-bday-film-hint {
  position:absolute;
  bottom:8.5%;
  left:0;
  right:0;
  text-align:center;
  font-family:var(--bd-font-display);
  font-weight:600;
  font-style:italic;
  font-size:clamp(13px, 3.1vw, 19px);
  letter-spacing:.16em;
  text-transform:uppercase;
  color:#b06a7c;
  opacity:0
}

.om-bday-film-flood {
  position:absolute;
  left:50%;
  top:50%;
  width:140px;
  height:140px;
  margin:-70px 0 0 -70px;
  border-radius:50%;
  background:radial-gradient(circle at 40% 34%,#ff5f86,#d4235c 46%,#a80f43 100%);
  z-index:5;
  opacity:0;
  transform:scale(.001);
  will-change:transform,opacity;
  pointer-events:none
}

.om-bday-film-field {
  position:absolute;
  inset:0;
  z-index:4;
  opacity:0;
  pointer-events:none;
  overflow:hidden;
  background:radial-gradient(120% 100% at 50% 8%,#d5265f 0,#d4235c 42%,#a80f43 78%,#6e0a31 100%);
  color:#fff
}

.om-bday-film-blob {
  position:absolute;
  border-radius:50%;
  filter:blur(46px);
  opacity:0;
  mix-blend-mode:screen;
  will-change:transform,opacity
}

.om-bday-film-blob-1 {
  width:58vmax;
  height:58vmax;
  left:-18vmax;
  top:-16vmax;
  background:radial-gradient(circle,rgb(255 130 160 / .9),transparent 62%)
}

.om-bday-film-blob-2 {
  width:46vmax;
  height:46vmax;
  right:-14vmax;
  top:22vmax;
  background:radial-gradient(circle,rgb(255 90 120 / .75),transparent 64%)
}

.om-bday-film-blob-3 {
  width:52vmax;
  height:52vmax;
  left:24vmax;
  bottom:-22vmax;
  background:radial-gradient(circle,rgb(180 20 70 / .8),transparent 66%)
}

.om-bday-film-fgrid {
  position:absolute;
  inset:-6%;
  background-image:linear-gradient(rgb(255 255 255 / .05) 1px,transparent 1px),linear-gradient(90deg,rgb(255 255 255 / .05) 1px,transparent 1px);
  background-size:clamp(38px,7vw,74px) clamp(38px,7vw,74px);
  will-change:transform
}

.om-bday-film-fvignette {
  position:absolute;
  inset:0;
  background:radial-gradient(128% 96% at 50% 44%,transparent 50%,rgb(70 6 28 / .5) 100%)
}

.om-bday-film-camera {
  position:absolute;
  inset:0;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  gap:clamp(4px,1.2vh,12px);
  padding:17vh 6vw;
  text-align:center;
  will-change:transform
}

.om-bday-film-keyebrow {
  font-family:var(--bd-font-display);
  font-style:italic;
  font-weight:500;
  font-size:clamp(15px, 3.6vw, 27px);
  letter-spacing:.08em;
  color:#ffd7c8;
  opacity:0;
  transform:translateY(10px);
  margin-bottom:clamp(2px,1vh,10px)
}

.om-bday-film-headline {
  font-family:var(--bd-font-display);
  line-height:.92
}

.om-bday-film-hlline {
  display:block;
  font-size:clamp(52px, 15vw, 168px);
  letter-spacing:-.02em
}

.om-bday-film-mask {
  display:inline-block;
  overflow:hidden;
  padding:.3em .14em .22em;
  margin:-.3em -.14em -.22em
}

.om-bday-film-hlword {
  display:inline-block;
  font-weight:900;
  font-size:1em;
  color:#fff6f1;
  text-shadow:0 6px 34px rgb(60 4 24 / .34),0 2px 3px rgb(90 8 36 / .42)
}

.om-bday-film-hlch {
  display:inline-block;
  will-change:transform
}

.om-bday-film-uline {
  width:clamp(150px,40vw,380px);
  height:auto;
  margin-top:clamp(4px,1.4vh,14px);
  color:#ffcf6a;
  filter:drop-shadow(0 2px 8px rgb(255 180 90 / .5))
}

.om-bday-film-ksub {
  margin-top:clamp(12px,2.4vh,26px);
  font-family:var(--bd-font-display);
  font-weight:500;
  font-style:italic;
  font-size:clamp(14px, 3.4vw, 24px);
  letter-spacing:.05em;
  color:#ffdfd2;
  opacity:0;
  transform:translateY(10px)
}

.om-bday-film-bar {
  position:absolute;
  left:0;
  right:0;
  height:16vh;
  background:#12040b;
  z-index:3;
  will-change:transform;
  pointer-events:none
}

.om-bday-film-bar-top {
  top:0
}

.om-bday-film-bar-bot {
  bottom:0
}

.om-bday-film-bloom {
  position:absolute;
  left:50%;
  top:50%;
  width:60px;
  height:60px;
  margin:-30px 0 0 -30px;
  border-radius:50%;
  background:radial-gradient(circle,#fff 0,#fff2d6 30%,rgb(255 214 150 / .85) 55%,#fff0 74%);
  z-index:8;
  opacity:0;
  transform:scale(.001);
  will-change:transform,opacity;
  pointer-events:none
}

.om-bday-film-burst {
  position:absolute;
  z-index:4;
  pointer-events:none
}

@media (prefers-reduced-motion:reduce) >> .om-bday-film-bloom,.om-bday-film-field,.om-bday-film-flood,.om-bday-film-hero {
  display:none!important
}

@media (max-height:600px) >> .om-bday-film-bar {
  height:11vh
}

@media (max-height:600px) >> .om-bday-film-camera {
  padding:12vh 6vw
}

@media (max-height:600px) >> .om-bday-film-hlline {
  font-size:clamp(40px, 11vw, 118px)
}
/* ===== end film CSS ===== */
  /* The film plays inside the tree scene, above the canvas. While it runs the
     tree's own copy must stay hidden, or the headline shows twice. */
  .tree-wrap.film-playing .tree-copy,
  .tree-wrap.film-playing .tree-tap{ opacity:0 !important; }
  .om-bday-film-hero{ z-index:6; }
  .tree-wrap > .om-bday-film-flood,
  .tree-wrap > .om-bday-film-field,
  .tree-wrap > .om-bday-film-bloom,
  .tree-wrap > .om-bday-film-camera{ z-index:7; }
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
    <div class="tree-wrap" id="treeWrap" onclick="treeSceneTap()">
<section class="om-bday-film-hero" id="om-bday-film-hero" aria-label="Draw the bow to open the card"><div class="om-bday-film-herobg" aria-hidden="true"></div><div class="om-bday-film-motes" id="om-bday-film-motes" aria-hidden="true"></div><p class="om-bday-film-eyebrow" id="om-bday-film-eyebrow">a little something, for you</p><div class="om-bday-film-targetwrap" aria-hidden="true"><div class="om-bday-film-target" id="om-bday-film-target"> <span class="om-bday-film-heartglow" aria-hidden="true"></span> <span class="om-bday-film-targetheart" id="om-bday-film-targetheart"> <svg class="om-bday-film-heartsvg" viewBox="0 0 100 92" aria-hidden="true"> <defs> <radialGradient id="omf-hg" cx="38%" cy="30%" r="80%"> <stop offset="0%" stop-color="#ffd9e4"/> <stop offset="42%" stop-color="#ff6f97"/> <stop offset="82%" stop-color="#d81e57"/> <stop offset="100%" stop-color="#9d0f3e"/> </radialGradient> <linearGradient id="omf-hsheen" x1="0" y1="0" x2="0" y2="1"> <stop offset="0%" stop-color="rgba(255,255,255,.85)"/> <stop offset="34%" stop-color="rgba(255,255,255,0)"/> </linearGradient> </defs> <path d="M50 86.5C26 68 10.5 53.6 10.5 34.6 10.5 20.4 21 11 33.2 11c8.6 0 14.2 4.7 16.8 11.4C52.6 15.7 58.2 11 66.8 11 79 11 89.5 20.4 89.5 34.6 89.5 53.6 74 68 50 86.5Z" fill="url(#omf-hg)"/> <path d="M50 86.5C26 68 10.5 53.6 10.5 34.6 10.5 20.4 21 11 33.2 11c8.6 0 14.2 4.7 16.8 11.4C52.6 15.7 58.2 11 66.8 11 79 11 89.5 20.4 89.5 34.6 89.5 53.6 74 68 50 86.5Z" fill="url(#omf-hsheen)" opacity=".7"/> <ellipse cx="34" cy="30" rx="8.5" ry="5.4" fill="#fff" opacity=".72" style="mix-blend-mode:screen"/> </svg> </span></div></div><div class="om-bday-film-archery" id="om-bday-film-archery" role="button" tabindex="0" aria-label="Draw the bow and release to send the arrow to the heart"><div class="om-bday-film-aim" id="om-bday-film-aim" aria-hidden="true"></div> <svg class="om-bday-film-bow" id="om-bday-film-bow" viewBox="0 0 460 300" aria-hidden="true"> <defs> <linearGradient id="omf-limb" x1="0" y1="0" x2="1" y2="0"> <stop offset="0" stop-color="#4a2a1a"/> <stop offset=".18" stop-color="#6b3f24"/> <stop offset=".5" stop-color="#8a5127"/> <stop offset=".82" stop-color="#6b3f24"/> <stop offset="1" stop-color="#4a2a1a"/> </linearGradient> <linearGradient id="omf-limbHi" x1="0" y1="0" x2="0" y2="1"> <stop offset="0" stop-color="rgba(255,214,160,.8)"/> <stop offset="1" stop-color="rgba(255,214,160,0)"/> </linearGradient> <linearGradient id="omf-grip" x1="0" y1="0" x2="1" y2="0"> <stop offset="0" stop-color="#2a1a10"/> <stop offset=".5" stop-color="#5a3822"/> <stop offset="1" stop-color="#2a1a10"/> </linearGradient> </defs> <path class="om-bday-film-bowlimb" d="M34 96 C 118 168, 168 240, 230 252 C 292 240, 342 168, 426 96" fill="none" stroke="url(#omf-limb)" stroke-width="13" stroke-linecap="round"/> <path d="M34 96 C 118 168, 168 240, 230 252 C 292 240, 342 168, 426 96" fill="none" stroke="url(#omf-limbHi)" stroke-width="3" stroke-linecap="round" opacity=".7"/> <path d="M34 96 C 22 82, 26 70, 40 66" fill="none" stroke="url(#omf-limb)" stroke-width="8" stroke-linecap="round"/> <path d="M426 96 C 438 82, 434 70, 420 66" fill="none" stroke="url(#omf-limb)" stroke-width="8" stroke-linecap="round"/> <rect x="216" y="206" width="28" height="70" rx="9" fill="url(#omf-grip)"/> <path d="M219 220h22 M219 236h22 M219 252h22" stroke="rgba(0,0,0,.35)" stroke-width="2"/> <line class="om-bday-film-str" id="omf-strL" x1="40" y1="70" x2="230" y2="96" stroke="#9a8068" stroke-width="2.2" stroke-linecap="round"/> <line class="om-bday-film-str" id="omf-strR" x1="420" y1="70" x2="230" y2="96" stroke="#9a8068" stroke-width="2.2" stroke-linecap="round"/> <circle id="omf-serving" cx="230" cy="96" r="4.5" fill="#6f5137"/> </svg> <svg class="om-bday-film-arrow" id="om-bday-film-arrow" viewBox="0 0 64 220" aria-hidden="true"> <defs> <linearGradient id="omf-shaft" x1="0" y1="0" x2="1" y2="0"> <stop offset="0" stop-color="#4a2c14"/> <stop offset=".5" stop-color="#8a5a2c"/> <stop offset="1" stop-color="#3e2410"/> </linearGradient> <linearGradient id="omf-gold" x1="0" y1="0" x2="1" y2="1"> <stop offset="0" stop-color="#ffe38c"/> <stop offset=".45" stop-color="#f4a626"/> <stop offset="1" stop-color="#a85f0e"/> </linearGradient> <linearGradient id="omf-feath" x1="0" y1="0" x2="1" y2="1"> <stop offset="0" stop-color="#ff7f9c"/> <stop offset=".5" stop-color="#e6396a"/> <stop offset="1" stop-color="#a8154a"/> </linearGradient> <linearGradient id="omf-wing" x1="0" y1="0" x2="0" y2="1"> <stop offset="0" stop-color="#ffffff"/> <stop offset="1" stop-color="#ffe0c4"/> </linearGradient> </defs> <rect x="29.4" y="30" width="5.2" height="168" rx="2.6" fill="url(#omf-shaft)"/> <g class="om-bday-film-wings"> <path class="om-bday-film-wingL" d="M31 30 C 10 16, 2 20, 4 34 C 12 30, 20 32, 31 40 Z" fill="url(#omf-wing)" stroke="rgba(196,132,58,.72)" stroke-width="1.2"/> <path class="om-bday-film-wingR" d="M33 30 C 54 16, 62 20, 60 34 C 52 30, 44 32, 33 40 Z" fill="url(#omf-wing)" stroke="rgba(196,132,58,.72)" stroke-width="1.2"/> </g> <path d="M32 12 C 30 7, 22 6.5, 21.5 13 C 21 18, 27 22, 32 27 C 37 22, 43 18, 42.5 13 C 42 6.5, 34 7, 32 12 Z" fill="url(#omf-gold)" stroke="#a5701a" stroke-width=".8"/> <ellipse cx="27" cy="13" rx="2.6" ry="1.7" fill="#fff" opacity=".8" style="mix-blend-mode:screen"/> <g class="om-bday-film-fletch"> <path d="M32 150 C 16 156, 10 178, 15 200 C 24 194, 30 184, 32 176 Z" fill="url(#omf-feath)"/> <path d="M32 150 C 48 156, 54 178, 49 200 C 40 194, 34 184, 32 176 Z" fill="url(#omf-feath)" opacity=".92"/> <path d="M28 160l4 3 M26 170l6 3 M25 180l7 3" stroke="rgba(130,12,48,.45)" stroke-width="1"/> <path d="M36 160l-4 3 M38 170l-6 3 M39 180l-7 3" stroke="rgba(130,12,48,.45)" stroke-width="1"/> </g> <path d="M29 200 L32 205 L35 200" fill="none" stroke="#c9a25a" stroke-width="2" stroke-linecap="round"/> <circle id="omf-tip" cx="32" cy="9" r="0.6" fill="none"/> </svg></div><p class="om-bday-film-hint" id="om-bday-film-hint">pull &amp; release</p></section><div class="om-bday-film-flood" id="om-bday-film-flood" aria-hidden="true"></div><div class="om-bday-film-field" id="om-bday-film-field" aria-hidden="true"><div class="om-bday-film-blob om-bday-film-blob-1"></div><div class="om-bday-film-blob om-bday-film-blob-2"></div><div class="om-bday-film-blob om-bday-film-blob-3"></div><div class="om-bday-film-fgrid" id="om-bday-film-fgrid"></div><div class="om-bday-film-fvignette"></div><div class="om-bday-film-camera" id="om-bday-film-camera"><p class="om-bday-film-keyebrow" id="om-bday-film-keyebrow">make a wish&hellip;</p><h2 class="om-bday-film-headline" id="om-bday-film-headline"> <span class="om-bday-film-hlline"><span class="om-bday-film-mask"><span class="om-bday-film-hlword" id="om-bday-film-wline1">Happy</span></span></span> <span class="om-bday-film-hlline"><span class="om-bday-film-mask"><span class="om-bday-film-hlword" id="om-bday-film-wline2">Birthday</span></span></span></h2> <svg class="om-bday-film-uline" id="om-bday-film-uline" viewBox="0 0 300 26" fill="none" aria-hidden="true"> <path class="om-bday-film-uline-path" d="M8 16C56 7 132 4 178 6c30 1 78 5 114 12-40 3-108 4-176 3" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/> </svg><p class="om-bday-film-ksub" id="om-bday-film-ksub">to someone worth celebrating</p></div><div class="om-bday-film-bar om-bday-film-bar-top" id="om-bday-film-bartop"></div><div class="om-bday-film-bar om-bday-film-bar-bot" id="om-bday-film-barbot"></div></div><div class="om-bday-film-bloom" id="om-bday-film-bloom" aria-hidden="true"></div>
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
<?php
// Inlined so the film has no CDN or CSP dependency, matching the reference's
// own reasoning. Never a heredoc — PHP would interpolate the $ sequences.
$bm_gsap_path = BM_PLUGIN_DIR . 'assets/js/gsap.min.js';
if ( is_readable( $bm_gsap_path ) ) {
	echo file_get_contents( $bm_gsap_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}
?>
</script>
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
/* ===== cupid film engine ===== */
/* =============================================================================
 * film-engine.transcribed.js
 * -----------------------------------------------------------------------------
 * Clean, readable, runnable transcription of the ourmoments.live "cupid film"
 * opening (the bow-and-heart scene that precedes the birthday card).
 *
 * Recovered from:
 *   reference/ourmoments-birthday/film-opening-deob.js       (module _0x52f44a)
 *   reference/ourmoments-birthday/film-opening-markup.html
 *   reference/ourmoments-birthday/film-opening-css.txt
 *   reference/ourmoments-birthday/app.deobfuscated.js        (call sites/helpers)
 *
 * Plain browser JS. No imports, no build step. Exposes exactly ONE global:
 *
 *     createCupidFilm(root, opts) -> { start, stop, seek, destroy, duration }
 *
 * `root` is the film's container element (in the original this is
 * #om-bday-scene-tree — the element that holds the .om-bday-film-hero <section>
 * AND its siblings .om-bday-film-flood / .om-bday-film-field / .om-bday-film-bloom,
 * and whose getBoundingClientRect() defines the scene coordinate space).
 *
 * Requires window.gsap (3.12.5 in the original). start() returns false if absent.
 *
 * DOM contract — every id the engine needs (all inside `root`):
 *   #om-bday-film-hero        the interactive stage <section>
 *   #om-bday-film-eyebrow     "a little something, for you"
 *   #om-bday-film-hint        "pull & release"
 *   #om-bday-film-motes       empty div; motes are injected here
 *   #om-bday-film-target      the heart wrapper (moves/squashes)
 *   #om-bday-film-targetheart the heart <span> (pulses)
 *     .om-bday-film-heartglow (inside #om-bday-film-target)
 *   #om-bday-film-aim         the aim beam
 *   #om-bday-film-archery     the DRAG TARGET (role=button, tabindex=0)
 *   #om-bday-film-bow         bow <svg viewBox="0 0 460 300">
 *   #om-bday-film-arrow       arrow <svg viewBox="0 0 64 220">
 *   #omf-strL #omf-strR       the two bowstring <line>s
 *   #omf-serving              the <circle> nocking point
 *   #omf-tip                  zero-radius <circle> at the arrowhead (measure only)
 *   #om-bday-film-flood       magenta flood disc (140x140, margin -70)
 *   #om-bday-film-field       the magenta title field
 *   #om-bday-film-camera      the parallax camera layer
 *   #om-bday-film-fgrid       the parallax grid layer
 *   #om-bday-film-keyebrow    "make a wish..."
 *   #om-bday-film-wline1      "Happy"     (split into chars)
 *   #om-bday-film-wline2      "Birthday"  (split into chars)
 *   #om-bday-film-uline       underline <svg>, holds .om-bday-film-uline-path
 *   #om-bday-film-ksub        "to someone worth celebrating"
 *   #om-bday-film-bartop      letterbox bar (top)
 *   #om-bday-film-barbot      letterbox bar (bottom)
 *   #om-bday-film-bloom       white bloom disc (60x60, margin -30)
 *   .om-bday-film-blob        x3, inside #om-bday-film-field
 *
 * CSS contract: #om-bday-film-archery MUST have `touch-action:none` or the
 * pointermove drag is stolen by the browser's scroll gesture on touch.
 * ============================================================================= */

/* eslint-disable no-var */
var createCupidFilm = (function () {
  'use strict';

  /* ===========================================================================
   * SECTION 1 — RECOVERED CONSTANTS
   * Every magic number from the original, named, with what it controls.
   * ========================================================================= */

  // ---- Geometry: SVG viewBox intrinsics (must match film-opening-markup.html)
  var BOW_VB_W = 460;          // 0x1cc — bow <svg viewBox="0 0 460 300"> width.
                               //         bowScale = bowRect.width / 460.
  var BOW_GRIP_Y_FRAC = 240 / 300;  // 0xf0/0x12c — grip centre as a fraction of
                               //         bow height. This is the ROTATION PIVOT.
  var ARROW_NOCK_Y_FRAC = 205 / 220;// 0xcd/0xdc — the nock notch as a fraction of
                               //         arrow height (viewBox 0 0 64 220).
  var NOCK_REST_Y = 96;        // 0x60 — bowstring y2 / serving cy at full rest,
                               //         in bow viewBox units. Matches the markup.

  // ---- Anchors: where the bow and the heart sit, as fractions of the scene box
  var ARCHERY_ANCHOR_X = 0.24; // bow grip x = sceneW * 0.24
  var ARCHERY_ANCHOR_Y = 0.76; // bow grip y = sceneH * 0.76
  var TARGET_ANCHOR_X = 0.50;  // heart x = sceneW * 0.50   (CSS: targetwrap centred)
  var TARGET_ANCHOR_Y = 0.33;  // heart y = sceneH * 0.33   (CSS: .targetwrap top:33%)
                               // The DRAW AXIS is atan2 between these two points.

  // ---- Draw mechanic
  var MAX_DRAW_PX_DEFAULT = 120;   // 0x78 — placeholder until first layout()
  var MAX_DRAW_BOW_FRAC = 0.72;    // maxDraw candidate: bowRect.height * 0.72
  var MAX_DRAW_SCENE_FRAC = 0.16;  // maxDraw candidate: sceneH * 0.16
  var MAX_DRAW_HARD_CAP = 132;     // 0x84 — absolute ceiling, px
  var RELEASE_THRESHOLD = 0.26;    // release fires only if draw > maxDraw * 0.26
  var AIM_MAX_OPACITY = 0.55;      // aim beam opacity = 0.55 * (draw / maxDraw)

  // ---- Flood / bloom disc radii (from CSS; these are the scale divisors)
  var FLOOD_RADIUS = 70;   // 0x46 — .om-bday-film-flood is 140x140, margin -70
  var FLOOD_OVERSHOOT = 1.12;  // floodScale = cornerDist * 1.12 / 70
  var FLOOD_START_SCALE = 0.02;
  var BLOOM_RADIUS = 30;   // 0x1e — .om-bday-film-bloom is 60x60, margin -30
  var BLOOM_OVERSHOOT = 1.20;  // bloomScale = halfDiagonal * 1.2 / 30
  var BLOOM_START_SCALE = 0.02;

  // ---- Heart fall
  var FALL_SCENE_FRAC = 0.26;      // fall distance candidate: sceneH * 0.26
  var FALL_TARGET_BOTTOM_PAD = 0.4;// ...clamped so the heart's lower 40% clears the
                                   //    bottom edge: sceneH - targetY - tH*0.4

  // ---- Headline char split (3D flip-up)
  var CHAR_PERSPECTIVE = 620;      // 0x26c
  var CHAR_START_YPCT = 135;       // 0x87
  var CHAR_START_ROTX = -82;       // -0x52
  var CHAR_STAGGER = 0.033;

  // ---- Letterbox bars
  var BAR_OFFSCREEN_PCT = 100;     // 0x64 — top bar sits at -100%, bottom at +100%

  // ---- Motes (the idle/attract dust)
  var MOTE_COUNT = 12;             // 0xc
  var MOTE_SIZE_MIN = 4,   MOTE_SIZE_MAX = 12;    // px
  var MOTE_LEFT_MIN = 4,   MOTE_LEFT_MAX = 96;    // %   (0x4 .. 0x60)
  var MOTE_TOP_MIN = 10,   MOTE_TOP_MAX = 96;     // %   (0xa .. 0x60)
  var MOTE_OPACITY_MIN = 0.25, MOTE_OPACITY_MAX = 0.7;
  var MOTE_RISE_MIN = 40,  MOTE_RISE_MAX = 140;   // px  (0x28 .. 0x8c), negated
  var MOTE_DRIFT = 30;                            // px  (+/- 0x1e)
  var MOTE_DUR_MIN = 7,    MOTE_DUR_MAX = 14;     // s   (0x7 .. 0xe)
  var MOTE_DELAY_MAX = 8;                         // s, applied as a NEGATIVE delay
  var MOTE_FLICKER_MIN = 0.1, MOTE_FLICKER_MAX = 0.5;
  var MOTE_FLICKER_DUR_MIN = 2.5, MOTE_FLICKER_DUR_MAX = 5;

  // ---- Impact burst
  var BURST_COUNT = 12;            // 0xc total
  var BURST_HEART_COUNT = 8;       // first 8 are hearts, last 4 are white sparks
  var BURST_ORIGIN_Y_FRAC = 0.42;  // burst origin = target top + height * 0.42
  var BURST_HEART_SIZE_MIN = 12, BURST_HEART_SIZE_MAX = 22;  // 0xc .. 0x16
  var BURST_SPARK_SIZE_MIN = 4,  BURST_SPARK_SIZE_MAX = 8;   // 0x4 .. 0x8
  var BURST_HEART_DIST_MIN = 70, BURST_HEART_DIST_MAX = 190; // 0x46 .. 0xbe
  var BURST_SPARK_DIST_MIN = 40, BURST_SPARK_DIST_MAX = 120; // 0x28 .. 0x78
  var BURST_LIFT_MIN = 10, BURST_LIFT_MAX = 50;              // 0xa .. 0x32
  var BURST_SPIN = 120;                                      // +/- 0x78 deg
  var BURST_COLORS = ['#ff6f97', '#ffb14e', '#ff8fae', '#ffd36a', '#e23b67'];

  // ---- Timing
  var IDLE_DEMO_MS = 6500;         // 0x1964 — if the user does nothing this long,
                                   //          the bow draws and fires itself.
  var AUTO_DRAW_FRAC = 0.94;       // the self-demo pulls to maxDraw * 0.94
  var AUTO_DRAW_DUR = 0.62;
  var AUTO_DRAW_FIRE_DELAY = 0.16; // delayedCall before fire() once the pull lands
  var SNAPBACK_DUR = 0.55;         // release below threshold -> elastic snap to 0
  var ARROW_FLIGHT_DUR = 0.26;     // fixed. Draw depth changes DISTANCE, not time.
  var FILM_DURATION = 4.00;        // 3.42 (bloom start) + 0.58 (bloom dur)
  var ADVANCE_AFTER_FILM_MS = 8200;// 0x2008 — original then auto-advanced the scene
  var ADVANCE_REDUCED_MS = 4200;   // 0x1068 — reduced-motion path
  var ADVANCE_NO_GSAP_MS = 9000;   // 0x2328 — no-gsap path

  // ---- CSS class names / ids used by the engine
  var CLS_CHAR = 'om-bday-film-hlch';
  var CLS_MOTE = 'om-bday-film-mote';
  var CLS_BURST = 'om-bday-film-burst';
  var SEL_BLOB = '.om-bday-film-blob';

  /* ===========================================================================
   * SECTION 2 — THE TWO HAND-ROLLED REPLACEMENTS FOR PAID GSAP CLUB PLUGINS
   * ========================================================================= */

  var drawnPluginRegistered = false;

  /**
   * VERBATIM replacement for DrawSVGPlugin (GSAP Club).
   * Recovered exactly as written in film-opening-deob.js lines ~978-991.
   *
   * Usage: gsap.to(pathEl, { drawn: 1, duration: 0.45, ease: 'power2.inOut' })
   * with a prior .set(pathEl, { drawn: 0 }).
   *
   * ratio 0 -> dashoffset = len      (path fully hidden)
   * ratio 1 -> dashoffset = 0        (path fully drawn)
   *
   * Note the original's limitation, preserved: it always measures from a full
   * dasharray, so it cannot animate a partial-to-partial range. That is fine
   * for the one place it is used (the underline sweep at t=2.54).
   */
  function registerDrawnPlugin(gsap) {
    if (drawnPluginRegistered) return;
    gsap.registerPlugin({
      name: 'drawn',
      init: function (target, value) {
        var len = target.getTotalLength();
        target.style.strokeDasharray = len;
        this.target = target;
        this.len = len;
        this.value = value;
      },
      render: function (ratio, data) {
        data.target.style.strokeDashoffset = data.len * (1 - data.value * ratio);
      }
    });
    drawnPluginRegistered = true;
  }

  /**
   * VERBATIM replacement for SplitText (GSAP Club), with ONE BUG FIXED.
   *
   * The original was:
   *     var chars = Array.prototype.slice.call(el.textContent);
   *
   * Array.prototype.slice.call() on a string walks it by INDEX, i.e. by UTF-16
   * code unit. Any astral-plane character (emoji above U+FFFF, e.g. a cake or a
   * heart emoji dropped into the headline by a user) is torn into its two
   * surrogate halves, each wrapped in its own <span>. Those halves render as
   * two replacement glyphs and then get staggered apart by the char animation,
   * so the emoji is destroyed twice over.
   *
   * FIX: Array.from() (equivalently [...str]) iterates by code POINT, so a
   * surrogate pair stays one character in one span. This still splits ZWJ
   * sequences and skin-tone modifiers (family emoji, flags) — a full fix needs
   * Intl.Segmenter with granularity 'grapheme', which is not available in every
   * browser the original targeted. Code points are the correct minimum.
   */
  function splitChars(el) {
    var chars = Array.from(el.textContent); // was Array.prototype.slice.call(...)
    el.textContent = '';
    return chars.map(function (ch) {
      var span = document.createElement('span');
      span.className = CLS_CHAR;
      // A plain space in an inline-block span collapses; NBSP holds the gap.
      span.textContent = ch === '\x20' ? '\u00a0' : ch;
      el.appendChild(span);
      return span;
    });
  }

  /* ===========================================================================
   * SECTION 3 — SMALL HELPERS (verbatim from the original)
   * ========================================================================= */

  function rand(min, max) { return min + Math.random() * (max - min); }
  function pick(arr) { return arr[(Math.random() * arr.length) | 0]; }
  function clamp(v, min, max) { return v < min ? min : (v > max ? max : v); }

  function prefersReducedMotion() {
    // app.deobfuscated.js @22135 (_0x2e2278)
    try {
      return typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
      return false;
    }
  }

  /** Rect of `el` expressed relative to an already-taken root rect. */
  function relRect(el, rootRect) {
    var r = el.getBoundingClientRect();
    return {
      left: r.left - rootRect.left,
      top: r.top - rootRect.top,
      width: r.width,
      height: r.height
    };
  }

  /** Inline heart SVG used by the impact burst particles. */
  function heartSvg(color) {
    return '<svg viewBox="0 0 24 22" width="100%" height="100%">' +
      '<path d="M12 20C5.5 15 1.5 11.4 1.5 6.9 1.5 3.6 4 1.5 7 1.5c2 0 3.4 1.1 5 3 ' +
      '1.6-1.9 3-3 5-3 3 0 5.5 2.1 5.5 5.4C23.5 11.4 19.5 15 12 20Z" fill="' + color + '"/></svg>';
  }

  /* ===========================================================================
   * SECTION 4 — THE FACTORY
   * ========================================================================= */

  function createCupidFilm(root, opts) {
    opts = opts || {};

    var gsap = null;

    // --- lifecycle flags (mirror the original's booleans) ---
    var wired = false;      // _0x2c5ab8 — DOM refs resolved + listeners bound
    var started = false;    // _0x39049f
    var enabled = false;    // _0x160828 — false in the reduced-motion / no-gsap path
    var fired = false;      // _0x113579 — the arrow has been released
    var dragging = false;   // _0x3ccd63
    var finished = false;   // _0x179bbc
    var canAdvance = false; // _0x820f35 — exported so the host can gate a tap-to-skip

    // --- element refs ---
    var scene, hero, eyebrow, hint, motesWrap, target, targetHeart, heartGlow,
      aim, archery, bow, arrow, strL, strR, serving, flood, field, camera,
      fgrid, keyebrow, ksub, barTop, barBot, ulinePath, bloom, tip;

    var chars1 = [];    // "Happy"
    var chars2 = [];    // "Birthday"
    var allChars = [];
    var blobs = [];

    // --- layout state ---
    var sceneW = 0, sceneH = 0;
    var bowScale = 1;                         // bowRect.width / 460
    var arrowRestX = 0, arrowRestY = 0;       // arrow transform at zero draw
    var maxDraw = MAX_DRAW_PX_DEFAULT;
    var draw = 0;                             // current draw, px along the axis
    var axisX = 0, axisY = 1;                 // unit draw axis (pointer projection)
    var nock = { val: NOCK_REST_Y };          // tweenable proxy for the bowstring

    // --- timelines / timers ---
    var pulseTl = null;      // heart heartbeat loop
    var fireTl = null;       // THE film timeline
    var idleTimer = 0;
    var introTl = null;

    // --- bound listeners (kept so destroy() can unbind; the original never did) ---
    var onPointerDown, onPointerMove, onPointerEnd, onKeyDown, onResize, onFontsReady;

    /* -------------------------------------------------------------------------
     * 4.1  DOM resolution
     * ---------------------------------------------------------------------- */

    function q(id) {
      return (root && root.querySelector('#' + id)) || document.getElementById(id);
    }

    function resolveRefs() {
      if (wired) return true;

      // `root` doubles as the scene coordinate space (original: #om-bday-scene-tree).
      scene = root;
      hero = q('om-bday-film-hero');
      if (!scene || !hero) return false;

      eyebrow     = q('om-bday-film-eyebrow');
      hint        = q('om-bday-film-hint');
      motesWrap   = q('om-bday-film-motes');
      target      = q('om-bday-film-target');
      targetHeart = q('om-bday-film-targetheart');
      heartGlow   = target.querySelector('.om-bday-film-heartglow');
      aim         = q('om-bday-film-aim');
      archery     = q('om-bday-film-archery');
      bow         = q('om-bday-film-bow');
      arrow       = q('om-bday-film-arrow');
      strL        = q('omf-strL');
      strR        = q('omf-strR');
      serving     = q('omf-serving');
      flood       = q('om-bday-film-flood');
      field       = q('om-bday-film-field');
      camera      = q('om-bday-film-camera');
      fgrid       = q('om-bday-film-fgrid');
      keyebrow    = q('om-bday-film-keyebrow');
      ksub        = q('om-bday-film-ksub');
      barTop      = q('om-bday-film-bartop');
      barBot      = q('om-bday-film-barbot');
      ulinePath   = q('om-bday-film-uline').querySelector('.om-bday-film-uline-path');
      bloom       = q('om-bday-film-bloom');
      tip         = q('omf-tip');

      // The original used the bare global selector '.om-bday-film-blob'.
      // Scoped to `root` here so two films on one page cannot cross-drive.
      blobs = Array.prototype.slice.call(root.querySelectorAll(SEL_BLOB));

      chars1 = splitChars(q('om-bday-film-wline1'));
      chars2 = splitChars(q('om-bday-film-wline2'));
      allChars = chars1.concat(chars2);

      gsap = window.gsap || null;
      if (gsap) {
        registerDrawnPlugin(gsap);
        bindPointer();

        onResize = handleResize;
        window.addEventListener('resize', onResize);

        // Web fonts change the headline metrics, which change the bow/heart
        // boxes, which change the draw axis. Re-layout once they land.
        if (document.fonts && document.fonts.ready) {
          onFontsReady = function () {
            if (started && enabled && !fired) { layout(); setDraw(0); }
          };
          document.fonts.ready.then(onFontsReady);
        }
      }

      wired = true;
      return true;
    }

    /* -------------------------------------------------------------------------
     * 4.2  LAYOUT — every getBoundingClientRect-derived value
     *
     * This positions the bow so its GRIP lands on (sceneW*0.24, sceneH*0.76),
     * rotates it to point at the heart at (sceneW*0.50, sceneH*0.33), parks the
     * arrow's nock notch exactly on the string's serving point, and derives the
     * draw axis and the maximum draw distance.
     * ---------------------------------------------------------------------- */

    function applyNock() {
      // The bowstring is two <line>s meeting at the serving <circle>. Pulling
      // the string just moves that meeting point DOWN in bow viewBox units.
      var v = nock.val;
      strL.setAttribute('y2', v);
      strR.setAttribute('y2', v);
      serving.setAttribute('cy', v);
    }

    function layout() {
      var r = scene.getBoundingClientRect();
      sceneW = r.width;
      sceneH = r.height;

      var ax = sceneW * ARCHERY_ANCHOR_X;   // bow grip target x
      var ay = sceneH * ARCHERY_ANCHOR_Y;   // bow grip target y
      var tx = sceneW * TARGET_ANCHOR_X;    // heart x
      var ty = sceneH * TARGET_ANCHOR_Y;    // heart y

      // NOTE the argument order: atan2(dx, dy), not the usual atan2(dy, dx).
      // The bow's "up" in its own viewBox is -y, so this angle is measured from
      // vertical, which is exactly what gsap `rotation` (degrees, clockwise)
      // wants when applied to a bow whose rest orientation is straight up.
      var angle = Math.atan2(tx - ax, ay - ty);

      // Draw axis = the DOWN-the-shaft direction, i.e. the direction the string
      // travels when pulled. Pointer delta is projected onto it with a dot
      // product in pointermove.
      axisX = -Math.sin(angle);
      axisY = Math.cos(angle);

      nock.val = NOCK_REST_Y;
      applyNock();

      // Neutralise every transform before measuring, or the rects lie.
      gsap.set(archery, { rotation: 0, scale: 1, x: 0, y: 0 });
      archery.style.left = '0px';
      archery.style.top = '0px';
      gsap.set(arrow, { x: 0, y: 0 });

      var aR  = relRect(archery, r);
      var bR  = relRect(bow, r);
      var sR  = relRect(serving, r);
      var arR = relRect(arrow, r);

      bowScale = bR.width / BOW_VB_W;   // px per bow-viewBox unit

      // Pivot = the bow grip, expressed in the archery element's own box.
      var pivotX = bR.left - aR.left + 0.5 * bR.width;
      var pivotY = bR.top  - aR.top  + BOW_GRIP_Y_FRAC * bR.height;

      // Serving (nocking point) centre, in the same box.
      var servX = sR.left - aR.left + 0.5 * sR.width;
      var servY = sR.top  - aR.top  + 0.5 * sR.height;

      // Arrow rest transform: whatever it takes to drop the arrow's NOCK NOTCH
      // (at ARROW_NOCK_Y_FRAC down its box) onto the serving point.
      arrowRestX = servX - (arR.left - aR.left + 0.5 * arR.width);
      arrowRestY = servY - (arR.top  - aR.top  + ARROW_NOCK_Y_FRAC * arR.height);

      // Slide the whole archery element so the pivot lands on the anchor.
      archery.style.left = (ax - pivotX) + 'px';
      archery.style.top  = (ay - pivotY) + 'px';

      gsap.set(archery, {
        transformOrigin: pivotX + 'px ' + pivotY + 'px',
        rotation: angle * 180 / Math.PI
      });
      gsap.set(arrow, { x: arrowRestX, y: arrowRestY });

      // maxDraw = min(bowRect.height * 0.72, sceneH * 0.16, 132)
      maxDraw = Math.min(
        bR.height * MAX_DRAW_BOW_FRAC,
        sceneH * MAX_DRAW_SCENE_FRAC,
        MAX_DRAW_HARD_CAP
      );

      draw = 0;
    }

    /** Apply a draw depth (px along the axis): moves arrow, string and aim beam. */
    function setDraw(d) {
      draw = clamp(d, 0, maxDraw);
      gsap.set(arrow, { x: arrowRestX, y: arrowRestY + draw });
      // draw is in SCREEN px; the string lives in bow viewBox units -> /bowScale.
      nock.val = NOCK_REST_Y + draw / bowScale;
      applyNock();
      gsap.set(aim, { opacity: AIM_MAX_OPACITY * (draw / maxDraw) });
    }

    /* -------------------------------------------------------------------------
     * 4.3  MEASURE — the numbers the fire timeline needs, taken AT RELEASE TIME
     * ---------------------------------------------------------------------- */

    function measure() {
      var r = scene.getBoundingClientRect();
      sceneW = r.width;
      sceneH = r.height;

      // #omf-tip is a zero-radius <circle> at the arrowhead. Its rect is read
      // AFTER the draw transform, so `dist` is the real drawn-tip-to-heart gap.
      var tipR = tip.getBoundingClientRect();
      var tgtR = target.getBoundingClientRect();

      var tipX = tipR.left + tipR.width / 2 - r.left;
      var tipY = tipR.top + tipR.height / 2 - r.top;
      var tgtX = tgtR.left + tgtR.width / 2 - r.left;
      var tgtY = tgtR.top + tgtR.height / 2 - r.top;

      var dist = Math.hypot(tgtX - tipX, tgtY - tipY);

      // How far the struck heart falls. Clamped so it does not leave the scene.
      var fallPx = Math.min(
        sceneH * FALL_SCENE_FRAC,
        sceneH - tgtY - tgtR.height * FALL_TARGET_BOTTOM_PAD
      );

      // The flood erupts from where the heart LANDS, not where it started.
      var restX = tgtX;
      var restY = tgtY + fallPx;

      // Distance from the landing point to the FARTHEST corner of the scene.
      var cornerDist = Math.hypot(
        Math.max(restX, sceneW - restX),
        Math.max(restY, sceneH - restY)
      );
      var halfDiag = Math.hypot(sceneW / 2, sceneH / 2);

      return {
        arrowStartY: arrowRestY + draw,
        arrowFlyY:   arrowRestY + draw - dist,  // travels `dist` in a fixed 0.26s,
                                                // so a deeper draw = faster arrow.
        drawnNock:   NOCK_REST_Y + draw / bowScale,
        fallPx:      fallPx,
        fx:          restX - sceneW / 2,  // flood is centred at 50%/50%; offset it
        fy:          restY - sceneH / 2,  // to the heart's landing point
        floodScale:  cornerDist * FLOOD_OVERSHOOT / FLOOD_RADIUS,
        bloomScale:  halfDiag * BLOOM_OVERSHOOT / BLOOM_RADIUS
      };
    }

    /* -------------------------------------------------------------------------
     * 4.4  IDLE / ATTRACT STATE
     * ---------------------------------------------------------------------- */

    /** 12 drifting dust motes injected into #om-bday-film-motes. */
    function buildMotes() {
      motesWrap.innerHTML = '';
      for (var i = 0; i < MOTE_COUNT; i++) {
        var el = document.createElement('span');
        el.className = CLS_MOTE;
        var size = rand(MOTE_SIZE_MIN, MOTE_SIZE_MAX);
        el.style.width = el.style.height = size + 'px';
        el.style.left = rand(MOTE_LEFT_MIN, MOTE_LEFT_MAX) + '%';
        el.style.top = rand(MOTE_TOP_MIN, MOTE_TOP_MAX) + '%';
        motesWrap.appendChild(el);

        gsap.set(el, { opacity: rand(MOTE_OPACITY_MIN, MOTE_OPACITY_MAX) });

        // Slow rise + sideways drift, yoyoing forever. The NEGATIVE delay is the
        // trick that desynchronises them without a stagger: each mote starts
        // mid-flight rather than all together at t=0.
        gsap.to(el, {
          y: -rand(MOTE_RISE_MIN, MOTE_RISE_MAX),
          x: rand(-MOTE_DRIFT, MOTE_DRIFT),
          duration: rand(MOTE_DUR_MIN, MOTE_DUR_MAX),
          repeat: -1,
          yoyo: true,
          ease: 'sine.inOut',
          delay: -rand(0, MOTE_DELAY_MAX)
        });

        // Independent, slower opacity flicker.
        gsap.to(el, {
          opacity: rand(MOTE_FLICKER_MIN, MOTE_FLICKER_MAX),
          duration: rand(MOTE_FLICKER_DUR_MIN, MOTE_FLICKER_DUR_MAX),
          repeat: -1,
          yoyo: true,
          ease: 'sine.inOut'
        });
      }
    }

    /**
     * The heart's double-thump heartbeat. Runs forever with a 0.5s rest between
     * beats. Started by the intro timeline's onComplete, killed on fire().
     */
    function startHeartPulse() {
      gsap.set(targetHeart, { scale: 1 });
      gsap.set(heartGlow, { scale: 1, opacity: 0.7 });

      pulseTl = gsap.timeline({ repeat: -1, repeatDelay: 0.5 });
      pulseTl
        .to(targetHeart, { scale: 1.07, duration: 0.13, ease: 'power2.out' }, 0)
        .to(heartGlow,   { scale: 1.15, opacity: 0.9, duration: 0.13, ease: 'power2.out' }, 0)
        .to(targetHeart, { scale: 1,    duration: 0.2,  ease: 'power2.in' },    0.13)
        .to(targetHeart, { scale: 1.05, duration: 0.12, ease: 'power2.out' },   0.30)  // 2nd thump
        .to(targetHeart, { scale: 1,    duration: 0.5,  ease: 'power2.inOut' }, 0.42)
        .to(heartGlow,   { scale: 1, opacity: 0.7, duration: 0.7, ease: 'power2.inOut' }, 0.30);
    }

    function stopHeartPulse() {
      if (pulseTl) { pulseTl.kill(); pulseTl = null; }
      gsap.set(targetHeart, { scale: 1 });
    }

    /** The reveal that runs on start(): heart, glow, bow, eyebrow, hint. */
    function playIntro() {
      gsap.set(hero, { autoAlpha: 1 });
      layout();
      setDraw(0);

      gsap.set([eyebrow, hint], { opacity: 0, y: 14 });
      gsap.set(target,   { opacity: 0, y: 10, scaleX: 0.9, scaleY: 0.9 });
      gsap.set(archery,  { opacity: 0, scale: 0.85 });  // keeps layout's rotation
      gsap.set(heartGlow,{ opacity: 0, scale: 1 });
      gsap.set(arrow,    { opacity: 1 });

      introTl = gsap.timeline({ onComplete: startHeartPulse });
      introTl
        .to(target,    { opacity: 1, y: 0, scaleX: 1, scaleY: 1, duration: 0.8, ease: 'power3.out' }, 0.10)
        .to(heartGlow, { opacity: 0.7,                           duration: 0.8, ease: 'power2.out' }, 0.20)
        .to(archery,   { opacity: 1, scale: 1,                   duration: 0.8, ease: 'power3.out' }, 0.28)
        .to(eyebrow,   { opacity: 1, y: 0,                       duration: 0.7, ease: 'power3.out' }, 0.40)
        .to(hint,      { opacity: 1, y: 0,                       duration: 0.7, ease: 'power3.out' }, 0.70);
    }

    /* -------------------------------------------------------------------------
     * 4.5  IMPACT BURST — 8 hearts + 4 white sparks, injected into the hero
     * ---------------------------------------------------------------------- */

    function impactBurst() {
      var tgtR = target.getBoundingClientRect();
      var heroR = hero.getBoundingClientRect();
      var cx = tgtR.left - heroR.left + tgtR.width / 2;
      var cy = tgtR.top - heroR.top + tgtR.height * BURST_ORIGIN_Y_FRAC;

      var frag = document.createDocumentFragment();
      var parts = [];

      for (var i = 0; i < BURST_COUNT; i++) {
        var isHeart = i < BURST_HEART_COUNT;
        var el = document.createElement('span');
        el.className = CLS_BURST;
        var size = isHeart
          ? rand(BURST_HEART_SIZE_MIN, BURST_HEART_SIZE_MAX)
          : rand(BURST_SPARK_SIZE_MIN, BURST_SPARK_SIZE_MAX);

        el.style.cssText =
          'position:absolute;left:' + cx + 'px;top:' + cy + 'px;' +
          'width:' + size + 'px;height:' + size + 'px;' +
          'margin:' + (-size / 2) + 'px 0 0 ' + (-size / 2) + 'px;' +
          'pointer-events:none;z-index:4;';

        if (isHeart) {
          el.innerHTML = heartSvg(pick(BURST_COLORS));
        } else {
          el.style.borderRadius = '50%';
          el.style.background = 'radial-gradient(circle,#fff,rgba(255,210,150,0) 70%)';
        }

        frag.appendChild(el);
        parts.push({ el: el, heart: isHeart });
      }

      hero.appendChild(frag);

      parts.forEach(function (p) {
        // angle in [-PI, 0] => the upper half-circle only; everything sprays up.
        var angle = rand(-Math.PI, 0);
        var dist = p.heart
          ? rand(BURST_HEART_DIST_MIN, BURST_HEART_DIST_MAX)
          : rand(BURST_SPARK_DIST_MIN, BURST_SPARK_DIST_MAX);

        gsap.to(p.el, {
          x: Math.cos(angle) * dist,
          y: Math.sin(angle) * dist - rand(BURST_LIFT_MIN, BURST_LIFT_MAX),
          rotation: rand(-BURST_SPIN, BURST_SPIN),
          scale: p.heart ? rand(0.7, 1.2) : rand(0.4, 1),
          duration: rand(0.7, 1.15),
          ease: 'power2.out'
        });
        gsap.to(p.el, {
          opacity: 0,
          duration: 0.5,
          delay: rand(0.35, 0.6),
          ease: 'power1.in',
          onComplete: function () { p.el.remove(); }
        });
      });
    }

    /* -------------------------------------------------------------------------
     * 4.6  THE FIRE TIMELINE
     *
     * Absolute positions in seconds from release. Total duration = 4.00s
     * (last beat starts at 3.42 and runs 0.58).
     * ---------------------------------------------------------------------- */

    function buildFireTimeline(m) {
      var tl = gsap.timeline({
        paused: true,
        onComplete: function () {
          gsap.set(field, { autoAlpha: 0 });
          finished = true;
          canAdvance = true;

          // HAND-OFF. In the original this was treeEngine.start() — the canvas
          // module that renders the next scene (the wish tree) and adds the
          // 'is-in' class to #om-bday-tree-wish / #om-bday-tree-tap. Then the
          // bloom faded out over 1.15s and a scene auto-advance was queued
          // 8200ms later.
          if (typeof opts.onComplete === 'function') opts.onComplete();
          gsap.to(bloom, { autoAlpha: 0, duration: 1.15, ease: 'power2.out' });
          if (typeof opts.onAdvanceRequest === 'function') {
            opts.onAdvanceRequest(ADVANCE_AFTER_FILM_MS);
          }
        }
      });

      /* ---- t = 0 : hard reset of every layer the film touches ------------- */
      tl.set(target, { y: 0, scaleX: 1, scaleY: 1, opacity: 1 })
        .set(arrow,  { opacity: 1, x: arrowRestX, y: m.arrowStartY, scaleY: 1 })
        .set([flood, bloom], { autoAlpha: 0, scale: 0.001, x: 0, y: 0 })
        .set(flood,  { x: m.fx, y: m.fy })          // move flood to the landing spot
        .set(field,  { autoAlpha: 0 })
        .set(blobs,  { opacity: 0 })
        .set(camera, { scale: 1, yPercent: 0 })
        .set(fgrid,  { xPercent: 0, yPercent: 0 })
        .set(barTop, { yPercent: -BAR_OFFSCREEN_PCT })
        .set(barBot, { yPercent:  BAR_OFFSCREEN_PCT })
        .set(keyebrow, { opacity: 0, y: 12 })
        .set(ksub,     { opacity: 0, y: 12 })
        .set(allChars, {
          transformPerspective: CHAR_PERSPECTIVE,
          transformOrigin: '50% 100%',
          yPercent: CHAR_START_YPCT,
          rotationX: CHAR_START_ROTX
        })
        .set(ulinePath, { drawn: 0 });

      /* ---- 0.00 RELEASE --------------------------------------------------- */
      tl.fromTo(nock,
          { val: m.drawnNock },
          { val: NOCK_REST_Y, duration: 0.5, ease: 'elastic.out(1,0.34)', onUpdate: applyNock },
          0)
        .to(arrow, { y: m.arrowFlyY, duration: ARROW_FLIGHT_DUR, ease: 'power2.in' }, 0)
        .to(arrow, { scaleY: 1.16, duration: 0.14, ease: 'power2.in' }, 0)      // stretch
        .to(arrow, { scaleY: 1,    duration: 0.10, ease: 'power1.out' }, 0.16)  // recover
        .to(aim,   { opacity: 0, duration: 0.18 }, 0)
        .to([eyebrow, hint], { opacity: 0, duration: 0.2, ease: 'power1.out' }, 0);

      /* ---- 0.26 IMPACT ---------------------------------------------------- */
      tl.add(function () {
          // Original called startBgm() here — the music comes in ON the hit.
          if (typeof opts.onImpact === 'function') opts.onImpact();
        }, 0.26)
        .add(impactBurst, 0.26)
        .to(target, { x: 7, y: -9, duration: 0.06, ease: 'power2.out' }, 0.26)  // knock
        .to(target, { x: 0, y: 0,  duration: 0.32, ease: 'power2.out' }, 0.32)
        .to(target, { scale: 1.14, duration: 0.06, ease: 'power2.out' }, 0.26)  // flinch
        .to(target, { scale: 1,    duration: 0.26, ease: 'power2.inOut' }, 0.32)

      /* ---- 0.27 QUIVER (the arrow shaft vibrating in the heart) ----------- */
        .to(arrow, {
          rotation: '+=4', duration: 0.05, yoyo: true, repeat: 4, ease: 'sine.inOut'
        }, 0.27)                                    // 0.05 * 5 legs = ends at 0.52
        .set(arrow, { rotation: 0 }, 0.52)
        .to(arrow, { opacity: 0, duration: 0.16, ease: 'power1.out' }, 0.56);

      /* ---- 0.64 FALL / 0.98 SQUASH / 1.00 FLOOD --------------------------- */
      tl.to(target, {
          y: m.fallPx, scaleX: 0.84, scaleY: 1.3, duration: 0.34, ease: 'power1.in'
        }, 0.64)                                    // stretches as it drops, lands 0.98
        .to(target, {
          scaleX: 1.4, scaleY: 0.6, duration: 0.07, ease: 'power2.out'
        }, 0.98)                                    // pancake on contact
        .set(flood, { autoAlpha: 1 }, 1.00)
        .fromTo(flood,
          { scale: FLOOD_START_SCALE },
          { scale: m.floodScale, duration: 0.34, ease: 'power2.in' },
          1.00)
        .to(target, { opacity: 0, duration: 0.12, ease: 'power1.out' }, 1.06);

      /* ---- 1.32 CROSS-FADE hero -> magenta field -------------------------- */
      tl.set(field, { autoAlpha: 1 }, 1.32)
        .set(hero,  { autoAlpha: 0 }, 1.33)
        .to(blobs,  { opacity: 1, duration: 0.6, ease: 'power2.out' }, 1.34)
        .set(flood, { autoAlpha: 0 }, 1.36);        // flood retired behind the field

      /* ---- 1.38 CAMERA (a slow 2.6s push that runs under everything) ------ */
      tl.fromTo(camera,
          { scale: 1, yPercent: 0 },
          { scale: 1.07, yPercent: -1.3, duration: 2.6, ease: 'none' },
          1.38)
        .fromTo(fgrid,
          { xPercent: 0, yPercent: 0 },
          { xPercent: -1.5, yPercent: -1, duration: 2.6, ease: 'none' },
          1.38);                                    // grid drifts at a different rate
                                                    // => parallax

      /* ---- 1.50 LETTERBOX BARS IN ---------------------------------------- */
      tl.to(barTop, { yPercent: 0, duration: 0.6, ease: 'power2.out' }, 1.50)
        .to(barBot, { yPercent: 0, duration: 0.6, ease: 'power2.out' }, 1.50);

      /* ---- 1.54 -> 2.74 TITLE CARD --------------------------------------- */
      tl.to(keyebrow, { opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }, 1.54)
        .to(chars1, {                                // "Happy"
          yPercent: 0, rotationX: 0, duration: 0.55, ease: 'power3.out', stagger: CHAR_STAGGER
        }, 1.68)
        .to(chars2, {                                // "Birthday"
          yPercent: 0, rotationX: 0, duration: 0.55, ease: 'power3.out', stagger: CHAR_STAGGER
        }, 2.06)
        .to(ulinePath, { drawn: 1, duration: 0.45, ease: 'power2.inOut' }, 2.54)
        .to(ksub, { opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }, 2.74);

      /* ---- 3.32 BARS OUT / 3.42 WHITE BLOOM WIPE (end of film) ------------ */
      tl.to(barTop, { yPercent: -BAR_OFFSCREEN_PCT, duration: 0.5, ease: 'power2.in' }, 3.32)
        .to(barBot, { yPercent:  BAR_OFFSCREEN_PCT, duration: 0.5, ease: 'power2.in' }, 3.32)
        .set(bloom, { autoAlpha: 1 }, 3.42)
        .fromTo(bloom,
          { scale: BLOOM_START_SCALE },
          { scale: m.bloomScale, duration: 0.58, ease: 'power2.in' },
          3.42);                                    // ends at 4.00

      return tl;
    }

    /* -------------------------------------------------------------------------
     * 4.7  DRAG MECHANIC
     * ---------------------------------------------------------------------- */

    var pointerStartX = 0, pointerStartY = 0, drawAtPointerStart = 0;

    function bindPointer() {
      onPointerDown = function (e) {
        if (fired) return;
        dragging = true;
        try { archery.setPointerCapture(e.pointerId); } catch (err) { /* older Safari */ }
        pointerStartX = e.clientX;
        pointerStartY = e.clientY;
        drawAtPointerStart = draw;   // resume from wherever the string already is
        e.preventDefault();
      };

      onPointerMove = function (e) {
        if (!dragging) return;
        // Project the pointer delta onto the unit draw axis (dot product).
        // Movement perpendicular to the shaft contributes nothing.
        var projected = (e.clientX - pointerStartX) * axisX +
                        (e.clientY - pointerStartY) * axisY;
        setDraw(drawAtPointerStart + projected);   // setDraw clamps to [0, maxDraw]
      };

      onPointerEnd = function () {
        if (!dragging) return;
        dragging = false;
        // Release threshold: a shallow tug snaps back instead of firing.
        if (draw > maxDraw * RELEASE_THRESHOLD) fire();
        else snapBack();
      };

      onKeyDown = function (e) {
        if (fired) return;
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          autoDraw();   // keyboard users get the scripted pull-and-fire
        }
      };

      // NOTE: only ONE element listens — #om-bday-film-archery (the bow). Pointer
      // Events only; there is no separate touch* path. This works on touch solely
      // because the CSS gives .om-bday-film-archery `touch-action:none`.
      archery.addEventListener('pointerdown', onPointerDown);
      archery.addEventListener('pointermove', onPointerMove);
      archery.addEventListener('pointerup', onPointerEnd);
      archery.addEventListener('pointercancel', onPointerEnd);
      archery.addEventListener('keydown', onKeyDown);
    }

    /** Under-threshold release: elastic snap back to rest. */
    function snapBack() {
      gsap.to({ d: draw }, {
        d: 0,
        duration: SNAPBACK_DUR,
        ease: 'elastic.out(1,0.4)',
        onUpdate: function () { setDraw(this.targets()[0].d); }
      });
    }

    /** Scripted pull used by the idle demo and by keyboard activation. */
    function autoDraw() {
      if (fired) return;
      gsap.to({ d: draw }, {
        d: maxDraw * AUTO_DRAW_FRAC,
        duration: AUTO_DRAW_DUR,
        ease: 'power2.inOut',
        onUpdate: function () { setDraw(this.targets()[0].d); },
        onComplete: function () { gsap.delayedCall(AUTO_DRAW_FIRE_DELAY, fire); }
      });
    }

    function clearIdleTimer() {
      if (idleTimer) { clearTimeout(idleTimer); idleTimer = 0; }
    }

    /** If the user does nothing for 6.5s, the bow fires itself. */
    function scheduleIdleDemo() {
      clearIdleTimer();
      idleTimer = setTimeout(function () {
        idleTimer = 0;
        if (fired) return;
        if (dragging) { scheduleIdleDemo(); return; }  // mid-drag: wait another 6.5s
        autoDraw();
      }, IDLE_DEMO_MS);
    }

    function fire() {
      if (fired) return;
      fired = true;
      dragging = false;
      clearIdleTimer();
      stopHeartPulse();
      fireTl = buildFireTimeline(measure());
      fireTl.play(0);
    }

    /* -------------------------------------------------------------------------
     * 4.8  RESIZE — rebuild the timeline and re-seek to the same instant
     * ---------------------------------------------------------------------- */

    function handleResize() {
      if (!started || !enabled || !gsap || finished) return;

      if (fired && fireTl) {
        var t = fireTl.time();
        var wasActive = fireTl.isActive();
        // The original did NOT kill the old timeline here — a leak that let a
        // second copy keep running and fire onComplete twice. Killed first.
        fireTl.kill();
        fireTl = buildFireTimeline(measure());
        fireTl.pause(t);
        if (wasActive) fireTl.play(t);
      } else {
        layout();
        setDraw(0);
      }
    }

    /* -------------------------------------------------------------------------
     * 4.9  RESET
     * ---------------------------------------------------------------------- */

    function resetFilm() {
      if (fireTl) { fireTl.kill(); fireTl = null; }
      if (introTl) { introTl.kill(); introTl = null; }
      stopHeartPulse();

      fired = false;
      dragging = false;
      finished = false;
      canAdvance = false;
      clearIdleTimer();

      if (motesWrap) {
        gsap.killTweensOf(motesWrap.querySelectorAll('.' + CLS_MOTE));
        motesWrap.innerHTML = '';
      }

      var bursts = hero.querySelectorAll('.' + CLS_BURST);
      for (var i = 0; i < bursts.length; i++) bursts[i].remove();

      gsap.set([flood, bloom], { autoAlpha: 0, scale: 0.001 });
      gsap.set(field, { autoAlpha: 0 });
      gsap.set(hero,  { autoAlpha: 1 });
      gsap.set(arrow, { opacity: 1, scaleY: 1, rotation: 0 });
      gsap.set(target, { opacity: 1, x: 0, y: 0, scale: 1, scaleX: 1, scaleY: 1 });
    }

    /* -------------------------------------------------------------------------
     * 4.10  PUBLIC API
     * ---------------------------------------------------------------------- */

    function start() {
      if (!resolveRefs()) return false;
      started = true;
      gsap = window.gsap || null;

      // No GSAP at all: the caller must fall back.
      if (!gsap) {
        started = false;
        return false;
      }

      if (prefersReducedMotion()) {
        // Reduced-motion path: skip the whole film, hand off immediately.
        // (The CSS also display:none's .om-bday-film-hero/-field/-flood/-bloom
        //  under prefers-reduced-motion, so nothing is left on screen.)
        enabled = false;
        fired = true;
        finished = true;
        canAdvance = true;
        if (hero) hero.style.display = 'none';
        if (typeof opts.onComplete === 'function') opts.onComplete();
        if (typeof opts.onImpact === 'function') opts.onImpact();  // was startBgm()
        if (typeof opts.onAdvanceRequest === 'function') {
          opts.onAdvanceRequest(ADVANCE_REDUCED_MS);
        }
        return true;
      }

      enabled = true;
      if (hero) hero.style.display = '';
      resetFilm();
      buildMotes();
      playIntro();
      scheduleIdleDemo();
      return true;
    }

    function stop() {
      started = false;
      enabled = false;
      clearIdleTimer();
      if (fireTl) { fireTl.kill(); fireTl = null; }
      if (introTl) { introTl.kill(); introTl = null; }
      if (pulseTl) { pulseTl.kill(); pulseTl = null; }
      fired = false;
      dragging = false;
      finished = false;
      canAdvance = false;
    }

    /**
     * TESTING ADDITION (not in the original).
     * Scrub the film to an absolute time in seconds from release.
     * Building the timeline necessarily arms the film, so this puts the engine
     * into the "fired" state: the idle demo and the drag stop responding.
     */
    function seek(t) {
      if (!resolveRefs()) return false;
      gsap = window.gsap || null;
      if (!gsap) return false;
      if (!fireTl) {
        if (!fired) { layout(); setDraw(0); }
        clearIdleTimer();
        stopHeartPulse();
        fired = true;
        fireTl = buildFireTimeline(measure());
        // Prime it. A brand-new paused timeline has never rendered, so pausing it
        // straight at 0 leaves the position-0 .set() calls unapplied (GSAP only
        // crosses a zero-duration tween when the playhead MOVES over it). Step a
        // hair forward once so every reset .set() lands, then scrub freely.
        fireTl.pause(0.001);
      }
      // Floor at 1ms, not 0. Landing the playhead on exactly 0 makes GSAP
      // UN-apply the zero-duration .set() calls that sit at position 0, which
      // leaves the reset state half-applied. 1ms of a 4000ms film is invisible.
      fireTl.pause(clamp(t, 0.001, fireTl.duration()));
      return true;
    }

    /**
     * TESTING ADDITION. Total film length in seconds from release.
     * Returns the live timeline's duration once one exists; otherwise the
     * constant, which is fully determined by the fixed beat positions
     * (last beat starts at 3.42, runs 0.58) and does not depend on measurement.
     */
    function duration() {
      return fireTl ? fireTl.duration() : FILM_DURATION;
    }

    function destroy() {
      stop();
      if (archery) {
        archery.removeEventListener('pointerdown', onPointerDown);
        archery.removeEventListener('pointermove', onPointerMove);
        archery.removeEventListener('pointerup', onPointerEnd);
        archery.removeEventListener('pointercancel', onPointerEnd);
        archery.removeEventListener('keydown', onKeyDown);
      }
      if (onResize) window.removeEventListener('resize', onResize);
      if (gsap && motesWrap) {
        gsap.killTweensOf(motesWrap.querySelectorAll('.' + CLS_MOTE));
        motesWrap.innerHTML = '';
      }
      wired = false;
    }

    return {
      start: start,
      stop: stop,
      seek: seek,
      destroy: destroy,
      duration: duration,
      /** Original public surface: true once the film has finished and the host
       *  may let a tap advance to the next scene. */
      canAdvance: function () { return canAdvance; },
      /** Escape hatch for testing — the raw GSAP timeline, or null. */
      timeline: function () { return fireTl; }
    };
  }

  return createCupidFilm;
}());

/* =============================================================================
 * TODO(GUESS) — everything below is NOT recoverable from the sources given.
 * All values above this line are verbatim from film-opening-deob.js.
 *
 * 1. opts.onImpact / opts.onAdvanceRequest names.
 *    The original called two module-scope functions that live outside the film:
 *      - at t=0.26 : startBgm()      (app.deobfuscated.js _0xb6e14 — plays the
 *                                     looping mp3 at ourmoments.live/.../blue-
 *                                     instrumental_128k-1.mp3)
 *      - at onComplete: treeEngine.start()  (app.deobfuscated.js _0x1f7a0b — the
 *                                     requestAnimationFrame canvas module for the
 *                                     next scene) and then sceneTimeout(advance,
 *                                     8200).
 *    I surfaced these as callbacks. The hook NAMES are mine; the call SITES,
 *    ordering and the 8200/4200/9000 ms values are verbatim.
 *
 * 2. No class is set on any element at hand-off. The original's only DOM signal
 *    was the tree module ADDING 'is-in' to #om-bday-tree-wish / #om-bday-tree-tap
 *    (and the film REMOVING it on reset, via _0x4cd386). I dropped that removal
 *    because it reaches outside `root` into the next scene's DOM — do it in the
 *    host if you need it.
 *
 * 3. `scene` === `root`. In the original these were two different lookups
 *    (#om-bday-scene-tree for the coordinate space, #om-bday-film-hero for the
 *    stage) and #om-bday-scene-tree was located globally. Collapsing the
 *    coordinate space onto `root` is correct ONLY if you pass #om-bday-scene-tree
 *    (or an element with the same box). Passing the hero itself would work too,
 *    since CSS gives it `position:absolute;inset:0` inside the scene — but then
 *    the flood/field/bloom siblings would not be found by the `q()` lookups.
 *
 * 4. handleResize now kills the previous timeline before rebuilding. The
 *    original did not (see the comment at that site). This is a deliberate,
 *    behaviour-changing correction, not a transcription.
 *
 * 5. blobs are scoped to `root`; the original used a bare '.om-bday-film-blob'
 *    global selector string passed straight to gsap.
 *
 * 6. splitChars uses Array.from instead of Array.prototype.slice.call — the
 *    requested surrogate-pair fix. See the comment on the function.
 *
 * 7. The reduced-motion branch's callback ORDER is mine. The original ran, in
 *    order: hero.style.display='none'; resetTreeText(); treeEngine.start();
 *    startBgm(); sceneTimeout(advance, reducedMotion?4200:9000). I fire
 *    onComplete before onImpact to match "hand-off first, audio second".
 *
 * 8. The original's reduced-motion branch used the SAME code path for "no gsap"
 *    (falling back to 9000ms). Here start() returns false with no gsap, per the
 *    brief, so ADVANCE_NO_GSAP_MS (9000) is recorded above but never used.
 * ========================================================================== */

/* ===== end film engine ===== */

  let bmTree = null;
  let bmFilm = null;

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

// The film engine is reference code we keep re-syncable, so its sound is
  // hooked from outside rather than edited in. The fire timeline only exists
  // once the arrow is released, so watch briefly for it and hang the beats off
  // it — GSAP fires them as the playhead crosses, which keeps them in sync even
  // when a resize rebuilds the timeline.
  let bmFilmSfxTimer = null;

  function bmFilmSfxStop(){
    if(bmFilmSfxTimer){ clearInterval(bmFilmSfxTimer); bmFilmSfxTimer = null; }
  }

  function bmFilmSfxWatch(){
    const archery = document.getElementById('om-bday-film-archery');
    if(archery && !archery.dataset.bmSfx){
      archery.dataset.bmSfx = '1';
      archery.addEventListener('pointerdown', function(){ initAudio(); playFilmSfx('draw'); });
    }
    bmFilmSfxStop();
    let waited = 0;
    bmFilmSfxTimer = setInterval(function(){
      // Give up rather than poll forever if the film never fires.
      if((waited += 60) > 120000){ bmFilmSfxStop(); return; }
      if(!bmFilm || typeof bmFilm.timeline !== 'function') return;
      const tl = bmFilm.timeline();
      if(!tl) return;
      bmFilmSfxStop();
      playFilmSfx('release');
      tl.call(function(){ playFilmSfx('ignite'); },   null, 1.00);
      tl.call(function(){ playFilmSfx('headline'); }, null, 2.06);
      tl.call(function(){ playFilmSfx('bloom'); },    null, 3.42);
    }, 60);
  }

  // The film is an enhancement over the tree, never a gate in front of it:
  // if GSAP fails to load or motion is reduced, the tree simply starts and
  // the scene still works. The reference is built the same way.
  function startTreeScene(){
    const wrap = document.getElementById('treeWrap');
    const hero = document.getElementById('om-bday-film-hero');
    const canPlayFilm = !!window.gsap && !fxReduced() && typeof createCupidFilm === 'function' && hero;

    if(!canPlayFilm){
      if(hero) hero.style.display = 'none';
      startTree();
      return;
    }

    if(wrap) wrap.classList.add('film-playing');
    try {
      bmFilm = createCupidFilm(wrap, {
        onComplete: function(){
          if(wrap) wrap.classList.remove('film-playing');
          startTree();
        }
      });
      if(bmFilm.start() === false) throw new Error('film declined to start');
      bmFilmSfxWatch();
    } catch(e){
      // Any failure degrades to a working card rather than a dead screen.
      bmFilmSfxStop();
      if(wrap) wrap.classList.remove('film-playing');
      if(hero) hero.style.display = 'none';
      bmFilm = null;
      startTree();
    }
  }

  // Ignore taps while the film is still running, otherwise the first drag on
  // the bowstring would skip the whole scene.
  function treeSceneTap(){
    if(bmFilm && bmFilm.canAdvance && !bmFilm.canAdvance()) return;
    toStep('title');
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
    if(n === 'tree') startTreeScene();
    else {
      if(bmTree) bmTree.stop();
      if(bmFilm && bmFilm.stop) bmFilm.stop();
      bmFilmSfxStop();
    }
    if(n === 'balloons') renderPopBalloons();
    if(n === 'photos') renderPhotos();
    if(n === 'closing') confettiBurst();
    window.scrollTo(0,0);
  }

  // Synthesized with the Web Audio API rather than shipped audio files — the
  // plugin ships no audio binaries at all, so there is nothing to license,
  // host or cache-bust. Everything below is built from oscillators plus one
  // procedurally generated reverb impulse.
  //
  // The melody is "Happy Birthday to You", which entered the public domain
  // with Marya v. Warner/Chappell (2016).
  let audioCtx = null;
  let soundOn = true;
  try { soundOn = localStorage.getItem('bm_sound_off') !== '1'; } catch(e){}

  function updateSoundIcon(){
    const el = document.getElementById('soundToggle');
    if(el) el.textContent = soundOn ? '🔊' : '🔇';
  }
  updateSoundIcon();

  function initAudio(){
    if(!audioCtx){
      try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e){}
    }
    // Safari/iOS in particular can leave a freshly-created context 'suspended'
    // even inside a click handler — resume() is safe to call unconditionally
    // and is a no-op once the context is already running.
    if(audioCtx && audioCtx.state === 'suspended'){
      audioCtx.resume().catch(() => {});
    }
  }

  /* ---------------------------------------------------------------- the bus
     One shared chain: everything lands on a compressor (oscillator stacks add
     up fast and would otherwise clip on a phone) and a master gain that mute
     rides. A convolver fed by a procedural impulse supplies the room. */
  const MUSIC_VOLUME = 0.45;
  let bus = null;

  function makeImpulse(seconds, decay){
    const rate = audioCtx.sampleRate;
    const len = Math.max(1, Math.floor(seconds * rate));
    const buf = audioCtx.createBuffer(2, len, rate);
    for(let c = 0; c < 2; c++){
      const d = buf.getChannelData(c);
      for(let i = 0; i < len; i++){
        let n = Math.sin(i * (c ? 12.9898 : 78.233)) * 43758.5453;
        n = (n - Math.floor(n)) * 2 - 1;
        d[i] = n * Math.pow(1 - i / len, decay);
      }
    }
    return buf;
  }

  function ensureBus(){
    if(bus || !audioCtx) return bus;
    const master = audioCtx.createGain();
    master.gain.value = soundOn ? 1 : 0;

    const comp = audioCtx.createDynamicsCompressor();
    comp.threshold.value = -18;
    comp.knee.value = 24;
    comp.ratio.value = 3;
    comp.attack.value = 0.006;
    comp.release.value = 0.25;

    const dry = audioCtx.createGain();
    const music = audioCtx.createGain();
    music.gain.value = MUSIC_VOLUME;
    const sfx = audioCtx.createGain();
    sfx.gain.value = 0.9;

    const conv = audioCtx.createConvolver();
    conv.buffer = makeImpulse(1.7, 2.6);
    const wetLP = audioCtx.createBiquadFilter();
    wetLP.type = 'lowpass';
    wetLP.frequency.value = 3400;
    const wet = audioCtx.createGain();
    wet.gain.value = 0.30;

    music.connect(dry);
    sfx.connect(dry);
    dry.connect(comp);
    dry.connect(conv);
    conv.connect(wetLP).connect(wet).connect(comp);
    comp.connect(master).connect(audioCtx.destination);

    bus = { music: music, sfx: sfx, master: master };
    return bus;
  }

  /* -------------------------------------------------------------- the score */
  const BEAT = 0.46;
  const LOOP_BEATS = 25;
  const TAIL_BEATS = 2;
  const CYCLE_BEATS = LOOP_BEATS + TAIL_BEATS;

  const HAPPY_BIRTHDAY_MELODY = [
    [392.00,0.5],[392.00,0.5],[440.00,1],[392.00,1],[523.25,1],[493.88,2],
    [392.00,0.5],[392.00,0.5],[440.00,1],[392.00,1],[587.33,1],[523.25,2],
    [392.00,0.5],[392.00,0.5],[783.99,1],[659.25,1],[523.25,1],[493.88,1],[440.00,2],
    [698.46,0.5],[698.46,0.5],[659.25,1],[523.25,1],[587.33,1],[523.25,2]
  ];

  const MELODY_NOTES = (function(){
    const out = []; let t = 0;
    HAPPY_BIRTHDAY_MELODY.forEach(function(n){ out.push({f:n[0], b:n[1], at:t}); t += n[1]; });
    return out;
  })();

  // C major. The root of each chord belongs to the bass voice, not the pad.
  const CHORD_TONES = {
    C:  [130.81, 261.63, 329.63, 392.00],
    G7: [ 98.00, 246.94, 293.66, 349.23],
    F:  [174.61, 261.63, 349.23, 440.00]
  };
  const CHORD_PLAN = [
    [0,4,'C'], [4,2,'G7'],
    [6,3,'C'], [9,1,'G7'], [10,2,'C'],
    [12,4,'C'], [16,3,'G7'],
    [19,2,'F'], [21,1,'C'], [22,1,'G7'], [23,2,'C']
  ];
  const SPARKLE_SCALE = [1046.50, 1174.66, 1318.51, 1567.98, 1760.00];

  /* ------------------------------------------------------------------ voices
     A music box is a struck metal tine: fast attack, long exponential decay,
     and an inharmonic upper partial that dies far sooner than the fundamental.
     A bare triangle oscillator has none of that, which is why a single-osc
     melody reads as a ringtone rather than a gift. */
  const MB_PARTIALS = [
    [1.000, 0.62, 1.00],
    [2.004, 0.26, 0.62],
    [3.011, 0.10, 0.38],
    [5.430, 0.095, 0.16]
  ];

  function vMusicBox(freq, when, beats, vel){
    if(!bus) return;
    const dur = Math.max(0.9, beats * BEAT * 2.2);
    const v = (vel === undefined) ? 1 : vel;
    for(let i = 0; i < MB_PARTIALS.length; i++){
      const p = MB_PARTIALS[i];
      const f = freq * p[0];
      if(f > 17000) continue;
      const osc = audioCtx.createOscillator();
      osc.type = 'sine';
      osc.frequency.value = f;
      osc.detune.value = (i % 2 ? 1 : -1) * (2 + i);
      const g = audioCtx.createGain();
      const peak = Math.max(0.0002, p[1] * v * 0.5);
      const dec = dur * p[2];
      // Never ramp exponentially to 0 — it is a no-op and leaves the gain stuck.
      g.gain.setValueAtTime(0.0001, when);
      g.gain.exponentialRampToValueAtTime(peak, when + 0.004);
      g.gain.exponentialRampToValueAtTime(0.0001, when + dec);
      osc.connect(g).connect(bus.music);
      osc.start(when);
      osc.stop(when + dec + 0.02);
    }
  }

  const PAD_DETUNE = [-8, 8];

  function vPad(freqs, when, beats){
    if(!bus) return;
    const dur = beats * BEAT;
    const out = audioCtx.createGain();
    out.gain.setValueAtTime(0.0001, when);
    out.gain.exponentialRampToValueAtTime(0.055, when + 0.35);
    out.gain.setValueAtTime(0.055, when + Math.max(0.4, dur - 0.35));
    out.gain.exponentialRampToValueAtTime(0.0001, when + dur + 0.30);

    const lp = audioCtx.createBiquadFilter();
    lp.type = 'lowpass';
    lp.frequency.setValueAtTime(520, when);
    lp.frequency.linearRampToValueAtTime(1500, when + dur * 0.6);
    lp.frequency.linearRampToValueAtTime(900, when + dur + 0.3);
    lp.Q.value = 0.6;
    lp.connect(out);
    out.connect(bus.music);

    for(let i = 1; i < freqs.length; i++){
      for(let d = 0; d < PAD_DETUNE.length; d++){
        const osc = audioCtx.createOscillator();
        osc.type = 'sawtooth';
        osc.frequency.value = freqs[i];
        osc.detune.value = PAD_DETUNE[d];
        const g = audioCtx.createGain();
        g.gain.value = 0.42;
        osc.connect(g).connect(lp);
        osc.start(when);
        osc.stop(when + dur + 0.35);
      }
    }
  }

  function vBass(freq, when, beats){
    if(!bus) return;
    const dur = beats * BEAT;
    const lp = audioCtx.createBiquadFilter();
    lp.type = 'lowpass';
    lp.frequency.value = 320;
    const g = audioCtx.createGain();
    g.gain.setValueAtTime(0.0001, when);
    g.gain.exponentialRampToValueAtTime(0.10, when + 0.05);
    g.gain.exponentialRampToValueAtTime(0.0001, when + dur * 0.95);
    const a = audioCtx.createOscillator();
    a.type = 'sine';
    a.frequency.value = freq / 2;
    const b = audioCtx.createOscillator();
    b.type = 'triangle';
    b.frequency.value = freq / 2;
    const bg = audioCtx.createGain();
    bg.gain.value = 0.18;
    a.connect(lp);
    b.connect(bg).connect(lp);
    lp.connect(g).connect(bus.music);
    a.start(when); a.stop(when + dur + 0.05);
    b.start(when); b.stop(when + dur + 0.05);
  }

  /* --------------------------------------------------------------- scheduler
     A lookahead scheduler rather than a setTimeout per note: setTimeout drifts
     and, worse, keeps firing while the tab is backgrounded, which used to pile
     up notes and dump them all at once on return. Events are laid out in beats
     and only committed to the clock a couple of seconds ahead. */
  const MUSIC_EVENTS = (function(){
    const ev = [];
    MELODY_NOTES.forEach(function(n){
      const vel = n.b >= 2 ? 1.0 : 0.82;
      ev.push({at:n.at, run:function(w){ vMusicBox(n.f, w, n.b, vel); }});
      // Octave doubling on held notes adds body without extra note density.
      if(n.b >= 2) ev.push({at:n.at, run:function(w){ vMusicBox(n.f * 2, w, n.b, 0.16); }});
    });
    CHORD_PLAN.forEach(function(c){
      const freqs = CHORD_TONES[c[2]];
      ev.push({at:c[0], run:function(w){ vPad(freqs, w, c[1]); vBass(freqs[0], w, c[1]); }});
    });
    // The pad carries through the seam so cycles join without a hole.
    ev.push({at:LOOP_BEATS, run:function(w){
      vPad(CHORD_TONES.C, w, TAIL_BEATS + 0.5);
      vBass(CHORD_TONES.C[0], w, TAIL_BEATS + 0.5);
    }});
    // A different sparkle each cycle, so leaving the page open does not become
    // a literal repeat.
    ev.push({at:LOOP_BEATS, run:function(w, cycle){
      const n = 3 + (cycle % 3);
      for(let i = 0; i < n; i++){
        vMusicBox(SPARKLE_SCALE[(i * 2 + cycle) % SPARKLE_SCALE.length], w + i * BEAT * 0.75, 0.4, 0.18);
      }
    }});
    return ev.sort(function(a, b){ return a.at - b.at; });
  })();

  const MUSIC_LOOKAHEAD = 2.0;
  let musicTimer = null;
  let musicStarted = false;
  let cycleStart = 0;
  let cycleIndex = 0;
  let eventPtr = 0;

  function musicTick(){
    if(!audioCtx || !bus) return;
    const horizon = audioCtx.currentTime + MUSIC_LOOKAHEAD;
    // Bounded so a long background pause can never spin here.
    let guard = 0;
    while(guard++ < 400){
      if(eventPtr >= MUSIC_EVENTS.length){
        cycleStart += CYCLE_BEATS * BEAT;
        cycleIndex++;
        eventPtr = 0;
      }
      const ev = MUSIC_EVENTS[eventPtr];
      const when = cycleStart + ev.at * BEAT;
      if(when > horizon) break;
      // A tab that was hidden for minutes must not dump its backlog at once.
      if(when >= audioCtx.currentTime - 0.05){
        try { ev.run(when, cycleIndex); } catch(e){}
      }
      eventPtr++;
    }
  }

  function startBackgroundMusic(){
    if(musicStarted || !audioCtx) return;
    if(!ensureBus()) return;
    musicStarted = true;
    cycleStart = audioCtx.currentTime + 0.15;
    cycleIndex = 0;
    eventPtr = 0;
    musicTick();
    musicTimer = setInterval(musicTick, 500);
  }

  function stopBackgroundMusic(){
    if(musicTimer){ clearInterval(musicTimer); musicTimer = null; }
    musicStarted = false;
  }

  // Muting stops the scheduler outright rather than just pulling the fader, so
  // a muted page costs nothing. Unmuting restarts the cycle from the clock.
  function toggleSound(){
    soundOn = !soundOn;
    try { localStorage.setItem('bm_sound_off', soundOn ? '0' : '1'); } catch(e){}
    updateSoundIcon();
    if(!audioCtx) return;
    if(bus) bus.master.gain.setTargetAtTime(soundOn ? 1 : 0, audioCtx.currentTime, 0.2);
    if(soundOn){
      if(!musicStarted) startBackgroundMusic();
    } else {
      stopBackgroundMusic();
    }
  }

  // Nothing should keep scheduling for a page nobody is looking at.
  document.addEventListener('visibilitychange', function(){
    if(document.hidden){
      if(musicTimer){ clearInterval(musicTimer); musicTimer = null; }
    } else if(soundOn && musicStarted && audioCtx && !musicTimer){
      cycleStart = audioCtx.currentTime + 0.15;
      eventPtr = 0;
      musicTick();
      musicTimer = setInterval(musicTick, 500);
    }
  });

  /* -------------------------------------------------------------------- sfx */

  // Kept for the callers that predate the bus (boomSfx among them).
  function playTone(freq, duration, type, vol){
    if(!soundOn || !audioCtx) return;
    ensureBus();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = type;
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(Math.max(0.0002, vol), audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + duration);
    osc.connect(gain).connect(bus ? bus.sfx : audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + duration);
  }

  // Filtered noise — the basis of every percussive, airy or impact sound here.
  function noiseBurst(when, dur, freq, q, vol, type){
    if(!soundOn || !audioCtx || !bus) return;
    const len = Math.max(1, Math.floor(dur * audioCtx.sampleRate));
    const buf = audioCtx.createBuffer(1, len, audioCtx.sampleRate);
    const d = buf.getChannelData(0);
    for(let i = 0; i < len; i++){
      let n = Math.sin(i * 91.7351) * 43758.5453;
      d[i] = ((n - Math.floor(n)) * 2 - 1) * (1 - i / len);
    }
    const src = audioCtx.createBufferSource();
    src.buffer = buf;
    const f = audioCtx.createBiquadFilter();
    f.type = type || 'bandpass';
    f.frequency.value = freq;
    f.Q.value = q;
    const g = audioCtx.createGain();
    g.gain.setValueAtTime(Math.max(0.0002, vol), when);
    g.gain.exponentialRampToValueAtTime(0.0001, when + dur);
    src.connect(f).connect(g).connect(bus.sfx);
    src.start(when);
    src.stop(when + dur + 0.02);
  }

  function playPop(){
    if(!soundOn || !audioCtx) return;
    ensureBus();
    const t = audioCtx.currentTime;
    // A balloon pop is a click plus a short body, not a beep.
    noiseBurst(t, 0.06, 1800, 1.2, 0.34, 'bandpass');
    const osc = audioCtx.createOscillator();
    const g = audioCtx.createGain();
    osc.type = 'triangle';
    osc.frequency.setValueAtTime(760, t);
    osc.frequency.exponentialRampToValueAtTime(180, t + 0.14);
    g.gain.setValueAtTime(0.22, t);
    g.gain.exponentialRampToValueAtTime(0.0001, t + 0.16);
    osc.connect(g).connect(bus.sfx);
    osc.start(t); osc.stop(t + 0.18);
  }

  function playChime(){
    if(!soundOn || !audioCtx) return;
    ensureBus();
    const t = audioCtx.currentTime;
    [523.25, 659.25, 783.99, 1046.50].forEach(function(f, i){
      vMusicBox(f, t + i * 0.075, 1.1, 0.55);
    });
  }

  /* The cupid film's beats. Times are seconds from the arrow's release and
     match the fire timeline exactly: it ignites the flood at 1.00, lands the
     headline at 2.06 and opens the bloom at 3.42, running 4.00 in total. */
  function playFilmSfx(name){
    if(!soundOn || !audioCtx) return;
    ensureBus();
    const t = audioCtx.currentTime;
    if(name === 'draw'){
      noiseBurst(t, 0.18, 320, 0.8, 0.10, 'bandpass');
    } else if(name === 'release'){
      // bowstring snap, then the arrow leaving
      noiseBurst(t, 0.09, 2600, 1.6, 0.30, 'bandpass');
      noiseBurst(t + 0.03, 0.26, 900, 0.7, 0.16, 'highpass');
    } else if(name === 'ignite'){
      const osc = audioCtx.createOscillator();
      const g = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(110, t);
      osc.frequency.exponentialRampToValueAtTime(55, t + 0.7);
      g.gain.setValueAtTime(0.0001, t);
      g.gain.exponentialRampToValueAtTime(0.30, t + 0.06);
      g.gain.exponentialRampToValueAtTime(0.0001, t + 0.8);
      osc.connect(g).connect(bus.sfx);
      osc.start(t); osc.stop(t + 0.85);
      noiseBurst(t, 0.55, 700, 0.5, 0.16, 'lowpass');
    } else if(name === 'headline'){
      [523.25, 659.25, 783.99, 1318.51].forEach(function(f, i){
        vMusicBox(f, t + i * 0.045, 1.6, 0.7);
      });
    } else if(name === 'bloom'){
      [659.25, 783.99, 1046.50, 1318.51, 1567.98].forEach(function(f, i){
        vMusicBox(f, t + i * 0.09, 1.2, 0.42);
      });
      noiseBurst(t, 0.9, 5200, 0.6, 0.07, 'highpass');
    }
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
