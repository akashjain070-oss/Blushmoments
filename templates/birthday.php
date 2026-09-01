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
  .step{ display:none; }
  .step.active{ display:block; animation:stepIn .5s cubic-bezier(.34,1.56,.64,1); }
  #confettiRain{ position:fixed; inset:0; pointer-events:none; z-index:40; overflow:hidden; }
  .rain-piece{ position:absolute; top:-40px; font-size:1.6rem; animation:fall linear forwards; }
  @keyframes fall{ to{ transform:translateY(110vh) rotate(200deg); opacity:.2; } }
  @keyframes stepIn{ 0%{ opacity:0; transform:translateY(14px); } 100%{ opacity:1; transform:translateY(0); } }
</style>
</head>
<body>

<div id="confettiRain"></div>

<div class="stage">

  <div class="step active" data-step="title">
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
      <button class="night-btn" id="keepGoingBtn" style="display:none;" onclick="toStep('envelope')">Keep going 💛</button>
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
  const BALLOON_COLORS = ['#ff8fa3','#8f7aff','#3fd6c0','#ffb347','#ff6f91'];

  function toStep(n){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
    if(n === 'balloons') renderPopBalloons();
    if(n === 'closing') confettiBurst();
    window.scrollTo(0,0);
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
</script>
</body>
</html>
