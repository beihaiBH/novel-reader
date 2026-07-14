<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>📚 书架 - 示例小说 |  悬疑小说在线阅读</title>
<meta name="description" content="《示例小说》是作者佚名创作的悬疑推理小说。在线阅读最新章节，支持语音朗读、评论区互动。一个关于犯罪与梦境、追捕与自我的故事。">
<meta name="keywords" content="示例小说,悬疑小说,推理小说,佚名,在线阅读,免费小说,犯罪小说">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://example.com/">
<meta property="og:title" content="📚 书架 - 示例小说">
<meta property="og:description" content="佚名悬疑推理小说《示例小说》，在线阅读最新章节，支持语音朗读。">
<meta property="og:type" content="website">
<meta property="og:url" content="https://example.com/">
<meta property="og:image" content="https://example.com/001/cover.jpg">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="📚 书架 - 示例小说">
<meta name="twitter:description" content="佚名悬疑推理小说，在线阅读最新章节。">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "📚 书架 - 示例小说",
  "description": "佚名创作的悬疑推理小说《示例小说》在线阅读。",
  "url": "https://example.com/",
  "author": {
    "@type": "Person",
    "name": "佚名"
  },
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {"@type": "ListItem", "position": 1, "name": "首页", "item": "https://example.com/"},
      {"@type": "ListItem", "position": 2, "name": "小说书架", "item": "https://example.com/"}
    ]
  }
}
</script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.breadcrumb {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    background: rgba(26,20,16,0.85); backdrop-filter: blur(8px);
    padding: 8px 16px; font-size: 12px; color: #888;
}
.breadcrumb a { color: #c4a882; text-decoration: none; }
.breadcrumb a:hover { color: #e0d0b8; }
.breadcrumb .sep { color: #555; margin: 0 4px; }
body {
    background: #1a1410;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-family: "Noto Serif SC", "Source Han Serif SC", serif;
    perspective: 1200px;
}

/* ====== 背景氛围 ====== */
.bg-ambient {
    position: fixed; inset: 0;
    background: 
        radial-gradient(ellipse at 30% 50%, rgba(139,111,92,0.12) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 30%, rgba(230,180,34,0.06) 0%, transparent 40%),
        radial-gradient(ellipse at 50% 80%, rgba(44,24,16,0.3) 0%, transparent 50%);
    pointer-events: none;
}

/* ====== 粒子 ====== */
.particles {
    position: fixed; inset: 0;
    pointer-events: none;
    overflow: hidden;
}
.particle {
    position: absolute;
    width: 3px; height: 3px;
    background: rgba(255,215,0,0.15);
    border-radius: 50%;
    animation: particleFloat linear infinite;
}
@keyframes particleFloat {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-20vh) rotate(720deg); opacity: 0; }
}

/* ====== 书架 ====== */
.shelf-container {
    position: relative;
    width: 600px; height: 520px;
    flex-shrink: 0;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    margin-top: 22vh;
    animation: shelfEnter 1.2s cubic-bezier(0.34,1.56,0.64,1) both;}
}
@keyframes shelfEnter {
    0% { opacity: 0; transform: translateY(60px) scale(0.9); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

/* 书架木板 */
.shelf-board {
    position: absolute;
    left: 0; right: 0;
    height: 24px;
    background: linear-gradient(180deg, #5c3a2a 0%, #3a2218 40%, #2c1810 100%);
    border-radius: 4px;
    box-shadow: 
        0 4px 20px rgba(0,0,0,0.5),
        inset 0 1px 0 rgba(255,255,255,0.05),
        inset 0 -1px 0 rgba(0,0,0,0.3);
    transform-style: preserve-3d;
}
.shelf-board::before {
    content: ''; position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(180deg, rgba(255,215,0,0.06), transparent);
    border-radius: 4px 4px 0 0;
}
.shelf-board::after {
    content: ''; position: absolute;
    bottom: -6px; left: -4px; right: -4px; height: 8px;
    background: linear-gradient(180deg, #1a1410 0%, transparent 100%);
    filter: blur(3px);
    border-radius: 50%;
}
.shelf-board.bot { bottom: 0; }
.shelf-board.top { bottom: 52%; }

/* 书架侧板 */
.shelf-side {
    position: absolute;
    bottom: 0;
    width: 16px; height: 400px;
    background: linear-gradient(90deg, #3a2218, #2c1810);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);
}
.shelf-side.left { left: -16px; border-radius: 4px 4px 0 4px; }
.shelf-side.right { right: -16px; border-radius: 4px 4px 4px 0; }

/* 书架背板 */
.shelf-back {
    position: absolute;
    bottom: 24px; left: -14px; right: -14px;
    height: 376px;
    background: linear-gradient(180deg, #2a1a12, #1a1410);
    border-radius: 0 0 2px 2px;
    transform: translateZ(-30px);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
}

/* ====== 3D 书本 ====== */
.book-wrapper {
    position: absolute;
    bottom: 57%;
    width: 200px; height: 260px;
    cursor: pointer;
    transform-style: preserve-3d;
    transform: rotateX(-5deg) rotateY(-20deg);
    transition: transform 1s cubic-bezier(0.34,1.56,0.64,1);
    animation: bookAppear 1.5s 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes bookAppear {
    0% { opacity: 0; transform: rotateX(20deg) rotateY(-40deg) translateY(80px); }
    100% { opacity: 1; transform: rotateX(-5deg) rotateY(-20deg) translateY(0); }
}
.book-wrapper:hover {
    transform: rotateX(-2deg) rotateY(-10deg) scale(1.03);
}
.book-wrapper:active {
    transform: rotateX(0deg) rotateY(0deg) scale(0.96);
}

/* 书本主体 */
.book {
    width: 100%; height: 100%;
    position: relative;
    transform-style: preserve-3d;
}

/* 封面 */
.book-cover {
    position: absolute;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #2c1810 0%, #4a2c20 30%, #2c1810 100%);
    border-radius: 4px 8px 8px 4px;
    transform: translateZ(12px);
    box-shadow: 
        0 8px 30px rgba(0,0,0,0.5),
        inset 0 0 40px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 24px;
    overflow: hidden;
    backface-visibility: hidden;
}
.book-cover::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: 
        radial-gradient(ellipse at 30% 20%, rgba(255,215,0,0.08), transparent 50%),
        radial-gradient(ellipse at 80% 60%, rgba(139,111,92,0.1), transparent 40%);
    pointer-events: none;
}

/* 封面装饰边框 */
.cover-border {
    position: absolute;
    top: 12px; left: 12px; right: 12px; bottom: 12px;
    border: 1px solid rgba(196,168,130,0.2);
    border-radius: 2px;
    pointer-events: none;
}
.cover-border-inner {
    position: absolute;
    top: 16px; left: 16px; right: 16px; bottom: 16px;
    border: 1px solid rgba(196,168,130,0.1);
    border-radius: 1px;
    pointer-events: none;
}

/* 封面文字 */
.cover-icon {
    font-size: 42px;
    margin-bottom: 16px;
    position: relative;
}
.cover-title {
    color: #f0e6d3;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 6px;
    text-align: center;
    position: relative;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.cover-subtitle {
    color: #c4a882;
    font-size: 13px;
    letter-spacing: 4px;
    margin-top: 8px;
    position: relative;
}
.cover-divider {
    width: 40px; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(196,168,130,0.5), transparent);
    margin: 14px auto;
    position: relative;
}
.cover-author {
    color: #a08060;
    font-size: 14px;
    letter-spacing: 3px;
    position: relative;
}
.cover-badge {
    position: absolute;
    top: 20px; right: 20px;
    background: linear-gradient(135deg, #f7d875, #e6b422);
    color: #5a3e0a;
    font-size: 10px; font-weight: 700;
    padding: 3px 10px;
    border-radius: 10px;
    letter-spacing: 1px;
    box-shadow: 0 2px 8px rgba(230,180,34,0.3);
}

/* 书脊 */
.book-spine {
    position: absolute;
    left: 0;
    width: 12px; height: 100%;
    background: linear-gradient(90deg, #1a0e08, #3a2218 40%, #2c1810 100%);
    transform: rotateY(-90deg) translateZ(-6px) translateX(6px);
    transform-origin: left center;
    border-radius: 2px 0 0 2px;
    box-shadow: inset -1px 0 0 rgba(255,255,255,0.03);
}
.book-spine::after {
    content: '示例小说';
    position: absolute;
    bottom: 20px; left: 50%;
    transform: translateX(-50%) rotate(-90deg);
    transform-origin: center;
    color: rgba(196,168,130,0.4);
    font-size: 11px;
    letter-spacing: 4px;
    white-space: nowrap;
    writing-mode: vertical-rl;
}

/* 书页（右侧） */
.book-pages {
    position: absolute;
    right: 0; top: 2px;
    width: 16px; height: calc(100% - 4px);
    background: repeating-linear-gradient(
        90deg,
        #f5f0e8 0px, #f5f0e8 1px,
        #e8ddd0 1px, #e8ddd0 2px,
        #f0e8dc 2px, #f0e8dc 3px
    );
    transform: rotateY(90deg) translateZ(-8px) translateX(-8px);
    transform-origin: right center;
    border-radius: 0 4px 4px 0;
    box-shadow: inset -1px 0 2px rgba(0,0,0,0.1);
}

/* 书页（底部） */
.book-pages-bottom {
    position: absolute;
    bottom: 0;
    width: 100%; height: 12px;
    background: repeating-linear-gradient(
        180deg,
        #f5f0e8 0px, #f5f0e8 1px,
        #e8ddd0 1px, #e8ddd0 2px
    );
    transform: rotateX(90deg) translateZ(-6px) translateY(6px);
    transform-origin: bottom center;
    border-radius: 0 0 4px 4px;
}

/* 封底 */
.book-back {
    position: absolute;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #1a0e08, #2c1810);
    border-radius: 4px 8px 8px 4px;
    transform: translateZ(-12px) rotateY(180deg);
    backface-visibility: hidden;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.3);
}

/* ====== 点击提示 ====== */
.click-hint {
    position: absolute;
    bottom: -50px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(196,168,130,0.4);
    font-size: 13px;
    letter-spacing: 2px;
    white-space: nowrap;
    animation: hintPulse 2s ease-in-out infinite;
}
@keyframes hintPulse {
    0%, 100% { opacity: 0.3; transform: translateX(-50%) translateY(0); }
    50% { opacity: 0.8; transform: translateX(-50%) translateY(-4px); }
}

/* ====== 底部装饰文字 ====== */
.shelf-label {
    position: absolute;
    bottom: -80px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(196,168,130,0.15);
    font-size: 12px;
    letter-spacing: 6px;
    white-space: nowrap;
}

/* 装饰元素 */
.shelf-deco {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    pointer-events: none;
}
.shelf-deco.bot-deco {
    bottom: 5%;
}
.deco-pot {
    font-size: 32px;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
    animation: decoSway 3s ease-in-out infinite;
}
@keyframes decoSway {
    0%, 100% { transform: rotate(-2deg); }
    50% { transform: rotate(2deg); }
}

/* 底层书堆 */
.deco-book-stack {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}
.dbook {
    width: 70px;
    border-radius: 2px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.3);
}
.dbook-1 {
    height: 12px;
    background: linear-gradient(90deg, #8b4513, #a0522d);
    transform: rotate(-3deg);
}
.dbook-2 {
    height: 10px;
    background: linear-gradient(90deg, #556b2f, #6b8e23);
    transform: rotate(2deg);
}
.dbook-3 {
    height: 14px;
    width: 60px;
    background: linear-gradient(90deg, #8b0000, #a52a2a);
    transform: rotate(-1deg);
}

/* ====== 加载/光效 ====== */
.book-glow {
    position: absolute;
    top: -20px; left: -20px;
    width: calc(100% + 40px); height: calc(100% + 40px);
    pointer-events: none;
    background: radial-gradient(ellipse at 50% 30%, rgba(255,215,0,0.06) 0%, transparent 60%);
    animation: glowPulse 3s ease-in-out infinite alternate;
}
@keyframes glowPulse {
    0% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* ====== 进入按钮（点击区域提示） ====== */
.enter-btn {
    position: absolute;
    bottom: -48px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(196,168,130,0.15);
    color: rgba(196,168,130,0.5);
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 12px;
    letter-spacing: 2px;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(4px);
    white-space: nowrap;
}
.enter-btn:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(196,168,130,0.3);
    color: rgba(196,168,130,0.8);
}

/* ====== 响应式 ====== */
@media (max-width: 640px) {
    .shelf-container { width: 100%; height: 420px; margin-top: 16vh; }
    .book-wrapper { width: 150px; height: 200px; bottom: 56%; }
    .cover-title { font-size: 18px; letter-spacing: 3px; }
    .cover-icon { font-size: 28px; }
    .cover-author { font-size: 11px; }
    .shelf-board { height: 18px; }
    .shelf-side { width: 10px; height: 300px; }
    .shelf-back { height: 278px; }
    .shelf-board.top { bottom: 50%; }
    .shelf-deco.bot-deco { bottom: 4%; }
    .book-spine { width: 10px; }
    .book-pages { width: 12px; }
    .book-pages-bottom { height: 10px; }
    .cover-badge { top: 14px; right: 14px; font-size: 9px; padding: 2px 8px; }
    .click-hint { font-size: 11px; }
    .deco-pot { font-size: 24px; }
    .dbook { width: 50px; }
}

/* 横屏适配 */
@media (orientation: landscape) and (max-height: 500px) {
    .shelf-container { height: 280px; }
    .book-wrapper { width: 120px; height: 160px; bottom: 56%; }
    .shelf-side { height: 200px; }
    .shelf-back { height: 178px; }
    .shelf-board.top { bottom: 48%; }
    .shelf-deco { display: none; }
}
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
</head>
<body>

<!-- 面包屑导航（360搜索富摘要） -->
<div class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
  <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <a href="https://example.com/" itemprop="item"><span itemprop="name">🏠 首页</span></a>
    <meta itemprop="position" content="1">
  </span>
  <span class="sep"> › </span>
  <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <span itemprop="name">📚 小说书架</span>
    <meta itemprop="position" content="2">
  </span>
</div>

<div class="bg-ambient"></div>

<div class="particles" id="particles"></div>

<div class="shelf-container">
    <!-- 书架 -->
    <div class="shelf-side left"></div>
    <div class="shelf-side right"></div>
    <div class="shelf-back"></div>
    <div class="shelf-board bot"></div>
    <div class="shelf-board top"></div>

    <!-- 装饰：底层盆栽+书堆 -->
    <div class="shelf-deco bot-deco">
      <div class="deco-pot">🌿</div>
      <div class="deco-book-stack">
        <div class="dbook dbook-1"></div>
        <div class="dbook dbook-2"></div>
        <div class="dbook dbook-3"></div>
      </div>
    </div>

    <!-- 书本 -->
    <div class="book-wrapper" id="bookWrapper" onclick="enterBook()">
        <div class="book-glow"></div>
        <div class="book">
            <div class="book-cover">
                <div class="cover-border"></div>
                <div class="cover-border-inner"></div>
                <div class="cover-badge">📖 悬疑</div>
                <div class="cover-icon">🕵️</div>
                <div class="cover-divider"></div>
                <div class="cover-title">示例小说</div>
                <div class="cover-subtitle">——《犯罪与梦》——</div>
                <div class="cover-divider"></div>
                <div class="cover-author">佚名 作品</div>
            </div>
            <div class="book-spine"></div>
            <div class="book-pages"></div>
            <div class="book-pages-bottom"></div>
            <div class="book-back"></div>
        </div>
        <div class="click-hint">📖 点击进入阅读</div>
    </div>

    <div class="shelf-label">✦ 书 架 ✦</div>
</div>

<button class="enter-btn" onclick="enterBook()">📖 打开阅读</button>

<script>
// 生成粒子
(function() {
    const container = document.getElementById('particles');
    for (let i = 0; i < 25; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDuration = (8 + Math.random() * 12) + 's';
        p.style.animationDelay = (Math.random() * 10) + 's';
        p.style.width = (2 + Math.random() * 3) + 'px';
        p.style.height = p.style.width;
        p.style.opacity = 0.1 + Math.random() * 0.2;
        container.appendChild(p);
    }
})();

// 进入阅读
function enterBook() {
    const wrapper = document.getElementById('bookWrapper');
    wrapper.style.transform = 'rotateX(0deg) rotateY(0deg) scale(0.9) translateZ(100px)';
    wrapper.style.opacity = '0';
    setTimeout(() => {
        // 自适应域名路径
        const isSub = window.location.hostname === 'example.com';
        window.location.href = isSub ? '/001/' : '/novel/001/';
    }, 400);
}

// 键盘点击支持
document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        enterBook();
    }
});


</script>

<!-- 下推间距，让版权需要滚动才能看到 -->
<div class="footer-spacer"></div>

<!-- 版权信息 -->
<div class="novel-footer">
  <div class="nf-copyright">
    ©2026 佚名 著作权所有，未经许可严禁商用、转载、改编、录制传播。<br>
    作品原作发布地址：<a href="https://www.dnforlife.com/?p=234299&preview=true" target="_blank" rel="noopener">https://www.dnforlife.com/?p=234299&preview=true</a><br>
    依据《中华人民共和国著作权法》，作者享有作品完整信息网络传播权、改编权、复制发行权等全部权利；发现侵权行为可留存证据联系作者维权。
  </div>
  <div class="nf-tech">
    网站服务器技术支持：天津海云互联网络科技有限公司<br>
    云计算底层服务：LGZ科技集团有限公司
  </div>
</div>

<style>
.footer-spacer {
  height: 20vh;
  min-height: 100px;
}

.novel-footer {
  text-align: center;
  padding: 30px 20px 20px;
  color: #b0a090;
  font-size: 11px;
  line-height: 1.7;
  letter-spacing: 0.5px;
  border-top: 1px solid #e8ddd0;
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
  color: #c4b8a8;
  font-size: 10px;
}
</style>

<script src="/assets/app.js?v=2"></script>
<script>
try { if (typeof initApp === 'function') initApp('home'); } catch(e) {}
</script>
</body>
</html>
