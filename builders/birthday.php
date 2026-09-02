<?php
/**
 * Builder wizard for the "birthday" experience, served at /create/birthday.
 * $rest_url is injected by Blush_Moments_Builder_View::maybe_render().
 *
 * Same pattern as builders/proposal.php: preview stays entirely client-side,
 * the server is only touched on the final "Send It to X", which creates the
 * real draft via POST /bm/v1/surprise.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Send a Birthday Surprise — Blush Moments</title>
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
    --spring:cubic-bezier(.34,1.56,.64,1);
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html,body{ height:100%; }
  body{
    font-family:-apple-system, 'Segoe UI', Roboto, sans-serif;
    background:linear-gradient(160deg,#faf3ec,#e6d6e0 30%,#f3ddc8 65%,#f7e6d4);
    min-height:100vh; overflow-x:hidden; color:var(--ink); position:relative;
  }
  h2, .closing-wrap .big, .night .big{ font-family:var(--font-display); letter-spacing:-.01em; }
  @keyframes shimmer{ 0%{ transform:translateX(-120%) skewX(-15deg); } 100%{ transform:translateX(220%) skewX(-15deg); } }
  @keyframes stepIn{ 0%{ opacity:0; transform:translateY(14px); } 100%{ opacity:1; transform:translateY(0); } }
  @keyframes balloonBob{ 0%,100%{ transform:translateY(0) rotate(-2deg); } 50%{ transform:translateY(-8px) rotate(2deg); } }
  @keyframes float{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-6px); } }
  .wordmark{ text-align:center; padding:14px 0 0; }
  .wordmark img{ height:46px; width:auto; }
  .stage{ max-width:420px; margin:0 auto; min-height:100vh; position:relative; padding-bottom:40px; }
  .progress{ display:flex; align-items:center; gap:6px; padding:18px 20px 6px; font-size:.72rem; font-weight:700; color:var(--gold-deep); letter-spacing:.3px; }
  .progress .balloons{ display:flex; gap:3px; margin-right:8px; }
  .progress .balloons span{ font-size:.85rem; opacity:.25; }
  .progress .balloons span.on{ opacity:1; }
  .card{ background:#fff; border-radius:22px; margin:14px 20px; padding:28px 24px; box-shadow:0 18px 40px rgba(200,120,20,.18); position:relative; }
  .card .back{ background:none; border:none; color:var(--gold-deep); font-weight:700; font-size:.85rem; cursor:pointer; margin-bottom:6px; padding:0; }
  .card h2{ font-size:1.35rem; text-align:center; margin-top:6px; }
  .card .sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-top:6px; margin-bottom:20px; }
  label.field-label{ display:block; font-size:.78rem; font-weight:700; color:var(--gold-deep); margin-bottom:6px; }
  input[type=text], input[type=number], textarea, select{ width:100%; border:1.5px solid #f2e0c8; border-radius:12px; padding:14px 16px; font-size:.95rem; margin-bottom:12px; font-family:inherit; background:#fffaf3; }
  input[type=text]:focus, input[type=number]:focus, textarea:focus, select:focus{ outline:none; border-color:var(--gold); }
  textarea{ resize:none; height:110px; }
  .row2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .primary-btn{ width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:700; font-family:var(--font-display); font-size:.95rem; cursor:pointer; box-shadow:0 10px 26px rgba(193,122,63,.35), 0 0 0 6px rgba(193,122,63,.08); position:relative; overflow:hidden; transition:transform .3s var(--spring), box-shadow .3s ease; }
  .primary-btn::after{ content:''; position:absolute; inset:0; background:linear-gradient(100deg,transparent 40%,rgba(255,255,255,.45) 50%,transparent 60%); transform:translateX(-120%) skewX(-15deg); animation:shimmer 2.8s ease-in-out infinite; animation-delay:1s; }
  .primary-btn:active{ transform:scale(.97); }
  .primary-btn:disabled{ opacity:.5; cursor:not-allowed; }
  .primary-btn:disabled::after{ display:none; }
  .grid3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:6px; }
  .cake-card{ border:1.5px solid #f6e6d0; border-radius:16px; padding:16px 6px; text-align:center; cursor:pointer; background:#fffaf3; transition:transform .25s var(--spring), border-color .2s ease, background .2s ease, box-shadow .2s ease; }
  .cake-card .emoji{ font-size:1.6rem; display:block; margin-bottom:8px; }
  .cake-card .name{ font-size:.8rem; font-weight:700; }
  .cake-card .desc{ font-size:.68rem; color:var(--muted); margin-top:2px; }
  .cake-card.selected{ border-color:var(--gold-deep); background:var(--peach-soft); transform:scale(1.04); box-shadow:0 8px 18px rgba(193,122,63,.18); }
  .balloon-field{ display:flex; align-items:center; gap:10px; border:1.5px solid #f2e0c8; border-radius:12px; padding:6px 14px; margin-bottom:10px; background:#fffaf3; }
  .balloon-field .bemoji{ font-size:1.1rem; }
  .balloon-field input{ border:none; background:none; padding:8px 0; margin:0; flex:1; }
  .balloon-field input:focus{ outline:none; }
  .balloon-field .count{ font-size:.68rem; color:var(--muted); }
  .spark-label{ text-align:center; font-size:.72rem; font-weight:700; letter-spacing:.4px; color:var(--muted); margin:14px 0 10px; }
  .spark-grid{ display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:16px; }
  .spark-chip{ border:1px dashed #e8c58f; border-radius:12px; padding:10px 10px; font-size:.78rem; color:var(--gold-deep); background:#fff8ee; cursor:pointer; line-height:1.35; }
  .upload-box{ border:1.5px dashed #f2d2a0; border-radius:16px; padding:26px 16px; margin-bottom:16px; text-align:center; cursor:pointer; background:#fffaf3; }
  .upload-box .icon{ font-size:1.6rem; margin-bottom:8px; }
  .upload-box .t1{ font-weight:700; font-size:.9rem; }
  .upload-box .t2{ font-size:.72rem; color:var(--muted); margin-top:4px; }
  .upload-box.full{ opacity:.6; cursor:default; }
  .photo-grid{ display:flex; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
  .photo-thumb{ position:relative; width:72px; height:72px; border-radius:12px; overflow:hidden; box-shadow:0 4px 10px rgba(193,122,63,.18); }
  .photo-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
  .photo-thumb .rm{ position:absolute; top:3px; right:3px; width:20px; height:20px; border-radius:50%; background:rgba(30,15,10,.65); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.68rem; cursor:pointer; line-height:1; }
  .skip-link{ display:block; text-align:center; color:var(--gold-deep); font-weight:700; font-size:.85rem; margin-top:14px; cursor:pointer; }
  .charcount{ text-align:right; font-size:.75rem; color:var(--muted); margin:-6px 0 12px; }
  .tmpl-row{ display:flex; align-items:center; gap:10px; margin-bottom:12px; font-size:.8rem; color:var(--muted); flex-wrap:wrap; }
  .tmpl-toggle{ display:flex; gap:6px; }
  .tmpl-toggle button{ border:1.5px solid #f2e0c8; background:#fff; border-radius:20px; padding:5px 14px; font-size:.78rem; font-weight:700; cursor:pointer; color:var(--muted); }
  .tmpl-toggle button.active{ border-color:var(--gold-deep); color:var(--gold-deep); background:var(--peach-soft); }
  .tmpl-list{ display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
  .tmpl-item{ border:1px dashed #e8c58f; border-radius:12px; padding:10px 12px; font-size:.82rem; color:var(--gold-deep); background:#fff8ee; cursor:pointer; line-height:1.4; }
  .gen-wrap{ text-align:center; padding:60px 30px; }
  .gen-wrap .spool{ font-size:3rem; margin-bottom:18px; display:inline-block; animation:spin 2.4s linear infinite; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  .gen-wrap h2{ font-size:1.3rem; }
  .gen-wrap .sub{ color:var(--muted); font-size:.85rem; margin:8px 0 20px; }
  .gen-bar{ height:6px; background:#ffe6c4; border-radius:6px; overflow:hidden; margin-bottom:22px; }
  .gen-bar-fill{ height:100%; background:linear-gradient(90deg,var(--gold-deep),var(--gold)); width:0%; transition:width .4s ease; }
  .gen-list{ text-align:left; display:flex; flex-direction:column; gap:12px; font-size:.85rem; }
  .gen-list .item{ display:flex; gap:10px; align-items:flex-start; opacity:.35; transition:opacity .3s; }
  .gen-list .item.done{ opacity:1; }
  .gen-list .check{ color:var(--gold-deep); font-weight:900; width:16px; }
  .preview-tag{ text-align:center; margin:0 20px 8px; }
  .preview-tag span{ background:#fff3c4; color:#9c7a00; font-weight:700; font-size:.72rem; padding:6px 16px; border-radius:20px; letter-spacing:.5px; }
  .night{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:40px 24px; color:#fff; text-align:center; min-height:60vh; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; }
  .night .wish{ font-style:italic; opacity:.75; font-size:1rem; margin-bottom:10px; }
  .night .big{ font-size:2.4rem; font-weight:900; line-height:1.15; margin-bottom:6px; }
  .night .name{ font-size:1.8rem; font-weight:800; color:var(--gold); margin-bottom:18px; }
  .night .tap{ font-size:.75rem; opacity:.55; margin-top:20px; letter-spacing:.4px; }
  .balloon-pop{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:30px 22px; color:#fff; min-height:60vh; }
  .balloon-pop h2{ text-align:center; font-size:1.3rem; }
  .balloon-pop .sub{ text-align:center; font-size:.82rem; opacity:.7; margin:8px 0 22px; }
  .pop-field{ display:flex; flex-wrap:wrap; gap:22px; justify-content:center; margin-bottom:10px; }
  .balloon{ width:64px; height:80px; border-radius:50% 50% 50% 50% / 60% 60% 40% 40%; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1.4rem; box-shadow:inset -6px -8px 14px rgba(0,0,0,.15); transition:transform .12s ease; animation:balloonBob 3.4s ease-in-out infinite; }
  .balloon:nth-child(2){ animation-delay:.3s; }
  .balloon:nth-child(3){ animation-delay:.6s; }
  .balloon:nth-child(4){ animation-delay:.9s; }
  .balloon:nth-child(5){ animation-delay:1.2s; }
  .balloon:active{ transform:scale(.9); }
  .balloon.popped{ visibility:hidden; }
  .reasons{ display:flex; flex-direction:column; gap:10px; margin-top:18px; }
  .reason-card{ border:1.5px solid; border-radius:14px; padding:12px 14px; background:rgba(255,255,255,.06); }
  .reason-card .tag{ display:inline-block; font-size:.65rem; font-weight:800; letter-spacing:.4px; padding:3px 10px; border-radius:12px; margin-bottom:6px; background:rgba(255,255,255,.12); }
  .reason-card .txt{ font-weight:700; font-size:.92rem; }
  .and-more{ text-align:center; font-size:.85rem; opacity:.7; margin:16px 0; font-style:italic; }
  .night-btn{ width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--gold-deep),var(--gold)); color:#fff; font-weight:700; font-size:.95rem; cursor:pointer; margin-top:6px; }
  .envelope-wrap{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:40px 24px; color:#fff; text-align:center; min-height:55vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .envelope-wrap h3{ font-size:1.2rem; margin-bottom:4px; }
  .envelope-wrap .sub{ font-size:.82rem; opacity:.7; margin-bottom:26px; }
  .envelope{ width:180px; height:120px; background:linear-gradient(160deg,#ffcf7a,#f2a83c); border-radius:8px; position:relative; cursor:pointer; box-shadow:0 14px 30px rgba(0,0,0,.3); animation:float 3s ease-in-out infinite; }
  .envelope::before{ content:''; position:absolute; inset:0; background:linear-gradient(135deg,transparent 49.5%,rgba(0,0,0,.15) 50%),linear-gradient(-135deg,transparent 49.5%,rgba(0,0,0,.15) 50%); }
  .envelope .seal{ position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:44px; height:44px; border-radius:50%; background:#fff; color:var(--gold-deep); font-weight:900; display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 4px 10px rgba(0,0,0,.2); }
  .envelope-wrap .tap{ font-size:.75rem; opacity:.6; margin-top:20px; }
  .letter-page{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:26px 22px; min-height:55vh; }
  .letter-paper{ background:#fff8e8; border-radius:14px; padding:22px 20px; min-height:260px; }
  .letter-paper .dear{ font-weight:800; color:var(--gold-deep); margin-bottom:14px; font-size:1.05rem; }
  .letter-paper .msg{ line-height:1.7; font-size:.95rem; color:#3a2a10; white-space:pre-wrap; }
  .letter-paper .sign{ text-align:right; margin-top:18px; font-weight:700; color:var(--gold-deep); }
  .letter-page .primary-btn{ margin-top:20px; }
  .closing-wrap{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:40px 24px; color:#fff; text-align:center; min-height:55vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
  .closing-wrap .big{ font-size:1.7rem; font-weight:900; line-height:1.25; }
  .closing-wrap .big .name{ color:var(--gold); }
  .closing-wrap .decor{ font-size:2.4rem; margin:18px 0; }
  .closing-wrap .from{ font-size:.85rem; opacity:.75; margin-bottom:24px; }
  .err-box{ background:#fdeaea; color:#b3261e; border-radius:10px; padding:10px 14px; font-size:.82rem; margin-bottom:12px; display:none; text-align:left; }
  .link-note-dark{ text-align:center; font-size:.72rem; opacity:.6; margin-top:10px; }
  .overlay{ position:fixed; inset:0; background:rgba(30,15,5,.5); display:none; align-items:flex-start; justify-content:center; z-index:50; padding:24px 16px; overflow-y:auto; }
  .overlay.show{ display:flex; }
  .paywall{ background:#fff; border-radius:22px; max-width:380px; width:100%; overflow:hidden; position:relative; margin-top:10px; color:var(--ink); }
  .paywall .close{ position:absolute; top:14px; right:14px; background:rgba(255,255,255,.85); border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:.9rem; }
  .paywall .head{ background:linear-gradient(135deg,var(--gold-deep),#ffcf7a); padding:26px 20px 18px; text-align:center; color:#fff; }
  .paywall .timer{ display:inline-block; background:rgba(255,255,255,.25); border-radius:20px; padding:6px 14px; font-size:.78rem; font-weight:700; margin-bottom:10px; }
  .paywall .head h3{ font-size:1.2rem; }
  .paywall .head p{ font-size:.8rem; opacity:.9; margin-top:4px; }
  .paywall .body{ padding:20px; }
  .price-box{ text-align:center; border:1.5px solid var(--peach-soft); border-radius:16px; padding:16px; margin-bottom:16px; }
  .price-box .old{ text-decoration:line-through; color:var(--muted); font-size:.95rem; margin-right:8px; }
  .price-box .new{ font-size:1.8rem; font-weight:900; color:var(--gold-deep); }
  .price-box .off{ display:inline-block; margin-top:6px; background:#e3f7e8; color:#1a8a3d; font-weight:700; font-size:.75rem; padding:4px 12px; border-radius:20px; }
  .price-box .sub{ font-size:.72rem; color:var(--muted); margin-top:8px; letter-spacing:.3px; }
  .tagline{ text-align:center; font-size:.85rem; color:var(--muted); margin-bottom:16px; font-style:italic; }
  .trust{ display:flex; justify-content:center; gap:10px; font-size:.72rem; color:var(--muted); margin-top:14px; flex-wrap:wrap; }
  .link-note{ text-align:center; font-size:.72rem; color:var(--muted); margin-top:10px; }
  .stub-note{ text-align:center; font-size:.72rem; color:#a06a00; background:#fff3c4; border-radius:10px; padding:10px; margin-top:14px; line-height:1.5; }
  .share-icon{ font-size:2.4rem; text-align:center; margin-bottom:6px; }
  .share-title{ text-align:center; font-weight:800; font-size:1.2rem; margin-bottom:4px; }
  .share-sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-bottom:20px; }
  .share-link{ background:#fff3e2; border:1px dashed var(--gold); border-radius:12px; padding:12px 14px; font-size:.8rem; color:var(--gold-deep); word-break:break-all; margin-bottom:16px; }
  .share-actions{ display:flex; flex-direction:column; gap:10px; }
  .share-actions button{ border:none; border-radius:40px; padding:14px; font-weight:700; font-size:.9rem; cursor:pointer; }
  .copy-btn{ background:var(--peach-soft); color:var(--gold-deep); }
  .prev-btn{ background:none; border:1.5px solid var(--gold-deep) !important; color:var(--gold-deep); }
  .step{ display:none; }
  .step.active{ display:block; animation:stepIn .5s var(--spring); }
  #confettiRain{ position:fixed; inset:0; pointer-events:none; z-index:40; overflow:hidden; }
  .rain-piece{ position:absolute; top:-40px; font-size:1.6rem; animation:fall linear forwards; }
  @keyframes fall{ to{ transform:translateY(110vh) rotate(200deg); opacity:.2; } }
</style>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>

<div id="confettiRain"></div>

<div class="stage">

  <div class="wordmark"><img src="<?php echo esc_url( BM_PLUGIN_URL . 'assets/logo.png' ); ?>" alt="Blush Moments"></div>

  <div class="progress" id="progressBar">
    <div class="balloons" id="progressBalloons"></div>
    <span id="progressLabel">Step 1 of 5</span>
  </div>

  <div class="step active" data-step="1">
    <div class="card">
      <h2>Who's the birthday star? 🌟</h2>
      <div class="sub">You're about to make someone's day unforgettable 🎀</div>
      <label class="field-label">Their name</label>
      <input type="text" id="theirName" placeholder="e.g. Ananya">
      <label class="field-label">Your name</label>
      <input type="text" id="yourName" placeholder="e.g. Rahul">
      <label class="field-label">Turning age (optional)</label>
      <input type="number" id="age" placeholder="e.g. 25" min="1" max="120">
      <button class="primary-btn" onclick="toStep(2)">Let's begin 🎂</button>
    </div>
  </div>

  <div class="step" data-step="2">
    <div class="card">
      <button class="back" onclick="toStep(1)">← Back</button>
      <h2>Pick their cake 🎂</h2>
      <div class="sub" id="cakeSub">They will light it, wish on it, and cut it.</div>
      <div class="grid3" id="cakeGrid"></div>
      <button class="primary-btn" onclick="toStep(3)">Continue</button>
    </div>
  </div>

  <div class="step" data-step="3">
    <div class="card">
      <button class="back" onclick="toStep(2)">← Back</button>
      <h2>Fill the balloons 🎈</h2>
      <div class="sub">Each balloon hides one reason they're loved. They'll pop them one by one.</div>
      <div id="balloonFields"></div>
      <div class="spark-label">NEED A SPARK? TAP TO USE</div>
      <div class="spark-grid" id="sparkGrid"></div>
      <button class="primary-btn" onclick="toStep(4)">Continue</button>
    </div>
  </div>

  <div class="step" data-step="4">
    <div class="card">
      <button class="back" onclick="toStep(3)">← Back</button>
      <h2>Hang up some memories 📸</h2>
      <div class="sub" id="memoriesSub">Up to 5 photos, strung on fairy lights.</div>
      <div class="photo-grid" id="photoGrid"></div>
      <div class="upload-box" id="uploadBox" onclick="triggerPhotoPick()">
        <div class="icon">🖼️</div>
        <div class="t1" id="uploadT1">Tap to add photos</div>
        <div class="t2">Any photo — we'll resize it for you</div>
      </div>
      <input type="file" id="photoInput" accept="image/*" multiple style="display:none;" onchange="handlePhotoPick(event)">
      <button class="primary-btn" onclick="toStep(5)">Continue</button>
      <div class="skip-link" onclick="toStep(5)">Skip photos for now</div>
    </div>
  </div>

  <div class="step" data-step="5">
    <div class="card">
      <button class="back" onclick="toStep(4)">← Back</button>
      <h2>Write their birthday letter 💌</h2>
      <div class="sub" id="letterSub">This is the part they'll read twice — and remember forever.</div>
      <textarea id="letterMsg" maxlength="500" placeholder="Dear them, another year of you is the best news of my year..." oninput="updateCount()"></textarea>
      <div class="charcount"><span id="charN">0</span> / 500</div>
      <div class="tmpl-row">
        Stuck? Tap one to start with:
        <div class="tmpl-toggle">
          <button id="tHinglish" class="active" onclick="setTmplLang('hi')">Hinglish</button>
          <button id="tEnglish" onclick="setTmplLang('en')">English</button>
        </div>
      </div>
      <div class="tmpl-list" id="tmplList"></div>
      <button class="primary-btn" onclick="startGenerating()">Bake the magic ✨</button>
    </div>
  </div>

  <div class="step" data-step="gen">
    <div class="card gen-wrap">
      <div class="spool">🎂</div>
      <h2 id="genTitle">Crafting the surprise...</h2>
      <div class="sub">Something sweet is taking shape</div>
      <div class="gen-bar"><div class="gen-bar-fill" id="genBarFill"></div></div>
      <div class="gen-list" id="genList"></div>
    </div>
  </div>

  <div class="step" data-step="title">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="night" onclick="toStep('balloons')">
      <div class="wish">make a wish...</div>
      <div class="big">Happy<br>Birthday</div>
      <div class="name" id="titleName">Ananya!</div>
      <div class="tap">tap anywhere to continue</div>
    </div>
  </div>

  <div class="step" data-step="balloons">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="balloon-pop">
      <h2>Pop the balloons 🎈</h2>
      <div class="sub" id="balloonSub">Each one holds a reason they're loved. Pop them all 🎈</div>
      <div class="pop-field" id="popField"></div>
      <div class="reasons" id="reasonsList"></div>
      <div class="and-more" id="andMore" style="display:none;">...and a thousand more reasons 💛</div>
      <button class="night-btn" id="keepGoingBtn" style="display:none;" onclick="toStep('envelope')">Keep going 💛</button>
    </div>
  </div>

  <div class="step" data-step="envelope">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="envelope-wrap">
      <h3 id="envTitle">One last thing, Ananya...</h3>
      <div class="sub" id="envSub">Rahul wrote you a letter.</div>
      <div class="envelope" onclick="openLetter()"><div class="seal">🎂</div></div>
      <div class="tap">Tap to open your letter</div>
    </div>
  </div>

  <div class="step" data-step="letter">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="letter-page">
      <div class="letter-paper">
        <div class="dear" id="letterDear">Dear Ananya,</div>
        <div class="msg" id="letterMsgOut"></div>
        <div class="sign" id="letterSign" style="display:none;">With all my love,<br>— Rahul</div>
      </div>
      <button class="primary-btn" id="letterContinueBtn" style="display:none;" onclick="toStep('closing')">Continue</button>
    </div>
  </div>

  <div class="step" data-step="closing">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="closing-wrap">
      <div class="big">HAPPY BIRTHDAY<br><span class="name" id="closingName">ANANYA!</span></div>
      <div class="decor">🎂🎈🎉</div>
      <div class="from" id="closingFrom">Made with love, just for you — Rahul 💛</div>
      <div class="err-box" id="createErr"></div>
      <button class="primary-btn" id="sendBtn" onclick="createAndOpenPaywall()">Send It to Ananya 🎁</button>
      <div class="link-note-dark">🔗 This creates the private link you'll send them</div>
    </div>
  </div>

  <div class="step" data-step="share">
    <div class="card">
      <div class="share-icon">🎉</div>
      <div class="share-title">You're all set!</div>
      <div class="share-sub">Payment received — this link is live and ready to share.</div>
      <div class="share-link" id="shareLink"></div>
      <div class="share-actions">
        <button class="copy-btn" onclick="copyLink()">🔗 Copy Link</button>
        <button class="prev-btn" onclick="toStep('title')">👁 Preview the recipient view</button>
      </div>
    </div>
  </div>

</div>

<div class="overlay" id="paywallOverlay">
  <div class="paywall">
    <button class="close" onclick="closePaywall()">✕</button>
    <div class="head">
      <div class="timer">⏳ Offer ends in <span id="countdown">10:00</span></div>
      <h3 id="pwTitle">Ananya's surprise is ready 🎉</h3>
      <p id="pwSub">A private link, made only for Ananya</p>
    </div>
    <div class="body">
      <div class="price-box">
        <div><span class="old">₹399</span><span class="new">₹149</span></div>
        <div class="off">60% OFF 🎉</div>
        <div class="sub">ONE-TIME · NO SUBSCRIPTION</div>
      </div>
      <div class="tagline">"The best gifts are made, not bought 💛"</div>
      <div class="err-box" id="payErr"></div>
      <button class="primary-btn" id="unlockBtn" onclick="unlockPay()">🎁 Pay ₹149 &amp; Send →</button>
      <div class="trust">🔒 Secure &nbsp;·&nbsp; ⚡ Instant &nbsp;·&nbsp; 💛 No ads</div>
      <div class="link-note">📅 Your link stays live for 90 days</div>
    </div>
  </div>
</div>

<script>
  const REST_URL = <?php echo wp_json_encode( $rest_url ); ?>;

  const CAKES = [
    {key:'chocolate', label:'Midnight Chocolate', desc:'Rich, dark & dreamy', emoji:'🍫'},
    {key:'strawberry', label:'Strawberry Blush', desc:'Soft, sweet & rosy', emoji:'🍓'},
    {key:'vanilla', label:'Vanilla Gold', desc:'Classic, warm & glowing', emoji:'🍦'},
  ];
  const SPARKS = [
    'Your laugh is my favourite sound',
    'You make ordinary days feel special',
    'You believed in me when I didn\'t',
    'The world is kinder with you in it',
    'You remember the little things I forget',
    'My worst days get shorter when you call',
  ];
  const TEMPLATES = {
    hi: [
      "Har saal tumhe dekh kar lagta hai zindagi thodi aasan ho gayi. Aaj ka din sirf tumhare liye hai — khush raho, hamesha.",
      "Tumhare jaisa dost/insaan milna kismat ki baat hai. Is naye saal mein tumhe wahi milе jo tum dusron ko deti/dete ho — pyaar aur sukoon."
    ],
    en: [
      "I keep thinking about how lucky I got with you. You've seen me at my worst and stayed anyway, and I don't say thank you nearly enough for that. This year, I hope life is gentle with you.",
      "Every year I try to find the perfect words and every year I fall short, so here's the honest version. You make my most ordinary days feel worth remembering. Happy birthday, my favourite person."
    ]
  };
  const BALLOON_COLORS = ['#ff8fa3','#8f7aff','#3fd6c0','#ffb347','#ff6f91'];

  const state = { theirName:'', yourName:'', age:'', cake:'strawberry', balloons:['','','','',''], tmplLang:'hi', photos:[] };
  const MAX_PHOTOS = 5;
  const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

  function triggerPhotoPick(){
    if(state.photos.length >= MAX_PHOTOS) return;
    document.getElementById('photoInput').click();
  }

  function loadImageFile(file){
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
      img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('unreadable')); };
      img.src = url;
    });
  }

  // Re-encodes every photo to a resized JPEG client-side, whatever it came in
  // as (HEIC off an iPhone, a 15MB camera JPEG, a screenshot PNG...). This is
  // what actually makes upload work in practice: without it, iPhones' default
  // HEIC format doesn't match a jpeg/png accept filter and gets silently
  // filtered out of the photo picker, and modern phone camera photos routinely
  // blow past a hard 5MB cap on their own — resizing sidesteps both.
  function resizeToJpeg(img, maxDim, quality){
    let width = img.naturalWidth || img.width;
    let height = img.naturalHeight || img.height;
    if(width > maxDim || height > maxDim){
      if(width >= height){ height = Math.round(height * maxDim / width); width = maxDim; }
      else { width = Math.round(width * maxDim / height); height = maxDim; }
    }
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.getContext('2d').drawImage(img, 0, 0, width, height);
    return canvas.toDataURL('image/jpeg', quality);
  }

  async function handlePhotoPick(e){
    const files = Array.from(e.target.files || []);
    const remaining = MAX_PHOTOS - state.photos.length;
    for(const file of files.slice(0, remaining)){
      if(!file.type.startsWith('image/')){
        alert(file.name + " doesn't look like a photo.");
        continue;
      }
      try{
        const img = await loadImageFile(file);
        let dataUrl = resizeToJpeg(img, 1600, 0.82);
        if(dataUrl.length > MAX_PHOTO_BYTES){
          dataUrl = resizeToJpeg(img, 1200, 0.7); // still too big — shrink further rather than reject
        }
        state.photos.push(dataUrl);
        renderPhotoGrid();
      } catch(err){
        alert("Couldn't read " + file.name + " — try a different photo.");
      }
    }
    e.target.value = '';
  }

  function removePhoto(i){
    state.photos.splice(i, 1);
    renderPhotoGrid();
  }

  function renderPhotoGrid(){
    const grid = document.getElementById('photoGrid');
    grid.innerHTML = '';
    state.photos.forEach((src, i) => {
      const d = document.createElement('div');
      d.className = 'photo-thumb';
      const img = document.createElement('img');
      img.src = src;
      const rm = document.createElement('div');
      rm.className = 'rm';
      rm.textContent = '✕';
      rm.onclick = (ev) => { ev.stopPropagation(); removePhoto(i); };
      d.appendChild(img);
      d.appendChild(rm);
      grid.appendChild(d);
    });
    const box = document.getElementById('uploadBox');
    const full = state.photos.length >= MAX_PHOTOS;
    box.classList.toggle('full', full);
    document.getElementById('uploadT1').textContent = full ? 'Photo limit reached' : 'Tap to add photos';
  }

  function toStep(n){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
    const progress = document.getElementById('progressBar');
    if(typeof n === 'number'){
      progress.style.display='flex';
      document.getElementById('progressLabel').textContent = `Step ${n} of 5`;
      const dots = document.getElementById('progressBalloons');
      dots.innerHTML='';
      for(let i=1;i<=5;i++){
        const s=document.createElement('span'); s.textContent='🎈'; if(i<=n) s.classList.add('on'); dots.appendChild(s);
      }
    } else {
      progress.style.display='none';
    }
    if(n === 'balloons') renderPopBalloons();
    window.scrollTo(0,0);
  }

  document.getElementById('theirName').addEventListener('input', e=>state.theirName = e.target.value.trim());
  document.getElementById('yourName').addEventListener('input', e=>state.yourName = e.target.value.trim());
  document.getElementById('age').addEventListener('input', e=>state.age = e.target.value.trim());

  const cakeGrid = document.getElementById('cakeGrid');
  CAKES.forEach(c=>{
    const d = document.createElement('div');
    d.className='cake-card' + (c.key===state.cake ? ' selected':'');
    d.innerHTML = `<span class="emoji">${c.emoji}</span><div class="name">${c.label}</div><div class="desc">${c.desc}</div>`;
    d.onclick = () => { state.cake = c.key; cakeGrid.querySelectorAll('.cake-card').forEach(x=>x.classList.remove('selected')); d.classList.add('selected'); };
    cakeGrid.appendChild(d);
  });

  const balloonWrap = document.getElementById('balloonFields');
  for(let i=0;i<5;i++){
    const row = document.createElement('div');
    row.className='balloon-field';
    row.innerHTML = `<span class="bemoji">🎈</span><input type="text" maxlength="50" data-i="${i}" placeholder="e.g. a reason they're loved"><span class="count">0/50</span>`;
    const input = row.querySelector('input');
    const count = row.querySelector('.count');
    input.addEventListener('input', e=>{ state.balloons[i] = e.target.value; count.textContent = e.target.value.length + '/50'; });
    balloonWrap.appendChild(row);
  }
  const sparkGrid = document.getElementById('sparkGrid');
  SPARKS.forEach(s=>{
    const chip = document.createElement('div');
    chip.className='spark-chip';
    chip.textContent = '+ ' + s;
    chip.onclick = () => {
      const idx = state.balloons.findIndex(b=>!b);
      if(idx === -1) return;
      state.balloons[idx] = s;
      const input = balloonWrap.querySelector(`input[data-i="${idx}"]`);
      input.value = s;
      input.parentElement.querySelector('.count').textContent = s.length + '/50';
    };
    sparkGrid.appendChild(chip);
  });

  function updateCount(){ document.getElementById('charN').textContent = document.getElementById('letterMsg').value.length; }
  function setTmplLang(lang){
    state.tmplLang = lang;
    document.getElementById('tHinglish').classList.toggle('active', lang==='hi');
    document.getElementById('tEnglish').classList.toggle('active', lang==='en');
    renderTemplates();
  }
  function renderTemplates(){
    const wrap = document.getElementById('tmplList');
    wrap.innerHTML='';
    TEMPLATES[state.tmplLang].forEach(t=>{
      const d = document.createElement('div');
      d.className='tmpl-item';
      d.textContent = t;
      d.onclick = ()=>{ document.getElementById('letterMsg').value = t; updateCount(); };
      wrap.appendChild(d);
    });
  }
  renderTemplates();

  function startGenerating(){
    const msg = document.getElementById('letterMsg').value.trim();
    state.message = msg || "Another year of you is the best news of my year. Happy birthday.";
    const their = state.theirName || 'them';
    const cakeLabel = CAKES.find(c=>c.key===state.cake).label;
    const filledBalloons = state.balloons.filter(Boolean).length || 3;
    const items = [
      `🎂 Baking the ${cakeLabel}`,
      state.age ? `🎈 Lighting candles for turning ${state.age}` : '🕯️ Lighting the candles',
      `🎈 Filling ${filledBalloons} balloon${filledBalloons===1?'':'s'} with your words`,
      '💌 Sealing your letter inside the card',
      `✍️ Signed with love — ${state.yourName || 'you'}`,
    ];
    toStep('gen');
    document.getElementById('genTitle').textContent = `Crafting ${their}'s surprise...`;
    const list = document.getElementById('genList');
    list.innerHTML='';
    items.forEach(t=>{ const d = document.createElement('div'); d.className='item'; d.innerHTML = `<span class="check">✓</span><span>${t}</span>`; list.appendChild(d); });
    const fill = document.getElementById('genBarFill');
    fill.style.width='0%';
    let i=0;
    const rows = list.querySelectorAll('.item');
    const tick = setInterval(()=>{
      if(i < rows.length){ rows[i].classList.add('done'); fill.style.width = Math.round(((i+1)/rows.length)*100) + '%'; i++; }
      else { clearInterval(tick); setTimeout(showRecipientPreview, 500); }
    }, 550);
  }

  function showRecipientPreview(){
    const their = state.theirName || 'them';
    const you = state.yourName || 'someone who loves you';
    document.getElementById('titleName').textContent = their + '!';
    document.getElementById('envTitle').textContent = `One last thing, ${their}...`;
    document.getElementById('envSub').textContent = `${you} wrote you a letter.`;
    document.getElementById('letterDear').textContent = `Dear ${their},`;
    document.getElementById('letterSign').innerHTML = `With all my love,<br>— ${you}`;
    document.getElementById('closingName').textContent = their.toUpperCase() + '!';
    document.getElementById('closingFrom').textContent = `Made with love, just for you — ${you} 💛`;
    document.getElementById('sendBtn').textContent = `Send It to ${their} 🎁`;
    document.getElementById('pwTitle').textContent = `${their}'s surprise is ready 🎉`;
    document.getElementById('pwSub').textContent = `A private link, made only for ${their}`;
    document.getElementById('reasonsList').innerHTML='';
    document.getElementById('andMore').style.display='none';
    document.getElementById('keepGoingBtn').style.display='none';
    document.getElementById('letterMsgOut').textContent='';
    document.getElementById('letterSign').style.display='none';
    document.getElementById('letterContinueBtn').style.display='none';
    toStep('title');
  }

  function renderPopBalloons(){
    const field = document.getElementById('popField');
    field.innerHTML='';
    const filled = state.balloons.map((b,i)=>({text:b,i})).filter(b=>b.text);
    const list = filled.length ? filled : [{text:'This whole surprise, honestly', i:0}];
    document.getElementById('balloonSub').textContent = `${list.length} balloon${list.length===1?'':'s'}. Each one holds a reason they're loved. Pop them all 🎈`;
    list.forEach((b,idx)=>{
      const d = document.createElement('div');
      d.className='balloon';
      d.style.background = BALLOON_COLORS[idx % BALLOON_COLORS.length];
      d.dataset.i = idx;
      d.onclick = () => popBalloon(d, b.text, idx);
      field.appendChild(d);
    });
    field.dataset.total = list.length;
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
    const total = parseInt(document.getElementById('popField').dataset.total, 10);
    if(reasons.children.length >= total){
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
    const msg = state.message;
    let i = 0;
    const typer = setInterval(()=>{
      out.textContent = msg.slice(0, i+1);
      i++;
      if(i >= msg.length){
        clearInterval(typer);
        sign.style.display='block';
        cont.style.display='block';
      }
    }, 18);
  }

  let createdSurprise = null;

  async function createAndOpenPaywall(){
    const errBox = document.getElementById('createErr');
    errBox.style.display = 'none';
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.textContent = 'Saving...';

    try{
      const res = await fetch(REST_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json; charset=utf-8' },
        body: JSON.stringify({
          their_name: state.theirName || 'Them',
          your_name: state.yourName || 'You',
          experience_type: 'birthday',
          message: state.message,
          content: { cake: state.cake, age: state.age, balloons: state.balloons.filter(Boolean), photos: state.photos },
        }),
      });
      if(!res.ok){ throw new Error(`Server returned ${res.status}`); }
      createdSurprise = await res.json();
      document.getElementById('shareLink').textContent = createdSurprise.url;
      openPaywall();
    } catch(err){
      errBox.textContent = "Couldn't save your surprise — " + err.message + '. Try again in a moment.';
      errBox.style.display = 'block';
    } finally {
      sendBtn.disabled = false;
      sendBtn.textContent = `Send It to ${state.theirName || 'Them'} 🎁`;
    }
  }

  let countdownTimer;
  function openPaywall(){
    document.getElementById('paywallOverlay').classList.add('show');
    let secs = 600;
    clearInterval(countdownTimer);
    countdownTimer = setInterval(()=>{
      secs--;
      const m = String(Math.floor(secs/60)).padStart(2,'0');
      const s = String(secs%60).padStart(2,'0');
      document.getElementById('countdown').textContent = `${m}:${s}`;
      if(secs<=0) clearInterval(countdownTimer);
    }, 1000);
  }
  function closePaywall(){
    document.getElementById('paywallOverlay').classList.remove('show');
    clearInterval(countdownTimer);
  }
  async function unlockPay(){
    const payErr = document.getElementById('payErr');
    payErr.style.display = 'none';
    const btn = document.getElementById('unlockBtn');
    const originalLabel = btn.textContent;

    if(!createdSurprise || !createdSurprise.id){
      payErr.textContent = "Something went wrong — please close this and try sending again.";
      payErr.style.display = 'block';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Preparing payment...';

    try{
      const orderRes = await fetch(`${REST_URL}/${createdSurprise.id}/order`, { method: 'POST' });
      const order = await orderRes.json();
      if(!orderRes.ok){ throw new Error(order.message || `Server returned ${orderRes.status}`); }

      const rzp = new Razorpay({
        key: order.key_id,
        amount: order.amount,
        currency: order.currency,
        order_id: order.order_id,
        name: 'Blush Moments',
        description: `Surprise for ${state.theirName || 'them'}`,
        prefill: { name: state.yourName || '' },
        theme: { color: '#c17a3f' },
        handler: async function(response){
          try{
            const verifyRes = await fetch(`${REST_URL}/${createdSurprise.id}/verify`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json; charset=utf-8' },
              body: JSON.stringify({
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
              }),
            });
            const verify = await verifyRes.json();
            if(!verifyRes.ok){ throw new Error(verify.message || 'Payment could not be verified.'); }
            closePaywall();
            toStep('share');
          } catch(err){
            payErr.textContent = "Payment went through but couldn't be verified — " + err.message + ' Please try again or contact support.';
            payErr.style.display = 'block';
            btn.disabled = false;
            btn.textContent = originalLabel;
          }
        },
        modal: {
          ondismiss: function(){
            btn.disabled = false;
            btn.textContent = originalLabel;
          }
        }
      });

      rzp.on('payment.failed', function(response){
        payErr.textContent = 'Payment failed — ' + (response.error && response.error.description ? response.error.description : 'please try again.');
        payErr.style.display = 'block';
        btn.disabled = false;
        btn.textContent = originalLabel;
      });

      rzp.open();
    } catch(err){
      payErr.textContent = "Couldn't start payment — " + err.message + '. Try again in a moment.';
      payErr.style.display = 'block';
      btn.disabled = false;
      btn.textContent = originalLabel;
    }
  }
  function copyLink(){
    const link = document.getElementById('shareLink').textContent;
    navigator.clipboard.writeText(link).then(()=>alert('Link copied.'));
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
