<?php
$NOVEL_ID = '001';
$file = __DIR__ . '/sample.txt';
$content = file_get_contents($file);
$lines = explode("\n", $content);

// Parse chapter boundaries
$chapters = [];
$currentChapter = null;
$currentStart = 0;

foreach ($lines as $i => $line) {
    if (preg_match('/^(第[0-9一二三四五六七八九十百千]+章[《\[]?[^》\]]*[》\]]?)/u', $line, $m)) {
        if ($currentChapter !== null) {
            $chapters[$currentChapter] = ['start' => $currentStart, 'end' => $i];
        }
        $currentChapter = trim($m[1]);
        $currentStart = $i;
    }
}
if ($currentChapter !== null) {
    $chapters[$currentChapter] = ['start' => $currentStart, 'end' => count($lines)];
}

$chapterNames = array_keys($chapters);
$activeChapter = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 0;
$activeChapter = max(0, min($activeChapter, count($chapterNames) - 1));

$chapterTitle = $chapterNames[$activeChapter] ?? '';
$range = $chapters[$chapterTitle] ?? ['start' => 0, 'end' => count($lines)];
$displayLines = array_slice($lines, $range['start'], $range['end'] - $range['start']);

// ====== 本章字数与预计阅读时间（每分钟约 500 字） ======
$chapterWordCount = 0;
foreach ($displayLines as $__cl) {
    $__ct = trim($__cl);
    if ($__ct === '') continue;
    if (preg_match('/^(第[0-9一二三四五六七八九十百千]+章)/u', $__ct)) continue;
    $chapterWordCount += mb_strlen(preg_replace('/\s+/u', '', $__ct));
}
$chapterReadMinutes = max(1, (int)ceil($chapterWordCount / 500));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>示例小说 - 佚名 悬疑推理小说 | 在线阅读</title>
<meta name="description" content="《示例小说》是作者佚名创作的悬疑推理小说。审讯室的灯光、外卖员鞋底的泥土、被盖上的毛毯……一个关于犯罪与梦境的悬疑故事。在线阅读最新章节，支持语音朗读和评论区互动。">
<meta name="keywords" content="示例小说,悬疑小说,推理小说,佚名,犯罪小说,在线阅读,免费阅读">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://example.com/001/">
<meta property="og:title" content="示例小说 - 佚名悬疑推理小说">
<meta property="og:description" content="佚名 悬疑推理小说《示例小说》。审讯室的灯光下，一个关于犯罪与梦境的故事。在线阅读最新章节。">
<meta property="og:type" content="book">
<meta property="og:url" content="https://example.com/001/">
<meta property="og:image" content="https://example.com/001/cover.jpg">
<meta property="book:author" content="佚名">
<meta property="book:tag" content="悬疑,推理,犯罪,小说">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="示例小说 - 佚名悬疑推理小说">
<meta name="twitter:description" content="佚名悬疑推理小说《示例小说》。在线阅读最新章节。">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Book",
  "name": "示例小说",
  "author": {
    "@type": "Person",
    "name": "佚名"
  },
  "alternateName": "佚名",
  "description": "审讯室的灯光闪了一下，夜神月坐在空椅子上……一个关于犯罪与梦境、追捕与自我的悬疑推理故事。",
  "genre": ["悬疑", "推理", "犯罪"],
  "url": "https://example.com/001/",
  "inLanguage": "zh-CN",
  "bookFormat": "http://schema.org/EBook",
  "numberOfPages": 3,
  "dateCreated": "2026",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {"@type": "ListItem", "position": 1, "name": "首页", "item": "https://example.com/"},
      {"@type": "ListItem", "position": 2, "name": "小说书架", "item": "https://example.com/"},
      {"@type": "ListItem", "position": 3, "name": "示例小说", "item": "https://example.com/001/"}
    ]
  }
}
</script>
<!-- 360搜索面包屑导航（对读者可见） -->
<div class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
  <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <a href="https://example.com/" itemprop="item"><span itemprop="name">🏠 首页</span></a>
    <meta itemprop="position" content="1">
  </span>
  <span class="sep"> › </span>
  <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <a href="https://example.com/" itemprop="item"><span itemprop="name">📚 书架</span></a>
    <meta itemprop="position" content="2">
  </span>
  <span class="sep"> › </span>
  <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <span itemprop="name">📖 <?= htmlspecialchars($chapterTitle ?: '示例小说') ?></span>
    <meta itemprop="position" content="3">
  </span>
  </span>
  <!-- 右侧控制按钮 -->
  <div class="top-controls">
    <button id="followBtn" class="follow-btn" onclick="toggleFollow()" title="追更">
      <svg id="followIcon" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M925.54 427.75a56 56 0 0 0-29.23-89.95l-195.52-50.15a56 56 0 0 1-33.37-24.25L559.29 93a56 56 0 0 0-94.57 0L356.6 263.4a56 56 0 0 1-33.38 24.25L127.7 337.8a56 56 0 0 0-29.23 89.95l128.7 155.51a56 56 0 0 1 12.75 39.23L227.2 823.94a56 56 0 0 0 76.52 55.59l187.66-74.34a56 56 0 0 1 41.25 0l187.66 74.34a56 56 0 0 0 76.52-55.59l-12.72-201.45a56 56 0 0 1 12.75-39.23zM719.8 519.5l-0.75 0.92a156 156 0 0 0-34.76 108.37L692.64 761l-123.18-48.78-1.1-0.44a156 156 0 0 0-113.81 0.44L331.37 761l8.35-132.22 0.07-1.19a156 156 0 0 0-35.58-108.1l-84.47-102.06 128.33-32.92 1.15-0.3A156 156 0 0 0 441 317l71-111.87L583 317l0.64 1a156 156 0 0 0 92.33 66.54l128.33 32.92z" fill="currentColor"></path></svg>
    </button>
    <button onclick="toggleReaderTheme()" title="切换暗夜/明亮模式">
      <svg id="themeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path id="themePath" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
    </button>
    <button onclick="openSettings()" title="阅读设置">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
      </svg>
    </button>
  </div>
