<?php
/**
 * Builder wizard for the "proposal" experience, served at /create/proposal.
 * $rest_url is injected by Blush_Moments_Builder_View::maybe_render().
 *
 * Ported from proposal-prototype/index.html — the standalone prototype that
 * was already designed, screenshotted against the real reference site, and
 * QA'd. Preview (steps recip-q/celebration/lovecards) stays client-side only,
 * same as the real product. The server is only touched on "Send This to X",
 * which creates the real draft via POST /bm/v1/surprise.
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
<title>Create a Love Surprise — Blush Moments</title>
<link rel="icon" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-32.png' ); ?>" sizes="32x32">
<link rel="apple-touch-icon" href="<?php echo esc_url( BM_PLUGIN_URL . 'assets/favicon-180.png' ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&family=Yellowtail&display=swap" rel="stylesheet">
<style>
  :root{
    --pink-deep:#d1476a; --pink:#e8637d; --pink-soft:#f8e3de;
    --gold:#b8794f; --ink:#3a2620; --muted:#8a6e63;
    --font-display:'Outfit', -apple-system, 'Segoe UI', Roboto, sans-serif;
    --font-script:'Yellowtail', cursive;
    --spring:cubic-bezier(.34,1.56,.64,1);
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html,body{ height:100%; }
  body{
    font-family:-apple-system, 'Segoe UI', Roboto, sans-serif;
    background:linear-gradient(160deg,#faf3ec,#e6d6ea 32%,#f0d6d8 62%,#f7e6de);
    min-height:100vh; overflow-x:hidden; color:var(--ink); position:relative;
  }
  h2, .celeb-title, .recip-name, .share-title, .letter-card .t1{ font-family:var(--font-display); letter-spacing:-.01em; }
  .wordmark{ text-align:center; padding:14px 0 0; }
  .wordmark img{ height:46px; width:auto; }
  @keyframes float{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-6px); } }
  @keyframes shimmer{ 0%{ transform:translateX(-120%) skewX(-15deg); } 100%{ transform:translateX(220%) skewX(-15deg); } }
  @keyframes stepIn{ 0%{ opacity:0; transform:translateY(14px); } 100%{ opacity:1; transform:translateY(0); } }
  @keyframes heartbeat{ 0%,100%{ transform:scale(1); } 15%{ transform:scale(1.15); } 30%{ transform:scale(1); } 45%{ transform:scale(1.15); } 60%{ transform:scale(1); } }
  .stage{ max-width:420px; margin:0 auto; min-height:100vh; position:relative; padding-bottom:40px; }
  .progress{ display:flex; align-items:center; gap:6px; padding:18px 20px 6px; font-size:.72rem; font-weight:700; color:var(--pink-deep); letter-spacing:.3px; }
  .progress .hearts{ display:flex; gap:3px; margin-right:8px; }
  .progress .hearts span{ font-size:.85rem; opacity:.25; }
  .progress .hearts span.on{ opacity:1; }
  .card{ background:#fff; border-radius:22px; margin:14px 20px; padding:28px 24px; box-shadow:0 18px 40px rgba(160,60,50,.18); position:relative; }
  .card .back{ background:none; border:none; color:var(--pink-deep); font-weight:700; font-size:.85rem; cursor:pointer; margin-bottom:6px; padding:0; }
  .card h2{ font-size:1.35rem; text-align:center; margin-top:6px; }
  .card .sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-top:6px; margin-bottom:20px; }
  input[type=text], textarea{ width:100%; border:1.5px solid #f0dbe2; border-radius:12px; padding:14px 16px; font-size:.95rem; margin-bottom:12px; font-family:inherit; background:#fffafb; }
  input[type=text]:focus, textarea:focus{ outline:none; border-color:var(--pink); }
  textarea{ resize:none; height:110px; }
  .primary-btn{ width:100%; border:none; border-radius:40px; padding:15px; background:linear-gradient(135deg,var(--pink-deep),var(--gold)); color:#fff; font-weight:700; font-family:var(--font-display); font-size:.95rem; cursor:pointer; box-shadow:0 10px 26px rgba(209,71,106,.35), 0 0 0 6px rgba(209,71,106,.08); position:relative; overflow:hidden; transition:transform .3s var(--spring), box-shadow .3s ease; }
  .primary-btn::after{ content:''; position:absolute; inset:0; background:linear-gradient(100deg,transparent 40%,rgba(255,255,255,.45) 50%,transparent 60%); transform:translateX(-120%) skewX(-15deg); animation:shimmer 2.8s ease-in-out infinite; animation-delay:1s; }
  .primary-btn:active{ transform:scale(.97); }
  .primary-btn:disabled{ opacity:.5; cursor:not-allowed; }
  .primary-btn:disabled::after{ display:none; }
  .grid2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:6px; }
  .opt-card{ border:1.5px solid #f6e2ea; border-radius:16px; padding:20px 8px; text-align:center; cursor:pointer; background:#fffafc; transition:transform .25s var(--spring), border-color .2s ease, background .2s ease, box-shadow .2s ease; }
  .opt-card .emoji{ font-size:1.6rem; display:block; margin-bottom:8px; }
  .opt-card.selected{ border-color:var(--pink-deep); background:var(--pink-soft); transform:scale(1.03); box-shadow:0 8px 18px rgba(209,71,106,.18); }
  .q-list{ display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
  .q-btn{ text-align:left; border:1.5px solid #f6e2ea; border-radius:14px; padding:14px 16px; cursor:pointer; background:#fffafc; font-size:.92rem; font-weight:600; }
  .q-btn.selected{ border-color:var(--pink-deep); background:var(--pink-soft); color:var(--pink-deep); }
  .charcount{ text-align:right; font-size:.75rem; color:var(--muted); margin:-6px 0 12px; }
  .tmpl-row{ display:flex; align-items:center; gap:10px; margin-bottom:12px; font-size:.8rem; color:var(--muted); flex-wrap:wrap; }
  .tmpl-toggle{ display:flex; gap:6px; }
  .tmpl-toggle button{ border:1.5px solid #f0dbe2; background:#fff; border-radius:20px; padding:5px 14px; font-size:.78rem; font-weight:700; cursor:pointer; color:var(--muted); }
  .tmpl-toggle button.active{ border-color:var(--pink-deep); color:var(--pink-deep); background:var(--pink-soft); }
  .tmpl-list{ display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
  .tmpl-item{ border:1px dashed #f0c9d8; border-radius:12px; padding:10px 12px; font-size:.82rem; color:var(--pink-deep); background:#fff6f9; cursor:pointer; line-height:1.4; }
  .love-box{ border:2px solid var(--pink-deep); border-radius:16px; padding:14px; margin-bottom:14px; }
  .love-box .head{ display:flex; justify-content:space-between; align-items:center; font-weight:700; font-size:.9rem; margin-bottom:10px; }
  .badge{ background:var(--pink-deep); color:#fff; font-size:.68rem; padding:4px 10px; border-radius:20px; font-weight:700; }
  .card-preview{ height:180px; border-radius:12px; background:linear-gradient(135deg,var(--pink),var(--pink-deep)); display:flex; align-items:center; justify-content:center; font-size:2.4rem; }
  .upload-box{ border:1.5px solid #f0dbe2; border-radius:16px; padding:14px; margin-bottom:20px; }
  .upload-box .head{ font-weight:700; font-size:.9rem; margin-bottom:10px; }
  .upload-btn{ width:100%; border:none; border-radius:40px; padding:13px; background:linear-gradient(135deg,var(--pink-deep),var(--gold)); color:#fff; font-weight:700; font-size:.88rem; cursor:pointer; }
  .upload-btn:disabled{ opacity:.6; cursor:default; }
  .photo-grid{ display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px; }
  .photo-thumb{ position:relative; width:68px; height:68px; border-radius:12px; overflow:hidden; box-shadow:0 4px 10px rgba(214,60,110,.18); }
  .photo-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
  .photo-thumb .rm{ position:absolute; top:3px; right:3px; width:20px; height:20px; border-radius:50%; background:rgba(60,15,30,.65); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.68rem; cursor:pointer; line-height:1; }
  .gen-wrap{ text-align:center; padding:60px 30px; }
  .gen-wrap .spool{ font-size:3rem; margin-bottom:18px; display:inline-block; animation:spin 2.4s linear infinite; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  .gen-wrap h2{ font-size:1.3rem; }
  .gen-wrap .sub{ color:var(--muted); font-size:.85rem; margin:8px 0 20px; }
  .gen-bar{ height:6px; background:#ffe1ea; border-radius:6px; overflow:hidden; margin-bottom:22px; }
  .gen-bar-fill{ height:100%; background:linear-gradient(90deg,var(--pink-deep),var(--gold)); width:0%; transition:width .4s ease; }
  .gen-list{ text-align:left; display:flex; flex-direction:column; gap:12px; font-size:.85rem; }
  .gen-list .item{ display:flex; gap:10px; align-items:flex-start; opacity:.35; transition:opacity .3s; }
  .gen-list .item.done{ opacity:1; }
  .gen-list .check{ color:var(--pink-deep); font-weight:900; width:16px; }
  .preview-tag{ text-align:center; margin:0 20px 8px; }
  .preview-tag span{ background:#fff3c4; color:#9c7a00; font-weight:700; font-size:.72rem; padding:6px 16px; border-radius:20px; letter-spacing:.5px; }
  .recip-heart{ font-size:2.8rem; text-align:center; margin-bottom:8px; filter:drop-shadow(0 4px 10px rgba(209,71,106,.4)); animation:heartbeat 2.5s ease-in-out infinite; }
  .recip-name{ text-align:center; font-size:1.6rem; font-weight:800; color:var(--pink-deep); margin-bottom:4px; }
  .recip-q{ text-align:center; font-size:1.15rem; font-weight:700; margin-bottom:26px; }
  .btnrow{ position:relative; display:flex; flex-direction:column; align-items:center; gap:16px; min-height:110px; }
  .yes-btn{ width:80%; border:none; border-radius:40px; padding:16px; background:linear-gradient(135deg,var(--pink-deep),var(--gold)); color:#fff; font-weight:800; font-family:var(--font-display); font-size:1rem; cursor:pointer; box-shadow:0 10px 26px rgba(209,71,106,.35), 0 0 0 6px rgba(209,71,106,.08); transition:transform .3s var(--spring); position:relative; overflow:hidden; }
  .yes-btn::after{ content:''; position:absolute; inset:0; background:linear-gradient(100deg,transparent 40%,rgba(255,255,255,.45) 50%,transparent 60%); transform:translateX(-120%) skewX(-15deg); animation:shimmer 2.8s ease-in-out infinite; }
  .yes-btn.grew{ transform:scale(1.08); }
  .no-btn{ border:1.5px solid #e9d3da; background:#fafafa; color:var(--muted); border-radius:40px; padding:12px 28px; font-weight:700; cursor:pointer; position:absolute; top:76px; transition:top .15s ease, left .15s ease; }
  .taunt{ text-align:center; font-size:.82rem; color:var(--pink-deep); font-weight:700; margin-top:22px; min-height:18px; opacity:0; transition:opacity .25s ease; }
  .taunt.show{ opacity:1; }
  .celeb-title{ text-align:center; font-size:1.4rem; font-weight:800; color:var(--pink-deep); margin:6px 0 4px; }
  .celeb-sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-bottom:18px; }
  .fairy-line{ display:flex; flex-wrap:wrap; gap:18px 12px; justify-content:center; margin-bottom:22px; }
  .fairy-photo{ width:92px; }
  .fairy-photo .bulb{ width:7px; height:7px; border-radius:50%; background:#ffe9a8; margin:0 auto 5px; box-shadow:0 0 8px 3px rgba(255,233,168,.85); }
  .fairy-photo img{ width:100%; height:108px; object-fit:cover; display:block; border-radius:6px; border:4px solid #fff; box-shadow:0 8px 16px rgba(0,0,0,.25); transform:rotate(var(--tilt,0deg)); }
  .letter-card{ background:linear-gradient(160deg,#fff,#fff6f0); border-radius:18px; padding:26px 20px; text-align:center; cursor:pointer; box-shadow:0 10px 30px rgba(160,60,50,.15); }
  .letter-card .env{ font-size:2rem; margin-bottom:10px; }
  .letter-card .t1{ font-weight:800; color:var(--pink-deep); }
  .letter-card .t2{ font-size:.78rem; color:var(--muted); margin-top:4px; }
  .letter-open{ background:#fffaf3; border-radius:18px; padding:24px; text-align:left; }
  .letter-open .dear{ font-weight:800; color:var(--pink-deep); margin-bottom:10px; }
  .letter-open .msg{ line-height:1.6; font-size:.95rem; }
  .letter-open .sign{ text-align:right; margin-top:16px; font-weight:700; color:var(--pink-deep); }
  .next-link{ display:block; text-align:center; margin-top:18px; border:1.5px solid var(--pink-deep); color:var(--pink-deep); border-radius:40px; padding:13px; font-weight:700; cursor:pointer; font-size:.9rem; }
  .lc-track{ display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; padding:6px 0 12px; }
  .lc-track::-webkit-scrollbar{ display:none; }
  .lc-card{ scroll-snap-align:center; flex:0 0 220px; height:260px; border-radius:16px; background:linear-gradient(150deg,var(--pink),var(--gold)); display:flex; flex-direction:column; align-items:center; justify-content:center; font-size:2.6rem; color:#fff; position:relative; overflow:hidden; }
  .lc-card .cap{ position:absolute; bottom:14px; font-size:.85rem; font-weight:800; color:#fff; text-shadow:0 2px 6px rgba(0,0,0,.25); }
  .lc-dots{ display:flex; justify-content:center; gap:6px; margin:6px 0 14px; }
  .lc-dots span{ width:6px; height:6px; border-radius:50%; background:#f3cfdb; }
  .lc-dots span.on{ background:var(--pink-deep); width:16px; border-radius:6px; }
  .lc-caption{ text-align:center; font-size:.85rem; color:var(--muted); margin-bottom:18px; }
  .overlay{ position:fixed; inset:0; background:rgba(30,10,20,.45); display:none; align-items:flex-start; justify-content:center; z-index:50; padding:24px 16px; overflow-y:auto; }
  .overlay.show{ display:flex; }
  .paywall{ background:#fff; border-radius:22px; max-width:380px; width:100%; overflow:hidden; position:relative; margin-top:10px; }
  .paywall .close{ position:absolute; top:14px; right:14px; background:rgba(255,255,255,.85); border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:.9rem; }
  .paywall .head{ background:linear-gradient(135deg,var(--pink-deep),var(--gold)); padding:26px 20px 18px; text-align:center; color:#fff; }
  .paywall .timer{ display:inline-block; background:rgba(255,255,255,.25); border-radius:20px; padding:6px 14px; font-size:.78rem; font-weight:700; margin-bottom:10px; }
  .paywall .head h3{ font-size:1.2rem; }
  .paywall .head p{ font-size:.8rem; opacity:.9; margin-top:4px; }
  .paywall .body{ padding:20px; }
  .price-box{ text-align:center; border:1.5px solid var(--pink-soft); border-radius:16px; padding:16px; margin-bottom:16px; }
  .price-box .old{ text-decoration:line-through; color:var(--muted); font-size:.95rem; margin-right:8px; }
  .price-box .new{ font-size:1.8rem; font-weight:900; color:var(--pink-deep); }
  .price-box .off{ display:inline-block; margin-top:6px; background:#e3f7e8; color:#1a8a3d; font-weight:700; font-size:.75rem; padding:4px 12px; border-radius:20px; }
  .price-box .sub{ font-size:.72rem; color:var(--muted); margin-top:8px; letter-spacing:.3px; }
  .tagline{ text-align:center; font-size:.85rem; color:var(--muted); margin-bottom:16px; font-style:italic; }
  .trust{ display:flex; justify-content:center; gap:10px; font-size:.72rem; color:var(--muted); margin-top:14px; flex-wrap:wrap; }
  .link-note{ text-align:center; font-size:.72rem; color:var(--muted); margin-top:10px; }
  .stub-note{ text-align:center; font-size:.72rem; color:#a06a00; background:#fff3c4; border-radius:10px; padding:10px; margin-top:14px; line-height:1.5; }
  .share-icon{ font-size:2.4rem; text-align:center; margin-bottom:6px; }
  .share-title{ text-align:center; font-weight:800; font-size:1.2rem; margin-bottom:4px; }
  .share-sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-bottom:20px; }
  .share-link{ background:#fbeef2; border:1px dashed var(--pink); border-radius:12px; padding:12px 14px; font-size:.8rem; color:var(--pink-deep); word-break:break-all; margin-bottom:16px; }
  .share-actions{ display:flex; flex-direction:column; gap:10px; }
  .share-actions button{ border:none; border-radius:40px; padding:14px; font-weight:700; font-size:.9rem; cursor:pointer; }
  .copy-btn{ background:var(--pink-soft); color:var(--pink-deep); }
  .prev-btn{ background:none; border:1.5px solid var(--pink-deep) !important; color:var(--pink-deep); }
  .err-box{ background:#fdeaea; color:#b3261e; border-radius:10px; padding:10px 14px; font-size:.82rem; margin-bottom:12px; display:none; }
  .step{ display:none; }
  .step.active{ display:block; animation:stepIn .5s var(--spring); }
  #emojiRain{ position:fixed; inset:0; pointer-events:none; z-index:40; overflow:hidden; }
  .rain-piece{ position:absolute; top:-40px; font-size:1.6rem; animation:fall linear forwards; }
  @keyframes fall{ to{ transform:translateY(110vh) rotate(200deg); opacity:.2; } }
</style>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>

<div id="emojiRain"></div>

<div class="stage">

  <div class="wordmark"><img src="<?php echo esc_url( BM_PLUGIN_URL . 'assets/logo.png' ); ?>" alt="Blush Moments"></div>

  <div class="progress" id="progressBar">
    <div class="hearts" id="progressHearts"></div>
    <span id="progressLabel">Step 1 of 5</span>
  </div>

  <div class="step active" data-step="1">
    <div class="card">
      <h2>Who is this for? 💝</h2>
      <div class="sub">You're about to make someone's day unforgettable 🎀</div>
      <input type="text" id="theirName" placeholder="Their name...">
      <input type="text" id="yourName" placeholder="Your name...">
      <input type="text" id="hpField" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;">
      <button class="primary-btn" onclick="toStep(2)">Continue →</button>
    </div>
  </div>

  <div class="step" data-step="2">
    <div class="card">
      <button class="back" onclick="toStep(1)">← Back</button>
      <h2>Who are they to you?</h2>
      <div class="sub">Pick what fits best</div>
      <div class="grid2" id="relationGrid"></div>
    </div>
  </div>

  <div class="step" data-step="3">
    <div class="card">
      <button class="back" onclick="toStep(2)">← Back</button>
      <h2>Pick a question</h2>
      <div class="sub">Or write your own below</div>
      <div class="q-list" id="questionList"></div>
      <input type="text" id="customQ" placeholder="Or type your own question...">
      <button class="primary-btn" onclick="toStep(4)">Continue →</button>
    </div>
  </div>

  <div class="step" data-step="4">
    <div class="card">
      <button class="back" onclick="toStep(3)">← Back</button>
      <h2>Make it personal 💝</h2>
      <div class="sub">Use GIFs to express, or upload your own photos</div>
      <div class="love-box">
        <div class="head"><span>🎬 Love Cards</span><span class="badge">✓ Selected</span></div>
        <div class="card-preview">🐧💕🐧</div>
        <div class="sub" style="margin:8px 0 0;">Curated for the moment ✨</div>
      </div>
      <div class="upload-box">
        <div class="head">📸 Your Photos</div>
        <div class="photo-grid" id="photoGrid"></div>
        <button class="upload-btn" id="uploadBtn" onclick="triggerPhotoPick()">📷 Tap to upload photos</button>
        <input type="file" id="photoInput" accept="image/*" multiple style="display:none;" onchange="handlePhotoPick(event)">
      </div>
      <button class="primary-btn" onclick="toStep(5)">Continue →</button>
    </div>
  </div>

  <div class="step" data-step="5">
    <div class="card">
      <button class="back" onclick="toStep(4)">← Back</button>
      <h2>The part they'll never forget 💌</h2>
      <div class="sub">A few honest lines. They'll read this at the very end — make it count.</div>
      <textarea id="letterMsg" maxlength="500" placeholder="Write what you never say out loud..." oninput="updateCount()"></textarea>
      <div class="charcount"><span id="charN">0</span> / 500</div>
      <div class="tmpl-row">
        Stuck? Tap one to start with:
        <div class="tmpl-toggle">
          <button id="tHinglish" class="active" onclick="setTmplLang('hi')">Hinglish</button>
          <button id="tEnglish" onclick="setTmplLang('en')">English</button>
        </div>
      </div>
      <div class="tmpl-list" id="tmplList"></div>
      <button class="primary-btn" onclick="startGenerating()">Create the surprise →</button>
    </div>
  </div>

  <div class="step" data-step="gen">
    <div class="card gen-wrap">
      <div class="spool">🧵</div>
      <h2>Almost there...</h2>
      <div class="sub">Something precious is taking shape</div>
      <div class="gen-bar"><div class="gen-bar-fill" id="genBarFill"></div></div>
      <div class="gen-list" id="genList"></div>
    </div>
  </div>

  <div class="step" data-step="recip-q">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="card">
      <div class="recip-heart">💗</div>
      <div class="recip-name" id="recipName">Sam,</div>
      <div class="recip-q" id="recipQ">Will you be my girlfriend?</div>
      <div class="btnrow">
        <button class="yes-btn" id="yesBtn" onclick="sayYes()">Yes 💕</button>
        <button class="no-btn" id="noBtn" onmouseover="dodge()" ontouchstart="dodge()" onclick="dodge()">No 🙈</button>
      </div>
      <div class="taunt" id="taunt"></div>
    </div>
  </div>

  <div class="step" data-step="celebration">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="card">
      <div class="celeb-title" id="celebTitle">Yayy! Sam said YES! 🎉</div>
      <div class="celeb-sub">Knew you'd say yes 💕</div>
      <div class="letter-card" id="letterClosed" onclick="openLetter()">
        <div class="env">💌</div>
        <div class="t1" id="letterClosedTitle">Your letter, sealed for Sam</div>
        <div class="t2">Tap to open it</div>
      </div>
      <div class="letter-open" id="letterOpen" style="display:none;">
        <div class="dear" id="letterDear">Dear Sam,</div>
        <div class="msg" id="letterMsgOut"></div>
        <div class="sign" id="letterSign">— with love, Alex 💗</div>
      </div>
      <div class="next-link" id="toLoveCards" style="display:none;" onclick="toStep('lovecards')">See the memory cards 🎬 →</div>
    </div>
  </div>

  <div class="step" data-step="lovecards">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="card">
      <div class="celeb-title" id="celebTitle2">Yayy! Sam said YES! 🎉</div>
      <div class="celeb-sub">Knew you'd say yes 💕</div>
      <div class="fairy-line" id="fairyLine"></div>
      <div class="lc-track" id="lcTrack"></div>
      <div class="lc-dots" id="lcDots"></div>
      <div class="lc-caption">👉 Jo abhi tumne feel kiya, woh bhi feel karegi — because of you 💕</div>
      <div class="err-box" id="createErr"></div>
      <button class="primary-btn" id="sendBtn" onclick="createAndOpenPaywall()">Send This to Sam →</button>
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
        <button class="prev-btn" onclick="toStep('recip-q')">👁 Preview the recipient view</button>
      </div>
    </div>
  </div>

</div>

<div class="overlay" id="paywallOverlay">
  <div class="paywall">
    <button class="close" onclick="closePaywall()">✕</button>
    <div class="head">
      <div class="timer">⏳ Offer ends in <span id="countdown">10:00</span></div>
      <h3 id="pwTitle">Sam's surprise is ready 🎉</h3>
      <p id="pwSub">A private link, made only for Sam</p>
    </div>
    <div class="body">
      <div class="price-box">
        <div><span class="old">₹499</span><span class="new">₹199</span></div>
        <div class="off">60% OFF 🎉</div>
        <div class="sub">ONE-TIME · NO SUBSCRIPTION</div>
      </div>
      <div class="tagline">"The best gifts are made, not bought 💝"</div>
      <div class="err-box" id="payErr"></div>
      <button class="primary-btn" id="unlockBtn" onclick="unlockPay()">🚩 Pay ₹199 &amp; Send →</button>
      <div class="trust">🔒 Secure &nbsp;·&nbsp; ⚡ Instant &nbsp;·&nbsp; 💜 No ads</div>
      <div class="link-note">📅 Your link stays live for 90 days</div>
    </div>
  </div>
</div>

<script>
  const REST_URL = <?php echo wp_json_encode( $rest_url ); ?>;

  const RELATIONS = [
    {key:'girlfriend', label:'Girlfriend', emoji:'💝'},
    {key:'boyfriend', label:'Boyfriend', emoji:'💝'},
    {key:'fbestie', label:'Female Bestie', emoji:'💜'},
    {key:'mbestie', label:'Male Bestie', emoji:'💙'},
    {key:'brother', label:'Brother', emoji:'🧡'},
    {key:'sister', label:'Sister', emoji:'🌸'},
  ];
  const QUESTIONS = {
    girlfriend: ['💕 Will you be my girlfriend?', '💍 Will you be mine forever?', '🌹 Will you be my valentine?'],
    boyfriend: ['💕 Will you be my boyfriend?', '💍 Will you be mine forever?', '🌹 Will you be my valentine?'],
    fbestie: ['💜 Will you be my ride-or-die?', '✨ Best friends for life?', '🎀 Will you be my person?'],
    mbestie: ['💙 Will you be my ride-or-die?', '✨ Best friends for life?', '🎀 Will you be my person?'],
    brother: ['🧡 Best sibling in the world?', '🎁 Will you always have my back?'],
    sister: ['🌸 Best sibling in the world?', '🎁 Will you always have my back?'],
  };
  const TEMPLATES = {
    hi: [
      "Main ye roz nahi bolta, par sach yahi hai — tum mere din ki sabse aasan cheez ho. Thank you ki tum ho.",
      "Pehli baar jab tum hasi thi, mujhe usi waqt laga tha ki ye yaad rakhne wala din hai. Tum meri favourite ho.",
      "Kuch baatein bolne ka sahi waqt kabhi milta hi nahi, isliye likh raha hoon. Bas itna jaan lo, main kahin nahi ja raha."
    ],
    en: [
      "You make ordinary days feel like something worth remembering. Thank you for being you.",
      "I don't say this enough, but you're the easiest part of my day. Every single time.",
      "There's never a perfect moment to say this out loud, so I'm writing it instead. I'm not going anywhere."
    ]
  };
  const LOVE_CARDS = [
    {emoji:'🐧💕🐧', cap:'my person'},
    {emoji:'🐻❤️🐰', cap:'forever'},
    {emoji:'😻', cap:'jaan'},
    {emoji:'🦋💗', cap:'us'},
  ];

  const state = { id:null, token:null, theirName:'', yourName:'', relation:'girlfriend', question:QUESTIONS.girlfriend[0], tmplLang:'hi', photos:[] };
  const MAX_PHOTOS = 5;
  const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

  // Silently creates or updates the draft on the server as the wizard
  // progresses, so an abandoned attempt — and any photos already
  // uploaded — still exists in the database instead of only living in
  // this browser tab. No-ops until the minimum required fields (from
  // step 1) are filled in. Never surfaces errors to the user — this is
  // a background save, not the real submit (that's createAndOpenPaywall).
  let draftSaveInFlight = false;
  async function saveDraftIfReady(step){
    if(!state.theirName || !state.yourName) return;
    if(draftSaveInFlight) return;
    draftSaveInFlight = true;
    try{
      const hp = document.getElementById('hpField');
      const res = await fetch(REST_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json; charset=utf-8' },
        body: JSON.stringify({
          id: state.id || undefined,
          token: state.token || undefined,
          their_name: state.theirName,
          your_name: state.yourName,
          experience_type: 'proposal',
          relation: state.relation,
          question: state.question,
          message: state.message,
          content: { love_cards: LOVE_CARDS, photos: state.photos },
          step: step == null ? '' : String(step),
          website: hp ? hp.value : '',
        }),
      });
      if(res.ok){
        const data = await res.json();
        if(data && data.id) state.id = data.id;
        if(data && data.token) state.token = data.token;
        // Swap in the real uploaded URLs so the next autosave doesn't
        // re-upload the same photos as brand new files.
        if(data && Array.isArray(data.photos) && data.photos.length === state.photos.length){
          state.photos = data.photos;
        }
      }
    } catch(err){
      // background autosave — swallow silently, never interrupts the wizard
    } finally {
      draftSaveInFlight = false;
    }
  }

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

  // Re-encodes to a resized JPEG client-side regardless of source format —
  // handles iPhone HEIC photos (which a jpeg/png accept filter silently
  // drops from the picker) and normal phone camera JPEGs that routinely
  // blow past a hard size cap on their own.
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
          dataUrl = resizeToJpeg(img, 1200, 0.7);
        }
        state.photos.push(dataUrl);
        renderPhotoGrid();
        saveDraftIfReady(4);
      } catch(err){
        alert("Couldn't read " + file.name + " — try a different photo.");
      }
    }
    e.target.value = '';
  }

  function removePhoto(i){
    state.photos.splice(i, 1);
    renderPhotoGrid();
    saveDraftIfReady(4);
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
    const btn = document.getElementById('uploadBtn');
    const full = state.photos.length >= MAX_PHOTOS;
    btn.disabled = full;
    btn.textContent = full ? '📷 Photo limit reached' : '📷 Tap to upload photos';
  }

  function toStep(n){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
    if(typeof n === 'number') saveDraftIfReady(n);
    const progress = document.getElementById('progressBar');
    if(typeof n === 'number'){
      progress.style.display='flex';
      document.getElementById('progressLabel').textContent = `Step ${n} of 5`;
      const hearts = document.getElementById('progressHearts');
      hearts.innerHTML='';
      for(let i=1;i<=5;i++){
        const s=document.createElement('span'); s.textContent='❤'; if(i<=n) s.classList.add('on'); hearts.appendChild(s);
      }
    } else {
      progress.style.display='none';
    }
    window.scrollTo(0,0);
  }

  document.getElementById('theirName').addEventListener('input', e=>state.theirName = e.target.value.trim());
  document.getElementById('yourName').addEventListener('input', e=>state.yourName = e.target.value.trim());

  const relGrid = document.getElementById('relationGrid');
  RELATIONS.forEach(r=>{
    const d = document.createElement('div');
    d.className='opt-card';
    d.innerHTML = `<span class="emoji">${r.emoji}</span>${r.label}`;
    d.onclick = () => { state.relation = r.key; renderQuestions(); toStep(3); };
    relGrid.appendChild(d);
  });

  function renderQuestions(){
    const list = document.getElementById('questionList');
    list.innerHTML='';
    const qs = QUESTIONS[state.relation];
    state.question = qs[0];
    qs.forEach((q,i)=>{
      const b = document.createElement('div');
      b.className = 'q-btn' + (i===0 ? ' selected' : '');
      b.textContent = q;
      b.onclick = ()=>{
        list.querySelectorAll('.q-btn').forEach(x=>x.classList.remove('selected'));
        b.classList.add('selected');
        state.question = q;
        document.getElementById('customQ').value='';
      };
      list.appendChild(b);
    });
  }
  renderQuestions();
  document.getElementById('customQ').addEventListener('input', e=>{
    if(e.target.value.trim()){
      document.querySelectorAll('#questionList .q-btn').forEach(x=>x.classList.remove('selected'));
      state.question = e.target.value.trim();
    }
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

  const GEN_STEPS = [
    '🎬 Picking the perfect love cards',
    q => `💬 Placing your question: "${q}"`,
    n => `📝 Folding your ${n} words into an envelope`,
    "😅 Teaching the \"No\" button to run away",
    name => `💌 Sealing it with your name, ${name}`,
  ];

  function startGenerating(){
    const msg = document.getElementById('letterMsg').value.trim();
    state.message = msg || "You make ordinary days feel like something worth remembering.";
    const wordCount = state.message.split(/\s+/).filter(Boolean).length;
    const items = [ GEN_STEPS[0], GEN_STEPS[1](state.question), GEN_STEPS[2](wordCount), GEN_STEPS[3], GEN_STEPS[4](state.yourName || 'you') ];
    toStep('gen');
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
    const their = state.theirName || 'Them';
    document.getElementById('recipName').textContent = their + ',';
    document.getElementById('recipQ').textContent = state.question.replace(/^[^\p{L}\p{N}]+\s*/u, '');
    document.getElementById('celebTitle').textContent = `Yayy! ${their} said YES! 🎉`;
    document.getElementById('celebTitle2').textContent = `Yayy! ${their} said YES! 🎉`;
    document.getElementById('letterClosedTitle').textContent = `Your letter, sealed for ${their}`;
    document.getElementById('letterDear').textContent = `Dear ${their},`;
    document.getElementById('letterMsgOut').textContent = state.message;
    document.getElementById('letterSign').textContent = `— with love, ${state.yourName || 'you'} 💗`;
    document.getElementById('sendBtn').textContent = `Send This to ${their} →`;
    document.getElementById('pwTitle').textContent = `${their}'s surprise is ready 🎉`;
    document.getElementById('pwSub').textContent = `A private link, made only for ${their}`;
    document.getElementById('letterOpen').style.display='none';
    document.getElementById('letterClosed').style.display='block';
    document.getElementById('toLoveCards').style.display='none';
    renderLoveCards();

    noClicks = 0;
    const tauntEl = document.getElementById('taunt');
    tauntEl.textContent = TAUNTS[0];
    tauntEl.classList.add('show');
    document.getElementById('yesBtn').textContent = 'Yes 💕';
    const noBtn = document.getElementById('noBtn');
    noBtn.style.left = ''; noBtn.style.top = ''; noBtn.style.transform = ''; noBtn.style.opacity = ''; noBtn.style.pointerEvents = ''; delete noBtn.dataset.gone;

    toStep('recip-q');
  }

  // Mirrors templates/proposal.php's lovecards step, which shows uploaded
  // photos as a fairy-light gallery above the memory cards. state.photos
  // here is a flat array of image src strings (no captions), unlike
  // birthday's {src, caption} objects.
  function renderFairyPhotos(){
    const line = document.getElementById('fairyLine');
    line.innerHTML = '';
    if(!state.photos.length){ line.style.display = 'none'; return; }
    line.style.display = 'flex';
    state.photos.forEach((src,i)=>{
      const tilt = (i % 2 === 0 ? -1 : 1) * (2 + (i % 3));
      const d = document.createElement('div');
      d.className = 'fairy-photo';
      d.innerHTML = `<div class="bulb"></div><img src="${src}" style="--tilt:${tilt}deg" alt="">`;
      line.appendChild(d);
    });
  }

  function renderLoveCards(){
    renderFairyPhotos();
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

  const TAUNTS = ['Are you sure?', 'Wait, think again 😢', "Don't do this to me 💔", 'Please? 🥺', "I'll wait right here..."];
  const YES_EMOJI = ['💕', '🥹', '😭', '😭', '😭'];
  let noClicks = 0;

  function dodge(){
    const btn = document.getElementById('noBtn');
    if (btn.dataset.gone) return;
    noClicks++;
    const idx = Math.min(noClicks, TAUNTS.length - 1);
    const isLast = idx === TAUNTS.length - 1;
    const scale = Math.max(0.55, 1 - noClicks * 0.1);

    btn.style.transition = 'top .15s ease, left .15s ease, transform .3s ease, opacity .35s ease';
    if (isLast) {
      btn.dataset.gone = '1';
      btn.style.transform = `translateX(-50%) scale(${scale * 0.4})`;
      btn.style.opacity = '0';
      btn.style.pointerEvents = 'none';
    } else {
      const row = btn.parentElement.getBoundingClientRect();
      const maxX = row.width/2 - 60;
      const x = (Math.random()*2 - 1) * maxX;
      const y = 30 + Math.random()*55;
      btn.style.left = `calc(50% + ${x}px)`;
      btn.style.top = `${y}px`;
      btn.style.transform = `translateX(-50%) scale(${scale})`;
    }

    const taunt = document.getElementById('taunt');
    taunt.textContent = TAUNTS[idx];
    taunt.classList.add('show');
    const yesBtn = document.getElementById('yesBtn');
    yesBtn.textContent = `Yes ${YES_EMOJI[Math.min(noClicks, YES_EMOJI.length - 1)]}`;
    yesBtn.classList.add('grew');
    setTimeout(()=>yesBtn.classList.remove('grew'), 200);
  }

  function sayYes(){ toStep('celebration'); emojiRain(); }

  function openLetter(){
    document.getElementById('letterClosed').style.display='none';
    document.getElementById('letterOpen').style.display='block';
    document.getElementById('toLoveCards').style.display='block';
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

  // This is the final, must-succeed submit — draft rows already exist from
  // saveDraftIfReady() as of step 1, so this is an update (via state.id),
  // not a fresh create, unlike the old one-shot-at-the-end version.
  let createdSurprise = null;

  async function createAndOpenPaywall(){
    const errBox = document.getElementById('createErr');
    errBox.style.display = 'none';
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.textContent = 'Saving...';

    try{
      const hp = document.getElementById('hpField');
      const res = await fetch(REST_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json; charset=utf-8' },
        body: JSON.stringify({
          id: state.id || undefined,
          token: state.token || undefined,
          their_name: state.theirName || 'Them',
          your_name: state.yourName || 'You',
          experience_type: 'proposal',
          relation: state.relation,
          question: state.question,
          message: state.message,
          content: { love_cards: LOVE_CARDS, photos: state.photos },
          step: 'final',
          website: hp ? hp.value : '',
        }),
      });
      if(!res.ok){ throw new Error(`Server returned ${res.status}`); }
      createdSurprise = await res.json();
      state.id = createdSurprise.id;
      if(createdSurprise.token) state.token = createdSurprise.token;
      if(Array.isArray(createdSurprise.photos) && createdSurprise.photos.length === state.photos.length){
        state.photos = createdSurprise.photos;
      }
      document.getElementById('shareLink').textContent = createdSurprise.url;
      openPaywall();
    } catch(err){
      errBox.textContent = "Couldn't save your surprise — " + err.message + '. Try again in a moment.';
      errBox.style.display = 'block';
    } finally {
      sendBtn.disabled = false;
      sendBtn.textContent = `Send This to ${state.theirName || 'Them'} →`;
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
        theme: { color: '#d1476a' },
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
</script>
</body>
</html>
