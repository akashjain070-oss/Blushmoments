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
  .envelope .seal{ position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:44px; height:44px; border-radius:50%; background:#fff; color:var(--gold-deep); font-weight:900; display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 4px 10px rgba(0,0,0,.2); }
  .envelope-wrap .tap{ font-size:.75rem; opacity:.6; margin-top:20px; animation:hintBounce 1.8s ease-in-out infinite; }
  @keyframes hintBounce{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-9px); } }
  .letter-page{ padding:26px 22px 40px; min-height:100vh; }
  .letter-paper{ background:#fff8e8; border-radius:14px; padding:22px 20px; min-height:260px; margin-top:20px; opacity:0; animation:letterAppear .7s cubic-bezier(.34,1.56,.64,1) .12s forwards; }
  @keyframes letterAppear{ 0%{ opacity:0; transform:scale(.92); } 100%{ opacity:1; transform:scale(1); } }
  .letter-paper .dear{ font-weight:800; color:var(--gold-deep); margin-bottom:14px; font-size:1.05rem; }
  .letter-paper .msg{ line-height:1.7; font-size:.95rem; color:#3a2a10; white-space:pre-wrap; }
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
    <div class="teaser-wrap" onclick="toStep('title')">
      <div class="big">Happy<br>Birthday</div>
      <div class="sub2">to someone worth celebrating</div>
      <div class="tap">tap anywhere to continue</div>
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
      <div class="envelope" onclick="openLetter()"><div class="seal">🎂</div></div>
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

  function openLetter(){
    toStep('letter');
    initAudio();
    playChime();
    const out = document.getElementById('letterMsgOut');
    const sign = document.getElementById('letterSign');
    const cont = document.getElementById('letterContinueBtn');
    out.textContent = '';
    sign.style.display = 'none';
    cont.style.display = 'none';
    let i = 0;
    const typer = setInterval(()=>{
      out.textContent = MESSAGE.slice(0, i+1);
      i++;
      if(i >= MESSAGE.length){
        clearInterval(typer);
        sign.style.display='block';
        cont.style.display='block';
      }
    }, 18);
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
