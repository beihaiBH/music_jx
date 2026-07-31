<?php
/**
 * 🎵 音乐解析台 - 多平台音乐解析前端
 * 支持：网易云音乐 / 酷我音乐 / 汽水音乐
 * 后端代理解析，前端美观展示
 */

// ============ API 处理 ============
if (isset($_POST['action']) && $_POST['action'] === 'parse') {
    header('Content-Type: application/json; charset=utf-8');
    $platform = $_POST['platform'] ?? '';
    $input    = trim($_POST['input'] ?? '');

    if ($input === '') {
        echo json_encode(['code' => 400, 'msg' => '请输入歌曲ID或分享链接'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $base = 'https://lgnb.asia/tool3/';
    $target = '';
    $isPureId = preg_match('/^\d+$/', $input);

    switch ($platform) {
        case 'netease': // 网易云
            if ($isPureId) {
                $target = $base . '163music.php?type=music&ids=' . urlencode($input);
            } else {
                $target = $base . '163music.php?type=music&url=' . urlencode($input);
            }
            break;

        case 'kuwo': // 酷我
            $url = $isPureId ? 'https://www.kuwo.cn/play_detail/' . $input : $input;
            $target = $base . 'kuwo.php?type=music&url=' . urlencode($url);
            break;

        case 'qishui': // 汽水音乐
            $url = $isPureId ? 'https://qishui.douyin.com/track?track_id=' . $input : $input;
            $target = $base . 'qsmusic.php?url=' . urlencode($url);
            break;

        default:
            echo json_encode(['code' => 400, 'msg' => '不支持的平台'], JSON_UNESCAPED_UNICODE);
            exit;
    }

    // 代理请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $target);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        echo json_encode(['code' => 500, 'msg' => '请求解析服务失败: ' . $err], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $json = json_decode($raw, true);
    if ($json === null) {
        echo json_encode(['code' => 500, 'msg' => '解析服务返回异常数据', 'raw' => substr($raw, 0, 500)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 统一标准化输出，方便前端渲染
    $data = $json['data'] ?? $json;
    $norm = [
        'raw'   => $json,
        'name'   => $data['name'] ?? $data['title'] ?? '',
        'artist' => $data['ar_name'] ?? $data['singer'] ?? $data['artist'] ?? $data['author'] ?? '',
        'album'  => $data['al_name'] ?? $data['album'] ?? '',
        'cover'  => $data['pic'] ?? $data['picimg'] ?? $data['cover'] ?? '',
        'music_url' => $data['url'] ?? $data['music_url'] ?? '',
        'level'  => $data['level'] ?? '',
        'size'   => $data['size'] ?? '',
        'lyrics' => $data['lyric'] ?? $data['lyrics'] ?? $data['lyrics_url'] ?? '',
        'msg'    => $json['msg'] ?? $json['message'] ?? '成功',
        'code'   => $json['code'] ?? 200,
    ];

    echo json_encode($norm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============ 页面展示 ============
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>🎵 音乐解析台 - 多平台音乐解析</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --bg1: #0f0c29;
    --bg2: #302b63;
    --bg3: #24243e;
    --card: rgba(255,255,255,.07);
    --card-border: rgba(255,255,255,.12);
    --text: #f0edff;
    --text-dim: #a9a3d0;
    --accent: #7c5cff;
    --accent2: #ff5c9d;
}
html, body { min-height: 100%; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif;
    background: linear-gradient(135deg, var(--bg1), var(--bg2), var(--bg3));
    background-attachment: fixed;
    color: var(--text);
    padding: 32px 16px 60px;
    overflow-x: hidden;
}
/* 漂浮音符 */
.note {
    position: fixed; font-size: 28px; opacity: .12;
    animation: float 12s ease-in-out infinite;
    pointer-events: none; user-select: none; z-index: 0;
}
@keyframes float {
    0%,100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-40px) rotate(15deg); }
}
.wrap { max-width: 960px; margin: 0 auto; position: relative; z-index: 1; }

/* 头部 */
.header { text-align: center; margin-bottom: 30px; }
.header .logo { font-size: 52px; display: block; margin-bottom: 8px; animation: bounce 2.5s infinite; }
@keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.header h1 {
    font-size: 30px; font-weight: 800;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: 2px;
}
.header p { color: var(--text-dim); font-size: 14px; margin-top: 8px; }

/* 主卡片 */
.panel {
    background: var(--card);
    border: 1px solid var(--card-border);
    border-radius: 20px;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    padding: 28px;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
}

/* 平台选择 */
.platform-label { font-size: 13px; color: var(--text-dim); margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.platforms { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 22px; }
.plat {
    position: relative; border-radius: 14px; padding: 16px 10px;
    border: 1.5px solid var(--card-border); background: rgba(255,255,255,.04);
    cursor: pointer; text-align: center; transition: all .25s ease;
    overflow: hidden; user-select: none;
}
.plat:hover { transform: translateY(-3px); border-color: rgba(255,255,255,.3); }
.plat.active {
    background: linear-gradient(135deg, var(--pc1, #7c5cff), var(--pc2, #ff5c9d));
    border-color: transparent;
    transform: translateY(-3px);
    box-shadow: 0 10px 30px var(--ps, rgba(124,92,255,.4));
}
.plat .icon { font-size: 26px; display: block; margin-bottom: 6px; }
.plat .name { font-size: 14px; font-weight: 600; }
.plat .desc { font-size: 11px; color: var(--text-dim); margin-top: 3px; }
.plat.active .desc { color: rgba(255,255,255,.85); }
.plat .badge { position: absolute; top: 8px; right: 8px; font-size: 9px; background: rgba(255,255,255,.25); padding: 2px 6px; border-radius: 20px; }
.plat.active .badge { background: rgba(255,255,255,.3); }

/* 输入区 */
.input-row { display: flex; gap: 10px; }
.input-box {
    flex: 1; display: flex; align-items: center; gap: 8px;
    background: rgba(0,0,0,.25); border: 1.5px solid var(--card-border);
    border-radius: 14px; padding: 0 16px; transition: border-color .25s;
}
.input-box:focus-within { border-color: var(--accent); }
.input-box .in-icon { font-size: 18px; opacity: .6; }
.input-box input {
    flex: 1; background: none; border: none; outline: none;
    color: var(--text); font-size: 15px; padding: 15px 0; font-family: inherit;
}
.input-box input::placeholder { color: var(--text-dim); opacity: .7; }
.btn {
    border: none; outline: none; cursor: pointer;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff; font-size: 15px; font-weight: 700; letter-spacing: 1px;
    padding: 0 34px; border-radius: 14px; transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 8px 25px rgba(124,92,255,.35);
    font-family: inherit;
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(124,92,255,.5); }
.btn:active { transform: translateY(0); }
.btn.loading { pointer-events: none; opacity: .8; }
.btn .spin {
    width: 16px; height: 16px; border: 2.5px solid rgba(255,255,255,.3);
    border-top-color: #fff; border-radius: 50%; animation: rot .7s linear infinite;
    display: none;
}
.btn.loading .spin { display: inline-block; }
.btn.loading .btn-txt { display: none; }
@keyframes rot { to { transform: rotate(360deg); } }
.hint { font-size: 12px; color: var(--text-dim); margin-top: 12px; line-height: 1.7; }
.hint b { color: var(--text); font-weight: 600; }

/* 结果区 */
.result { margin-top: 24px; display: none; }
.result.show { display: block; animation: fadeUp .45s ease; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

/* 歌曲信息卡 */
.song-card {
    display: flex; gap: 20px; align-items: center;
    background: rgba(255,255,255,.06); border: 1px solid var(--card-border);
    border-radius: 16px; padding: 20px; margin-bottom: 16px;
}
.song-card .cover {
    width: 96px; height: 96px; border-radius: 14px; object-fit: cover; flex-shrink: 0;
    box-shadow: 0 8px 25px rgba(0,0,0,.4); background: rgba(255,255,255,.1);
}
.song-card .info { flex: 1; min-width: 0; }
.song-card .title { font-size: 19px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.song-card .artist { font-size: 14px; color: var(--text-dim); margin-top: 6px; }
.song-card .tags { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
.tag {
    font-size: 11px; padding: 3px 10px; border-radius: 20px;
    background: rgba(124,92,255,.2); border: 1px solid rgba(124,92,255,.35); color: #c9bfff;
}
.song-card audio { width: 100%; margin-top: 14px; height: 40px; }
.song-card audio::-webkit-media-controls-panel { background: #2a2452; }

/* JSON 展示 */
.json-box {
    background: rgba(0,0,0,.35); border: 1px solid var(--card-border);
    border-radius: 16px; overflow: hidden;
}
.json-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-bottom: 1px solid var(--card-border);
    background: rgba(255,255,255,.04);
}
.json-head .j-title { font-size: 13px; font-weight: 600; color: var(--text-dim); display: flex; align-items: center; gap: 8px; }
.json-head .j-title .dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 8px #34d399; }
.copy-btn {
    background: rgba(255,255,255,.1); border: 1px solid var(--card-border);
    color: var(--text); font-size: 12px; padding: 5px 14px; border-radius: 8px;
    cursor: pointer; transition: all .2s; font-family: inherit;
}
.copy-btn:hover { background: rgba(255,255,255,.2); }
.json-body { padding: 16px; max-height: 420px; overflow: auto; }
.json-body pre { font-family: "JetBrains Mono", "Fira Code", Consolas, monospace; font-size: 12.5px; line-height: 1.65; white-space: pre-wrap; word-break: break-all; }
.k { color: #7dd3fc; }          /* key */
.s { color: #86efac; }          /* string */
.n { color: #fca5a5; }          /* number */
.b { color: #fbbf24; font-weight: 600; } /* bool/null */
.p { color: #c4b5fd; }          /* punctuation */

/* 歌词 */
.lyric-box {
    margin-top: 16px; background: rgba(0,0,0,.25); border: 1px solid var(--card-border);
    border-radius: 16px; padding: 16px; max-height: 260px; overflow: auto;
    font-size: 13px; line-height: 1.9; color: var(--text-dim); white-space: pre-wrap;
}

/* Toast */
.toast {
    position: fixed; top: 24px; left: 50%; transform: translateX(-50%) translateY(-80px);
    background: rgba(30,26,60,.95); border: 1px solid var(--card-border);
    color: var(--text); padding: 12px 26px; border-radius: 40px; font-size: 14px;
    transition: all .35s ease; opacity: 0; z-index: 99; backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0,0,0,.4);
}
.toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.toast.err { border-color: rgba(255,92,120,.5); }

/* 错误提示 */
.error-box {
    display: none; padding: 18px 20px; border-radius: 14px; margin-bottom: 16px;
    background: rgba(255,92,120,.1); border: 1px solid rgba(255,92,120,.4); color: #ffb3c0;
    font-size: 14px; align-items: center; gap: 10px;
}
.error-box.show { display: flex; animation: fadeUp .35s ease; }

/* 页脚 */
.footer { text-align: center; margin-top: 30px; font-size: 12px; color: var(--text-dim); opacity: .7; }

@media (max-width: 640px) {
    body { padding: 20px 12px 40px; }
    .panel { padding: 20px; border-radius: 16px; }
    .platforms { gap: 8px; }
    .plat { padding: 12px 6px; }
    .plat .icon { font-size: 22px; }
    .plat .name { font-size: 12px; }
    .plat .desc { display: none; }
    .input-row { flex-direction: column; }
    .btn { padding: 15px; }
    .song-card { flex-direction: column; text-align: center; }
    .song-card .cover { width: 130px; height: 130px; }
    .song-card .tags { justify-content: center; }
    .header h1 { font-size: 24px; }
}
</style>
</head>
<body>

<!-- 漂浮音符 -->
<div class="note" style="top:12%;left:6%;animation-delay:0s">🎵</div>
<div class="note" style="top:70%;left:90%;animation-delay:2s">🎶</div>
<div class="note" style="top:30%;left:94%;animation-delay:4s">🎵</div>
<div class="note" style="top:80%;left:8%;animation-delay:6s">🎶</div>
<div class="note" style="top:50%;left:2%;animation-delay:8s">♪</div>
<div class="note" style="top:15%;left:88%;animation-delay:10s">♫</div>

<div class="wrap">
    <div class="header">
        <span class="logo">🎧</span>
        <h1>音乐解析台</h1>
        <p>选择平台 · 输入歌曲ID · 一键解析</p>
    </div>

    <div class="panel">
        <div class="platform-label">🎯 选择解析平台</div>
        <div class="platforms" id="platforms">
            <div class="plat active" data-p="netease" style="--pc1:#e74c3c;--pc2:#ff8a5c;--ps:rgba(231,76,60,.35)">
                <span class="badge">推荐</span>
                <span class="icon">🎼</span>
                <div class="name">网易云音乐</div>
                <div class="desc">支持歌曲ID / 链接</div>
            </div>
            <div class="plat" data-p="kuwo" style="--pc1:#1e90ff;--pc2:#00c6ff;--ps:rgba(30,144,255,.35)">
                <span class="icon">🎤</span>
                <div class="name">酷我音乐</div>
                <div class="desc">支持歌曲ID / 链接</div>
            </div>
            <div class="plat" data-p="qishui" style="--pc1:#ff7a00;--pc2:#ffb84d;--ps:rgba(255,122,0,.35)">
                <span class="icon">🎹</span>
                <div class="name">汽水音乐</div>
                <div class="desc">抖音系 · 分享链接</div>
            </div>
        </div>

        <div class="input-row">
            <div class="input-box">
                <span class="in-icon">🔗</span>
                <input type="text" id="songInput" placeholder="请输入歌曲ID 或 分享链接，例如：1315196858" autocomplete="off">
            </div>
            <button class="btn" id="parseBtn"><span class="spin"></span><span class="btn-txt">⚡ 立即解析</span></button>
        </div>
        <div class="hint" id="hint">
            💡 <b>网易云/酷我</b>：直接粘贴歌曲ID 或 完整链接均可<br>
            💡 <b>汽水音乐</b>：粘贴 qishui.douyin.com 开头的分享链接
        </div>
    </div>

    <!-- 错误提示 -->
    <div class="error-box" id="errorBox">⚠️ <span id="errorMsg"></span></div>

    <!-- 解析结果 -->
    <div class="result" id="result">
        <div class="song-card" id="songCard">
            <img class="cover" id="songCover" src="" alt="封面" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2214%22 fill=%22%23302b63%22/><text x=%2250%22 y=%2255%22 font-size=%2238%22 text-anchor=%22middle%22>🎵</text></svg>'">
            <div class="info">
                <div class="title" id="songTitle">歌名</div>
                <div class="artist" id="songArtist">歌手</div>
                <div class="tags">
                    <span class="tag" id="tagAlbum">专辑：—</span>
                    <span class="tag" id="tagLevel">音质：—</span>
                    <span class="tag" id="tagSize">大小：—</span>
                </div>
                <audio id="player" controls preload="none" style="display:none"></audio>
            </div>
        </div>

        <div class="lyric-box" id="lyricBox" style="display:none"></div>

        <div class="json-box">
            <div class="json-head">
                <div class="j-title"><span class="dot"></span> 原始 JSON 数据</div>
                <button class="copy-btn" id="copyBtn">📋 复制</button>
            </div>
            <div class="json-body"><pre id="jsonView"></pre></div>
        </div>
    </div>

    <div class="footer">音乐解析台 · 本地部署版 🎶</div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    'use strict';

    // ---- 平台选择 ----
    var platforms = document.getElementById('platforms');
    var currentPlatform = 'netease';
    var hintEl = document.getElementById('hint');
    var hintText = {
        netease: '💡 <b>网易云音乐</b>：直接粘贴歌曲ID 或 完整链接均可<br>💡 示例：1315196858 或 https://music.163.com/song?id=1315196858',
        kuwo: '💡 <b>酷我音乐</b>：直接粘贴歌曲ID 或 完整链接均可<br>💡 示例：382383309 或 https://www.kuwo.cn/play_detail/382383309',
        qishui: '💡 <b>汽水音乐</b>：粘贴 qishui.douyin.com 开头的分享链接<br>💡 抖音汽水音乐分享链接格式：https://qishui.douyin.com/s/xxxxx'
    };

    platforms.addEventListener('click', function (e) {
        var card = e.target.closest('.plat');
        if (!card) return;
        platforms.querySelectorAll('.plat').forEach(function (p) { p.classList.remove('active'); });
        card.classList.add('active');
        currentPlatform = card.getAttribute('data-p');
        hintEl.innerHTML = hintText[currentPlatform] || '';
    });

    // ---- JSON 语法高亮 ----
    function highlight(json) {
        if (typeof json !== 'string') json = JSON.stringify(json, null, 2);
        return json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                var cls = 'n';
                if (/^"/.test(match)) {
                    cls = /:$/.test(match) ? 'k' : 's';
                } else if (/true|false/.test(match)) {
                    cls = 'b';
                } else if (/null/.test(match)) {
                    cls = 'b';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
    }

    // ---- Toast ----
    var toastTimer = null;
    function toast(msg, isErr) {
        var t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast show' + (isErr ? ' err' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { t.classList.remove('show'); }, 2600);
    }

    // ---- 解析 ----
    var btn = document.getElementById('parseBtn');
    var input = document.getElementById('songInput');

    function parse() {
        var val = input.value.trim();
        if (!val) { toast('先输入歌曲ID或链接哦~', true); input.focus(); return; }

        btn.classList.add('loading');
        document.getElementById('errorBox').classList.remove('show');
        document.getElementById('result').classList.remove('show');

        var fd = new FormData();
        fd.append('action', 'parse');
        fd.append('platform', currentPlatform);
        fd.append('input', val);

        fetch('index.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                btn.classList.remove('loading');
                if (!res || (res.code && res.code !== 200)) {
                    showError(res && res.msg ? res.msg : '解析失败，请检查ID是否正确');
                    return;
                }
                render(res);
            })
            .catch(function () {
                btn.classList.remove('loading');
                showError('网络请求失败，请稍后再试');
            });
    }

    function showError(msg) {
        document.getElementById('errorMsg').textContent = msg;
        document.getElementById('errorBox').classList.add('show');
        document.getElementById('result').classList.remove('show');
    }

    function render(res) {
        // 歌曲信息
        document.getElementById('songTitle').textContent = res.name || '未知歌名';
        document.getElementById('songArtist').textContent = res.artist || '未知歌手';
        document.getElementById('tagAlbum').textContent = '专辑：' + (res.album || '—');
        document.getElementById('tagLevel').textContent = '音质：' + (res.level || '—');
        document.getElementById('tagSize').textContent = '大小：' + (res.size || '—');
        if (res.cover) document.getElementById('songCover').src = res.cover;

        // 播放器
        var player = document.getElementById('player');
        if (res.music_url) {
            player.src = res.music_url;
            player.style.display = 'block';
        } else {
            player.removeAttribute('src');
            player.style.display = 'none';
        }

        // 歌词
        var lyricBox = document.getElementById('lyricBox');
        if (res.lyrics && res.lyrics.length > 20) {
            lyricBox.textContent = res.lyrics;
            lyricBox.style.display = 'block';
        } else {
            lyricBox.style.display = 'none';
        }

        // JSON
        document.getElementById('jsonView').innerHTML = highlight(res.raw || res);

        var resultEl = document.getElementById('result');
        resultEl.classList.remove('show');
        void resultEl.offsetWidth;
        resultEl.classList.add('show');

        toast('✅ 解析成功！');
    }

    btn.addEventListener('click', parse);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') parse(); });

    // ---- 复制 ----
    document.getElementById('copyBtn').addEventListener('click', function () {
        var text = document.getElementById('jsonView').textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { toast('📋 JSON 已复制'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta);
            toast('📋 JSON 已复制');
        }
    });
})();
</script>
</body>
</html>