</div>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
.breadcrumb {
    background: var(--breadcrumb-bg); padding: 6px 16px; font-size: 12px; color: var(--breadcrumb-text);
    display: flex; align-items: center;
}
.breadcrumb a { color: var(--breadcrumb-link); text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb .sep { color: var(--breadcrumb-text); margin: 0 4px; }
body {
    background: #f5f0eb;
    font-family: "Noto Serif SC", "Source Han Serif SC", "SimSun", serif;
    color: #3a3a3a;
    min-height: 100vh;
    padding-bottom: 100px;
    -webkit-font-smoothing: antialiased;
}
.header {
    background: linear-gradient(135deg, #2c1810, #4a2c20);
    color: #f0e6d3;
    padding: 32px 20px 22px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.header::after {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle at 30% 70%, rgba(255,215,0,0.06) 0%, transparent 50%);
    pointer-events: none;
}
.header h1 { font-size: 23px; font-weight: 700; letter-spacing: 4px; position: relative; }
.header .author { font-size: 13px; color: #c4a882; margin-top: 8px; letter-spacing: 2px; position: relative; }
.chapter-nav {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    padding: 12px 16px;
    display: flex; align-items: center; gap: 10px;
    overflow-x: auto;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    position: sticky; top: 0; z-index: 10;
    transition: box-shadow 0.3s;
}
.chapter-nav.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.chapter-nav .nav-btn {
    background: #8b6f5c; color: #fff; border: none;
    padding: 6px 14px; border-radius: 18px; cursor: pointer;
    font-size: 12px; white-space: nowrap;
    transition: all 0.25s ease; user-select: none;
}
.chapter-nav .nav-btn:hover { background: #6d5544; transform: translateY(-1px); }
.chapter-nav .nav-btn:disabled { background: #ddd; cursor: default; transform: none; }
.chapter-nav select {
    flex: 1; padding: 6px 10px; border: 1px solid #e0d5c8;
    border-radius: 8px; font-size: 13px; background: #faf8f5;
    color: #3a3a3a; cursor: pointer;
    outline: none; transition: border 0.2s;
}
.chapter-nav select:focus { border-color: #8b6f5c; }
.chapter-nav .chapter-info { font-size: 12px; color: #999; white-space: nowrap; }
.container { max-width: 760px; margin: 0 auto; padding: 28px 20px 40px; }
.chapter-title {
    text-align: center; font-size: 21px; font-weight: 700;
    color: #2c1810; margin-bottom: 28px; letter-spacing: 3px;
    padding-bottom: 14px; border-bottom: 1px dashed #ddd5c8;
}
.content p {
    text-indent: 2em; line-height: 2.05; font-size: 20px;
    margin-bottom: 6px; letter-spacing: 0.8px;
    word-break: break-word; transition: color 0.3s;
}
.content p.empty { text-indent: 0; height: 1em; }
.content .chapter-line {
    text-indent: 0; text-align: center; font-size: 18px;
    font-weight: 600; color: #4a2c20; margin: 24px 0;
}
.footer-nav {
    display: flex; justify-content: space-between; margin-top: 40px;
    padding-top: 20px; border-top: 1px solid #e8ddd0;
}
.footer-nav a {
    padding: 10px 22px; background: #8b6f5c; color: #fff;
    text-decoration: none; border-radius: 20px; font-size: 13px;
    transition: all 0.25s ease;
}
.footer-nav a:hover { background: #6d5544; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(74,44,32,0.2); }
.footer-nav a.disabled { background: #ddd; pointer-events: none; color: #aaa; box-shadow: none; }
.footer { text-align: center; padding: 20px 20px 6px; color: #b0a090; font-size: 12px; letter-spacing: 1px; }
.novel-footer {
  text-align: center;
  padding: 6px 20px 20px;
  color: #b0a090;
  font-size: 11px;
  line-height: 1.7;
  letter-spacing: 0.5px;
}
.novel-footer a {
  color: #a08060;
  text-decoration: none;
}
.novel-footer a:hover {
  text-decoration: underline;
}
.novel-footer .nf-copyright {
  margin-bottom: 10px;
}
.novel-footer .nf-tech {
  color: #b8a898;
  font-size: 10px;
}

/* ========== TTS 迷你播放器 ========== */
.tts-player {
    position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%) translateY(20px);
    background: rgba(44,24,16,0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 50px; padding: 8px 20px;
    display: none; align-items: center; gap: 14px;
    z-index: 1002;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
    border: 1px solid rgba(255,255,255,0.1);
    color: #f0e6d3; font-size: 13px;
}
.tts-player.show { display: flex; transform: translateX(-50%) translateY(0); }
.tts-player .tts-btn {
    width: 34px; height: 34px; border-radius: 50%;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.25s ease;
    flex-shrink: 0;
}
.tts-player .tts-btn svg { width: 18px; height: 18px; }
.tts-player .tts-play {
    background: #f7d875; color: #5a3e0a;
    width: 38px; height: 38px;
}
.tts-player .tts-play:hover { transform: scale(1.1); box-shadow: 0 0 20px rgba(247,216,117,0.4); }
.tts-player .tts-play.pulse { animation: ttsPulse 0.6s ease infinite alternate; }
@keyframes ttsPulse { from { box-shadow: 0 0 8px rgba(247,216,117,0.3); } to { box-shadow: 0 0 24px rgba(247,216,117,0.6); } }
.tts-player .tts-prev,
.tts-player .tts-next {
    background: rgba(255,255,255,0.1); color: #f0e6d3;
}
.tts-player .tts-prev:hover,
.tts-player .tts-next:hover { background: rgba(255,255,255,0.2); }
.tts-player .tts-close {
    background: rgba(255,255,255,0.08); color: #999;
    width: 26px; height: 26px;
}
.tts-player .tts-close:hover { background: rgba(255,255,255,0.15); color: #fff; }
.tts-player .tts-progress {
    flex: 1; min-width: 60px;
}
.tts-player .tts-progress-bar {
    width: 100%; height: 3px; border-radius: 3px;
    background: rgba(255,255,255,0.15); overflow: hidden;
    cursor: pointer; position: relative;
}
.tts-player .tts-progress-fill {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #f7d875, #e6b422);
    border-radius: 3px; transition: width 0.3s;
}
.tts-player .tts-time { font-size: 11px; color: rgba(255,255,255,0.5); white-space: nowrap; min-width: 36px; }
.tts-player .tts-label { font-size: 11px; color: rgba(255,255,255,0.4); white-space: nowrap; max-width: 80px; overflow: hidden; text-overflow: ellipsis; }

@media (max-width: 600px) {
    .tts-player { bottom: 86px; padding: 6px 14px; gap: 10px; width: calc(100% - 32px); }
    .tts-player .tts-label { display: none; }
}

/* ========== 抖音风格浮动按钮 ========== */
.float-actions {
    position: fixed; bottom: 36px; right: 20px;
    display: flex; flex-direction: column; align-items: center; gap: 14px;
    z-index: 999;
}
.float-btns-group {
    display: flex; flex-direction: column; align-items: center; gap: 14px;
    transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
    opacity: 1; transform: scaleY(1); transform-origin: bottom;
}
.float-btns-group.collapsed {
    opacity: 0; transform: scaleY(0); max-height: 0;
    margin: 0; gap: 0; pointer-events: none;
}
.float-toggle {
    width: 40px !important; height: 40px !important;
    background: rgba(58,34,24,0.7) !important;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.1);
}
.float-toggle svg { width: 18px !important; height: 18px !important; }
.float-toggle:hover { background: rgba(58,34,24,0.9) !important; }
.float-toggle.collapsed svg { transform: rotate(180deg); }
.float-btn {
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, #3a2218, #5c3a2a);
    color: #f0e6d3;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    cursor: pointer; 
    box-shadow: 0 4px 16px rgba(44,24,16,0.3);
    transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
    border: none; position: relative;
    font-size: 9px; line-height: 1.1;
    user-select: none; -webkit-tap-highlight-color: transparent;
}
.float-btn:active { transform: scale(0.88); }
.float-btn svg { width: 22px; height: 22px; transition: transform 0.3s; fill: currentColor; }
.float-btn:hover svg { transform: scale(1.12); }
.float-btn .count {
    font-size: 9px; margin-top: 1px;
    font-weight: 500; letter-spacing: 0;
}
.float-btn.liked svg { fill: #FC4672; animation: heartPop 0.4s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes heartPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.25); }
    100% { transform: scale(1); }
}
.float-btn .badge {
    position: absolute; top: -4px; right: -4px;
    background: #ff6b6b; color: #fff;
    font-size: 9px; font-weight: 600;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}

/* ========== 评论区遮罩 ========== */
.comment-overlay {
    display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.55);
    z-index: 1000;
    animation: overlayIn 0.3s ease;
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}
.comment-overlay.show { display: block; }
@keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }

/* ========== 评论区面板 - 抖音风格 ========== */
.comment-panel {
    position: fixed; bottom: 0; left: 0; right: 0;
    max-height: 82vh; background: #fff;
    border-radius: 24px 24px 0 0;
    z-index: 1001;
    transform: translateY(100%);
    transition: transform 0.4s cubic-bezier(0.22,1,0.36,1);
    display: flex; flex-direction: column;
    box-shadow: 0 -8px 40px rgba(0,0,0,0.18);
}
.comment-panel.show { transform: translateY(0); }

/* 面板头部 - 抖音拉条 */
.panel-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 18px 24px 14px; border-bottom: 1px solid #f0ebe5;
    flex-shrink: 0;
    position: relative;
}
.panel-header::before {
    content: ''; position: absolute; top: 8px; left: 50%;
    transform: translateX(-50%);
    width: 36px; height: 4px; border-radius: 4px;
    background: #ddd5c8;
}
.panel-header .title {
    font-size: 16px; font-weight: 700; color: #2c1810;
    letter-spacing: 0.5px;
}
.panel-header .close-btn {
    width: 30px; height: 30px; border-radius: 50%;
    background: #f5f0eb; border: none; cursor: pointer;
    font-size: 16px; display: flex; align-items: center; justify-content: center;
    color: #666; transition: all 0.2s;
}
.panel-header .close-btn:hover { background: #e8ddd0; transform: rotate(90deg); }

/* 评论列表 */
.comment-list {
    flex: 1; overflow-y: auto; padding: 12px 24px;
    -webkit-overflow-scrolling: touch;
}
.comment-item {
    padding: 16px 0; border-bottom: 1px solid #f5f2ee;
    animation: commentSlide 0.35s ease both;
}
@keyframes commentSlide {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.comment-item:last-child { border-bottom: none; }
.comment-header {
    display: flex; align-items: center; gap: 10px; margin-bottom: 8px;
}
.comment-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, #c4a882, #a08060);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
}
.comment-name-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.comment-nickname { font-size: 14px; font-weight: 600; color: #333; }
.comment-badge {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #f7d875, #e6b422);
    color: #5a3e0a; font-size: 10px; font-weight: 700;
    padding: 1px 7px; border-radius: 8px;
    letter-spacing: 0.5px;
    box-shadow: 0 1px 4px rgba(230,180,34,0.3);
}
.comment-tag {
    display: inline-flex; align-items: center;
    background: linear-gradient(135deg, #a8d8ea, #7ec8e3);
    color: #1a5276; font-size: 10px; font-weight: 700;
    padding: 1px 7px; border-radius: 8px;
    letter-spacing: 0.5px;
    box-shadow: 0 1px 4px rgba(126,200,227,0.3);
}
.comment-tag.tag-gold {
    background: linear-gradient(135deg, #d4a840, #b8860b);
    color: #fff6e0;
    box-shadow: 0 0 8px rgba(218,165,32,0.4);

/* ====== 评论区等级标识 ====== */
.comment-level-badge {
    display: inline-flex; align-items: center;
    font-size: 9px; font-weight: 800;
    padding: 0 6px; border-radius: 6px;
    letter-spacing: 0.3px;
    margin-left: 2px;
    line-height: 1.6;
}
/* Lv1~Lv3 入门基础 — 纯静态文字 */
.comment-level-badge.level-basic {
    background: #f5f5f5;
    color: #666;
    border: 1px solid #ddd;
}
/* Lv4~Lv7 黄金尊贵 — 暗金渐变 + 金属阴影 */
.comment-level-badge.level-gold {
    background: linear-gradient(135deg, #2c1810, #6d4c2e);
    color: #d4a840;
    border: 1px solid rgba(212,168,64,0.4);
    box-shadow: 0 1px 6px rgba(212,168,64,0.25);
}
/* Lv8~Lv9 巅峰紫金 — 紫金/黑底银铂 */
.comment-level-badge.level-top {
    background: linear-gradient(135deg, #0d0a1a, #1a0a2e);
    color: #d4a840;
    border: 1px solid rgba(179,136,255,0.4);
    box-shadow: 0 0 8px rgba(179,136,255,0.35);
}
/* Lv10 满级至尊 — 烫金渐变 + 流光动效 */
@keyframes lv10BadgeFlow {
    0% { background-position: 200% center; }
    100% { background-position: -200% center; }
}
.comment-level-badge.level-lv10 {
    background: linear-gradient(90deg, #1a1410 0%, #2a2018 20%, #d4a840 40%, #f7d875 55%, #d4a840 70%, #2a2018 85%, #1a1410 100%);
    background-size: 200% auto;
    color: transparent;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    border: 1px solid rgba(212,168,64,0.5);
    box-shadow: 0 0 10px rgba(212,168,64,0.35), inset 0 0 20px rgba(212,168,64,0.05);
    animation: lv10BadgeFlow 3s linear infinite;
    font-weight: 900;
    padding: 1px 8px;
}
    border: 1px solid rgba(255,215,0,0.3);
}
[data-theme="dark"] .comment-tag.tag-gold {
    background: linear-gradient(135deg, #d4a840, #b8860b);
    color: #fff6e0;
    box-shadow: 0 0 10px rgba(218,165,32,0.3);
}
.comment-time { font-size: 11px; color: #bbb; margin-left: auto; }
.comment-content {
    font-size: 15px; line-height: 1.65; color: #444;
    word-break: break-word; margin-left: 44px;
}
.comment-item.comment-reply {
    margin-left: 32px; padding-left: 12px;
    border-left: 2px solid #e8ddd0;
    background: rgba(245,240,235,0.3);
    border-radius: 0 0 0 8px;
    margin-bottom: 4px; margin-top: -4px;
}
.comment-avatar-sm { width: 26px !important; height: 26px !important; font-size: 11px !important; }
.reply-arrow { color: #c4a882; font-size: 12px; margin: 0 2px; }
.reply-target { color: #8b6f5c !important; font-weight: 400 !important; }
.comment-reply-btn {
    background: none; border: none; color: #bbb; font-size: 12px;
    cursor: pointer; padding: 3px 8px; border-radius: 12px;
    transition: all 0.2s;
}
.comment-reply-btn:hover { background: #f0ebe5; color: #8b6f5c; }
.comment-content[c\:href] { cursor: pointer; }
.comment-content img[alt="emoji"] {
    width: 18px !important; height: 18px !important;
    vertical-align: -3px; display: inline !important;
    margin: 0 1px !important;
}
.comment-actions { display: flex; align-items: center; gap: 12px; margin-top: 8px; margin-left: 44px; }
.comment-like-btn {
    display: flex; align-items: center; gap: 4px;
    font-size: 12px; color: #bbb; cursor: pointer; border: none;
    background: none; padding: 3px 8px; border-radius: 12px;
    transition: all 0.25s ease;
}
.comment-like-btn:hover { background: #fdf0f0; }
.comment-like-btn.liked { color: #FC4672; }
.comment-like-btn.liked svg { fill: #FC4672; }
.comment-like-btn svg { width: 14px; height: 14px; transition: transform 0.2s; }
.comment-like-btn:hover svg { transform: scale(1.15); }

/* 空状态 */
.empty-comments {
    text-align: center; padding: 48px 0; color: #ccc;
}
.empty-comments svg { width: 56px; height: 56px; margin-bottom: 12px; opacity: 0.3; }
.empty-comments p { font-size: 14px; }

/* ========== 底部输入区 - 抖音风格 ========== */
.comment-input-area {
    border-top: 1px solid #f0ebe5; padding: 12px 20px 24px;
    flex-shrink: 0; background: #fff;
    border-radius: 0 0 24px 24px;
}
.input-row { display: flex; gap: 10px; align-items: flex-end; }
.input-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #c4a882, #a08060);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700; flex-shrink: 0;
    position: relative;
}
.input-avatar .admin-badge {
    position: absolute; bottom: -4px; right: -4px;
    background: linear-gradient(135deg, #f7d875, #e6b422);
    color: #5a3e0a; font-size: 8px; font-weight: 700;
    padding: 1px 5px; border-radius: 6px;
    line-height: 1.2; white-space: nowrap;
    box-shadow: 0 1px 4px rgba(230,180,34,0.3);
}
.input-box { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.input-name-row { display: flex; align-items: center; gap: 6px; }
.input-nickname {
    font-size: 12px; color: #999; background: none;
    border: none; padding: 2px 4px; outline: none;
    border-bottom: 1px dashed #e0d5c8; max-width: 140px;
    transition: border 0.2s;
}
.input-nickname:focus { border-bottom-color: #8b6f5c; }
.input-badge {
    display: none; align-items: center;
    background: linear-gradient(135deg, #f7d875, #e6b422);
    color: #5a3e0a; font-size: 9px; font-weight: 700;
    padding: 1px 7px; border-radius: 8px;
    letter-spacing: 0.5px; box-shadow: 0 1px 4px rgba(230,180,34,0.2);
}
.input-content-wrap {
    display: flex; gap: 8px; align-items: flex-end;
}
.input-content {
    flex: 1; border: none; background: #f5f2ee;
    border-radius: 20px; padding: 10px 16px; font-size: 14px;
    outline: none; resize: none; min-height: 38px; max-height: 80px;
    line-height: 1.5; font-family: inherit;
    transition: background 0.2s, box-shadow 0.2s;
}
.input-content:focus { background: #efe8e0; box-shadow: 0 0 0 2px rgba(139,111,92,0.15); }
.send-btn {
    background: #8b6f5c; color: #fff; border: none;
    width: 38px; height: 38px; border-radius: 50%;
    cursor: pointer; font-size: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.25s ease; flex-shrink: 0;
}
.send-btn svg { width: 16px; height: 16px; fill: #fff; }
.send-btn:hover { background: #6d5544; transform: scale(1.05); }
.send-btn:disabled { background: #d0c5b8; cursor: default; transform: none; }

/* Emoji 按钮 */
.emoji-toggle {
    align-self: flex-start; cursor: pointer;
    background: none; border: none; padding: 6px;
    border-radius: 8px; transition: background 0.2s;
    line-height: 1; display: flex;
}
.emoji-toggle:hover { background: #f0ebe5; }
.emoji-toggle svg { width: 26px; height: 26px; }

/* 输入区标签提示 */
.input-tag-row {
    display: flex; align-items: center; gap: 6px;
    margin-top: 6px; padding: 0 4px;
    flex-wrap: wrap;
}
.tag-hint-label {
    font-size: 11px; color: #bbb;
    flex-shrink: 0;
}
.tag-hint-list {
    display: flex; gap: 5px; flex-wrap: wrap;
}
.tag-hint-item {
    cursor: pointer; font-size: 10px;
    padding: 1px 8px; border-radius: 10px;
    transition: all .15s; border: 1px solid transparent;
    letter-spacing: 0.3px;
}
.tag-hint-item:hover { opacity: 0.8; }
.tag-hint-item.tag-gold {
    background: rgba(218,165,32,0.15);
    color: #d4a840;
    border-color: rgba(218,165,32,0.3);
}
.tag-hint-item.tag-normal {
    background: rgba(100,160,200,0.12);
    color: #70a8c8;
    border-color: rgba(100,160,200,0.2);
}
[data-theme="dark"] .input-tag-row { border-top-color: var(--border); }

/* ========== 表情选择器 ========== */
.emoji-picker {
    display: none; border-top: 1px solid #f0ebe5;
    background: #faf8f5; max-height: 240px;
    flex-direction: column; overflow: hidden;
}
.emoji-picker.show { display: flex; }
.emoji-tabs {
    display: flex; border-bottom: 1px solid #e8e0d8;
    flex-shrink: 0; overflow-x: auto;
}
.emoji-tab {
    flex: 1; min-width: 60px; padding: 8px 0; text-align: center;
    font-size: 11px; cursor: pointer; border: none;
    background: none; color: #999; transition: all 0.2s;
    border-bottom: 2px solid transparent; font-weight: 500;
    white-space: nowrap;
}
.emoji-tab.active { color: #4a2c20; border-bottom-color: #8b6f5c; font-weight: 700; }
.emoji-tab:hover { color: #4a2c20; background: #f5f2ee; }
.emoji-grid-wrap { flex: 1; overflow-y: auto; padding: 8px; }
.emoji-grid { display: none; flex-wrap: wrap; gap: 6px; justify-content: center; padding: 4px; }
.emoji-grid.active { display: flex; }
.emoji-grid img {
    width: 38px; height: 38px; object-fit: contain;
    cursor: pointer; border-radius: 6px;
    transition: all 0.15s ease; padding: 3px;
    background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.emoji-grid img:hover { transform: scale(1.3); background: #f0ebe5; box-shadow: 0 4px 12px rgba(0,0,0,0.12); }

/* ========== Toast ========== */
.toast {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%,-50%) scale(0.85);
    background: rgba(0,0,0,0.78); color: #fff;
    padding: 14px 28px; border-radius: 12px;
    font-size: 14px; z-index: 2000;
    opacity: 0; transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
    pointer-events: none; backdrop-filter: blur(8px);
    text-align: center; max-width: 280px;
}
.toast.show { opacity: 1; transform: translate(-50%,-50%) scale(1); }

@media (max-width: 600px) {
    .container { padding: 20px 16px 40px; }
    .content p { font-size: 16px; }
    .header h1 { font-size: 19px; }
    .float-actions { bottom: 24px; right: 14px; gap: 12px; }
    .float-btn { width: 46px; height: 46px; }
    .float-btn svg { width: 20px; height: 20px; }
    .float-toggle { width: 38px !important; height: 38px !important; }
    .float-toggle svg { width: 16px !important; height: 16px !important; }
    .float-btns-group { gap: 12px; }
    .comment-list { padding: 12px 16px; }
    .comment-input-area { padding: 10px 14px 20px; }
    .panel-header { padding: 16px 18px 12px; }
}

/* ====== 暗夜模式覆盖 ====== */
[data-theme="dark"] body {
  background: var(--bg) !important;
  color: var(--text) !important;
}
[data-theme="dark"] .header {
  background: var(--header-bg) !important;
  color: var(--header-text) !important;
}
[data-theme="dark"] .header .author { color: #a08060 !important; }
[data-theme="dark"] .chapter-nav {
  background: var(--nav-bg) !important;
  border-bottom-color: var(--nav-border) !important;
}
[data-theme="dark"] .chapter-nav select {
  background: var(--select-bg) !important;
  border-color: var(--select-border) !important;
  color: var(--text) !important;
}
[data-theme="dark"] .chapter-nav select:focus { border-color: var(--slider-thumb) !important; }
[data-theme="dark"] .chapter-nav .nav-btn:disabled { background: #444 !important; }
[data-theme="dark"] .chapter-nav .chapter-info { color: var(--breadcrumb-text) !important; }
[data-theme="dark"] .chapter-title { color: var(--chapter-title-color) !important; border-bottom-color: var(--border) !important; }
[data-theme="dark"] .content p { color: var(--content-text) !important; }
[data-theme="dark"] .content .chapter-line { color: var(--chapter-line-color) !important; }
[data-theme="dark"] .footer-nav a:not(.disabled) { background: #5a4a3a !important; }
[data-theme="dark"] .footer-nav a:hover:not(.disabled) { background: #4a3a2a !important; }
[data-theme="dark"] .footer-nav a.disabled { background: #333 !important; color: #666 !important; }
[data-theme="dark"] .footer { color: var(--footer-text) !important; }
[data-theme="dark"] .novel-footer { color: var(--footer-text) !important; }
[data-theme="dark"] .novel-footer .nf-tech { color: #777 !important; }
[data-theme="dark"] .tts-player { background: var(--tts-bg) !important; color: var(--tts-text) !important; }
[data-theme="dark"] .comment-panel { background: var(--card-bg) !important; }
[data-theme="dark"] .comment-header { border-bottom-color: var(--border) !important; }
[data-theme="dark"] .comment-header h3 { color: var(--text) !important; }
[data-theme="dark"] .comment-input-area { background: var(--card-bg) !important; border-top-color: var(--border) !important; }
[data-theme="dark"] .comment-input-area textarea {
  background: var(--input-bg) !important;
  color: var(--text) !important;
  border-color: var(--input-border) !important;
}
[data-theme="dark"] .comment-item { border-bottom-color: var(--border) !important; }
[data-theme="dark"] .comment-content { color: var(--text) !important; }
[data-theme="dark"] .comment-nickname { color: var(--breadcrumb-link) !important; }
[data-theme="dark"] .comment-time { color: var(--breadcrumb-text) !important; }
[data-theme="dark"] .comment-likes { color: var(--breadcrumb-text) !important; }
[data-theme="dark"] .comment-likes.liked { color: #FC4672 !important; }
[data-theme="dark"] .comment-reply { color: var(--breadcrumb-link) !important; }
[data-theme="dark"] .reply-input { background: var(--input-bg) !important; color: var(--text) !important; border-color: var(--input-border) !important; }
[data-theme="dark"] .comment-input-area .send-btn { background: #5a4a3a !important; }
[data-theme="dark"] .comment-input-area .send-btn:hover { background: #4a3a2a !important; }
[data-theme="dark"] .reply-to { background: var(--badge-bg) !important; color: var(--badge-text) !important; }
[data-theme="dark"] .badge-tag { background: var(--badge-bg) !important; color: var(--badge-text) !important; }
[data-theme="dark"] .view-stats { color: var(--breadcrumb-text) !important; }
[data-theme="dark"] .float-btn { background: linear-gradient(135deg, #3a2a20, #4a3a2a) !important; }
[data-theme="dark"] .float-toggle { background: rgba(58,42,32,0.8) !important; }
[data-theme="dark"] .float-btns-group .float-label { color: var(--text) !important; }
[data-theme="dark"] .emoji-picker { background: var(--card-bg) !important; border-color: var(--border) !important; }
[data-theme="dark"] .emoji-tab { color: var(--breadcrumb-text) !important; }
[data-theme="dark"] .emoji-tab.active { color: var(--breadcrumb-link) !important; border-bottom-color: var(--breadcrumb-link) !important; }
[data-theme="dark"] .emoji-tab:hover { background: var(--hover-bg) !important; }
[data-theme="dark"] .emoji-grid img:hover { background: var(--hover-bg) !important; }
[data-theme="dark"] .comment-tag { background: var(--badge-bg) !important; color: var(--badge-text) !important; }

</style>
<!-- 搜索引擎主动推送 -->
<script>
(function(){
    var bp = document.createElement('script');
    var curProtocol = window.location.protocol.split(':')[0];
    if (curProtocol === 'https') {
        bp.src = 'https://zz.bdstatic.com/linksubmit/push.js';
    } else {
        bp.src = 'http://push.zhanzhang.baidu.com/push.js';
    }
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(bp, s);
})();
</script>
<!-- 暗夜模式 CSS 变量 -->
<style>
:root {
  --bg: #f5f0eb;
  --text: #3a3a3a;
  --header-bg: linear-gradient(135deg, #2c1810, #4a2c20);
  --header-text: #f0e6d3;
  --card-bg: #fff;
  --card-shadow: 0 1px 4px rgba(0,0,0,0.04);
  --border: #e0d5c8;
  --breadcrumb-bg: #ede5db;
  --breadcrumb-text: #999;
  --breadcrumb-link: #8b6f5c;
  --chapter-title-color: #2c1810;
  --content-text: #3a3a3a;
  --nav-bg: rgba(255,255,255,0.95);
  --nav-border: rgba(0,0,0,0.06);
  --footer-text: #b0a090;
  --select-bg: #faf8f5;
  --select-border: #e0d5c8;
  --chapter-line-color: #4a2c20;
  --tts-bg: rgba(44,24,16,0.92);
  --tts-text: #f0e6d3;
  --modal-overlay: rgba(0,0,0,0.5);
  --modal-bg: #fff;
  --modal-text: #3a3a3a;
  --input-bg: #f5f0eb;
  --input-border: #ddd5c8;
  --slider-track: #ddd5c8;
  --slider-thumb: #8b6f5c;
  --badge-bg: #f0ebe5;
  --badge-text: #8b6f5c;
  --hover-bg: #f5f2ee;
}

[data-theme="dark"] {
  --bg: #1a1a1a;
  --text: #c8c8c8;
  --header-bg: linear-gradient(135deg, #0d0d0d, #1a1a1a);
  --header-text: #ccc;
  --card-bg: #2a2a2a;
  --card-shadow: 0 1px 4px rgba(0,0,0,0.2);
  --border: #3a3a3a;
  --breadcrumb-bg: #2a2a2a;
  --breadcrumb-text: #666;
  --breadcrumb-link: #a08060;
  --chapter-title-color: #d4c8b8;
  --content-text: #c8c8c8;
  --nav-bg: rgba(30,30,30,0.95);
  --nav-border: rgba(255,255,255,0.06);
  --footer-text: #555;
  --select-bg: #333;
  --select-border: #444;
  --chapter-line-color: #b8a090;
  --tts-bg: rgba(20,20,20,0.95);
  --tts-text: #ccc;
  --modal-overlay: rgba(0,0,0,0.7);
  --modal-bg: #2a2a2a;
  --modal-text: #c8c8c8;
  --input-bg: #333;
  --input-border: #444;
  --slider-track: #444;
  --slider-thumb: #a08060;
  --badge-bg: #333;
  --badge-text: #a08060;
  --hover-bg: #333;
}

body {
  background: var(--bg);
  color: var(--text);
  transition: background 0.3s, color 0.3s;
}

/* 设置按钮样式 */
.top-controls {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-left: auto;
  flex-shrink: 0;
}

.top-controls button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  color: var(--breadcrumb-link);
}

.top-controls button:hover {
  background: var(--hover-bg);
  transform: scale(1.1);
}

.top-controls button svg {
  width: 18px;
  height: 18px;
}
/* 追更按钮：已追更时高亮为暖金色（实心星），未追更为默认色（描边星） */
.top-controls button.follow-btn { color: var(--breadcrumb-link); }
.top-controls button.follow-btn.followed { color: #e0a83e; }
.top-controls button.follow-btn.followed svg { color: #e0a83e; }
.top-controls button.follow-btn svg { transition: transform .2s, color .2s; }
.top-controls button.follow-btn:active svg { transform: scale(0.82); }

/* 设置弹窗遮罩 */
.settings-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: var(--modal-overlay);
  z-index: 9999;
  animation: fadeIn 0.2s ease;
}
.settings-overlay.show { display: block; }

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* 设置弹窗 */
.settings-modal {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  background: var(--modal-bg);
  color: var(--modal-text);
  border-radius: 20px 20px 0 0;
  padding: 24px 28px 36px;
  max-height: 70vh;
  overflow-y: auto;
  z-index: 10000;
  display: none;
  box-shadow: 0 -8px 40px rgba(0,0,0,0.15);
  animation: slideUp 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
.settings-modal.show { display: block; }

.settings-modal .s-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--chapter-title-color);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.settings-modal .s-title .s-close {
  background: none;
  border: none;
  font-size: 22px;
  cursor: pointer;
  color: var(--breadcrumb-text);
  padding: 2px 8px;
  border-radius: 6px;
  transition: all 0.2s;
}
.settings-modal .s-title .s-close:hover {
  background: var(--hover-bg);
}

.settings-modal .s-group {
  margin-bottom: 20px;
}

.settings-modal .s-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--breadcrumb-link);
  margin-bottom: 8px;
  display: block;
}

.settings-modal .s-desc {
  font-size: 12px;
  color: var(--breadcrumb-text);
  margin-top: 4px;
}

/* 字体大小滑块 */
.font-size-control {
  display: flex;
  align-items: center;
  gap: 12px;
}
.font-size-control .size-label {
  font-size: 12px;
  color: var(--breadcrumb-text);
  min-width: 28px;
  text-align: center;
}
.font-size-control input[type="range"] {
  flex: 1;
  height: 4px;
  -webkit-appearance: none;
  appearance: none;
  background: var(--slider-track);
  border-radius: 2px;
  outline: none;
}
.font-size-control input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--slider-thumb);
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  transition: transform 0.2s;
}
.font-size-control input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.15);
}
.font-size-control input[type="range"]::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--slider-thumb);
  cursor: pointer;
  border: none;
}

/* 主题选择按钮组 */
.theme-group {
  display: flex;
  gap: 8px;
}
.theme-group button {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid var(--input-border);
  border-radius: 10px;
  background: var(--modal-bg);
  color: var(--modal-text);
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}
.theme-group button:hover {
  border-color: var(--slider-thumb);
}
.theme-group button.active {
  border-color: var(--slider-thumb);
  background: var(--badge-bg);
  color: var(--badge-text);
  font-weight: 600;
}

/* 通知开关 */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.toggle-switch {
  position: relative;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
  cursor: pointer;
}
.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.toggle-switch .slider {
  position: absolute;
  inset: 0;
  background: #ccc;
  border-radius: 12px;
  transition: 0.3s;
}
.toggle-switch .slider::before {
  content: '';
  position: absolute;
  width: 18px;
  height: 18px;
  left: 3px;
  top: 3px;
  background: #fff;
  border-radius: 50%;
  transition: 0.3s;
}
.toggle-switch input:checked + .slider {
  background: #8b6f5c;
}
.toggle-switch input:checked + .slider::before {
  transform: translateX(20px);
}

.email-input-box {
  margin-top: 10px;
  display: none;
}
.email-input-box.show { display: block; }
.email-input-box input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--input-border);
  border-radius: 10px;
  font-size: 13px;
  background: var(--input-bg);
  color: var(--modal-text);
  outline: none;
  transition: border 0.2s;
}
.email-input-box input:focus {
  border-color: var(--slider-thumb);
}
.email-input-box .hint {
  font-size: 11px;
  color: var(--breadcrumb-text);
  margin-top: 4px;
}
</style>
<link rel="stylesheet" href="/assets/levels.css?v=1">
<style>
.chapter-meta{ text-align:center; font-size:12px; color:#a08a6e; margin:-4px 0 18px; letter-spacing:.5px; }
[data-theme="dark"] .chapter-meta{ color:#8a7860; }
mark.hl-note{ background:linear-gradient(180deg,transparent 55%,rgba(255,210,120,.65) 55%); cursor:pointer; padding:0 1px; border-radius:2px; }
[data-theme="dark"] mark.hl-note{ background:linear-gradient(180deg,transparent 55%,rgba(200,150,60,.5) 55%); color:inherit; }
mark.hl-note::after{ content:'\1F4DD'; font-size:10px; vertical-align:super; margin-left:1px; opacity:.7; }
#hlNoteBar{ position:absolute; z-index:99998; display:none; background:#2c1810; color:#f0e6d3; border-radius:20px; padding:6px 14px; font-size:13px; box-shadow:0 4px 16px rgba(0,0,0,.3); cursor:pointer; white-space:nowrap; }
.hl-modal-mask{ position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:99999; display:flex; align-items:center; justify-content:center; }
.hl-modal{ background:#fff; border-radius:16px; padding:18px; width:86%; max-width:360px; box-shadow:0 10px 40px rgba(0,0,0,.3); }
[data-theme="dark"] .hl-modal{ background:#231a12; color:#e8ddd0; }
.hl-modal h4{ margin:0 0 8px; font-size:15px; }
.hl-modal .hl-quote{ font-size:12px; color:#a08a6e; background:rgba(196,168,130,.12); border-left:3px solid #c4a882; padding:8px 10px; border-radius:6px; margin-bottom:10px; max-height:80px; overflow:auto; }
.hl-modal textarea{ width:100%; min-height:80px; border:1px solid #ddd; border-radius:10px; padding:10px; font-size:14px; font-family:inherit; box-sizing:border-box; resize:vertical; }
[data-theme="dark"] .hl-modal textarea{ background:#1a1410; color:#e8ddd0; border-color:#3a2f24; }
.hl-modal .hl-actions{ display:flex; gap:8px; margin-top:12px; }
.hl-modal button{ flex:1; padding:10px; border:none; border-radius:10px; font-size:14px; font-family:inherit; cursor:pointer; }
.hl-modal .hl-save{ background:linear-gradient(135deg,#c4a882,#a08060); color:#fff; font-weight:700; }
.hl-modal .hl-del{ background:#f5e9e9; color:#e74c3c; }
.hl-modal .hl-cancel{ background:#eee; color:#666; }
[data-theme="dark"] .hl-modal .hl-cancel{ background:#3a2f24; color:#c9b89f; }
</style>
</head>
<body>

<div class="header">
    <h1>📖 示例小说</h1>
    <div class="author">作者：佚名</div>
</div>

<div class="chapter-nav" id="chapterNav">
    <button class="nav-btn" onclick="goChapter(0)" <?= $activeChapter === 0 ? 'disabled' : '' ?>>⏮</button>
    <button class="nav-btn" onclick="goChapter(<?= $activeChapter - 1 ?>)" <?= $activeChapter === 0 ? 'disabled' : '' ?>>◀</button>
    <select onchange="goChapter(this.value)">
        <?php foreach ($chapterNames as $i => $name): ?>
        <option value="<?= $i ?>" <?= $i === $activeChapter ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="nav-btn" onclick="goChapter(<?= $activeChapter + 1 ?>)" <?= $activeChapter >= count($chapterNames) - 1 ? 'disabled' : '' ?>>▶</button>
    <button class="nav-btn" onclick="goChapter(<?= count($chapterNames) - 1 ?>)" <?= $activeChapter >= count($chapterNames) - 1 ? 'disabled' : '' ?>>⏭</button>
    <span class="chapter-info"><?= $activeChapter + 1 ?>/<?= count($chapterNames) ?>章</span>
</div>

<div class="container">
    <div class="chapter-title"><?= htmlspecialchars($chapterTitle) ?></div>
    <div class="chapter-meta">📖 本章约 <?= number_format($chapterWordCount) ?> 字 · 预计阅读 <?= $chapterReadMinutes ?> 分钟</div>
    <div class="content">
        <?php foreach ($displayLines as $line): 
            $line = rtrim($line);
            if (preg_match('/^(第[0-9一二三四五六七八九十百千]+章[《\[]?)/u', $line)): ?>
                <p class="chapter-line"><?= htmlspecialchars($line) ?></p>
            <?php elseif (trim($line) === ''): ?>
                <p class="empty">&nbsp;</p>
            <?php else: ?>
                <p><?= htmlspecialchars($line) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="footer-nav">
        <a href="?chapter=<?= $activeChapter - 1 ?>" class="<?= $activeChapter === 0 ? 'disabled' : '' ?>">◀ 上一章</a>
        <a href="?chapter=<?= $activeChapter + 1 ?>" class="<?= $activeChapter >= count($chapterNames) - 1 ? 'disabled' : '' ?>">下一章 ▶</a>
    </div>
</div>

<!-- ====== TTS 迷你播放器 ====== -->
<div class="tts-player" id="ttsPlayer">
    <span class="tts-label" id="ttsLabel">🔊 朗读中</span>
    <button class="tts-btn tts-prev" onclick="ttsPrev()" title="上一句">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
    </button>
    <button class="tts-btn tts-play" id="ttsPlayBtn" onclick="ttsPlayPause()" title="播放/暂停">
        <svg viewBox="0 0 24 24" fill="currentColor" id="ttsPlayIcon"><path d="M8 5v14l11-7z"/></svg>
    </button>
    <button class="tts-btn tts-next" onclick="ttsNext()" title="下一句">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
    </button>
    <div class="tts-progress">
        <div class="tts-progress-bar" onclick="ttsSeek(event)">
            <div class="tts-progress-fill" id="ttsProgressFill"></div>
        </div>
    </div>
    <span class="tts-time" id="ttsTime">00:00</span>
    <button class="tts-btn tts-close" onclick="ttsStop()" title="关闭">✕</button>
</div>

<div class="footer">
    <p>🍃 <a href="https://example.com/" style="color:#a08060;text-decoration:none;">示例小说</a> · 佚名 作品</p>
</div>

<!-- 版权信息 -->
<div class="novel-footer">
  <div class="nf-copyright">
    ©2026 佚名 保留本小说全部著作权，原作链接：<a href="https://www.dnforlife.com/?p=234299&preview=true" target="_blank" rel="noopener">https://www.dnforlife.com/?p=234299&preview=true</a><br>
    未经作者书面许可禁止转载、复制、二次分发，侵权必究。
  </div>
  <div class="nf-tech">
    技术支持：天津海云互联网络科技有限公司<br>
    云计算服务：LGZ科技集团有限公司
  </div>
</div>

<!-- ====== 抖音风格浮动按钮 ====== -->
<div class="float-actions" id="floatActions">
    <div class="float-btns-group" id="floatBtnsGroup">
        <!-- 语音朗读 -->
        <div class="float-btn" id="floatTts" onclick="toggleTts()">
            <svg viewBox="0 0 1024 1024" fill="currentColor"><path d="M342.4 384H192c-35.2 0-64 28.8-64 64v128c0 35.2 28.8 64 64 64h150.4l178.24 158.4c22.4 19.84 57.6 3.84 57.6-25.92V251.52c0-29.76-35.2-45.76-57.6-25.92L342.4 384z m388.16-105.92c12.48 12.48 12.48 32.64 0 45.12-60.48 60.48-60.48 158.4 0 218.88 12.48 12.48 12.48 32.64 0 45.12-12.48 12.48-32.64 12.48-45.12 0-85.44-85.44-85.44-223.68 0-309.12 12.48-12.48 32.64-12.48 45.12 0z m84.8-84.8c12.48-12.48 32.64-12.48 45.12 0 120.96 120.96 120.96 317.12 0 438.08-12.48 12.48-32.64 12.48-45.12 0-12.48-12.48-12.48-32.64 0-45.12 96-96 96-251.84 0-347.84-12.48-12.48-12.48-32.64 0-45.12z"/></svg>
            <span class="count" id="ttsStatus">听</span>
        </div>
        <!-- 评论 -->
        <div class="float-btn" id="floatComment" onclick="openComment()">
            <svg viewBox="0 0 1024 1024" fill="currentColor"><path d="M511.26 105.76c247.42 0 448 159.73 448 356.77 0 45.15-10.54 88.34-29.75 128.09 0 0-76.1 192.15-406.28 329.69V819.16c-3.97 0.09-7.96 0.13-11.96 0.13-247.42 0-448-159.73-448-356.77s200.57-356.76 447.99-356.76z m224 407.27c33.74 0 61.09-27.35 61.09-61.09s-27.35-61.09-61.09-61.09-61.09 27.35-61.09 61.09 27.35 61.09 61.09 61.09z m-203.63 0c33.74 0 61.09-27.35 61.09-61.09s-27.35-61.09-61.09-61.09-61.09 27.35-61.09 61.09 27.35 61.09 61.09 61.09z m-203.64 0c33.74 0 61.09-27.35 61.09-61.09s-27.35-61.09-61.09-61.09-61.09 27.35-61.09 61.09 27.35 61.09 61.09 61.09z"/></svg>
            <span class="count" id="commentCount">0</span>
        </div>
        <!-- 点赞 -->
        <div class="float-btn" id="floatLike" onclick="toggleGlobalLike()">
            <svg viewBox="0 0 1024 1024" fill="currentColor" id="globalLikeIcon"><path d="M748.74 55.05c-73.1-4.96-138.9 19.85-192.51 59.55-26.81 19.85-60.93 19.85-87.73 0-53.62-39.7-119.41-62.03-192.52-59.55C105.4 64.97-14 226.26 3.06 397.48c24.37 280.4 275.36 466.5 414.26 548.39 58.48 34.74 131.6 34.74 187.64 0 141.35-84.37 389.91-270.47 414.27-548.39C1036.29 226.26 919.32 64.97 748.74 55.05z"/></svg>
            <span class="count" id="likeCount">0</span>
        </div>
        <!-- 观看 -->
        <div class="float-btn" id="floatView">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
            <span class="count" id="viewCount">0</span>
        </div>
    </div>
    <!-- 展开/收切换 -->
    <div class="float-btn float-toggle" id="floatToggle" onclick="toggleFloatGroup()">
        <svg viewBox="0 0 1024 1024" fill="currentColor" id="toggleIcon"><path d="M512 128c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z m0 320c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z m0 320c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z"/></svg>
    </div>
</div>

<!-- ====== 评论区遮罩 ====== -->
<div class="comment-overlay" id="commentOverlay" onclick="closeComment()"></div>

<!-- ====== 评论区面板 ====== -->
<div class="comment-panel" id="commentPanel">
    <div class="panel-header">
        <span class="title">💬 读者评论区</span>
        <button class="close-btn" onclick="closeComment()">✕</button>
    </div>

    <div class="comment-list" id="commentList">
        <div class="empty-comments" id="emptyComments">
            <svg viewBox="0 0 24 24" fill="#ccc"><path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
            <p>还没有评论呢~ 来写第一条吧！</p>
        </div>
    </div>

    <div class="emoji-picker" id="emojiPicker">
        <div class="emoji-tabs" id="emojiTabs"></div>
        <div class="emoji-grid-wrap" id="emojiGridWrap"></div>
    </div>

    <div class="comment-input-area">
        <div class="input-row">
            <div class="input-avatar" id="inputAvatar" onclick="selectAvatar()" title="点击上传头像">
                <span id="avatarText">匿</span>
                <div class="avatar-upload-overlay">📷</div>
                <div class="avatar-badge" id="avatarBadge">✓</div>
            </div>
            <div class="input-box">
                <div class="input-name-row">
                    <input class="input-nickname" id="inputNickname" placeholder="你的昵称" maxlength="20">
                    <span class="input-badge" id="inputBadge">👑 站长</span>
                </div>
                <div class="input-content-wrap">
                    <button class="emoji-toggle" id="emojiToggle" onclick="toggleEmoji()">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                    </button>
                    <textarea class="input-content" id="inputContent" placeholder="写点感想..." rows="1" maxlength="500"></textarea>
                    <button class="send-btn" id="sendBtn" onclick="sendComment()">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
                <div class="input-tag-row" id="tagHintRow" style="display:none">
                    <span class="tag-hint-label">🏷️</span>
                    <span class="tag-hint-list" id="tagHintList"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== Toast ====== -->
<div class="toast" id="toast"></div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" async></script>

<!-- 头像裁剪弹窗 -->
<div class="crop-overlay" id="cropOverlay" onclick="closeCrop()"></div>
<div class="crop-modal" id="cropModal">
    <div class="crop-header">
        <span>✂️ 裁剪头像</span>
        <button class="close-btn" onclick="closeCrop()">✕</button>
    </div>
    <div class="crop-body">
        <img id="cropImage" style="max-width:100%;">
    </div>
    <div class="crop-footer">
        <button class="crop-cancel" onclick="closeCrop()">取消</button>
        <button class="crop-confirm" onclick="confirmCrop()">✓ 确定</button>
    </div>
</div>

<style>
/* 头像上传样式 */
.input-avatar { cursor: pointer; overflow: hidden; }
.input-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.input-avatar .avatar-upload-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.35); color: #fff;
    display: none; align-items: center; justify-content: center;
    border-radius: 50%; font-size: 11px; font-weight: 600;
    letter-spacing: 0.5px; backdrop-filter: blur(2px);
}
.input-avatar:hover .avatar-upload-overlay { display: flex; }
.input-avatar .avatar-badge {
    position: absolute; bottom: -2px; right: -2px;
    width: 14px; height: 14px; border-radius: 50%;
    background: #4CAF50; color: #fff;
    display: none; align-items: center; justify-content: center;
    font-size: 8px; border: 2px solid #fff;
}

/* 裁剪弹窗 */
.crop-overlay {
    display: none; position: fixed; top: 0; left: 0;
    width: 100%; height: 100%; background: rgba(0,0,0,0.6);
    z-index: 10001; backdrop-filter: blur(4px);
}
.crop-overlay.show { display: block; }
.crop-modal {
    display: none; position: fixed; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    width: 90%; max-width: 420px;
    background: #fff; border-radius: 16px;
    z-index: 10002; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.crop-modal.show { display: block; }
.crop-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 18px; border-bottom: 1px solid #eee;
    font-size: 16px; font-weight: 700; color: #333;
}
.crop-header .close-btn { background: none; border: none; font-size: 18px; cursor: pointer; color: #999; padding: 4px; }
.crop-body { max-height: 60vh; overflow: hidden; }
.crop-body img { display: block; max-width: 100%; }
.crop-footer {
    display: flex; gap: 10px; padding: 12px 18px;
    border-top: 1px solid #eee; justify-content: flex-end;
}
.crop-cancel {
    padding: 8px 20px; border-radius: 20px; border: 1px solid #ddd;
    background: #fff; color: #666; font-size: 14px; cursor: pointer;
}
.crop-confirm {
    padding: 8px 20px; border-radius: 20px; border: none;
    background: linear-gradient(135deg, #c4a882, #a08060);
    color: #fff; font-size: 14px; cursor: pointer; font-weight: 600;
}
.crop-confirm:disabled { opacity: 0.5; cursor: not-allowed; }
.comment-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
</style>

<script>
const API = '/api.php';
const NOVEL_ID = '<?= $NOVEL_ID ?>';
let emojiData = [];
let isAdmin = false;

// ====== 用户数据云同步（永久保存） ======
function getUUID() {
    let uuid = localStorage.getItem('novel_uuid');
    if (!uuid) {
        // 先试试服务器cookie里有没有
        fetch(API + '?action=get_uuid')
            .then(r => r.json())
            .then(d => {
                if (d.code === 0) {
                    localStorage.setItem('novel_uuid', d.uuid);
                }
            }).catch(() => {});
        // 临时用本地生成的
        uuid = 'u_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('novel_uuid', uuid);
    }
    return uuid;
}

function syncUserData(data) {
    const uuid = getUUID();
    data.uuid = uuid;
    data.action = 'save_user_data';
    fetch(API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).catch(() => {});
}

function loadUserData() {
    // 优先使用登录token获取用户数据
    var token = localStorage.getItem('novel_token');
    if (token) {
        return fetch(API + '?action=check_login&token=' + encodeURIComponent(token))
            .then(r => r.json())
            .then(function(d) {
                if (d.code === 0 && d.data) {
                    var u = d.data;
                    if (u.uuid) localStorage.setItem('novel_uuid', u.uuid);
                    if (u.avatar) localStorage.setItem('novel_avatar_url', u.avatar);
                    if (u.nickname) localStorage.setItem('novel_nick_admin', u.nickname);
                    if (u.bind_email) {
                      localStorage.setItem('readerEmail', u.bind_email);
                      localStorage.setItem('readerNotif', 'on');
                    }
                    return u;
                }
                return Promise.reject();
            })
            .catch(function() {
                // fallback: 从cookie恢复
                return fetch(API + '?action=load_user_data')
                    .then(r => r.json())
                    .then(function(d) {
                        if (d.code === 0 && d.data) {
                            if (d.data.uuid) localStorage.setItem('novel_uuid', d.data.uuid);
                            return d.data;
                        }
                        return null;
                    });
            });
    }
    // 没有token，从cookie恢复
    return fetch(API + '?action=load_user_data')
        .then(r => r.json())
        .then(function(d) {
            if (d.code === 0 && d.data) {
                if (d.data.uuid) localStorage.setItem('novel_uuid', d.data.uuid);
                return d.data;
            }
            return null;
        })
        .catch(function() { return null; });
}

document.addEventListener('DOMContentLoaded', () => {
    checkAdmin();
    loadStats();
    loadEmojis();
    loadNickname();
    recordView();
    initFollowBtn();
    
    // 从服务器加载用户数据
    loadUserData().then(data => {
        if (!data) return;
        // 点赞状态
        if (data.liked_novel) {
            localStorage.setItem('novel_global_liked', '1');
            document.getElementById('floatLike').classList.add('liked');
        }
        // 昵称
        if (data.nickname && !isAdmin) {
            document.getElementById('inputNickname').value = data.nickname;
            localStorage.setItem('novel_nick_admin', data.nickname);
        }
        // 头像
        if (data.avatar) {
            localStorage.setItem('novel_avatar_url', data.avatar);
        }
        // 设置
        if (data.settings) {
            const s = data.settings;
            if (s.fontSize) localStorage.setItem('readerFontSize', s.fontSize);
            if (s.theme) localStorage.setItem('readerTheme', s.theme);
            if (s.email) localStorage.setItem('readerEmail', s.email);
            if (s.notif) localStorage.setItem('readerNotif', 'on');
        }
        // 重新加载显示
        setTimeout(() => {
            loadAvatar();
            // 应用设置
            const savedSize = localStorage.getItem('readerFontSize');
            if (savedSize) {
                document.querySelectorAll('.content p').forEach(p => p.style.fontSize = savedSize + 'px');
            }
            const savedTheme = localStorage.getItem('readerTheme');
            if (savedTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
            else if (savedTheme === 'system') {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches)
                    document.documentElement.setAttribute('data-theme', 'dark');
            }
            updateThemeIcon();
        }, 100);
    });
    
    // 恢复点赞状态（本地回退）
    if (!document.getElementById('floatLike').classList.contains('liked') && localStorage.getItem('novel_global_liked')) {
        document.getElementById('floatLike').classList.add('liked');
    }

    // Nav scroll effect
    const nav = document.getElementById('chapterNav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 10);
    });

    document.getElementById('inputContent').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendComment();
        }
    });
    document.getElementById('inputContent').addEventListener('input', function() {
        autoResize(this);
    });
    document.getElementById('inputNickname').addEventListener('change', saveNickname);
    loadAvatar();
});

// ====== 管理员检测 ======
function checkAdmin() {
    fetch(API + '?action=check_admin')
        .then(r => r.json())
        .then(d => {
            if (d.code === 0 && d.is_admin) {
                isAdmin = true;
                document.getElementById('inputNickname').value = 'admin';
                updateAvatarDisplay('b', null);
                document.getElementById('inputBadge').style.display = 'flex';
                saveNickname();
            }
        })
        .catch(() => {});
}

// ====== 统计 ======
function recordView() {
    const key = 'novel_viewed_' + window.location.pathname;
    if (localStorage.getItem(key)) {
        // 已记录过，只更新显示不重复计数
        fetch(API + '?action=list&novel_id=' + NOVEL_ID)
            .then(r => r.json())
            .then(d => { if (d.code === 0) updateViewCount(d.data.views); })
            .catch(() => {});
        return;
    }
    localStorage.setItem(key, '1');
    fetch(API + '?action=view&novel_id=' + NOVEL_ID, { method: 'POST' })
        .then(r => r.json())
        .then(d => { if (d.code === 0) updateViewCount(d.views); })
        .catch(() => {});
}

function loadStats() {
    fetch(API + '?action=list&novel_id=' + NOVEL_ID)
        .then(r => r.json())
        .then(d => {
            if (d.code !== 0) return;
            const data = d.data;
            updateViewCount(data.views || 0);
            updateLikeCount(data.total_likes || 0);
            document.getElementById('commentCount').textContent = data.comments?.length || 0;
        })
        .catch(() => {});
}

function updateViewCount(n) {
    const el = document.getElementById('viewCount');
    el.textContent = n;
}
function updateLikeCount(n) {
    document.getElementById('likeCount').textContent = n;
}

// ====== 表情加载 ======
function loadEmojis() {
    fetch(API + '?action=emoji_list')
        .then(r => r.json())
        .then(d => {
            if (d.code !== 0) return;
            emojiData = d.data;
            renderEmojiTabs();
            renderEmojiGrid(0);
        })
        .catch(() => {});
}

function renderEmojiTabs() {
    const tabs = document.getElementById('emojiTabs');
    tabs.innerHTML = '';
    const labels = ['QQ', '哔哩', 'Pao', 'Aru'];
    emojiData.forEach((cat, i) => {
        const btn = document.createElement('button');
        btn.className = 'emoji-tab' + (i === 0 ? ' active' : '');
        btn.textContent = labels[i] || cat.label;
        btn.onclick = () => switchEmojiTab(i);
        tabs.appendChild(btn);
    });
}

function renderEmojiGrid(activeIdx) {
    const wrap = document.getElementById('emojiGridWrap');
    wrap.innerHTML = '';
    emojiData.forEach((cat, i) => {
        const grid = document.createElement('div');
        grid.className = 'emoji-grid' + (i === activeIdx ? ' active' : '');
        grid.id = 'emojiGrid_' + i;
        cat.emojis.forEach(emo => {
            const img = document.createElement('img');
            img.src = emo.url;
            img.title = emo.name;
            img.loading = 'lazy';
            img.onclick = () => insertEmoji(emo.url);
            grid.appendChild(img);
        });
        wrap.appendChild(grid);
    });
}

function switchEmojiTab(idx) {
    document.querySelectorAll('.emoji-tab').forEach((t, i) => t.classList.toggle('active', i === idx));
    document.querySelectorAll('.emoji-grid').forEach((g, i) => g.classList.toggle('active', i === idx));
}

function toggleEmoji() {
    document.getElementById('emojiPicker').classList.toggle('show');
}

function insertEmoji(url) {
    const input = document.getElementById('inputContent');
    const tag = '![](' + url + ')';
    const start = input.selectionStart;
    const val = input.value;
    input.value = val.slice(0, start) + tag + val.slice(input.selectionEnd);
    input.focus();
    input.setSelectionRange(start + tag.length, start + tag.length);
    autoResize(input);
}

// ====== 评论区 ======
function openComment() {
    document.getElementById('commentOverlay').classList.add('show');
    document.getElementById('commentPanel').classList.add('show');
    document.body.style.overflow = 'hidden';
    loadComments();
    loadUserTags();
}

function closeComment() {
    document.getElementById('commentOverlay').classList.remove('show');
    document.getElementById('commentPanel').classList.remove('show');
    document.getElementById('emojiPicker').classList.remove('show');
    document.body.style.overflow = '';
}

function loadComments() {
    const list = document.getElementById('commentList');
    const empty = document.getElementById('emptyComments');

    fetch(API + '?action=list&novel_id=' + NOVEL_ID)
        .then(r => r.json())
        .then(d => {
            if (d.code !== 0) return;
            const comments = d.data.comments || [];
            document.getElementById('commentCount').textContent = comments.length;
            updateLikeCount(d.data.total_likes || 0);

            // Remove old items
            list.querySelectorAll('.comment-item').forEach(el => el.remove());

            if (comments.length === 0) {
                empty.style.display = 'block';
                return;
            }
            empty.style.display = 'none';

            // Separate top-level comments and replies
            const topLevel = comments.filter(cc => !cc.parent_id);
            const replies = {};
            comments.filter(cc => cc.parent_id).forEach(cc => {
                if (!replies[cc.parent_id]) replies[cc.parent_id] = [];
                replies[cc.parent_id].push(cc);
            });
            
            const likedComments = getLikedSet();
            
            topLevel.forEach((c, idx) => {
                const item = document.createElement('div');
                item.className = 'comment-item';
                item.style.animationDelay = (idx * 0.04) + 's';
                const avatarHtml = c.avatar ? `<img src="${c.avatar}" alt="头像">` : c.nickname.charAt(0);
                const isBeihai = c.nickname === 'admin';
                const isLiked = likedComments.has(c.id);
                
                // 提取标签
                const rawContent = c.content;
                const { tag, level, content: cleanContent } = extractTag(rawContent);
                const tagCls = level === 2 ? 'comment-tag tag-gold' : 'comment-tag';
                const tagIcon = level === 2 ? '⭐' : '';
                const tagHtml = tag ? `<span class="${tagCls}">${tagIcon}${escapeHtml(tag)}</span>` : '';
                // 构建所有标签徽章
                const tagBadges = [];
                if (tagHtml) tagBadges.push(tagHtml);
                if (isBeihai) tagBadges.push('<span class="comment-badge">👑 站长</span>');
                if (c.user_tags) {
                  c.user_tags.split(',').forEach(function(t) {
                    t = t.trim();
                    if (t) tagBadges.push('<span class="comment-tag">' + escapeHtml(t) + '</span>');
                  });
                }
                // 等级标识
                var levelBadge = '';
                if (c.level !== undefined && c.level > 0) {
                  levelBadge = '<span class="comment-level-badge level-badge-' + c.level + '">Lv.' + c.level + '</span>';
                }
                const extraBadge = tagBadges.join('') + levelBadge;
                
                item.innerHTML = `
                    <div class="comment-header">
                        <div class="comment-avatar lv-avatar-${c.level||0}" onclick="window.location.href='/profile.php?uuid=${c.uuid || ''}'" style="cursor:pointer">${avatarHtml}</div>
                        <div class="comment-name-row">
                            <span class="comment-nickname lv-name-${c.level||0}" onclick="window.location.href='/profile.php?uuid=${c.uuid || ''}'" style="cursor:pointer">${escapeHtml(c.nickname)}</span>
                            ${extraBadge}
                        </div>
                        <span class="comment-time">${formatTime(c.time)}</span>
                    </div>
                    <div class="comment-content" onclick="replyTo(${c.id}, '${escapeHtml(c.nickname)}')">${renderContent(escapeHtml(cleanContent))}</div>
                    <div class="comment-actions">
                        <button class="comment-like-btn ${isLiked ? 'liked' : ''}" onclick="likeComment(${c.id}, this)">
                            <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <span>${c.likes || 0}</span>
                        </button>
                        <button class="comment-reply-btn" onclick="replyTo(${c.id}, '${escapeHtml(c.nickname)}')">💬 回复</button>
                    </div>
                `;
                list.appendChild(item);
                
                // Show replies
                const replyList = replies[c.id];
                if (replyList) {
                    replyList.forEach(r => {
                        const rItem = document.createElement('div');
                        rItem.className = 'comment-item comment-reply';
                        const rAvatar = r.avatar ? `<img src="${r.avatar}" alt="头像">` : r.nickname.charAt(0);
                        const rLiked = likedComments.has(r.id);
                        
                        const { tag: rTag, level: rLevel, content: rCleanContent } = extractTag(r.content);
                        const rTagCls = rLevel === 2 ? 'comment-tag tag-gold' : 'comment-tag';
                        const rTagIcon = rLevel === 2 ? '⭐' : '';
                        const rTagHtml = rTag ? `<span class="${rTagCls}">${rTagIcon}${escapeHtml(rTag)}</span>` : '';
                        const rTagBadges = [];
                        if (rTagHtml) rTagBadges.push(rTagHtml);
                        if (r.nickname === 'admin') rTagBadges.push('<span class="comment-badge">👑 站长</span>');
                        if (r.user_tags) {
                          r.user_tags.split(',').forEach(function(t) {
                            t = t.trim();
                            if (t) rTagBadges.push('<span class="comment-tag">' + escapeHtml(t) + '</span>');
                          });
                        }
                        var rLevelBadge = '';
                        if (r.level !== undefined && r.level > 0) {
                          rLevelBadge = '<span class="comment-level-badge level-badge-' + r.level + '">Lv.' + r.level + '</span>';
                        }
                        const rExtraBadge = rTagBadges.join('') + rLevelBadge;
                        
                        rItem.innerHTML = `
                            <div class="comment-header">
                                <div class="comment-avatar comment-avatar-sm lv-avatar-${r.level||0}" onclick="window.location.href='/profile.php?uuid=${r.uuid || ''}'" style="cursor:pointer">${rAvatar}</div>
                                <div class="comment-name-row">
                                    <span class="comment-nickname lv-name-${r.level||0}" onclick="window.location.href='/profile.php?uuid=${r.uuid || ''}'" style="cursor:pointer">${escapeHtml(r.nickname)}</span>
                                    ${rExtraBadge}
                                    <span class="reply-arrow">↩</span>
                                    <span class="comment-nickname reply-target">${escapeHtml(c.nickname)}</span>
                                </div>
                                <span class="comment-time">${formatTime(r.time)}</span>
                            </div>
                            <div class="comment-content">${renderContent(escapeHtml(rCleanContent))}</div>
                            <div class="comment-actions">
                                <button class="comment-like-btn ${rLiked ? 'liked' : ''}" onclick="likeComment(${r.id}, this)">
                                    <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                    <span>${r.likes || 0}</span>
                                </button>
                            </div>
                        `;
                        list.appendChild(rItem);
                    });
                }
            });
        })
        .catch(() => {});
}

function getLikedSet() {
    const set = new Set();
    for (let i = 0; i < localStorage.length; i++) {
        const k = localStorage.key(i);
        if (k && k.startsWith('novel_liked_')) {
            set.add(parseInt(k.replace('novel_liked_', '')));
        }
    }
    return set;
}

let replyTarget = { id: 0, name: '' };
function replyTo(id, name) {
    replyTarget = { id, name };
    document.getElementById('inputContent').placeholder = '回复 @' + name + '...';
    document.getElementById('inputContent').focus();
    showToast('💬 正在回复 @' + name);
}
function cancelReply() {
    replyTarget = { id: 0, name: '' };
    document.getElementById('inputContent').placeholder = '写点感想...';
}

// ====== 标签持久化 ======
const NovelAPI = (() => {
    const host = window.location.hostname;
    return (host === 'example.com') ? '/api.php' : '/novel/api.php';
})();

// 加载用户标签历史并渲染提示
function loadUserTags() {
    const uuid = getUUID();
    const row = document.getElementById('tagHintRow');
    const list = document.getElementById('tagHintList');
    
    // 优先 localStorage
    const cached = localStorage.getItem('novel_tags');
    let tags = cached ? JSON.parse(cached) : [];
    renderTagHints(tags, list, row);
    
    // 服务器拉取最新
    fetch(NovelAPI + '?action=get_user_tags&uuid=' + encodeURIComponent(uuid))
        .then(r => r.json())
        .then(d => {
            if (d.code === 0 && d.tags && d.tags.length > 0) {
                localStorage.setItem('novel_tags', JSON.stringify(d.tags));
                renderTagHints(d.tags, list, row);
                autoFillTags(d.tags);
            }
        }).catch(() => {});
}

// 自动填充标签到输入框
function autoFillTags(tags) {
    if (!tags || tags.length === 0) return;
    const input = document.getElementById('inputContent');
    if (input.value) return;
    const parts = tags.map(t => {
        const prefix = t.level >= 2 ? '!!' : '!';
        return '[' + prefix + t.tag + ']';
    });
    input.value = parts.join(' ') + ' ';
    autoResize(input);
}

// 渲染标签提示
function renderTagHints(tags, list, row) {
    if (!tags || tags.length === 0) {
        row.style.display = 'none';
        return;
    }
    const sorted = [...tags].sort((a, b) => (b.count || 0) - (a.count || 0)).slice(0, 5);
    list.innerHTML = sorted.map(t => {
        const cls = t.level >= 2 ? 'tag-gold' : 'tag-normal';
        const prefix = t.level >= 2 ? '!!' : '!';
        return `<span class="tag-hint-item ${cls}" onclick="insertTag('[${prefix}${t.tag}]')">[${prefix}${t.tag}]</span>`;
    }).join('');
    row.style.display = 'flex';
}

// 点击标签插入
function insertTag(tagStr) {
    const input = document.getElementById('inputContent');
    if (input.value.includes(tagStr)) return;
    input.value = input.value + tagStr + ' ';
    autoResize(input);
    input.focus();
}

function extractTag(text) {
    // 双叹号 [!!标签名] → level 2 (金色)
    const m2 = text.match(/^\[!!([^\]]+)\]\s*/);
    if (m2) {
        return { tag: m2[1].trim(), level: 2, content: text.slice(m2[0].length) };
    }
    // 单叹号 [!标签名] → level 1 (普通)
    const m1 = text.match(/^\[!([^\]]+)\]\s*/);
    if (m1) {
        return { tag: m1[1].trim(), level: 1, content: text.slice(m1[0].length) };
    }
    return { tag: null, level: 0, content: text };
}

function renderContent(text) {
    return text.replace(/!\[\]\(([^)]+)\)/g, '<img src="$1" width="18" height="18" alt="emoji">');
}

function sendComment() {
    const nickname = document.getElementById('inputNickname').value.trim() || '匿名读者';
    const content = document.getElementById('inputContent').value.trim();
    if (!content) {
        showToast('写点内容再发送吧~');
        return;
    }
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    const form = new FormData();
    form.append('action', 'add');
    form.append('nickname', nickname);
    form.append('avatar', getAvatarUrl());
    form.append('content', content);
    form.append('uuid', getUUID());
    form.append('token', (typeof getToken === 'function' ? getToken() : (localStorage.getItem('novel_token') || '')));
    form.append('email', localStorage.getItem('readerEmail') || '');
    form.append('novel_id', NOVEL_ID);
    if (replyTarget.id > 0) {
        form.append('parent_id', replyTarget.id);
    }
    fetch(API, { method: 'POST', body: form })
        .then(r => r.json())
        .then(d => {
            if (d.code === 0) {
                document.getElementById('inputContent').value = '';
                autoResize(document.getElementById('inputContent'));
                cancelReply();
                showToast('✨ 发送成功');
                loadComments();
                loadStats();
                // 保存标签到 localStorage
                const { tag, level } = extractTag(content);
                if (tag) {
                    let tags = JSON.parse(localStorage.getItem('novel_tags') || '[]');
                    const found = tags.find(t => t.tag === tag);
                    if (found) {
                        found.level = Math.max(found.level, level);
                        found.count = (found.count || 0) + 1;
                    } else {
                        tags.push({ tag, level, count: 1 });
                    }
                    localStorage.setItem('novel_tags', JSON.stringify(tags));
                }
                loadUserTags();
            } else {
                showToast(d.msg || '发送失败啦');
            }
        })
        .catch(() => showToast('网络开小差了~'))
        .finally(() => { btn.disabled = false; });
}

function likeComment(id, btn) {
    const key = 'novel_liked_' + id;
    if (localStorage.getItem(key)) {
        showToast('已经点过赞啦❤️');
        return;
    }
    const form = new FormData();
    form.append('action', 'like');
    form.append('id', id);
    form.append('novel_id', NOVEL_ID);
    fetch(API, { method: 'POST', body: form })
        .then(r => r.json())
        .then(d => {
            if (d.code === 0) {
                btn.classList.add('liked');
                btn.querySelector('span').textContent = d.likes;
                // 评论点赞与小说首页点赞相互独立，这里不再改动首页点赞数
                localStorage.setItem(key, '1');
                showToast('❤️ 已点赞');
                // 同步点赞到服务器
                const liked = JSON.parse(localStorage.getItem('novel_liked_comments') || '[]');
                if (!liked.includes(id)) {
                    liked.push(id);
                    localStorage.setItem('novel_liked_comments', JSON.stringify(liked));
                    syncUserData({liked_comments: liked});
                }
            }
        })
        .catch(() => {});
}

// ====== 全局点赞 ======
let globalLiked = false;
// ====== 追更（关注）======
var FOLLOW_PATH_OFF = "M925.54 427.75a56 56 0 0 0-29.23-89.95l-195.52-50.15a56 56 0 0 1-33.37-24.25L559.29 93a56 56 0 0 0-94.57 0L356.6 263.4a56 56 0 0 1-33.38 24.25L127.7 337.8a56 56 0 0 0-29.23 89.95l128.7 155.51a56 56 0 0 1 12.75 39.23L227.2 823.94a56 56 0 0 0 76.52 55.59l187.66-74.34a56 56 0 0 1 41.25 0l187.66 74.34a56 56 0 0 0 76.52-55.59l-12.72-201.45a56 56 0 0 1 12.75-39.23zM719.8 519.5l-0.75 0.92a156 156 0 0 0-34.76 108.37L692.64 761l-123.18-48.78-1.1-0.44a156 156 0 0 0-113.81 0.44L331.37 761l8.35-132.22 0.07-1.19a156 156 0 0 0-35.58-108.1l-84.47-102.06 128.33-32.92 1.15-0.3A156 156 0 0 0 441 317l71-111.87L583 317l0.64 1a156 156 0 0 0 92.33 66.54l128.33 32.92z";
var FOLLOW_PATH_ON = "M925.53 427.36a56 56 0 0 0-29.22-89.95l-195.53-50.16A56 56 0 0 1 667.41 263L559.29 92.55a56 56 0 0 0-94.58 0L356.59 263a56 56 0 0 1-33.37 24.25l-195.53 50.16a56 56 0 0 0-29.22 89.95l128.7 155.5a56 56 0 0 1 12.74 39.23L227.2 823.55a56 56 0 0 0 76.51 55.59l187.66-74.35a56 56 0 0 1 41.26 0l187.66 74.35a56 56 0 0 0 76.51-55.59l-12.71-201.46a56 56 0 0 1 12.74-39.23z";
var isFollowing001 = false;
function _ft() { return (typeof getToken === 'function') ? getToken() : (localStorage.getItem('novel_token') || ''); }
function renderFollowBtn() {
    var btn = document.getElementById('followBtn');
    var icon = document.getElementById('followIcon');
    if (!btn || !icon) return;
    icon.innerHTML = '<path d="' + (isFollowing001 ? FOLLOW_PATH_ON : FOLLOW_PATH_OFF) + '" fill="currentColor"></path>';
    btn.classList.toggle('followed', isFollowing001);
    btn.title = isFollowing001 ? '已追更（点击取消）' : '点击追更';
}
function initFollowBtn() {
    renderFollowBtn();
    var t = _ft();
    var u = (typeof getUUID === 'function') ? getUUID() : (localStorage.getItem('novel_uuid') || '');
    if (!t && !u) return;
    fetch(API + '?action=follow_status&novel_id=' + NOVEL_ID + '&token=' + encodeURIComponent(t) + '&uuid=' + encodeURIComponent(u))
        .then(function(r){ return r.json(); })
        .then(function(d){ if (d.code === 0) { isFollowing001 = !!d.following; renderFollowBtn(); } })
        .catch(function(){});
}
function toggleFollow() {
    var t = _ft();
    if (!t) { if (typeof showAuth === 'function') { showAuth(); } else { showToast('请先登录后再追更~'); } return; }
    var btn = document.getElementById('followBtn');
    if (btn) btn.disabled = true;
    var form = new FormData();
    form.append('action', 'toggle_follow');
    form.append('novel_id', NOVEL_ID);
    form.append('token', t);
    fetch(API, { method: 'POST', body: form })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (btn) btn.disabled = false;
            if (d.code === 0) {
                isFollowing001 = !!d.following;
                renderFollowBtn();
                showToast(d.msg || (isFollowing001 ? '❤️ 已追更' : '已取消追更'));
            } else {
                showToast(d.msg || '操作失败');
                if (d.msg && d.msg.indexOf('登录') >= 0 && typeof showAuth === 'function') showAuth();
            }
        })
        .catch(function(){ if (btn) btn.disabled = false; showToast('网络开小差了~'); });
}
// 登录成功后刷新追更按钮状态
var _prevLoadUserAfterLogin = (typeof loadUserAfterLogin === 'function') ? loadUserAfterLogin : null;
window.loadUserAfterLogin = function() {
    try { if (_prevLoadUserAfterLogin) _prevLoadUserAfterLogin(); } catch(e) {}
    initFollowBtn();
};

function toggleGlobalLike() {
    const btn = document.getElementById('floatLike');
    const key = 'novel_global_liked';
    if (localStorage.getItem(key)) {
        showToast('已经点过赞啦❤️');
        return;
    }
    btn.classList.add('liked');
    updateLikeCount(parseInt(btn.querySelector('.count').textContent || '0') + 1);
    
    fetch(API + '?action=like_toggle&novel_id=' + NOVEL_ID, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.code === 0) {
                updateLikeCount(d.total_likes);
                localStorage.setItem(key, '1');
                showToast('❤️ 感谢喜欢！');
                syncUserData({liked_novel: true});
            }
        })
        .catch(() => {});
}

// ====== 昵称存储 ======
function loadNickname() {
    const saved = localStorage.getItem('novel_nick_admin');
    if (saved && !isAdmin) {
        document.getElementById('inputNickname').value = saved;
        updateAvatarDisplay(saved.charAt(0), null);
    }
}
function saveNickname() {
    const val = document.getElementById('inputNickname').value.trim();
    if (val) {
        localStorage.setItem('novel_nick_admin', val);
        syncUserData({nickname: val});
    }
}

// ====== 头像上传 ======
let cropper = null;
let avatarFile = null;

function loadAvatar() {
    const url = localStorage.getItem('novel_avatar_url');
    if (url) {
        const nick = document.getElementById('inputNickname').value.trim() || '匿';
        updateAvatarDisplay(nick.charAt(0), url);
    }
}

function selectAvatar() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/gif,image/webp';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            showToast('图片不能超过5MB~');
            return;
        }
        avatarFile = file;
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('cropImage').src = ev.target.result;
            document.getElementById('cropOverlay').classList.add('show');
            document.getElementById('cropModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                if (cropper) cropper.destroy();
                const img = document.getElementById('cropImage');
                cropper = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    background: false
                });
            }, 300);
        };
        reader.readAsDataURL(file);
    };
    input.click();
}

function closeCrop() {
    document.getElementById('cropOverlay').classList.remove('show');
    document.getElementById('cropModal').classList.remove('show');
    document.body.style.overflow = '';
    if (cropper) { cropper.destroy(); cropper = null; }
}

function confirmCrop() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({
        width: 200,
        height: 200,
        imageSmoothingQuality: 'high'
    });
    if (!canvas) {
        showToast('裁剪失败，请重试~');
        return;
    }
    
    canvas.toBlob(function(blob) {
        const form = new FormData();
        form.append('action', 'upload_avatar');
        form.append('avatar', blob, 'avatar.png');
        
        fetch(API, { method: 'POST', body: form })
            .then(r => r.json())
            .then(d => {
                if (d.code === 0) {
                    localStorage.setItem('novel_avatar_url', d.url);
                    syncUserData({avatar: d.url});
                    const nick = document.getElementById('inputNickname').value.trim() || '匿';
                    updateAvatarDisplay(nick.charAt(0), d.url);
                    showToast('✅ 头像上传成功~');
                    closeCrop();
                } else {
                    showToast(d.msg || '上传失败~');
                }
            })
            .catch(() => showToast('网络开小差了~'));
    }, 'image/png', 0.9);
}

function updateAvatarDisplay(char, url) {
    const avatar = document.getElementById('inputAvatar');
    const text = document.getElementById('avatarText');
    const badge = document.getElementById('avatarBadge');
    
    // Remove old img
    const oldImg = avatar.querySelector('img');
    if (oldImg) oldImg.remove();
    
    if (url) {
        text.style.display = 'none';
        const img = document.createElement('img');
        img.src = url;
        img.onerror = function() {
            text.style.display = '';
            text.textContent = char;
            badge.style.display = 'none';
        };
        avatar.insertBefore(img, avatar.firstChild);
        badge.style.display = 'flex';
    } else {
        text.style.display = '';
        text.textContent = char;
        badge.style.display = 'none';
    }
}

function getAvatarUrl() {
    const saved = localStorage.getItem('novel_avatar_url');
    if (saved) {
        // Extract filename from URL
        const parts = saved.split('/');
        return parts[parts.length - 1];
    }
    return '';
}

// ====== 工具函数 ======
function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 80) + 'px';
}
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2000);
}
function formatTime(t) {
    const d = new Date(t);
    const now = new Date();
    const diff = now - d;
    if (diff < 60000) return '刚刚';
    if (diff < 3600000) return Math.floor(diff/60000) + '分钟前';
    if (diff < 86400000) return Math.floor(diff/3600000) + '小时前';
    return (d.getMonth()+1) + '/' + d.getDate() + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
}
function goChapter(idx) {
    idx = parseInt(idx);
    if (idx >= 0 && idx < <?= count($chapterNames) ?>) {
        window.location.href = '?chapter=' + idx;
    }
}



// ====== 阅读位置自动保存/恢复 ======
(function() {
  var saveTimer = null;
  var chapterIdx = <?= $activeChapter ?>;
  var storageKey = 'novel_readpos_001_' + chapterIdx;

  // 保存位置到localStorage
  function savePosition() {
    var scrollY = window.scrollY || window.pageYOffset || 0;
    if (scrollY < 10) return; // 顶部不保存
    localStorage.setItem(storageKey, scrollY);
    // 同时记录总进度
    var totalHeight = document.documentElement.scrollHeight - window.innerHeight;
    var pct = totalHeight > 0 ? Math.round(scrollY / totalHeight * 100) : 0;
    localStorage.setItem('novel_readpos_001_latest', JSON.stringify({
      chapter: chapterIdx,
      scroll: scrollY,
      pct: pct,
      time: Date.now()
    }));
  }

  // 保存到服务器（登录用户）
  function saveToServer() {
    var uuid = getUUID();
    if (!uuid) return;
    var scrollY = window.scrollY || window.pageYOffset || 0;
    fetch(API, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        action: 'save_progress',
        uuid: uuid,
        novel_id: NOVEL_ID,
        chapter_index: chapterIdx,
        scroll_pos: Math.round(scrollY)
      })
    }).catch(function(){});
  }

  // 恢复位置
  function restorePosition() {
    // 先尝试服务器进度（登录用户）
    var uuid = getUUID();
    if (uuid) {
      fetch(API + '?action=get_progress&uuid=' + encodeURIComponent(uuid) + '&novel_id=' + NOVEL_ID)
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.code === 0 && d.data && parseInt(d.data.chapter) === chapterIdx) {
            var pos = parseInt(d.data.scroll) || 0;
            if (pos > 50) {
              setTimeout(function() { window.scrollTo(0, pos); }, 100);
              return;
            }
          }
          // 服务器没有 → 用本地
          fallbackRestore();
        })
        .catch(function() { fallbackRestore(); });
    } else {
      fallbackRestore();
    }
  }

  function fallbackRestore() {
    var saved = localStorage.getItem(storageKey);
    if (saved) {
      var pos = parseInt(saved);
      if (pos > 50) {
        setTimeout(function() { window.scrollTo(0, pos); }, 100);
      }
    }
  }

  // 滚动监听（防抖）
  window.addEventListener('scroll', function() {
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(savePosition, 800);
  });

  // 离开页面时保存到服务器
  window.addEventListener('beforeunload', function() {
    savePosition();
    saveToServer();
  });

  // 页面加载后恢复
  if (document.readyState === 'complete') {
    restorePosition();
  } else {
    window.addEventListener('load', function() {
      setTimeout(restorePosition, 200);
    });
  }
})();

// ====== 浮动按钮收起/展开 ======
function toggleFloatGroup() {
    const group = document.getElementById('floatBtnsGroup');
    const toggle = document.getElementById('floatToggle');
    const icon = document.getElementById('toggleIcon');
    group.classList.toggle('collapsed');
    toggle.classList.toggle('collapsed');
    if (group.classList.contains('collapsed')) {
        icon.innerHTML = '<path d="M864 256H160v-64h704v64z m0 256H160v-64h704v64z m0 256H160v-64h704v64z"/>';
    } else {
        icon.innerHTML = '<path d="M512 128c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z m0 320c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z m0 320c-70.4 0-128 57.6-128 128s57.6 128 128 128 128-57.6 128-128-57.6-128-128-128z"/>';
    }
}

// ====== TTS 语音朗读（服务器引擎） ======
let ttsSentences = [];
let ttsCurrentIdx = 0;
let ttsIsPlaying = false;
let ttsTimer = null;
let ttsStartTime = 0;
let ttsAudio = null;
let ttsLoading = false;

function toggleTts() {
    const panel = document.getElementById('ttsPlayer');
    const paras = document.querySelectorAll('.content p');
    ttsSentences = [];
    paras.forEach(p => {
        const txt = p.textContent.trim();
        if (txt && !p.classList.contains('chapter-line') && !p.classList.contains('empty')) {
            const parts = txt.split(/(?<=[。！？.!?，,])/);
            parts.forEach(s => {
                const st = s.trim();
                if (st.length > 0) ttsSentences.push(st);
            });
        }
    });
    
    if (ttsSentences.length === 0) {
        showToast('当前章节没有可朗读的内容~');
        return;
    }
    
    if (panel.classList.contains('show')) {
        ttsStop();
    } else {
        panel.classList.add('show');
        document.getElementById('ttsLabel').textContent = '🔊 ' + ttsSentences.length + '句';
        document.getElementById('ttsStatus').textContent = '♪';
        ttsCurrentIdx = 0;
        ttsStartTime = Date.now();
        updateProgress();
        // 预加载+播放第一句
        preloadAndPlay();
    }
}

function preloadAndPlay() {
    // 防止重复加载导致重音
    if (ttsLoading) return;
    if (ttsIsPlaying) return;
    if (ttsCurrentIdx >= ttsSentences.length) {
        ttsStop();
        showToast('🎉 本章朗读完毕~');
        return;
    }
    
    const text = ttsSentences[ttsCurrentIdx];
    if (!text.trim()) {
        ttsCurrentIdx++;
        setTimeout(preloadAndPlay, 100);
        return;
    }
    
    ttsLoading = true;
    document.getElementById('ttsLabel').textContent = '🔊 加载中 ' + (ttsCurrentIdx+1) + '/' + ttsSentences.length;
    
    const form = new FormData();
    form.append('text', text);
    
    fetch('/tts.php', { method: 'POST', body: form })
        .then(r => {
            if (!r.ok) throw new Error('TTS Failed');
            return r.blob();
        })
        .then(blob => {
            // 先停止之前的音频
            if (ttsAudio) {
                ttsAudio.pause();
                ttsAudio.src = '';
                ttsAudio = null;
            }
            const url = URL.createObjectURL(blob);
            const audio = new Audio(url);
            ttsAudio = audio;
            ttsLoading = false;
            
            let played = false;
            audio.oncanplaythrough = () => {
                if (played) return;
                played = true;
                ttsIsPlaying = true;
                document.getElementById('ttsPlayIcon').innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
                document.getElementById('ttsPlayBtn').classList.add('pulse');
                document.getElementById('ttsLabel').textContent = '🔊 ' + (ttsCurrentIdx+1) + '/' + ttsSentences.length;
                document.getElementById('ttsStatus').textContent = '♪';
                audio.play().catch(() => {
                    ttsCurrentIdx++;
                    URL.revokeObjectURL(url);
                    ttsIsPlaying = false;
                    setTimeout(preloadAndPlay, 200);
                });
            };
            
            audio.onended = () => {
                if (!played) return;
                played = false;
                ttsIsPlaying = false;
                document.getElementById('ttsPlayBtn').classList.remove('pulse');
                URL.revokeObjectURL(url);
                ttsCurrentIdx++;
                setTimeout(preloadAndPlay, 300);
            };
            
            audio.onerror = () => {
                if (!played && ttsLoading) {
                    ttsLoading = false;
                }
                ttsIsPlaying = false;
                document.getElementById('ttsPlayBtn').classList.remove('pulse');
                URL.revokeObjectURL(url);
                ttsCurrentIdx++;
                setTimeout(preloadAndPlay, 200);
            };
        })
        .catch(e => {
            ttsLoading = false;
            showToast('语音加载失败，跳过~');
            ttsCurrentIdx++;
            setTimeout(preloadAndPlay, 200);
        });
    
    updateProgress();
}

function ttsPlayPause() {
    if (ttsAudio) {
        if (ttsIsPlaying) {
            ttsAudio.pause();
            ttsIsPlaying = false;
            document.getElementById('ttsPlayIcon').innerHTML = '<path d="M8 5v14l11-7z"/>';
            document.getElementById('ttsPlayBtn').classList.remove('pulse');
            document.getElementById('ttsStatus').textContent = '⏸';
            clearInterval(ttsTimer);
        } else {
            ttsAudio.play().catch(() => {});
            ttsIsPlaying = true;
            document.getElementById('ttsPlayIcon').innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
            document.getElementById('ttsPlayBtn').classList.add('pulse');
            document.getElementById('ttsStatus').textContent = '♪';
            updateProgress();
        }
    } else {
        preloadAndPlay();
    }
}

function ttsNext() {
    if (ttsAudio) { ttsAudio.pause(); ttsAudio = null; }
    ttsIsPlaying = false;
    document.getElementById('ttsPlayBtn').classList.remove('pulse');
    if (ttsCurrentIdx < ttsSentences.length - 1) {
        ttsCurrentIdx++;
        setTimeout(preloadAndPlay, 200);
    }
}

function ttsPrev() {
    if (ttsAudio) { ttsAudio.pause(); ttsAudio = null; }
    ttsIsPlaying = false;
    document.getElementById('ttsPlayBtn').classList.remove('pulse');
    if (ttsCurrentIdx > 0) {
        ttsCurrentIdx--;
        setTimeout(preloadAndPlay, 200);
    }
}

function ttsStop() {
    if (ttsAudio) { ttsAudio.pause(); ttsAudio = null; }
    ttsIsPlaying = false;
    document.getElementById('ttsPlayBtn').classList.remove('pulse');
    document.getElementById('ttsPlayIcon').innerHTML = '<path d="M8 5v14l11-7z"/>';
    document.getElementById('ttsPlayer').classList.remove('show');
    document.getElementById('ttsProgressFill').style.width = '0%';
    document.getElementById('ttsTime').textContent = '00:00';
    document.getElementById('ttsStatus').textContent = '听';
    clearInterval(ttsTimer);
}

function updateProgress() {
    clearInterval(ttsTimer);
    ttsTimer = setInterval(() => {
        if (ttsIsPlaying && ttsAudio && !ttsAudio.paused) {
            const elapsed = Math.floor((Date.now() - ttsStartTime) / 1000);
            const m = Math.floor(elapsed / 60);
            const s = elapsed % 60;
            document.getElementById('ttsTime').textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
            const estTotal = 5;
            const progress = Math.min(((ttsCurrentIdx * estTotal) + Math.min(elapsed % estTotal, estTotal)) / Math.max(ttsSentences.length * estTotal, 1) * 100, 100);
            document.getElementById('ttsProgressFill').style.width = progress + '%';
        }
    }, 300);
}

function ttsSeek(e) {}

// 页面离开时停止朗读
window.addEventListener('beforeunload', () => {
    if (window.speechSynthesis) window.speechSynthesis.cancel();
});
</script>

<!-- ====== 阅读设置弹窗 ====== -->
<div class="settings-overlay" id="settingsOverlay" onclick="closeSettings()"></div>
<div class="settings-modal" id="settingsModal">
  <div class="s-title">
    <span>⚙️ 阅读设置</span>
    <button class="s-close" onclick="closeSettings()">✕</button>
  </div>

  <!-- 字体大小 -->
  <div class="s-group">
    <label class="s-label">📝 字体大小</label>
    <div class="font-size-control">
      <span class="size-label">A</span>
      <input type="range" id="fontSizeSlider" min="15" max="25" step="1" value="20" oninput="changeFontSize(this.value)">
      <span class="size-label" style="font-size:16px;font-weight:700;">A</span>
    </div>
  </div>

  <!-- 主题选择 -->
  <div class="s-group">
    <label class="s-label">🎨 主题</label>
    <div class="theme-group" id="themeGroup">
      <button data-theme-val="light" class="active" onclick="selectTheme('light')">☀️ 始终明亮</button>
      <button data-theme-val="dark" onclick="selectTheme('dark')">🌙 始终黑暗</button>
      <button data-theme-val="system" onclick="selectTheme('system')">🔄 跟随系统</button>
    </div>
  </div>

  <!-- 评论通知 -->
  <div class="s-group">
    <label class="s-label">🔔 评论通知</label>
    <div class="toggle-row">
      <span style="font-size:13px;color:var(--modal-text);">点赞和回复邮件通知</span>
      <label class="toggle-switch">
        <input type="checkbox" id="notifToggle" onchange="toggleNotif(this.checked)">
        <span class="slider"></span>
      </label>
    </div>
    <div class="email-input-box" id="emailInputBox">
      <input type="email" id="emailInput" placeholder="请输入邮箱地址" oninput="saveEmail(this.value)">
      <div class="hint">开启后可收到发布评论的点赞和回复通知</div>
    </div>
  </div>
</div>

<script>
// ====== 阅读器主题 ======
function toggleReaderTheme() {
  const html = document.documentElement;
  const current = html.getAttribute('data-theme');
  if (current === 'dark') {
    html.removeAttribute('data-theme');
    localStorage.setItem('readerTheme', 'light');
  } else {
    html.setAttribute('data-theme', 'dark');
    localStorage.setItem('readerTheme', 'dark');
  }
  updateThemeIcon();
  syncSettings();
}

function updateThemeIcon() {
  const html = document.documentElement;
  const isDark = html.getAttribute('data-theme') === 'dark';
  const path = document.getElementById('themePath');
  const icon = document.getElementById('themeIcon');
  if (isDark) {
    // 暗夜模式 → 太阳图标（点击变明亮）
    icon.setAttribute('viewBox', '0 0 24 24');
    path.setAttribute('d', 'M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10z');
  } else {
    // 明亮模式 → 月亮图标（点击变暗夜）
    icon.setAttribute('viewBox', '0 0 24 24');
    path.setAttribute('d', 'M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z');
  }
}

// ====== 设置弹窗 ======
function openSettings() {
  document.getElementById('settingsOverlay').classList.add('show');
  document.getElementById('settingsModal').classList.add('show');
  document.body.style.overflow = 'hidden';
  
  // 恢复已保存设置
  const fontSize = localStorage.getItem('readerFontSize') || '20';
  document.getElementById('fontSizeSlider').value = fontSize;
  
  const theme = localStorage.getItem('readerTheme') || 'light';
  document.querySelectorAll('#themeGroup button').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.themeVal === theme);
  });
  
  const notifOn = localStorage.getItem('readerNotif') === 'on';
  document.getElementById('notifToggle').checked = notifOn;
  document.getElementById('emailInputBox').classList.toggle('show', notifOn);
  
  const email = localStorage.getItem('readerEmail') || '';
  document.getElementById('emailInput').value = email;
}

function closeSettings() {
  document.getElementById('settingsOverlay').classList.remove('show');
  document.getElementById('settingsModal').classList.remove('show');
  document.body.style.overflow = '';
}

// ====== 字体大小 ======
function changeFontSize(val) {
  document.querySelectorAll('.content p').forEach(p => {
    p.style.fontSize = val + 'px';
  });
  localStorage.setItem('readerFontSize', val);
  syncSettings();
}

// ====== 主题选择 ======
function selectTheme(val) {
  const html = document.documentElement;
  document.querySelectorAll('#themeGroup button').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.themeVal === val);
  });
  localStorage.setItem('readerTheme', val);
  
  if (val === 'light') {
    html.removeAttribute('data-theme');
  } else if (val === 'dark') {
    html.setAttribute('data-theme', 'dark');
  } else {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
      html.setAttribute('data-theme', 'dark');
    } else {
      html.removeAttribute('data-theme');
    }
  }
  updateThemeIcon();
  syncSettings();
}

// ====== 通知开关 ======
function toggleNotif(on) {
  document.getElementById('emailInputBox').classList.toggle('show', on);
  if (on) {
    localStorage.setItem('readerNotif', 'on');
  } else {
    localStorage.removeItem('readerNotif');
  }
  syncSettings();
}

function saveEmail(val) {
  localStorage.setItem('readerEmail', val);
  syncSettings();
}

// ====== 同步所有设置到服务器 ======
function syncSettings() {
  const s = {
    fontSize: localStorage.getItem('readerFontSize') || '20',
    theme: localStorage.getItem('readerTheme') || 'light',
    notif: localStorage.getItem('readerNotif') === 'on',
    email: localStorage.getItem('readerEmail') || ''
  };
  syncUserData({settings: s});
}

// ====== 初始化加载已保存设置 ======
document.addEventListener('DOMContentLoaded', function() {
  // 字体大小
  const savedSize = localStorage.getItem('readerFontSize');
  if (savedSize) {
    document.querySelectorAll('.content p').forEach(p => {
      p.style.fontSize = savedSize + 'px';
    });
  }
  
  // 主题
  const savedTheme = localStorage.getItem('readerTheme');
  if (savedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  } else if (savedTheme === 'system') {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  }
  updateThemeIcon();
  
  // 监听系统主题变化
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    const savedTheme = localStorage.getItem('readerTheme');
    if (savedTheme === 'system') {
      if (e.matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
    }
  });
});

// ====== 阅读心跳积分（防刷，每1分钟+1分） ======
(function() {
  var heartbeatTimer = null;
  var heartbeatActive = false;
  
  function sendHeartbeat() {
    var token = localStorage.getItem('novel_token');
    if (!token) return;
    if (heartbeatActive) return;
    heartbeatActive = true;
    
    if (document.hidden) {
      heartbeatActive = false;
      return;
    }
    
    fetch(API + '?action=heartbeat&token=' + encodeURIComponent(token))
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.code === 0 && d.data && d.data.score) {
          var el = document.getElementById('readingScore');
          if (el) el.textContent = d.data.score;
        }
      })
      .catch(function(){})
      .finally(function() {
        heartbeatActive = false;
      });
  }
  
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
      setTimeout(sendHeartbeat, 2000);
    }
  });
  
  window.addEventListener('beforeunload', function() {
    if (heartbeatTimer) clearInterval(heartbeatTimer);
  });
  
  setTimeout(sendHeartbeat, 3000);
  heartbeatTimer = setInterval(sendHeartbeat, 60000);
})();

// ====== 阅读页签到按钮 ======
(function() {
  var token = localStorage.getItem('novel_token');
  if (!token) return;
  
  fetch(API + '?action=get_points_summary&token=' + encodeURIComponent(token))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.code !== 0 || !d.data) return;
      var data = d.data;
      var el = document.querySelector('.header .author');
      if (!el) return;
      
      if (data.today_checked_in) {
        var badge = document.createElement('span');
        badge.style.cssText = 'display:inline-block;background:linear-gradient(135deg,#4caf50,#388e3c);color:#fff;padding:1px 10px;border-radius:10px;font-size:10px;font-weight:600;margin-left:8px;vertical-align:middle';
        badge.textContent = '🔥 ' + data.consecutive_checkin_days + '天';
        el.appendChild(badge);
      } else {
        var row = document.createElement('div');
        row.style.cssText = 'text-align:center;margin-top:6px';
        var btn = document.createElement('button');
        btn.textContent = '📍 签到领积分';
        btn.style.cssText = 'background:linear-gradient(135deg,#ff9a56,#e67e22);color:#fff;border:none;border-radius:20px;padding:4px 18px;font-size:11px;font-weight:700;cursor:pointer;transition:all 0.25s;font-family:inherit';
        btn.onclick = function() {
          btn.disabled = true;
          btn.textContent = '签到中...';
          fetch(API + '?action=checkin&token=' + encodeURIComponent(token))
            .then(function(r) { return r.json(); })
            .then(function(cd) {
              if (cd.code === 0) {
                appToast('✅ 签到成功！+' + (cd.data.total_earned || cd.data.points) + '积分');
                btn.textContent = '已签到 ✓';
                btn.style.background = '#aaa';
                btn.disabled = true;
                setTimeout(function() { location.reload(); }, 2000);
              } else {
                appToast(cd.msg || '签到失败');
                btn.textContent = '📍 签到领积分';
                btn.disabled = false;
              }
            })
            .catch(function() {
              appToast('❌ 网络错误');
              btn.textContent = '📍 签到领积分';
              btn.disabled = false;
            });
        };
        row.appendChild(btn);
        el.parentNode.insertBefore(row, el.nextSibling);
      }
    })
    .catch(function(){});
})();
</script>
<script src="/assets/app.js?v=30"></script>
<script>
localStorage.setItem('lastReading', '/001/');
try { if (typeof initApp === 'function') initApp('reading'); } catch(e) {}
</script>
<script>
/* ====== 本地化段落高亮笔记（LocalStorage，无需登录/数据库） ====== */
(function(){
  var NID='001', CH=<?= (int)$activeChapter ?>;
  var KEY='novel_notes_'+NID+'_'+CH;
  var contentEl=document.querySelector('.content');
  if(!contentEl) return;
  function load(){ try{return JSON.parse(localStorage.getItem(KEY)||'[]');}catch(e){return [];} }
  function save(a){ try{localStorage.setItem(KEY, JSON.stringify(a));}catch(e){} }
  function escLocal(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  function highlightText(text, note){
    if(!text) return false;
    var walker=document.createTreeWalker(contentEl, NodeFilter.SHOW_TEXT, null);
    var node;
    while(node=walker.nextNode()){
      var p=node.parentNode;
      if(p && p.classList && p.classList.contains('hl-note')) continue;
      var idx=node.nodeValue.indexOf(text);
      if(idx>-1){
        var range=document.createRange();
        range.setStart(node, idx); range.setEnd(node, idx+text.length);
        var mark=document.createElement('mark');
        mark.className='hl-note'; mark.setAttribute('data-note', note||''); mark.title=note||'点击查看笔记';
        try{ range.surroundContents(mark);}catch(e){ return false; }
        mark.addEventListener('click', function(e){ e.stopPropagation(); openEdit(this); });
        return true;
      }
    }
    return false;
  }
  function applyAll(){ load().forEach(function(n){ highlightText(n.text, n.note); }); }
  var bar=document.createElement('div'); bar.id='hlNoteBar'; bar.textContent='📝 添加笔记';
  document.body.appendChild(bar);
  var pendingText='';
  function hideBar(){ bar.style.display='none'; }
  document.addEventListener('selectionchange', function(){
    var sel=document.getSelection();
    if(!sel || sel.isCollapsed){ hideBar(); return; }
    var t=sel.toString().replace(/\s+/g,' ').trim();
    if(!t || t.length<2){ hideBar(); return; }
    var anc=sel.anchorNode; if(anc && anc.nodeType===3) anc=anc.parentNode;
    if(!anc || !contentEl.contains(anc)){ hideBar(); return; }
    pendingText=sel.toString();
    var rect=sel.getRangeAt(0).getBoundingClientRect();
    bar.style.display='block';
    bar.style.top=(window.scrollY+rect.top-42)+'px';
    bar.style.left=(window.scrollX+rect.left+rect.width/2-46)+'px';
  });
  bar.addEventListener('mousedown', function(e){ e.preventDefault(); });
  bar.addEventListener('click', function(){ var t=pendingText; hideBar(); openCreate(t); });
  function modal(html){
    var mask=document.createElement('div'); mask.className='hl-modal-mask';
    mask.innerHTML='<div class="hl-modal">'+html+'</div>';
    mask.addEventListener('click', function(e){ if(e.target===mask) document.body.removeChild(mask); });
    document.body.appendChild(mask); return mask;
  }
  function openCreate(text){
    var t=(text||'').replace(/\s+$/,'');
    var m=modal('<h4>📝 添加笔记</h4><div class="hl-quote">'+escLocal(t)+'</div><textarea id="hlTa" placeholder="写下你的想法（可留空仅高亮）"></textarea><div class="hl-actions"><button class="hl-cancel">取消</button><button class="hl-save">保存</button></div>');
    m.querySelector('.hl-cancel').onclick=function(){ document.body.removeChild(m); };
    m.querySelector('.hl-save').onclick=function(){
      var note=m.querySelector('#hlTa').value.trim();
      var ok=highlightText(t, note);
      if(ok){ var a=load(); a.push({text:t, note:note, ts:Date.now()}); save(a); if(window.appToast) appToast('\u2705 已保存笔记'); }
      else { if(window.appToast) appToast('\u274C 高亮失败，请重选文字'); }
      document.body.removeChild(m);
      var s=document.getSelection(); if(s) s.removeAllRanges();
    };
  }
  function openEdit(mark){
    var text=mark.textContent, note=mark.getAttribute('data-note')||'';
    var m=modal('<h4>📝 我的笔记</h4><div class="hl-quote">'+escLocal(text)+'</div><textarea id="hlTa">'+escLocal(note)+'</textarea><div class="hl-actions"><button class="hl-del">删除</button><button class="hl-cancel">关闭</button><button class="hl-save">更新</button></div>');
    m.querySelector('.hl-cancel').onclick=function(){ document.body.removeChild(m); };
    m.querySelector('.hl-save').onclick=function(){
      var note2=m.querySelector('#hlTa').value.trim();
      mark.setAttribute('data-note', note2); mark.title=note2||'点击查看笔记';
      var a=load(); for(var i=0;i<a.length;i++){ if(a[i].text===text){ a[i].note=note2; break; } } save(a);
      document.body.removeChild(m);
    };
    m.querySelector('.hl-del').onclick=function(){
      var a=load().filter(function(x){ return x.text!==text; }); save(a);
      var parent=mark.parentNode; while(mark.firstChild) parent.insertBefore(mark.firstChild, mark); parent.removeChild(mark); parent.normalize();
      document.body.removeChild(m);
    };
  }
  if(document.readyState!=='loading') applyAll();
  else document.addEventListener('DOMContentLoaded', applyAll);
})();
</script>
</body>
</html>
