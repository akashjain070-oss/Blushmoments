<?php
/**
 * Recipient-facing template for the "proposal" experience.
 *
 * Loaded by Blush_Moments_Recipient_View::render_experience() with $surprise
 * already populated from postmeta. This file owns the FULL html document —
 * we bypass the WordPress theme entirely for recipient pages.
 *
 * Ported from proposal-prototype/index.html (the validated, QA'd standalone
 * build) minus the builder wizard, generating screen, and paywall — none of
 * that belongs here, it already happened before this link was ever sent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$their_name = $surprise['their_name'] ?: 'Them';
$your_name  = $surprise['your_name'] ?: 'Someone';
$question   = preg_replace( '/^\S+\s/', '', $surprise['question'] ?: 'Will you be mine?' ); // strip leading emoji
$message    = $surprise['message'] ?: '';
$love_cards = ! empty( $surprise['content']['love_cards'] ) ? $surprise['content']['love_cards'] : array(
	array( 'emoji' => '🐧💕🐧', 'cap' => 'my person' ),
	array( 'emoji' => '🐻❤️🐰', 'cap' => 'forever' ),
	array( 'emoji' => '😻', 'cap' => 'jaan' ),
	array( 'emoji' => '🦋💗', 'cap' => 'us' ),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?php echo esc_html( $their_name ); ?>'s Surprise</title>
<style>
  :root{
    --pink-deep:#ff5c8a; --pink:#ff7aa2; --pink-soft:#ffe1ea;
    --gold:#ffb84d; --ink:#2c1c2e; --muted:#8a7580;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html,body{ height:100%; }
  body{
    font-family:-apple-system, 'Segoe UI', Roboto, sans-serif;
    background:linear-gradient(160deg,#ffd6e2,#ffb8cf 55%,#f3c9e0);
    min-height:100vh; overflow-x:hidden; color:var(--ink); position:relative;
  }
  .stage{ max-width:420px; margin:0 auto; min-height:100vh; position:relative; padding-bottom:40px; }
  .card{
    background:#fff; border-radius:22px; margin:14px 20px; padding:28px 24px;
    box-shadow:0 18px 40px rgba(200,50,110,.18); position:relative;
  }
  .primary-btn{
    width:100%; border:none; border-radius:40px; padding:15px;
    background:linear-gradient(135deg,var(--pink-deep),var(--gold));
    color:#fff; font-weight:700; font-size:.95rem; cursor:pointer;
    box-shadow:0 10px 26px rgba(255,92,138,.35);
  }
  .recip-heart{ font-size:2.8rem; text-align:center; margin-bottom:8px; filter:drop-shadow(0 4px 10px rgba(255,92,138,.4)); }
  .recip-name{ text-align:center; font-size:1.6rem; font-weight:800; color:var(--pink-deep); margin-bottom:4px; }
  .recip-q{ text-align:center; font-size:1.15rem; font-weight:700; margin-bottom:26px; }
  .btnrow{ position:relative; display:flex; flex-direction:column; align-items:center; gap:16px; min-height:110px; }
  .yes-btn{
    width:80%; border:none; border-radius:40px; padding:16px;
    background:linear-gradient(135deg,var(--pink-deep),var(--gold)); color:#fff;
    font-weight:800; font-size:1rem; cursor:pointer; box-shadow:0 10px 26px rgba(255,92,138,.35);
    transition:transform .15s ease;
  }
  .yes-btn.grew{ transform:scale(1.05); }
  .no-btn{
    border:1.5px solid #e9d3da; background:#fafafa; color:var(--muted);
    border-radius:40px; padding:12px 28px; font-weight:700; cursor:pointer;
    position:absolute; top:76px; transition:top .15s ease, left .15s ease;
  }
  .taunt{
    text-align:center; font-size:.82rem; color:var(--pink-deep); font-weight:700;
    margin-top:22px; min-height:18px; opacity:0; transition:opacity .25s ease;
  }
  .taunt.show{ opacity:1; }
  .celeb-title{ text-align:center; font-size:1.4rem; font-weight:800; color:var(--pink-deep); margin:6px 0 4px; }
  .celeb-sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-bottom:18px; }
  .letter-card{
    background:linear-gradient(160deg,#fff,#fff6f0); border-radius:18px; padding:26px 20px;
    text-align:center; cursor:pointer; box-shadow:0 10px 30px rgba(200,50,110,.15);
  }
  .letter-card .env{ font-size:2rem; margin-bottom:10px; }
  .letter-card .t1{ font-weight:800; color:var(--pink-deep); }
  .letter-card .t2{ font-size:.78rem; color:var(--muted); margin-top:4px; }
  .letter-open{ background:#fffaf3; border-radius:18px; padding:24px; text-align:left; }
  .letter-open .dear{ font-weight:800; color:var(--pink-deep); margin-bottom:10px; }
  .letter-open .msg{ line-height:1.6; font-size:.95rem; }
  .letter-open .sign{ text-align:right; margin-top:16px; font-weight:700; color:var(--pink-deep); }
  .next-link{
    display:block; text-align:center; margin-top:18px; border:1.5px solid var(--pink-deep);
    color:var(--pink-deep); border-radius:40px; padding:13px; font-weight:700; cursor:pointer; font-size:.9rem;
  }
  .lc-track{ display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; padding:6px 0 12px; }
  .lc-track::-webkit-scrollbar{ display:none; }
  .lc-card{
    scroll-snap-align:center; flex:0 0 220px; height:260px; border-radius:16px;
    background:linear-gradient(150deg,#ffd6e2,#ffb0cd);
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    font-size:2.6rem; color:#fff; position:relative; overflow:hidden;
  }
  .lc-card .cap{ position:absolute; bottom:14px; font-size:.85rem; font-weight:800; color:#fff; text-shadow:0 2px 6px rgba(0,0,0,.25); }
  .lc-dots{ display:flex; justify-content:center; gap:6px; margin:6px 0 14px; }
  .lc-dots span{ width:6px; height:6px; border-radius:50%; background:#f3cfdb; }
  .lc-dots span.on{ background:var(--pink-deep); width:16px; border-radius:6px; }
  .lc-caption{ text-align:center; font-size:.85rem; color:var(--muted); margin-bottom:8px; }
  .closing{ text-align:center; font-size:.85rem; color:var(--muted); padding:20px 24px 0; }
  .step{ display:none; }
  .step.active{ display:block; }
  #emojiRain{ position:fixed; inset:0; pointer-events:none; z-index:40; overflow:hidden; }
  .rain-piece{ position:absolute; top:-40px; font-size:1.6rem; animation:fall linear forwards; }
  @keyframes fall{ to{ transform:translateY(110vh) rotate(200deg); opacity:.2; } }
</style>
</head>
<body>

<div id="emojiRain"></div>

<div class="stage">

  <div class="step active" data-step="question">
    <div class="card">
      <div class="recip-heart">💗</div>
      <div class="recip-name"><?php echo esc_html( $their_name ); ?>,</div>
      <div class="recip-q"><?php echo esc_html( $question ); ?></div>
      <div class="btnrow">
        <button class="yes-btn" id="yesBtn" onclick="sayYes()">Yes 💕</button>
        <button class="no-btn" id="noBtn" onmouseover="dodge()" ontouchstart="dodge()" onclick="dodge()">No 🙈</button>
      </div>
      <div class="taunt" id="taunt"></div>
    </div>
  </div>

  <div class="step" data-step="celebration">
    <div class="card">
      <div class="celeb-title">Yayy! <?php echo esc_html( $their_name ); ?> said YES! 🎉</div>
      <div class="celeb-sub">Knew you'd say yes 💕</div>
      <div class="letter-card" id="letterClosed" onclick="openLetter()">
        <div class="env">💌</div>
        <div class="t1">Your letter, sealed for <?php echo esc_html( $their_name ); ?></div>
        <div class="t2">Tap to open it</div>
      </div>
      <div class="letter-open" id="letterOpen" style="display:none;">
        <div class="dear">Dear <?php echo esc_html( $their_name ); ?>,</div>
        <div class="msg"><?php echo esc_html( $message ); ?></div>
        <div class="sign">— with love, <?php echo esc_html( $your_name ); ?> 💗</div>
      </div>
      <div class="next-link" id="toLoveCards" style="display:none;" onclick="toStep('lovecards')">See the memory cards 🎬 →</div>
    </div>
  </div>

  <div class="step" data-step="lovecards">
    <div class="card">
      <div class="celeb-title">Yayy! <?php echo esc_html( $their_name ); ?> said YES! 🎉</div>
      <div class="celeb-sub">Knew you'd say yes 💕</div>
      <div class="lc-track" id="lcTrack"></div>
      <div class="lc-dots" id="lcDots"></div>
      <div class="lc-caption">👉 Jo abhi tumne feel kiya, woh bhi feel karegi — because of you 💕</div>
      <div class="closing">Made with Blush Moments, just for you.</div>
    </div>
  </div>

</div>

<script>
  const LOVE_CARDS = <?php echo wp_json_encode( $love_cards ); ?>;
  const TAUNTS = ['Are you sure?', 'Wait, think again 😢', "Don't do this to me 💔", 'Please? 🥺', "I'll wait right here..."];
  const YES_EMOJI = ['💕', '🥹', '😭', '😭', '😭'];
  let noClicks = 0;

  function toStep(n){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
    if(n === 'lovecards') renderLoveCards();
    window.scrollTo(0,0);
  }

  function dodge(){
    const btn = document.getElementById('noBtn');
    const row = btn.parentElement.getBoundingClientRect();
    const maxX = row.width/2 - 60;
    const x = (Math.random()*2 - 1) * maxX;
    const y = 30 + Math.random()*55;
    btn.style.left = `calc(50% + ${x}px)`;
    btn.style.top = `${y}px`;
    btn.style.transform = 'translateX(-50%)';

    noClicks++;
    const idx = Math.min(noClicks, TAUNTS.length - 1);
    const taunt = document.getElementById('taunt');
    taunt.textContent = TAUNTS[idx];
    taunt.classList.add('show');

    const yesBtn = document.getElementById('yesBtn');
    yesBtn.textContent = `Yes ${YES_EMOJI[Math.min(noClicks, YES_EMOJI.length - 1)]}`;
    yesBtn.classList.add('grew');
    setTimeout(()=>yesBtn.classList.remove('grew'), 200);
  }

  function sayYes(){
    toStep('celebration');
    emojiRain();
  }

  function openLetter(){
    document.getElementById('letterClosed').style.display='none';
    document.getElementById('letterOpen').style.display='block';
    document.getElementById('toLoveCards').style.display='block';
  }

  function renderLoveCards(){
    const track = document.getElementById('lcTrack');
    const dots = document.getElementById('lcDots');
    track.innerHTML=''; dots.innerHTML='';
    LOVE_CARDS.forEach((c,i)=>{
      const d = document.createElement('div');
      d.className='lc-card';
      d.innerHTML = `${c.emoji}<div class="cap">${c.cap}</div>`;
      track.appendChild(d);
      const dot = document.createElement('span');
      if(i===0) dot.classList.add('on');
      dots.appendChild(dot);
    });
    track.onscroll = () => {
      const w = track.firstElementChild.offsetWidth + 14;
      const idx = Math.round(track.scrollLeft / w);
      [...dots.children].forEach((d,i)=>d.classList.toggle('on', i===idx));
    };
  }

  function emojiRain(){
    const wrap = document.getElementById('emojiRain');
    const symbols = ['🌹','💍','🎉','💕','✨','🎀','⭐'];
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
</script>
</body>
</html>
