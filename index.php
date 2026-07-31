<?php
/**
 * 音乐解析 - 多平台聚合解析页面
 * 配色: #e2c5dd / #aad4f4
 * 支持: 网易云 / 酷我 / 汽水音乐
 * 输入: 歌曲链接 或 复制的分享文本(自动提取链接) 或 纯歌曲ID
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>🎵 音乐解析</title>
<style>
  :root{
    --pink:#e2c5dd;
    --blue:#aad4f4;
    --pink-deep:#c98fb8;
    --blue-deep:#6fa8dc;
    --ink:#4a3a4d;
    --ink-2:#8a7590;
    --card:rgba(255,255,255,.72);
    --card-border:rgba(255,255,255,.85);
    --shadow:0 18px 50px rgba(160,120,170,.28);
    --r:22px;
  }
  *{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
  html,body{height:100%}
  body{
    font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Microsoft YaHei",sans-serif;
    color:var(--ink);
    background:linear-gradient(150deg,#f7e8f1 0%,var(--pink) 30%,#dbe9f8 68%,var(--blue) 100%);
    background-attachment:fixed;
    min-height:100vh;
    padding:34px 16px 60px;
  }
  /* 漂浮音符 */
  .notes{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
  .note{position:absolute;bottom:-60px;font-size:26px;opacity:.22;animation:rise linear infinite;filter:blur(.4px)}
  @keyframes rise{
    0%{transform:translateY(0) rotate(0deg);opacity:0}
    10%{opacity:.25}
    90%{opacity:.18}
    100%{transform:translateY(-110vh) rotate(40deg);opacity:0}
  }
  .wrap{position:relative;z-index:1;max-width:760px;margin:0 auto}

  /* 头部 */
  header{text-align:center;margin-bottom:26px}
  header h1{
    font-size:30px;font-weight:800;letter-spacing:2px;
    background:linear-gradient(90deg,var(--pink-deep),var(--blue-deep));
    -webkit-background-clip:text;background-clip:text;color:transparent;
    text-shadow:0 2px 20px rgba(255,255,255,.6);
  }
  header p{margin-top:8px;font-size:13px;color:var(--ink-2);letter-spacing:1px}

  /* 平台卡片 */
  .platforms{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
  .pf{
    background:var(--card);border:2px solid transparent;border-radius:18px;
    padding:14px 8px 12px;text-align:center;cursor:pointer;
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    box-shadow:0 6px 22px rgba(160,120,170,.16);
    transition:.25s;user-select:none;
  }
  .pf:hover{transform:translateY(-3px)}
  .pf.active{
    border-color:var(--blue-deep);
    background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(255,255,255,.65));
    box-shadow:0 10px 30px rgba(110,160,220,.35);
  }
  .pf .ico{font-size:30px;line-height:1;display:flex;align-items:center;justify-content:center;margin-bottom:7px}
  .pf .ico svg{width:36px;height:36px;display:block}
  .pf .nm{font-size:14px;font-weight:700;color:var(--ink)}
  .pf .ds{font-size:11px;color:var(--ink-2);margin-top:3px;display:block}
  .pf .dot{display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:5px;vertical-align:1px}
  .dot-netease{background:#e74c5a}.dot-kuwo{background:#3aa0ff}.dot-qishui{background:#12d8c8}

  /* 输入区 */
  .input-card{
    background:var(--card);border:1px solid var(--card-border);border-radius:var(--r);
    padding:16px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    box-shadow:var(--shadow);margin-bottom:20px;
  }
  .input-row{display:flex;gap:10px}
  .input-row input{
    flex:1;border:none;outline:none;background:rgba(255,255,255,.75);
    border-radius:14px;padding:14px 16px;font-size:15px;color:var(--ink);
    box-shadow:inset 0 2px 8px rgba(160,120,170,.12);
  }
  .input-row input::placeholder{color:#b9a3bf}
  .btn-parse{
    border:none;outline:none;cursor:pointer;border-radius:14px;padding:0 26px;
    font-size:15px;font-weight:700;color:#fff;letter-spacing:2px;
    background:linear-gradient(135deg,var(--pink-deep),var(--blue-deep));
    box-shadow:0 8px 24px rgba(190,120,180,.4);
    transition:.25s;white-space:nowrap;
  }
  .btn-parse:hover{transform:translateY(-2px);box-shadow:0 12px 30px rgba(190,120,180,.5)}
  .btn-parse:active{transform:scale(.97)}
  .btn-parse:disabled{opacity:.6;cursor:not-allowed;transform:none}
  .hint{margin-top:10px;font-size:12px;color:var(--ink-2);line-height:1.7}
  .hint b{color:var(--pink-deep)}

  /* 加载 */
  .loading{display:none;text-align:center;padding:44px 0}
  .loading.show{display:block}
  .spinner{
    width:44px;height:44px;margin:0 auto 14px;border-radius:50%;
    border:4px solid rgba(255,255,255,.8);border-top-color:var(--pink-deep);
    animation:spin 1s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg)}}
  .loading span{color:var(--ink-2);font-size:14px}

  /* 错误 */
  .error{
    display:none;background:rgba(255,255,255,.8);border-radius:var(--r);
    padding:18px 20px;margin-bottom:20px;backdrop-filter:blur(14px);
    box-shadow:var(--shadow);border-left:5px solid #e74c5a;
  }
  .error.show{display:block}
  .error .t{font-weight:700;color:#d6455a;font-size:15px;margin-bottom:4px}
  .error .m{font-size:13px;color:var(--ink-2);word-break:break-all}

  /* 结果 */
  .result{display:none}
  .result.show{display:block}

  /* 播放卡片 */
  .player{
    background:var(--card);border:1px solid var(--card-border);border-radius:var(--r);
    padding:22px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    box-shadow:var(--shadow);margin-bottom:20px;
    display:flex;gap:20px;flex-wrap:wrap;
  }
  .cover-wrap{position:relative;flex:0 0 auto}
  .cover{
    width:150px;height:150px;border-radius:20px;object-fit:cover;
    box-shadow:0 12px 30px rgba(160,110,170,.35);background:#eee;
  }
  .cover-mask{
    position:absolute;inset:0;border-radius:20px;
    background:linear-gradient(160deg,rgba(255,255,255,.18),rgba(255,255,255,0));
    pointer-events:none;
  }
  .p-info{flex:1;min-width:230px;display:flex;flex-direction:column;justify-content:center}
  .p-name{font-size:21px;font-weight:800;line-height:1.4;margin-bottom:6px;word-break:break-all}
  .p-artist{font-size:14px;color:var(--ink-2);margin-bottom:2px}
  .p-album{font-size:13px;color:var(--ink-2);margin-bottom:12px}
  .p-meta{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
  .tag{
    font-size:11px;padding:3px 10px;border-radius:20px;
    background:linear-gradient(135deg,rgba(226,197,221,.55),rgba(170,212,244,.55));
    color:var(--ink);font-weight:600;
  }
  .p-actions{display:flex;gap:10px;flex-wrap:wrap}
  .btn{
    display:inline-flex;align-items:center;gap:6px;border:none;outline:none;cursor:pointer;
    padding:10px 18px;border-radius:12px;font-size:13px;font-weight:700;
    background:rgba(255,255,255,.85);color:var(--ink);
    box-shadow:0 4px 14px rgba(160,120,170,.2);transition:.2s;text-decoration:none;
  }
  .btn:hover{transform:translateY(-2px)}
  .btn-primary{background:linear-gradient(135deg,var(--pink-deep),var(--blue-deep));color:#fff}
  audio{width:100%;margin-top:16px;height:44px;border-radius:12px;display:block}

  /* 歌词卡片 */
  .lyrics-card{
    background:var(--card);border:1px solid var(--card-border);border-radius:var(--r);
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden;
  }
  .tabs{display:flex;background:rgba(255,255,255,.55)}
  .tab{
    flex:1;border:none;outline:none;cursor:pointer;background:transparent;
    padding:14px 0;font-size:14px;font-weight:700;color:var(--ink-2);
    border-bottom:3px solid transparent;transition:.2s;font-family:inherit;
  }
  .tab.active{color:var(--pink-deep);border-bottom-color:var(--pink-deep);background:rgba(255,255,255,.5)}
  .lyrics-body{position:relative;height:320px;overflow:hidden}
  .scroll-lyrics{
    height:100%;overflow-y:auto;padding:140px 24px;scroll-behavior:smooth;
    -ms-overflow-style:none;scrollbar-width:none;
  }
  .scroll-lyrics::-webkit-scrollbar{display:none}
  .l-line{
    text-align:center;font-size:15px;color:var(--ink-2);padding:9px 0;
    transition:.25s;line-height:1.5;word-break:break-all;
  }
  .l-line.active{
    color:var(--ink);font-size:18px;font-weight:800;
    text-shadow:0 0 22px rgba(201,143,184,.5);
    transform:scale(1.02);
  }
  .l-empty{text-align:center;color:#b9a3bf;padding:60px 20px;font-size:13px}
  .full-lyrics{
    display:none;height:100%;overflow-y:auto;padding:20px 24px;
    font-size:14px;line-height:2;color:var(--ink-2);white-space:pre-wrap;word-break:break-all;
  }
  .full-lyrics.show{display:block}
  .lyrics-body.empty .scroll-lyrics,.lyrics-body.empty .full-lyrics{display:none}

  /* JSON */
  .json-card{
    background:rgba(52,42,66,.92);border-radius:var(--r);
    box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px;
  }
  .json-head{
    display:flex;align-items:center;justify-content:space-between;
    padding:13px 18px;background:rgba(255,255,255,.06);
  }
  .json-head .t{color:#e6d8ef;font-size:13px;font-weight:700;letter-spacing:1px}
  .json-head .btn{
    padding:6px 14px;font-size:12px;background:rgba(255,255,255,.12);color:#e6d8ef;
    box-shadow:none;
  }
  .json-head .btn:hover{background:rgba(255,255,255,.22)}
  pre.code{
    padding:18px;overflow-x:auto;font-size:12.5px;line-height:1.65;
    font-family:"SF Mono",Consolas,Menlo,monospace;
    color:#e8e0f0;max-height:420px;overflow-y:auto;
  }
  .jk{color:#e8a5d8}         /* key 粉紫 */
  .js{color:#8fd8a8}         /* string 绿 */
  .jn{color:#ffb86c}         /* number 橙 */
  .jb{color:#7cc4ff}         /* boolean 蓝 */
  .jp{color:#9d93b8}         /* 标点 */

  /* toast */
  .toast{
    position:fixed;left:50%;bottom:36px;transform:translateX(-50%) translateY(20px);
    background:rgba(60,46,66,.92);color:#fff;padding:12px 22px;border-radius:30px;
    font-size:13px;opacity:0;pointer-events:none;transition:.3s;z-index:99;
    backdrop-filter:blur(8px);box-shadow:0 10px 30px rgba(0,0,0,.2);
  }
  .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

  footer{text-align:center;font-size:12px;color:var(--ink-2);margin-top:26px;opacity:.8}

  @media(max-width:520px){
    body{padding:22px 12px 48px}
    .cover{width:120px;height:120px}
    .p-name{font-size:18px}
    .platforms{gap:8px}
    .pf .ico{font-size:24px}
    .input-row{flex-direction:column}
    .btn-parse{padding:13px 0}
  }
</style>
</head>
<body>

<div class="notes" id="notes"></div>

<div class="wrap">
  <header>
    <h1>🎵 音乐解析</h1>
    <p>粘贴歌曲链接 / 分享文本 / 歌曲ID · 自动识别平台</p>
  </header>

  <!-- 平台选择 -->
  <div class="platforms">
    <div class="pf active" data-pf="netease" onclick="selectPf('netease')">
      <span class="ico"><svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M627.086668 5.114963c28.132297-7.672445 58.822075-7.672445 86.954372 0 33.24726 7.672445 63.937038 23.017334 89.511853 43.477186 10.229926 7.672445 17.902371 15.344889 23.017334 28.132297 7.672445 17.902371 5.114963 38.362223-5.114963 53.707112-7.672445 12.787408-23.017334 23.017334-40.919704 25.574815-12.787408 2.557482-25.574815 0-38.362223-7.672445-5.114963-2.557482-10.229926-10.229926-17.902371-12.787407-17.902371-10.229926-35.804741-20.459852-56.264593-17.902371-15.344889 0-28.132297 7.672445-35.804742 17.902371-10.229926 10.229926-12.787408 23.017334-10.229926 35.804741 7.672445 25.574815 12.787408 53.707112 20.459853 79.281927 51.14963 2.557482 99.741779 15.344889 143.218965 40.919704 40.919704 25.574815 79.281927 58.822075 109.971705 97.184298 25.574815 33.24726 46.034667 71.609483 56.264593 112.529187 12.787408 43.477186 17.902371 89.511853 12.787408 132.989039-2.557482 38.362223-10.229926 74.166964-23.017334 109.971705-33.24726 84.39689-92.069335 161.121336-171.351261 209.713485-56.264593 35.804741-122.759113 58.822075-189.253633 66.49452-46.034667 5.114963-92.069335 5.114963-138.104002-2.557482-94.626816-15.344889-181.581188-61.379556-250.633189-130.431558-66.49452-66.49452-112.529187-153.448891-132.989039-245.518225-7.672445-69.052001-7.672445-138.104002 7.672445-207.156004 17.902371-81.839409 61.379556-161.121336 117.644149-222.500892 48.592149-51.14963 107.414224-89.511853 171.351262-117.64415 7.672445-2.557482 12.787408-5.114963 20.459852-7.672444 15.344889-2.557482 30.689778 0 43.477186 10.229926 17.902371 12.787408 25.574815 33.24726 23.017334 53.707112-2.557482 20.459852-17.902371 38.362223-35.804741 46.034667-63.937038 25.574815-122.759113 69.052001-163.678818 122.759113C205.102218 373.392302 179.527402 432.214377 171.854958 493.593933c-7.672445 61.379556 0 122.759113 20.459852 181.581188 30.689778 84.39689 94.626816 156.006373 173.908743 196.926077 48.592149 25.574815 102.299261 38.362223 156.006373 38.362223 43.477186 0 89.511853-7.672445 130.431558-23.017334 35.804741-12.787408 71.609483-33.24726 99.741779-58.822074 28.132297-23.017334 51.14963-53.707112 66.494519-84.396891 7.672445-15.344889 17.902371-33.24726 20.459853-51.14963 15.344889-51.14963 17.902371-107.414224 2.557481-158.563854-12.787408-43.477186-38.362223-81.839409-71.609482-109.971706-15.344889-12.787408-30.689778-25.574815-48.592149-35.804741-15.344889-7.672445-30.689778-15.344889-48.592149-17.902371 12.787408 46.034667 23.017334 92.069335 35.804741 135.546521 2.557482 10.229926 5.114963 23.017334 5.114963 33.24726 2.557482 46.034667-15.344889 94.626816-46.034667 130.431557-28.132297 33.24726-69.052001 58.822075-112.529187 66.49452-46.034667 10.229926-97.184298 0-138.104002-25.574815-38.362223-25.574815-66.49452-63.937038-81.839409-104.856743-7.672445-23.017334-12.787408-48.592149-12.787407-74.166964-2.557482-56.264593 12.787408-109.971705 43.477185-156.006373 35.804741-53.707112 94.626816-92.069335 158.563855-109.971705-5.114963-17.902371-10.229926-35.804741-12.787408-53.707112-12.787408-38.362223-10.229926-81.839409 7.672445-115.086668 10.229926-20.459852 23.017334-38.362223 40.919704-51.149631C583.609483 25.574815 604.069335 12.787408 627.086668 5.114963m-148.333928 414.312006c-17.902371 17.902371-28.132297 40.919704-33.24726 63.937038-5.114963 20.459852-5.114963 43.477186 0 66.49452 5.114963 23.017334 17.902371 46.034667 38.362223 61.379556 15.344889 10.229926 35.804741 15.344889 56.264594 10.229926 35.804741-5.114963 63.937038-38.362223 63.937038-74.166964-2.557482-7.672445-2.557482-17.902371-5.114963-25.574815-12.787408-48.592149-25.574815-99.741779-38.362223-148.333928-30.689778 7.672445-58.822075 23.017334-81.839409 46.034667z" fill="#E72D2C"></path></svg></span>
      <span class="nm"><span class="dot dot-netease"></span>网易云音乐</span>
      <span class="ds">163music</span>
    </div>
    <div class="pf" data-pf="kuwo" onclick="selectPf('kuwo')">
      <span class="ico"><svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M919.04 824.32s-384 138.24-478.72 161.28c0-7.68-12.8-519.68-12.8-519.68s2.56 0 20.48-5.12c33.28-10.24 401.92-94.72 465.92-110.08 5.12 23.04 5.12 473.6 5.12 473.6z m-263.68-268.8l-5.12-2.56V524.8c-2.56-33.28-25.6-53.76-56.32-51.2-30.72 2.56-56.32 30.72-56.32 66.56 0 51.2 0 102.4 2.56 151.04 2.56 38.4 0 76.8 2.56 117.76 0 43.52 25.6 66.56 61.44 61.44 28.16-2.56 51.2-28.16 51.2-58.88v-51.2c0-20.48 0-23.04 20.48-10.24 33.28 17.92 64 38.4 97.28 53.76 40.96 20.48 84.48-15.36 79.36-58.88-2.56-25.6-23.04-33.28-40.96-43.52l-69.12-38.4c-15.36-7.68-15.36-17.92-2.56-28.16 15.36-17.92 33.28-35.84 48.64-53.76 15.36-17.92 33.28-35.84 46.08-53.76 23.04-33.28 7.68-71.68-30.72-81.92-28.16-7.68-48.64 5.12-66.56 25.6-30.72 25.6-56.32 56.32-81.92 84.48z" fill="#FDF09F"></path><path d="M256 632.32s30.72-5.12 35.84-12.8c33.28-38.4 66.56-74.24 99.84-112.64 7.68-7.68 23.04-35.84 33.28-43.52 10.24-7.68 7.68 0 7.68 10.24 0 7.68 5.12 450.56 7.68 506.88C432.64 985.6 192 796.16 192 796.16s-15.36-202.24-15.36-207.36c2.56-10.24 17.92 7.68 25.6 10.24 17.92 10.24 35.84 23.04 53.76 33.28z m15.36-235.52c-25.6-15.36-53.76-23.04-79.36-33.28-7.68-2.56-17.92-2.56-20.48-15.36 56.32-10.24 110.08-20.48 166.4-28.16 46.08-7.68 89.6-17.92 135.68-23.04 0 25.6 5.12 51.2 28.16 61.44 35.84 15.36 69.12 10.24 99.84-10.24 25.6-17.92 38.4-46.08 35.84-79.36l17.92-2.56c0 28.16 0 120.32-2.56 143.36-46.08 12.8-212.48 51.2-215.04 51.2-51.2-23.04-115.2-43.52-166.4-64z" fill="#FCB22D"></path><path d="M634.88 271.36c5.12 33.28-7.68 61.44-35.84 79.36-30.72 23.04-66.56 28.16-99.84 10.24-25.6-12.8-30.72-35.84-28.16-61.44 12.8-40.96 43.52-66.56 87.04-74.24 12.8-2.56 5.12-7.68 0-12.8-23.04-23.04-43.52-46.08-66.56-71.68-10.24-10.24-17.92-17.92-25.6-28.16-15.36-17.92-15.36-35.84 2.56-51.2 7.68-7.68 17.92-12.8 28.16-17.92C522.24 33.28 550.4 23.04 570.88 0 576 15.36 576 28.16 576 40.96c0 40.96-10.24 53.76-48.64 61.44-17.92 5.12-15.36 10.24-7.68 23.04 33.28 38.4 64 79.36 97.28 120.32 7.68 7.68 12.8 15.36 17.92 25.6z" fill="#0994CD"></path><path d="M652.8 409.6c0-51.2 2.56-107.52 2.56-143.36 25.6 2.56 202.24 61.44 263.68 79.36 0 7.68-217.6 53.76-266.24 64z" fill="#FA7A33"></path><path d="M309.76 222.72c5.12-38.4 10.24-74.24 15.36-110.08 2.56-28.16 15.36-35.84 43.52-28.16 10.24 2.56 17.92 5.12 28.16 10.24 15.36 10.24 33.28 15.36 51.2 17.92-35.84 43.52-43.52 46.08-94.72 20.48l-20.48 94.72c-2.56 10.24-5.12 23.04-7.68 33.28-7.68 30.72-28.16 46.08-61.44 43.52-33.28-2.56-61.44-25.6-66.56-51.2-5.12-28.16 12.8-51.2 40.96-56.32 25.6-5.12 48.64 2.56 71.68 25.6z" fill="#0994CD"></path><path d="M919.04 445.44v-97.28c20.48 30.72 43.52 64 61.44 97.28h-61.44z" fill="#FCB22D"></path><path d="M655.36 555.52c25.6-30.72 53.76-58.88 79.36-89.6 17.92-20.48 38.4-33.28 66.56-25.6 38.4 10.24 53.76 48.64 30.72 81.92-12.8 20.48-30.72 35.84-46.08 53.76-15.36 17.92-30.72 35.84-48.64 51.2-10.24 12.8-10.24 20.48 2.56 28.16l69.12 38.4c17.92 10.24 38.4 20.48 40.96 43.52 5.12 46.08-38.4 79.36-79.36 58.88-33.28-15.36-66.56-35.84-97.28-53.76-20.48-10.24-20.48-10.24-20.48 10.24v51.2c-2.56 30.72-23.04 56.32-51.2 58.88-38.4 5.12-61.44-17.92-61.44-61.44 0-38.4 0-76.8-2.56-115.2-2.56-51.2-2.56-102.4-2.56-151.04 0-35.84 25.6-64 56.32-66.56 33.28-2.56 56.32 17.92 56.32 51.2v28.16l7.68 7.68z" fill="#FA7A33"></path><path d="M271.36 632.32c-5.12 5.12-10.24 5.12-15.36 0h15.36z" fill="#FAEF8B"></path><path d="M166.4 348.16L33.28 509.44l243.2 135.68L440.32 460.8 166.4 348.16z" fill="#FAEF8B"></path></svg></span>
      <span class="nm"><span class="dot dot-kuwo"></span>酷我音乐</span>
      <span class="ds">kuwo</span>
    </div>
    <div class="pf" data-pf="qishui" onclick="selectPf('qishui')">
      <span class="ico"><svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M213.985 0h605.029l0.062 6.082c-203.683-0.124-407.366 0.869-611.049-0.497L213.985 0zM0 214.047l5.275-5.958c1.8 206.662 1.055 413.386 0.373 620.048L0 822.489V214.047z" opacity=".25"></path><path d="M5.275 208.09C14.212 103.64 103.393 13.963 208.027 5.584c203.683 1.366 407.366 0.373 611.049 0.497 98.614 3.91 184.568 82.665 204.924 177.99v662.931c-17.19 86.264-87.63 156.517-172.653 176.997h-667.4c-92.966-19.425-168.804-100.91-178.3-195.863 0.683-206.662 1.428-413.386-0.372-620.048m403.766-35.809c3.041 10.613 4.159 21.784 8.255 32.086l4.778 2.42-2.482 2.73c5.15 31.465 16.136 61.689 22.714 92.905l0.993 0.373c4.965 23.645 11.73 46.793 17.687 70.19l0.497 0.186c4.716 20.915 10.426 41.581 15.639 62.371-3.91 0.745-11.792 2.172-15.701 2.917-32.334-0.682-64.543 12.598-93.277 27.431-25.694 15.08-48.594 34.754-66.964 58.275-17.377 23.459-31.03 49.71-39.47 77.824 43.318 1.303 87.07-2.979 130.14 2.979-18.742 3.227-37.422 6.826-55.544 12.846l-0.372-0.186c-21.66 5.461-43.256 11.171-64.853 16.757-44.001 11.667-88.126 22.652-132.127 34.319 37.422 7.2 76.582 1.365 114.75 2.917 0.744 9.495 1.551 18.99 2.482 28.486 11.42 71.494 58.027 136.657 123.563 168.122 61.812 31.775 137.836 31.465 200.394 1.8 21.1-10.427 40.835-23.46 57.84-39.843l0.62 0.558c10.985-11.605 21.412-23.831 30.534-36.988l0.31 0.186c9.372-14.212 17.564-29.168 23.832-44.994l0.683 0.683a517.943 517.943 0 0 0 12.288-45.242c-25.383 0.124-50.704-0.31-76.025 0.372-14.025-0.372-27.989-0.186-42.015-0.248 11.047-4.096 22.342-7.261 33.761-9.992 37.92-9.805 75.838-19.549 113.695-29.603 36.554-10.302 74.1-17.253 109.848-30.037-45.739-0.93-91.54 0-137.34-0.31-5.71-28.796-13.468-57.096-20.977-85.458-6.33-24.948-13.033-49.772-19.177-74.783l-0.868-0.372c-5.276-23.46-11.606-46.67-17.626-69.942l-0.682-0.187c-4.469-19.673-9.558-39.222-14.957-58.647 55.544 1.427 111.15 0.372 166.695 0.931-2.607-9.992-5.213-19.984-7.696-29.975l-0.62-0.186c-7.261-30.72-15.702-61.13-23.273-91.788l-0.745 0.186c-3.91-17.501-8.254-34.878-13.095-52.069-33.574 0-67.087-0.31-100.662 0.186-45.366-0.496-90.732-0.434-136.099 0.062-36.43-0.744-72.921-0.186-109.35-0.248z"></path><path d="M409.041 172.28c36.43 0.062 72.922-0.496 109.351 0.248 9.558 8.937 19.922 17.067 31.9 22.528 33.388 16.012 66.342 32.893 99.607 49.215 46.731 25.258 92.408 53.185 142.367 71.68l0.62 0.186c2.483 9.991 5.09 19.983 7.696 29.975-55.544-0.559-111.15 0.496-166.695-0.93 15.888-3.042 32.085-4.655 48.221-5.897-55.172-37.546-119.156-59.578-176.004-94.394-27.244-14.088-53.62-31.775-84.03-38.105l-4.778-2.42c-4.096-10.302-5.214-21.473-8.255-32.086z" fill="#BDFD45"></path><path d="M518.392 172.528c45.367-0.496 90.733-0.558 136.1-0.062 32.147 22.032 68.142 37.795 103.392 54.18 2.607-0.56 7.758-1.738 10.364-2.297l0.745-0.186c7.571 30.658 16.012 61.068 23.273 91.788-49.96-18.495-95.636-46.422-142.367-71.68-33.265-16.322-66.219-33.203-99.608-49.215-11.977-5.46-22.341-13.59-31.899-22.528z" fill="#DEFC46"></path><path d="M654.491 172.466c33.575-0.496 67.088-0.186 100.662-0.186 4.841 17.191 9.185 34.568 13.095 52.07-2.606 0.558-7.757 1.737-10.364 2.295-35.25-16.384-71.245-32.147-103.393-54.179z" fill="#FCFC47"></path><path d="M422.074 206.786c30.41 6.33 56.786 24.017 84.03 38.105 56.848 34.816 120.832 56.848 176.004 94.394-16.136 1.242-32.333 2.855-48.221 5.896 5.4 19.425 10.488 38.974 14.957 58.647-31.713-7.323-57.468-27.927-86.016-42.263-39.967-19.3-78.941-40.712-119.53-58.771l-0.992-0.373c-6.578-31.216-17.563-61.44-22.714-92.904l2.482-2.731z" fill="#98FD44"></path><path d="M443.299 302.794c40.588 18.06 79.562 39.47 119.529 58.771 28.548 14.336 54.303 34.94 86.016 42.263l0.682 0.187c6.02 23.272 12.35 46.483 17.626 69.942-71.867-26.624-133.617-74.535-205.67-100.787l-0.496-0.186c-5.958-23.397-12.722-46.545-17.687-70.19z" fill="#77FD43"></path><path d="M461.483 373.17c72.052 26.252 133.802 74.163 205.669 100.787l0.868 0.372c6.144 25.01 12.847 49.835 19.177 74.783-24.017 3.103-43.442-14.212-63.55-24.39-52.441-31.775-111.088-52.193-162.226-86.264 3.91-0.745 11.791-2.172 15.7-2.917-5.212-20.79-10.922-41.456-15.638-62.37z" fill="#5EFD43"></path><path d="M368.144 465.889c28.734-14.833 60.943-28.113 93.277-27.43 51.138 34.07 109.785 54.488 162.226 86.263 20.108 10.178 39.533 27.493 63.55 24.39 7.51 28.362 15.267 56.662 20.977 85.458 45.8 0.31 91.601-0.62 137.34 0.31-35.747 12.784-73.294 19.735-109.848 30.037-41.332-25.63-86.884-43.38-128.775-68.018-54.117-31.961-112.95-54.862-166.757-87.443-23.707-15.081-52.938-21.784-71.99-43.567z" fill="#41FE42"></path><path d="M301.18 524.164c18.37-23.521 41.27-43.194 66.964-58.275 19.052 21.783 48.283 28.486 71.99 43.567 53.806 32.581 112.64 55.482 166.757 87.443 41.89 24.638 87.443 42.387 128.775 68.018-37.857 10.054-75.776 19.798-113.695 29.603-61.998-39.656-130.885-66.59-194.373-103.641-41.27-23.893-86.203-40.96-126.418-66.715z" fill="#2AFE41"></path><path d="M261.71 601.988c8.44-28.114 22.093-54.365 39.47-77.824 40.215 25.755 85.147 42.822 126.418 66.715 63.488 37.05 132.375 63.985 194.373 103.641-11.419 2.73-22.714 5.896-33.76 9.992 14.025 0.062 27.989-0.124 42.014 0.248 18.867 17.998 47.042 22.839 63.054 44.187-6.268 15.826-14.46 30.782-23.831 44.994l-0.31-0.186c-43.257-22.466-87.382-43.256-129.273-68.267-68.825-34.009-137.526-68.39-203.559-107.675 18.122-6.02 36.802-9.62 55.545-12.846-43.07-5.958-86.823-1.676-130.141-2.98z" fill="#26FE68"></path><path d="M271.08 634.384c21.598-5.586 43.195-11.296 64.854-16.757l0.372 0.186c66.033 39.285 134.734 73.666 203.56 107.675 41.89 25.01 86.015 45.801 129.271 68.267-9.122 13.157-19.549 25.383-30.533 36.988l-0.621-0.558c-21.1-19.24-48.407-28.796-73.294-41.767-62.246-30.037-121.7-65.35-184.01-95.201-37.794-17.253-72.61-40.03-109.598-58.833z" fill="#25FE89"></path><path d="M138.954 668.703c44-11.667 88.126-22.652 132.127-34.32 36.988 18.805 71.804 41.581 109.599 58.834 62.309 29.851 121.763 65.164 184.01 95.2 24.886 12.972 52.192 22.529 73.293 41.768-17.005 16.384-36.74 29.416-57.84 39.843-56.165-39.223-120.646-63.985-180.597-96.567-46.545-26.748-94.518-51.138-143.36-73.355-0.93-9.496-1.738-18.99-2.482-28.486-38.168-1.552-77.328 4.282-114.75-2.917z" fill="#25FEA6"></path><path d="M256.186 700.106c48.842 22.217 96.815 46.607 143.36 73.355 59.95 32.582 124.432 57.344 180.597 96.567-62.558 29.665-138.582 29.975-200.394-1.8-65.536-31.465-112.144-96.628-123.563-168.122z" fill="#24FEC5"></path><path d="M630.225 704.76c25.321-0.682 50.642-0.248 76.025-0.372a517.943 517.943 0 0 1-12.288 45.242l-0.683-0.683c-16.012-21.348-44.187-26.19-63.054-44.187z" fill="#27FE4F"></path></svg></span>
      <span class="nm"><span class="dot dot-qishui"></span>汽水音乐</span>
      <span class="ds">qishui</span>
    </div>
  </div>

  <!-- 输入区 -->
  <div class="input-card">
    <div class="input-row">
      <input id="inp" type="text" placeholder="粘贴链接或分享文本，如：《吃我一击吧》@汽水音乐 https://qishui.douyin.com/s/xxxx/" autocomplete="off">
      <button class="btn-parse" id="btnParse" onclick="doParse()">解析</button>
    </div>
    <div class="hint">
      💡 提示：点击分享复制链接，粘贴此页输入框，即可解析。网易云音乐APP里复制的链接，需要在浏览器打开，再次复制链接粘贴到此页输入框。
    </div>
  </div>

  <!-- 加载 -->
  <div class="loading" id="loading">
    <div class="spinner"></div>
    <span>正在解析歌曲...</span>
  </div>

  <!-- 错误 -->
  <div class="error" id="error">
    <div class="t">⚠️ 解析失败</div>
    <div class="m" id="errorMsg"></div>
  </div>

  <!-- 结果 -->
  <div class="result" id="result">
    <!-- 播放卡片 -->
    <div class="player">
      <div class="cover-wrap">
        <img class="cover" id="cover" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTJjNWRkIi8+PHRleHQgeD0iNzUiIHk9Ijc4IiBmb250LXNpemU9IjQwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBhbGlnbm1lbnQtYmFzZWxpbmU9Im1pZGRsZSI+5Zyn5L2TPC90ZXh0Pjwvc3ZnPg==" alt="cover">
        <div class="cover-mask"></div>
      </div>
      <div class="p-info">
        <div class="p-name" id="pName">歌曲名称</div>
        <div class="p-artist" id="pArtist">歌手</div>
        <div class="p-album" id="pAlbum">专辑</div>
        <div class="p-meta">
          <span class="tag" id="tLevel">--</span>
          <span class="tag" id="tSize">--</span>
          <span class="tag" id="tPlat">--</span>
        </div>
        <div class="p-actions">
          <a class="btn btn-primary" id="btnDown" href="javascript:;" target="_blank" rel="noopener">⬇ 下载</a>
          <button class="btn" onclick="copyLink()">🔗 复制链接</button>
          <button class="btn" onclick="copyJson()">📋 复制JSON</button>
        </div>
      </div>
      <audio id="audio" controls preload="metadata" style="flex-basis:100%"></audio>
    </div>

    <!-- 歌词卡片 -->
    <div class="lyrics-card" id="lyricsCard">
      <div class="tabs">
        <button class="tab active" id="tabScroll" onclick="switchTab('scroll')">🎶 滚动歌词</button>
        <button class="tab" id="tabFull" onclick="switchTab('full')">📜 完整歌词</button>
      </div>
      <div class="lyrics-body" id="lyricsBody">
        <div class="scroll-lyrics" id="scrollLyrics"></div>
        <div class="full-lyrics" id="fullLyrics"></div>
      </div>
    </div>

    <!-- 原始 JSON -->
    <div class="json-card">
      <div class="json-head">
        <span class="t">📦 原始 JSON 数据</span>
        <button class="btn" onclick="copyJson()">复制</button>
      </div>
      <pre class="code" id="jsonOut"></pre>
    </div>
  </div>

  <footer>powered by music_jx · JH-Ahua</footer>
</div>

<div class="toast" id="toast"></div>

<script>
/* ========== 状态 ========== */
let PLATFORM = 'netease';
let LAST = null;          // 标准化结果
let RAW = null;           // 原始 JSON
let LYRIC_LINES = [];     // 解析后的歌词行 [{time,text}]

/* ========== 平台选择 ========== */
function selectPf(p){
  PLATFORM = p;
  document.querySelectorAll('.pf').forEach(el=>el.classList.toggle('active', el.dataset.pf===p));
}

/* ========== 工具 ========== */
function $(id){return document.getElementById(id)}
function esc(s){return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}
function pad2(n){return String(n).padStart(2,'0')}
function pad3(n){return String(n).padStart(3,'0')}
function fmtSize(b){
  if(!b) return '';
  b = Number(b); if(isNaN(b)||b<=0) return '';
  const u=['B','KB','MB','GB'];
  let i=0; while(b>=1024&&i<u.length-1){b/=1024;i++}
  return b.toFixed(1)+' '+u[i];
}
let toastTimer=null;
function toast(msg){
  const t=$('toast'); t.textContent=msg; t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer=setTimeout(()=>t.classList.remove('show'),2200);
}

/* 从输入中提取 URL */
function extractUrl(text){
  const m = text.match(/https?:\/\/[^\s"'<>，。、；：（）【】《》]+/i);
  return m ? m[0].replace(/[，。、；：！？]+$/,'') : null;
}
/* 自动识别平台 */
function detectPlatform(url){
  if(/music\.163\.com|163cn\.tv|y\.music\.163\.com/.test(url)) return 'netease';
  if(/kuwo\.cn|kuwo\.com/.test(url)) return 'kuwo';
  if(/qishui\.douyin\.com|douyin\.com/.test(url)) return 'qishui';
  return null;
}

/* ========== 主流程 ========== */
async function doParse(){
  const raw = $('inp').value.trim();
  if(!raw){ toast('请先输入链接或分享文本~'); return; }

  let url = extractUrl(raw);
  // 纯数字 ID -> 构造链接
  if(!url && /^\d+$/.test(raw)){
    if(PLATFORM==='netease') url = 'https://music.163.com/song?id='+raw;
    else if(PLATFORM==='kuwo') url = 'https://www.kuwo.cn/play_detail/'+raw;
    else { showError('汽水音乐需要完整分享链接哦~'); return; }
  }
  if(!url){ showError('未能从输入中识别到链接，请检查后重试'); return; }

  // 自动识别平台并切换
  const det = detectPlatform(url);
  if(det){ selectPf(det); }

  setLoading(true); hideError(); hideResult();
  try{
    let norm;
    if(PLATFORM==='netease')   norm = await parseNetease(url);
    else if(PLATFORM==='kuwo') norm = await parseKuwo(url);
    else                       norm = await parseQishui(url);

    if(!norm || norm.err){ showError((norm&&norm.err)||'解析失败，请换一个链接试试'); return; }
    render(norm);
  }catch(e){
    showError('请求出错：'+(e.message||e));
  }finally{
    setLoading(false);
  }
}

/* ---------- 网易云 ---------- */
async function parseNetease(url){
  const r = await fetch('163music.php?type=music&url='+encodeURIComponent(url));
  const j = await r.json();
  if(j.status!==200 || !j.url){
    return {err:(j.msg||'网易云解析失败')};
  }
  return {
    name:j.name||'', artist:j.ar_name||'', album:j.al_name||'',
    cover:j.pic||'', url:j.url||'', level:j.level||'', size:j.size||'',
    lyrics:j.lyric||j.tlyric||'',
    plat:'网易云音乐'
  };
}

/* ---------- 酷我 ---------- */
async function parseKuwo(url){
  const r = await fetch('kuwo.php?url='+encodeURIComponent(url));
  const j = await r.json();
  const d = j.data || {};
  if(j.code!==200 || !d.music_url){
    return {err:(j.message||j.msg||'酷我解析失败')};
  }
  return {
    name:d.title||'', artist:d.artist||'', album:d.album||'',
    cover:d.pic||d.albumpic||'', url:d.music_url||'',
    level:'', size:d.songTimeMinutes||'',
    lyrics:d.lyrics_url||'',
    plat:'酷我音乐'
  };
}

/* ---------- 汽水音乐（合并 qsmusic + dymusic） ---------- */
async function parseQishui(url){
  const [qRes, dRes] = await Promise.all([
    fetch('qsmusic.php?url='+encodeURIComponent(url)).catch(()=>null),
    fetch('dymusic.php?url='+encodeURIComponent(url)).catch(()=>null)
  ]);
  let q=null, d=null;
  if(qRes && qRes.ok){ try{ q = await qRes.json(); }catch(e){} }
  if(dRes && dRes.ok){ try{ d = await dRes.json(); }catch(e){} }

  const qd = (q && q.data) || {};
  const okQ = q && q.code===200 && (qd.url||(d&&d.url));
  const okD = d && d.url;

  if(!okQ && !okD) return {err:((q&&q.msg)||'汽水音乐解析失败')};

  let lyrics = (d && d.lyrics) || qd.lyric || '';
  // qsmusic 的 lyric 是逐字时间戳格式，转换为标准 LRC
  if(!(d && d.lyrics) && qd.lyric){
    lyrics = qsLyricToLrc(qd.lyric);
  }

  const cover = (d && d.cover) || (Array.isArray(qd.artistsmedium_avatar_url)&&qd.artistsmedium_avatar_url[0]) || '';
  const avatar = (Array.isArray(qd.artistsmedium_avatar_url)&&qd.artistsmedium_avatar_url[0]) || '';

  return {
    name: qd.albumname || (d&&d.name||'').replace(/@汽水音乐/,'') || '',
    artist: qd.artistsname || '',
    album: '',
    cover: cover || avatar,
    url: qd.url || d.url || '',
    level: (d&&d.core)||'',
    size: fmtSize((qd.video_meta&&qd.video_meta.size)||0) || (d&&d.video_meta&&fmtSize(d.video_meta.size)) || '',
    lyrics: lyrics || '',
    plat:'汽水音乐'
  };
}

/* 汽水逐字歌词 [13645,2079]<0,309,0>吃<...> -> 标准 LRC */
function qsLyricToLrc(lrc){
  if(!lrc) return '';
  return lrc.split('\n').map(line=>{
    const m = line.match(/^\[(\d+),(\d+)\](.*)$/);
    if(!m) return line;
    const ms = parseInt(m[1],10);
    const mm = Math.floor(ms/60000), ss = Math.floor((ms%60000)/1000), mmm = ms%1000;
    const text = m[3].replace(/<\d+(?:,\d+,\d+)?>/g,'');
    return '['+pad2(mm)+':'+pad2(ss)+'.'+pad3(mmm)+']'+text;
  }).join('\n');
}

/* ========== 渲染 ========== */
function render(n){
  LAST = n; RAW = RAW || n;
  $('cover').src = n.cover || $('cover').src;
  $('pName').textContent = n.name || '未知歌曲';
  $('pArtist').textContent = n.artist ? ('歌手：'+n.artist) : '';
  $('pAlbum').textContent = n.album ? ('专辑：'+n.album) : '';
  $('tLevel').textContent = n.level || '标准音质';
  $('tSize').textContent = n.size || '--';
  $('tPlat').textContent = n.plat || '--';

  const audio = $('audio');
  audio.src = n.url || '';
  audio.load();

  const down = $('btnDown');
  if(n.url){ down.href = n.url; down.style.display=''; }
  else { down.style.display='none'; }

  // 歌词
  LYRIC_LINES = parseLRC(n.lyrics||'');
  buildLyrics();
  switchTab('scroll');

  // JSON 高亮
  $('jsonOut').innerHTML = highlightJSON(RAW);
  $('result').classList.add('show');
}

/* ---------- LRC 解析 ---------- */
function parseLRC(lrc){
  if(!lrc) return [];
  const lines = [];
  const re = /\[(\d{1,2}):(\d{1,2})(?:[.:](\d{1,3}))?\]/g;
  lrc.split('\n').forEach(line=>{
    const text = line.replace(re,'').trim();
    if(!text) return;
    const times = [];
    let m; const rr = new RegExp(re.source,'g');
    while((m=rr.exec(line))!==null){
      const min=+m[1], sec=+m[2], ms=+( (m[3]||'0').padEnd(3,'0') );
      times.push(min*60+sec+ms/1000);
    }
    times.forEach(t=>lines.push({time:t,text}));
  });
  lines.sort((a,b)=>a.time-b.time);
  return lines;
}

function buildLyrics(){
  const sl = $('scrollLyrics'), fl = $('fullLyrics'), card = $('lyricsCard'), body = $('lyricsBody');
  body.classList.remove('empty');
  if(!LYRIC_LINES.length){
    sl.innerHTML = '<div class="l-empty">🎵 暂无歌词</div>';
    fl.textContent = '暂无歌词';
    return;
  }
  sl.innerHTML = LYRIC_LINES.map((l,i)=>
    '<div class="l-line" id="ll'+i+'" data-i="'+i+'">'+esc(l.text)+'</div>'
  ).join('');
  fl.textContent = LYRIC_LINES.map(l=>l.text).join('\n');
  $('audio').ontimeupdate = onTimeUpdate;
}

/* ---------- 滚动歌词 ---------- */
let curIdx = -1;
function onTimeUpdate(){
  if(!LYRIC_LINES.length) return;
  const t = $('audio').currentTime;
  let idx = -1;
  for(let i=0;i<LYRIC_LINES.length;i++){
    if(LYRIC_LINES[i].time <= t+0.15) idx = i; else break;
  }
  if(idx===curIdx) return;
  curIdx = idx;
  document.querySelectorAll('.l-line').forEach(el=>el.classList.remove('active'));
  if(idx>=0){
    const el = $('ll'+idx);
    if(el){
      el.classList.add('active');
      // 居中滚动
      const box = $('scrollLyrics');
      const target = el.offsetTop - box.clientHeight/2 + el.clientHeight/2;
      box.scrollTo({top:Math.max(0,target), behavior:'smooth'});
    }
  }
}

/* ---------- tab 切换 ---------- */
function switchTab(which){
  $('tabScroll').classList.toggle('active', which==='scroll');
  $('tabFull').classList.toggle('active', which==='full');
  $('scrollLyrics').style.display = which==='scroll' ? '' : 'none';
  $('fullLyrics').classList.toggle('show', which==='full');
}

/* ---------- JSON 高亮 ---------- */
function highlightJSON(obj){
  const s = JSON.stringify(obj, null, 2);
  let html='', i=0;
  const escT = t=>t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const punct = /[{}[\],:]/;
  while(i<s.length){
    const ch = s[i];
    if(ch==='"'){
      let j=i+1, str='"';
      while(j<s.length){
        if(s[j]==='\\'){ str+=s[j]+(s[j+1]||''); j+=2; continue; }
        if(s[j]==='"'){ str+='"'; j++; break; }
        str+=s[j]; j++;
      }
      let k=j; while(s[k]===' ') k++;
      if(s[k]===':') html+='<span class="jk">'+escT(str)+'</span>';
      else html+='<span class="js">'+escT(str)+'</span>';
      i=j;
    }
    else if(/[\d-]/.test(ch) && (i+1>=s.length || /[\d.eE+-]/.test(s[i+1]) || (ch==='-'&&/[\d]/.test(s[i+1]||'')))){
      let j=i+1;
      while(/[\d.eE+-]/.test(s[j]||'')) j++;
      html+='<span class="jn">'+escT(s.slice(i,j))+'</span>';
      i=j;
    }
    else if(s.startsWith('true',i)){ html+='<span class="jb">true</span>'; i+=4; }
    else if(s.startsWith('false',i)){ html+='<span class="jb">false</span>'; i+=5; }
    else if(s.startsWith('null',i)){ html+='<span class="jn">null</span>'; i+=4; }
    else if(punct.test(ch)){ html+='<span class="jp">'+escT(ch)+'</span>'; i++; }
    else { html+=escT(ch); i++; }
  }
  return html;
}

/* ---------- 复制 ---------- */
function copyLink(){
  if(!LAST || !LAST.url){ toast('暂无链接'); return; }
  copyText(LAST.url);
}
function copyJson(){
  if(!RAW){ toast('暂无数据'); return; }
  copyText(JSON.stringify(RAW,null,2));
}
function copyText(t){
  if(navigator.clipboard && navigator.clipboard.writeText){
    navigator.clipboard.writeText(t).then(()=>toast('已复制 ✓'),()=>fallbackCopy(t));
  } else fallbackCopy(t);
}
function fallbackCopy(t){
  const ta=document.createElement('textarea');
  ta.value=t; ta.style.position='fixed'; ta.style.opacity='0';
  document.body.appendChild(ta); ta.select();
  try{ document.execCommand('copy'); toast('已复制 ✓'); }catch(e){ toast('复制失败'); }
  document.body.removeChild(ta);
}

/* ---------- UI ---------- */
function setLoading(v){ $('loading').classList.toggle('show', v); $('btnParse').disabled = v; }
function showError(msg){ $('errorMsg').textContent = msg; $('error').classList.add('show'); }
function hideError(){ $('error').classList.remove('show'); }
function hideResult(){ $('result').classList.remove('show'); RAW=null; }

/* ---------- 漂浮音符 ---------- */
(function(){
  const icons=['🎵','🎶','♪','♫','🎧','🎼'];
  const box=$('notes');
  for(let i=0;i<14;i++){
    const s=document.createElement('span');
    s.className='note';
    s.textContent=icons[i%icons.length];
    s.style.left=(Math.random()*100)+'%';
    s.style.fontSize=(16+Math.random()*22)+'px';
    s.style.animationDuration=(9+Math.random()*14)+'s';
    s.style.animationDelay=(-Math.random()*18)+'s';
    box.appendChild(s);
  }
})();

/* 回车解析 */
$('inp').addEventListener('keydown',e=>{ if(e.key==='Enter') doParse(); });
</script>
</body>
</html>
