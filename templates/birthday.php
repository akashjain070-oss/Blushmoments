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
  .balloon{ width:64px; height:80px; border-radius:50% 50% 50% 50% / 60% 60% 40% 40%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.4rem; box-shadow:inset -6px -8px 14px rgba(0,0,0,.15); transition:transform .12s ease; animation:balloonBob 3.4s ease-in-out infinite; }
  @keyframes balloonBob{ 0%,100%{ transform:translateY(0) rotate(-2deg); } 50%{ transform:translateY(-8px) rotate(2deg); } }
  .balloon:active{ transform:scale(.9); }
  .balloon.popped{ visibility:hidden; }
  .reasons{ display:flex; flex-direction:column; gap:10px; margin-top:18px; }
  .reason-card{ border:1.5px solid; border-radius:14px; padding:12px 14px; background:rgba(255,255,255,.06); }
  .reason-card .tag{ display:inline-block; font-size:.65rem; font-weight:800; letter-spacing:.4px; padding:3px 10px; border-radius:12px; margin-bottom:6px; background:rgba(255,255,255,.12); }
  .reason-card .txt{ font-weight:700; font-size:.92rem; }
  .and-more{ text-align:center; font-size:.85rem; opacity:.7; margin:16px 0; font-style:italic; }
  .night-btn{ width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:700; font-size:.95rem; cursor:pointer; margin-top:6px; }
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
  .envelope-wrap .tap{ font-size:.75rem; opacity:.6; margin-top:20px; }
  .letter-page{ padding:26px 22px 40px; min-height:100vh; }
  .letter-paper{ background:#fff8e8; border-radius:14px; padding:22px 20px; min-height:260px; margin-top:20px; }
  .letter-paper .dear{ font-weight:800; color:var(--gold-deep); margin-bottom:14px; font-size:1.05rem; }
  .letter-paper .msg{ line-height:1.7; font-size:.95rem; color:#3a2a10; white-space:pre-wrap; }
  .letter-paper .sign{ text-align:right; margin-top:18px; font-weight:700; color:var(--gold-deep); }
  .letter-page .primary-btn{ margin-top:20px; }
  .primary-btn{ width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:700; font-size:.95rem; cursor:pointer; box-shadow:0 10px 26px rgba(242,121,11,.35); }
  .closing-wrap{ padding:60px 24px; text-align:center; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .closing-wrap .big{ font-size:1.7rem; font-weight:900; line-height:1.25; }
  .closing-wrap .big .name{ color:var(--gold); }
  .closing-wrap .decor{ font-size:2.4rem; margin:18px 0; }
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
  .locked-wrap .emoji{ font-size:2.6rem; margin-bottom:14px; }
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
  .photo-carousel-item{ scroll-snap-align:center; flex:0 0 auto; width:200px; }
  .photo-carousel-item img{ width:200px; height:240px; object-fit:cover; border-radius:14px; border:5px solid #fff; box-shadow:0 12px 26px rgba(0,0,0,.35); display:block; }
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
  @media (prefers-reduced-motion: reduce){ .swipe-hint-icon{ animation:none; display:none; } }
  .step{ display:none; }
  .step.active{ display:block; animation:stepIn .5s cubic-bezier(.2,.82,.3,1) both; }
  #confettiRain{ position:fixed; inset:0; pointer-events:none; z-index:40; overflow:hidden; }
  .rain-piece{ position:absolute; top:-40px; font-size:1.6rem; animation:fall linear forwards; }
  @keyframes fall{ to{ transform:translateY(110vh) rotate(200deg); opacity:.2; } }
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
<div id="confettiRain"></div>
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

  function toStep(n){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
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
    const wrap = document.getElementById('confettiRain');
    const symbols = ['🎉','🎈','🎂','✨','🎁','⭐'];
    for(let i=0;i<40;i++){
      const s = document.createElement('div');
      s.className='rain-piece';
      s.textContent = symbols[Math.floor(Math.random()*symbols.length)];
      s.style.left = Math.random()*100 + 'vw';
      s.style.animationDuration = (2.5 + Math.random()*2) + 's';
      s.style.animationDelay = (Math.random()*0.6) + 's';
      wrap.appendChild(s);
      setTimeout(()=>s.remove(), 5000);
    }
  }

  // Ambient background decoration, independent of step navigation — sits
  // fixed over the whole page so it drifts continuously no matter which
  // .step is active, rather than being rebuilt per-screen.
  (function(){
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
