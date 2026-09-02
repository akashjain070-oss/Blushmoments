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
  .wordmark{ position:relative; z-index:4; text-align:center; padding:14px 0 0; }
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
  .cake-icon{ width:48px; height:46px; margin:0 auto 8px; position:relative; }
  .cake-icon .flame{ width:6px; height:9px; border-radius:50% 50% 50% 0; background:#ffb238; margin:0 auto; transform:rotate(45deg); box-shadow:0 0 6px rgba(255,178,56,.7); }
  .cake-icon .candle{ width:3px; height:11px; background:#fff8e0; margin:1px auto 0; }
  .cake-icon .tier-top{ width:26px; height:14px; border-radius:6px 6px 2px 2px; margin:0 auto; position:relative; }
  .cake-icon .tier-top::after{ content:''; position:absolute; inset:0 0 auto 0; height:4px; background:rgba(255,255,255,.55); border-radius:6px 6px 0 0; }
  .cake-icon .tier-bottom{ width:44px; height:18px; border-radius:4px; margin-top:-2px; position:relative; }
  .cake-icon .tier-bottom::after{ content:''; position:absolute; inset:0 0 auto 0; height:5px; background:rgba(255,255,255,.5); border-radius:4px 4px 0 0; }
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
  .photo-item{ width:72px; }
  .photo-thumb{ position:relative; width:72px; height:72px; border-radius:12px; overflow:hidden; box-shadow:0 4px 10px rgba(193,122,63,.18); }
  .photo-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
  .photo-thumb .rm{ position:absolute; top:3px; right:3px; width:20px; height:20px; border-radius:50%; background:rgba(30,15,10,.65); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.68rem; cursor:pointer; line-height:1; }
  .photo-caption{ width:72px; border:none; border-bottom:1.5px solid #f2e0c8; border-radius:0; background:none; padding:4px 2px; margin:4px 0 0; font-size:.68rem; text-align:center; }
  .photo-caption:focus{ outline:none; border-color:var(--gold); }
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
  .teaser-wrap{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:40px 24px; color:#fff; text-align:center; min-height:60vh; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; }
  .teaser-wrap .big{ font-family:var(--font-display); font-size:2.4rem; font-weight:900; line-height:1.15; letter-spacing:-.01em; }
  .teaser-wrap .sub2{ font-size:.9rem; opacity:.7; margin-top:14px; }
  .teaser-wrap .tap{ font-size:.75rem; opacity:.55; margin-top:20px; letter-spacing:.4px; }
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
  .photos-wrap{ background:linear-gradient(180deg,var(--night),#4a2a4d); border-radius:22px; margin:14px 20px; padding:40px 22px; color:#fff; text-align:center; min-height:55vh; }
  .photos-wrap h2{ font-size:1.3rem; }
  .photos-wrap .sub{ font-size:.82rem; opacity:.7; margin:8px 0 30px; }
  .photo-carousel{ display:flex; gap:16px; overflow-x:auto; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; padding:10px 22px 20px; margin:0 -22px 10px; }
  .photo-carousel::-webkit-scrollbar{ display:none; }
  .photo-carousel-item{ scroll-snap-align:center; flex:0 0 auto; width:180px; }
  .photo-carousel-item img{ width:180px; height:216px; object-fit:cover; border-radius:14px; border:5px solid #fff; box-shadow:0 12px 26px rgba(0,0,0,.35); display:block; }
  .photo-carousel-item .cap{ text-align:center; font-size:.75rem; opacity:.7; margin-top:8px; }
  .photo-dots{ display:flex; justify-content:center; gap:6px; margin-bottom:6px; }
  .photo-dots span{ width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,.25); }
  .photo-dots span.on{ background:var(--gold); }
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
  .tstm-label{ text-align:center; font-size:.7rem; font-weight:700; color:var(--muted); letter-spacing:.3px; margin-bottom:8px; }
  .tstm-track{ display:flex; gap:10px; overflow-x:auto; padding-bottom:14px; margin-bottom:6px; -webkit-overflow-scrolling:touch; }
  .tstm-track::-webkit-scrollbar{ display:none; }
  .tstm-card{ flex:0 0 auto; width:220px; background:#fffaf3; border:1px solid #f2e0c8; border-radius:12px; padding:12px 14px; font-size:.78rem; line-height:1.5; color:var(--ink); }
  .tstm-card b{ display:block; margin-top:6px; color:var(--gold-deep); font-size:.72rem; }
  .price-box{ text-align:center; border:1.5px solid var(--peach-soft); border-radius:16px; padding:16px; margin-bottom:16px; }
  .price-box .old{ text-decoration:line-through; color:var(--muted); font-size:.95rem; margin-right:8px; }
  .price-box .new{ font-size:1.8rem; font-weight:900; color:var(--gold-deep); }
  .price-box .off{ display:inline-block; margin-top:6px; background:#e3f7e8; color:#1a8a3d; font-weight:700; font-size:.75rem; padding:4px 12px; border-radius:20px; }
  .price-box .sub{ font-size:.72rem; color:var(--muted); margin-top:8px; letter-spacing:.3px; }
  .tagline{ text-align:center; font-size:.85rem; color:var(--muted); margin-bottom:16px; font-style:italic; }
  .trust{ display:flex; justify-content:center; gap:10px; font-size:.72rem; color:var(--muted); margin-top:14px; flex-wrap:wrap; }
  .link-note{ text-align:center; font-size:.72rem; color:var(--muted); margin-top:10px; }
  .stub-note{ text-align:center; font-size:.72rem; color:#a06a00; background:#fff3c4; border-radius:10px; padding:10px; margin-top:14px; line-height:1.5; }
  .nudge{ padding:32px 26px 26px; text-align:center; }
  .nudge .emoji{ font-size:2.4rem; margin-bottom:10px; }
  .nudge h3{ font-size:1.25rem; margin-bottom:10px; }
  .nudge p{ color:var(--muted); font-size:.88rem; line-height:1.6; margin-bottom:22px; }
  .nudge .prev-btn{ margin-top:10px; width:100%; }
  .share-icon{ font-size:2.4rem; text-align:center; margin-bottom:6px; }
  .share-title{ text-align:center; font-weight:800; font-size:1.2rem; margin-bottom:4px; }
  .share-sub{ text-align:center; color:var(--muted); font-size:.85rem; margin-bottom:20px; }
  .share-link{ background:#fff3e2; border:1px dashed var(--gold); border-radius:12px; padding:12px 14px; font-size:.8rem; color:var(--gold-deep); word-break:break-all; margin-bottom:16px; }
  .share-actions{ display:flex; flex-direction:column; gap:10px; }
  .share-actions button{ border:none; border-radius:40px; padding:14px; font-weight:700; font-size:.9rem; cursor:pointer; }
  .wa-btn{ background:#25d366; color:#fff; }
  .copy-btn{ background:var(--peach-soft); color:var(--gold-deep); }
  .prev-btn{ background:none; border:1.5px solid var(--gold-deep) !important; color:var(--gold-deep); }
  .step{ display:none; }
  .step.active{ display:block; animation:stepIn .5s var(--spring); }
  #confettiRain{ position:fixed; inset:0; pointer-events:none; z-index:40; overflow:hidden; }
  .sound-toggle{ position:fixed; top:16px; right:16px; z-index:45; width:38px; height:38px; border-radius:50%; border:none; background:rgba(0,0,0,.12); color:var(--ink); font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; }
  .bg-balloons{ position:fixed; inset:0; z-index:3; overflow:hidden; pointer-events:none; }
  .bg-balloon{ position:absolute; bottom:-10vh; will-change:transform; animation:bgBalloonFloat linear infinite; }
  @keyframes bgBalloonFloat{
    0%{ transform:translateY(0) translateX(0) rotate(-4deg); }
    50%{ transform:translateY(-55vh) translateX(16px) rotate(4deg); }
    100%{ transform:translateY(-115vh) translateX(-12px) rotate(-4deg); }
  }
  @media (prefers-reduced-motion: reduce){ .bg-balloon{ animation:none; display:none; } }
  .rain-piece{ position:absolute; top:-40px; font-size:1.6rem; animation:fall linear forwards; }
  @keyframes fall{ to{ transform:translateY(110vh) rotate(200deg); opacity:.2; } }
</style>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>

<div class="bg-balloons" id="bgBalloons" aria-hidden="true"></div>
<div id="confettiRain"></div>
<button class="sound-toggle" id="soundToggle" onclick="toggleSound()" aria-label="Toggle sound">🔊</button>

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
      <label class="field-label">Their birthday <span style="font-weight:400; color:var(--muted);">(optional — unlocks midnight magic)</span></label>
      <div class="row2">
        <select id="birthDay"><option value="">Day —</option></select>
        <select id="birthMonth"><option value="">Month —</option></select>
      </div>
      <input type="text" id="hpField" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;">
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

  <div class="step" data-step="teaser">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="teaser-wrap" onclick="toStep('title')">
      <div class="big">Happy<br>Birthday</div>
      <div class="sub2">to someone worth celebrating</div>
      <div class="tap">tap anywhere to continue</div>
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
      <button class="night-btn" id="keepGoingBtn" style="display:none;" onclick="goAfterBalloons()">Keep going 💛</button>
    </div>
  </div>

  <div class="step" data-step="photos">
    <div class="preview-tag"><span>PREVIEW MODE</span></div>
    <div class="photos-wrap">
      <h2>A walk down memory lane 📸</h2>
      <div class="sub">swipe through</div>
      <div class="photo-carousel" id="photoCarousel"></div>
      <div class="photo-dots" id="photoDots"></div>
      <button class="night-btn" onclick="toStep('envelope')">Keep going 💛</button>
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
      <div class="share-sub" id="shareSub">Payment received — this link is live and ready to share.</div>
      <div class="share-link" id="shareLink"></div>
      <div class="share-actions">
        <button class="wa-btn" onclick="shareOnWhatsApp()">Send on WhatsApp 💬</button>
        <button class="copy-btn" onclick="copyLink()">📋 Copy the link</button>
        <button class="prev-btn" onclick="toStep('teaser')">👁️ See what they'll see</button>
      </div>
      <div class="link-note">📅 Your link and its photos stay live for 90 days</div>
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
      <div class="tstm-label">💬 Our happy customers</div>
      <div class="tstm-track">
        <div class="tstm-card">"Put it together on my lunch break and sent it that evening. He read it twice before he replied." <b>— Ananya</b></div>
        <div class="tstm-card">"Used our trip photos for the birthday page. She screenshotted the whole thing." <b>— Meera</b></div>
        <div class="tstm-card">"The balloon reasons made him tear up. Worth every rupee." <b>— Karan</b></div>
      </div>
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

<div class="overlay" id="nudgeOverlay">
  <div class="paywall">
    <div class="nudge">
      <div class="emoji">🥺</div>
      <h3>Wait — it isn't saved yet</h3>
      <p>The cake, the balloons, your letter — everything you just made lives only on this screen right now. One step secures it forever.</p>
      <button class="primary-btn" onclick="nudgeStay()">Finish the surprise 💛</button>
      <button class="prev-btn" onclick="nudgeLeave()">I'll let it go</button>
    </div>
  </div>
</div>

<script>
  const REST_URL = <?php echo wp_json_encode( $rest_url ); ?>;

  const CAKES = [
    {key:'chocolate', label:'Midnight Chocolate', desc:'Rich, dark & dreamy', top:'#5c3826', bottom:'#7a4a2f'},
    {key:'strawberry', label:'Strawberry Blush', desc:'Soft, sweet & rosy', top:'#f2a4bb', bottom:'#f6c2d3'},
    {key:'vanilla', label:'Vanilla Gold', desc:'Classic, warm & glowing', top:'#f0dfae', bottom:'#f6ecc8'},
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

  const state = { id:null, token:null, theirName:'', yourName:'', age:'', birthDay:'', birthMonth:'', cake:'strawberry', balloons:['','','','',''], tmplLang:'hi', photos:[] };
  const MAX_PHOTOS = 5;
  const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

  // Synthesized with the Web Audio API rather than shipped audio files —
  // the plugin has no audio assets, and a couple of tasteful oscillator
  // tones cover the preview's interaction sounds without adding binary
  // asset management or licensing questions. Mirrors templates/birthday.php.
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
  // preview — actual melody notes rather than an abstract ambient pad,
  // since generic drone tones didn't read as "birthday music" at all.
  // Runs independently of step navigation so it keeps looping across every
  // screen once the creator reaches the preview, until sound is muted.
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

  // Silently creates or updates the draft on the server as the wizard
  // progresses, so an abandoned attempt — and any photos already
  // uploaded — still exists in the database instead of only living in
  // this browser tab. No-ops until step 1's required fields are filled
  // in. Never surfaces errors to the user — this is a background save,
  // not the real submit (that's createAndOpenPaywall).
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
          experience_type: 'birthday',
          message: state.message,
          content: { cake: state.cake, age: state.age, birth_day: state.birthDay, birth_month: state.birthMonth, balloons: state.balloons.filter(Boolean), photos: state.photos.map(p => ({ data: p.src, caption: p.caption })) },
          step: step == null ? '' : String(step),
          website: hp ? hp.value : '',
        }),
      });
      if(res.ok){
        const data = await res.json();
        if(data && data.id) state.id = data.id;
        if(data && data.token) state.token = data.token;
        // Swap in the real uploaded URLs (in memory only — no re-render,
        // so this never disrupts someone actively typing a caption) so the
        // next autosave doesn't re-upload the same photos as brand new
        // files. Captions are preserved from local state, not overwritten,
        // since the user may have edited one after this request was sent.
        if(data && Array.isArray(data.photos) && data.photos.length === state.photos.length){
          data.photos.forEach((p, i) => { if(state.photos[i]) state.photos[i].src = p.url; });
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
        state.photos.push({ src: dataUrl, caption: '' });
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
    state.photos.forEach((photo, i) => {
      const wrap = document.createElement('div');
      wrap.className = 'photo-item';
      const d = document.createElement('div');
      d.className = 'photo-thumb';
      const img = document.createElement('img');
      img.src = photo.src;
      const rm = document.createElement('div');
      rm.className = 'rm';
      rm.textContent = '✕';
      rm.onclick = (ev) => { ev.stopPropagation(); removePhoto(i); };
      d.appendChild(img);
      d.appendChild(rm);
      const cap = document.createElement('input');
      cap.type = 'text';
      cap.className = 'photo-caption';
      cap.maxLength = 40;
      cap.placeholder = 'e.g. Goa, 2023';
      cap.value = photo.caption;
      cap.addEventListener('click', ev => ev.stopPropagation());
      cap.addEventListener('input', e => { state.photos[i].caption = e.target.value; });
      wrap.appendChild(d);
      wrap.appendChild(cap);
      grid.appendChild(wrap);
    });
    const box = document.getElementById('uploadBox');
    const full = state.photos.length >= MAX_PHOTOS;
    box.classList.toggle('full', full);
    document.getElementById('uploadT1').textContent = full ? 'Photo limit reached' : 'Tap to add photos';
  }

  function toStep(n){
    document.querySelectorAll('.step').forEach(s=>s.classList.remove('active'));
    document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
    if(typeof n === 'number') saveDraftIfReady(n);
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
    if(typeof n === 'number' && n >= 2) updateSubtitles();
    if(n === 'balloons') renderPopBalloons();
    if(n === 'photos') renderFairyPhotos();
    if(n === 'closing') confettiBurst();
    window.scrollTo(0,0);
  }

  // Mirrors templates/birthday.php's goAfterBalloons(): the recipient-facing
  // link skips straight to the envelope when no photos were added, and this
  // in-builder preview needs to match that exactly, or a photo-less draft
  // would still show a preview step the real link never shows.
  function goAfterBalloons(){
    toStep(state.photos.length ? 'photos' : 'envelope');
  }

  function renderFairyPhotos(){
    const track = document.getElementById('photoCarousel');
    const dots = document.getElementById('photoDots');
    track.innerHTML = '';
    dots.innerHTML = '';
    state.photos.forEach((photo,i)=>{
      const item = document.createElement('div');
      item.className = 'photo-carousel-item';
      const capHtml = photo.caption ? `<div class="cap">${photo.caption}</div>` : '';
      item.innerHTML = `<img src="${photo.src}" alt="">${capHtml}`;
      track.appendChild(item);
      const dot = document.createElement('span');
      if(i === 0) dot.classList.add('on');
      dots.appendChild(dot);
    });
    if(state.photos.length <= 1) return;
    const dotEls = dots.children;
    track.onscroll = () => {
      const idx = Math.round(track.scrollLeft / (track.firstElementChild.offsetWidth + 16));
      Array.from(dotEls).forEach((d,i)=>d.classList.toggle('on', i === idx));
    };
  }

  document.getElementById('theirName').addEventListener('input', e=>state.theirName = e.target.value.trim());
  document.getElementById('yourName').addEventListener('input', e=>state.yourName = e.target.value.trim());
  document.getElementById('age').addEventListener('input', e=>state.age = e.target.value.trim());

  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const daySelect = document.getElementById('birthDay');
  for(let d=1; d<=31; d++){
    const opt = document.createElement('option');
    opt.value = d; opt.textContent = d;
    daySelect.appendChild(opt);
  }
  const monthSelect = document.getElementById('birthMonth');
  MONTHS.forEach((m,i)=>{
    const opt = document.createElement('option');
    opt.value = i+1; opt.textContent = m;
    monthSelect.appendChild(opt);
  });
  daySelect.addEventListener('change', e=>state.birthDay = e.target.value);
  monthSelect.addEventListener('change', e=>state.birthMonth = e.target.value);

  // Keeps the step 2-5 subtitles in sync with the name typed in step 1 —
  // they're static markup so without this they'd stay stuck on "they/them"
  // even once theirName is known, unlike the recipient-preview strings
  // (showRecipientPreview) which already personalize correctly.
  function updateSubtitles(){
    const their = state.theirName || 'They';
    document.getElementById('cakeSub').textContent = `${their} will light it, wish on it, and cut it.`;
    document.getElementById('memoriesSub').textContent = `Up to 5 photos of ${state.theirName || 'them'}, strung on fairy lights. A caption like "Goa, 2023" makes hearts melt.`;
    document.getElementById('letterSub').textContent = `This is the part ${state.theirName || 'they'} will read twice — and remember forever.`;
  }

  const cakeGrid = document.getElementById('cakeGrid');
  CAKES.forEach(c=>{
    const d = document.createElement('div');
    d.className='cake-card' + (c.key===state.cake ? ' selected':'');
    d.innerHTML = `<div class="cake-icon"><div class="flame"></div><div class="candle"></div><div class="tier-top" style="background:${c.top}"></div><div class="tier-bottom" style="background:${c.bottom}"></div></div><div class="name">${c.label}</div><div class="desc">${c.desc}</div>`;
    d.onclick = () => { state.cake = c.key; cakeGrid.querySelectorAll('.cake-card').forEach(x=>x.classList.remove('selected')); d.classList.add('selected'); };
    cakeGrid.appendChild(d);
  });

  const BALLOON_PLACEHOLDERS = [
    "e.g. You always know what to say",
    "e.g. Your hugs fix everything",
    "e.g. You make everyone feel welcome",
    "e.g. You never give up on people",
    "e.g. Being around you feels like home",
  ];
  const balloonWrap = document.getElementById('balloonFields');
  for(let i=0;i<5;i++){
    const row = document.createElement('div');
    row.className='balloon-field';
    row.innerHTML = `<span class="bemoji">🎈</span><input type="text" maxlength="50" data-i="${i}" placeholder="${BALLOON_PLACEHOLDERS[i]}"><span class="count">0/50</span>`;
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
    initAudio();
    startBackgroundMusic();
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
    toStep('teaser');
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
    initAudio();
    playPop();
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
    initAudio();
    playChime();
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

  // This is the final, must-succeed submit — draft rows already exist from
  // saveDraftIfReady() as of step 1, so this is an update (via state.id),
  // not a fresh create.
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
          experience_type: 'birthday',
          message: state.message,
          content: { cake: state.cake, age: state.age, birth_day: state.birthDay, birth_month: state.birthMonth, balloons: state.balloons.filter(Boolean), photos: state.photos.map(p => ({ data: p.src, caption: p.caption })) },
          step: 'final',
          website: hp ? hp.value : '',
        }),
      });
      if(!res.ok){ throw new Error(`Server returned ${res.status}`); }
      createdSurprise = await res.json();
      state.id = createdSurprise.id;
      if(createdSurprise.token) state.token = createdSurprise.token;
      if(Array.isArray(createdSurprise.photos) && createdSurprise.photos.length === state.photos.length){
        createdSurprise.photos.forEach((p, i) => { if(state.photos[i]) state.photos[i].src = p.url; });
      }
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
  // First close attempt shows a loss-aversion nudge instead of actually
  // closing — mirrors ourmoments.live's paywall exit-intent behavior
  // (intercepting the × button / Escape, not a mouse-leave detector).
  let nudgeShown = false;
  function closePaywall(){
    if(!nudgeShown){
      nudgeShown = true;
      document.getElementById('nudgeOverlay').classList.add('show');
      return;
    }
    actuallyClosePaywall();
  }
  function actuallyClosePaywall(){
    document.getElementById('paywallOverlay').classList.remove('show');
    document.getElementById('nudgeOverlay').classList.remove('show');
    clearInterval(countdownTimer);
  }
  function nudgeStay(){
    document.getElementById('nudgeOverlay').classList.remove('show');
  }
  function nudgeLeave(){
    actuallyClosePaywall();
  }
  document.addEventListener('keydown', function(e){
    if(e.key !== 'Escape') return;
    if(document.getElementById('nudgeOverlay').classList.contains('show')){
      nudgeStay();
    } else if(document.getElementById('paywallOverlay').classList.contains('show')){
      closePaywall();
    }
  });
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
  function shareOnWhatsApp(){
    const link = document.getElementById('shareLink').textContent;
    const their = state.theirName || 'them';
    const text = `A birthday surprise for ${their} 🎂 ${link}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
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
