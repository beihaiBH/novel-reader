<?php
/**
 * 个人主页 - example.com
 */
$uuid = $_GET['uuid'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>👤 个人主页 | 小说站</title>
<link rel="stylesheet" href="/assets/app.css?v=26">
<link rel="stylesheet" href="/assets/levels.css?v=1">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #f5f0eb; font-family: "Noto Serif SC", "Source Han Serif SC", serif; color: #3a3a3a; min-height: 100vh; }
[data-theme="dark"] body { background: #1a1410; color: #d4c8b8; }
.pf-loading { display: flex; align-items: center; justify-content: center; min-height: 80vh; font-size: 14px; color: #aaa; }
.pf-loading::before { content: ''; width: 20px; height: 20px; border: 2px solid #e0d5c8; border-top-color: #c4a882; border-radius: 50%; animation: pspin 0.6s linear infinite; margin-right: 10px; }
@keyframes pspin { to { transform: rotate(360deg); } }
.submission-item { padding: 12px; border-bottom: 1px solid #f0ebe5; font-size: 13px; }
.submission-item:last-child { border-bottom: none; }
.submission-item .s-title { font-weight: 600; color: #2c1810; }
.submission-item .s-time { font-size: 11px; color: #aaa; margin-top: 2px; }
[data-theme="dark"] .submission-item .s-title { color: #e8ddd0; }
</style>
</head>
<body class="page-with-nav">
<div id="pfApp"><div class="pf-loading">加载中...</div></div>

<script>
const API = '/api.php';
var loadUserAfterLogin = null;

function calcLevel(score) {
  var levels = [
    {level:0, total:0, inc:0},
    {level:1, total:10, inc:10},
    {level:2, total:40, inc:30},
    {level:3, total:90, inc:50},
    {level:4, total:180, inc:90},
    {level:5, total:350, inc:170},
    {level:6, total:620, inc:270},
    {level:7, total:980, inc:360},
    {level:8, total:1450, inc:470},
    {level:9, total:2050, inc:600},
    {level:10, total:2800, inc:750}
  ];
  var result = {level:0, next:10, progress:0};
  for (var i = 0; i < levels.length; i++) {
    if (score >= levels[i].total) {
      result.level = levels[i].level;
    }
  }
  for (var j = 1; j < levels.length; j++) {
    if (levels[j].level === result.level + 1) {
      result.next = levels[j].total;
      var prev = levels[j-1].total;
      var gap = levels[j].total - prev;
      if (gap > 0) {
        result.progress = Math.round((score - prev) / gap * 100);
        if (result.progress > 100) result.progress = 100;
      }
      break;
    }
  }
  if (result.level >= 10) { result.next = 999999; result.progress = 100; }
  return result;
}

function loadProfile() {
  var params = new URLSearchParams(window.location.search);
  var hasUuidParam = params.has('uuid');
  var uuid = params.get('uuid') || '';
  // 仅当地址栏完全没带 uuid 参数（即直接访问 /profile.php）时，才回退到当前登录用户查看自己的主页；
  // 若是从评论区等入口带着 uuid 参数进来（哪怕该 uuid 为空/无效），绝不回退到当前账号，
  // 否则会出现“点别人却跳到自己账号”的错乱。
  if (!uuid && !hasUuidParam) {
    uuid = localStorage.getItem('novel_uuid') || '';
  }
  if (!uuid) {
    document.getElementById('pfApp').innerHTML = '<div class="profile-page" style="display:flex;align-items:center;justify-content:center;min-height:80vh;flex-direction:column;gap:12px"><p style="color:#aaa;font-size:16px">👤 暂无用户信息</p><p style="color:#bbb;font-size:13px">请先 <a href="/ucenter.php" style="color:#c4a882;text-decoration:none">登录/注册</a> 后可查看个人主页</p></div>';
    return;
  }
  
  var token = getToken() || '';
  fetch(API + '?action=get_user_profile&uuid=' + encodeURIComponent(uuid) + '&token=' + encodeURIComponent(token))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.code !== 0 || !d.data) {
        document.getElementById('pfApp').innerHTML = '<div class="profile-page" style="display:flex;align-items:center;justify-content:center;min-height:80vh;flex-direction:column;gap:12px"><p style="color:#aaa;font-size:16px">👤 该用户尚未注册</p><p style="color:#bbb;font-size:13px">此用户可能只是匿名访客，<a href="/ucenter.php" style="color:#c4a882;text-decoration:none">登录后可拥有个人主页</a></p></div>';
        return;
      }
      var u = d.data;
      var lv = calcLevel(parseInt(u.score) || 0);
      var isMaxLv = (lv.level >= 10);
      var lvText = isMaxLv ? ('Lv.' + lv.level + ' · 满级') : ('Lv.' + lv.level + ' (' + (u.score||0) + '/' + lv.next + ')');
      // 查看他人主页时，数据看板按【当前登录用户】的权限显示，不按对方权限
      var viewer = d.viewer || {};
      var isAdmin = viewer.is_admin || (viewer.username === 'admin');
      var isTestGroup = (viewer.tags || '').split(',').map(function(t){return t.trim();}).indexOf('测试组') !== -1;
      var canViewDashboard = isAdmin || isTestGroup;
      // 对方本人身份用于徽章/皇冠显示
      var targetIsAdmin = u.is_admin || (u.username === 'admin');
      // 投稿按钮只出现在自己的个人主页
      var isOwnProfile = (uuid === localStorage.getItem('novel_uuid'));
      var html = 
        '<div class="profile-page">' +
          '<div class="profile-header profile-theme-' + lv.level + '">' +
            '<div class="profile-avatar">' +
              (u.avatar ? '<img src="' + u.avatar + '">' : '👤') +
              (targetIsAdmin ? '<span class="crown">👑</span>' : '') +
            '</div>' +
            '<div class="profile-name">' + (u.nickname || u.username) + '</div>' +
            '<div class="profile-badge">' + (targetIsAdmin ? '👑 站长' : '📖 读者') + '</div>' +
            (u.tags ? '<div class="profile-tags">' + u.tags.split(',').map(function(t){return t.trim();}).filter(function(t){return t;}).map(function(t){return '<span class="profile-tag">' + escapeHtml(t) + '</span>';}).join('') + '</div>' : '') +
            (u.bio ? '<div class="profile-bio">' + u.bio + '</div>' : '') +
            '<div class="profile-level-row">' +
              '<div class="profile-level-bar"><div class="profile-level-fill" style="width:' + lv.progress + '%"></div></div>' +
              '<span class="profile-level-text">' + lvText + '</span>' +
            '</div>' +
            '<div class="profile-stats">' +
              '<div class="profile-stat"><div class="profile-stat-num">' + (u.score||0) + '</div><div class="profile-stat-label">积分</div></div>' +
              '<div class="profile-stat"><div class="profile-stat-num">' + (u.comment_count||0) + '</div><div class="profile-stat-label">评论</div></div>' +
              '<div class="profile-stat"><div class="profile-stat-num">' + (u.submission_count||0) + '</div><div class="profile-stat-label">投稿</div></div>' +
            '</div>' +
          '</div>' +
          '<div class="profile-body">' +
            '<div class="profile-card">' +
              '<div class="profile-card-title">📝 个人简介</div>' +
              '<p style="font-size:13px;color:#888;line-height:1.6">' + (u.bio || '这个人很懒，什么都没写...') + '</p>' +
            '</div>' +
            (canViewDashboard ? '<div class="profile-card" id="authorDashCard"><div class="profile-card-title">📊 数据看板</div><div class="pf-loading" style="padding:10px">加载中...</div></div>' : '') +
            '<div class="profile-card">' +
              '<div class="profile-card-title">📤 投稿作品</div>' +
              '<div id="submissionList" style="font-size:13px;color:#aaa;padding:8px 0">加载中...</div>' +
            '</div>' +
            '<div style="text-align:center;margin:16px 0">' +
              (canViewDashboard ? '<button class="profile-btn" onclick="location.href=\'/dashboard.php\'" style="margin-right:8px">📊 数据看板</button>' : '') +
              (isOwnProfile ? '<button class="profile-btn" onclick="showSubmitModal()">📤 投稿</button>' : '') +
            '</div>' +
          '</div>' +
        '</div>';
      document.getElementById('pfApp').innerHTML = html;
      loadUserAfterLogin = function() { window.location.reload(); };
      
      // 加载投稿列表
      loadSubmissions(uuid);
      // 加载数据看板
      if (canViewDashboard) loadAuthorDash();
    })
    .catch(function() {
      document.getElementById('pfApp').innerHTML = '<div class="profile-page" style="display:flex;align-items:center;justify-content:center;min-height:80vh"><p style="color:#aaa">加载失败</p></div>';
    });
}

// ====== 加载投稿列表 ======
function loadSubmissions(uuid) {
  // 从API获取投稿列表（需要后端支持）
  fetch(API + '?action=get_submissions&uuid=' + encodeURIComponent(uuid))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var list = document.getElementById('submissionList');
      if (!list) return;
      if (d.code === 0 && d.data && d.data.length > 0) {
        list.innerHTML = d.data.map(function(s) {
          return '<div class="submission-item"><div class="s-title">📄 ' + escapeHtml(s.title) + '</div><div class="s-time">' + (s.created_at || '') + '</div></div>';
        }).join('');
      } else {
        list.innerHTML = '<p style="color:#aaa;font-size:13px;padding:4px 0">还没有投稿作品～</p>';
      }
    })
    .catch(function() {
      var list = document.getElementById('submissionList');
      if (list) list.innerHTML = '<p style="color:#aaa;font-size:13px;padding:4px 0">加载失败</p>';
    });
}

// ====== 站长加载数据看板 ======
function loadAuthorDash() {
  var token = getToken();
  if (!token) return;
  fetch(API + '?action=get_author_stats&token=' + encodeURIComponent(token) + '&novel_id=001')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var card = document.getElementById('authorDashCard');
      if (!card) return;
      if (d.code === 0 && d.data) {
        var s = d.data;
        card.innerHTML = '<div class="profile-card-title">📊 数据看板</div>' +
          '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">' +
            '<div style="text-align:center;padding:12px;background:#faf8f5;border-radius:10px"><div style="font-size:20px;font-weight:700;color:#c4a882">' + (s.total_views||0) + '</div><div style="font-size:10px;color:#aaa">浏览量</div></div>' +
            '<div style="text-align:center;padding:12px;background:#faf8f5;border-radius:10px"><div style="font-size:20px;font-weight:700;color:#c4a882">' + (s.total_readers||0) + '</div><div style="font-size:10px;color:#aaa">读者</div></div>' +
            '<div style="text-align:center;padding:12px;background:#faf8f5;border-radius:10px"><div style="font-size:20px;font-weight:700;color:#c4a882">' + (s.total_comments||0) + '</div><div style="font-size:10px;color:#aaa">评论</div></div>' +
            '<div style="text-align:center;padding:12px;background:#faf8f5;border-radius:10px"><div style="font-size:20px;font-weight:700;color:#c4a882">' + (s.total_follows||0) + '</div><div style="font-size:10px;color:#aaa">追更</div></div>' +
          '</div>' +
          '<div style="text-align:center;margin-top:10px"><a href="/dashboard.php" class="profile-btn" style="font-size:12px">查看完整看板 →</a></div>';
      }
    })
    .catch(function() {});
}

function escapeHtml(text) {
  var div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// ====== 投稿弹窗 ======
function showSubmitModal() {
  var user = getUser();
  if (!user) { showAuth(); return; }
  var overlay = document.createElement('div');
  overlay.className = 'auth-overlay';
  overlay.style.cssText = 'display:flex;z-index:100000';
  overlay.onclick = function(e) { if (e.target === this) document.body.removeChild(overlay); };
  overlay.innerHTML =
    '<div class="auth-modal">' +
      '<button class="auth-close" onclick="document.body.removeChild(this.parentNode.parentNode)">✕</button>' +
      '<div class="auth-title">📤 投稿</div>' +
      '<div class="auth-subtitle">上传你的原创作品</div>' +
      '<div class="auth-form show">' +
        '<div class="auth-input-group"><label>作品标题</label><input type="text" id="subTitle" placeholder="输入标题" maxlength="100"></div>' +
        '<div class="auth-input-group"><label>选择文件</label>' +
          '<input type="file" id="subFile" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px;font-size:13px">' +
          '<p style="font-size:11px;color:#aaa;margin-top:4px">支持 txt/pdf/doc/epub/md 等格式</p>' +
        '</div>' +
        '<button class="auth-btn auth-btn-primary" id="subBtn" onclick="doSubmit()">提交投稿</button>' +
        '<div class="auth-error" id="subError"></div>' +
      '</div>' +
    '</div>';
  document.body.appendChild(overlay);
}

function doSubmit() {
  var title = document.getElementById('subTitle').value.trim();
  var fileInput = document.getElementById('subFile');
  var err = document.getElementById('subError');
  var btn = document.getElementById('subBtn');
  if (!title) { err.textContent = '请输入标题'; return; }
  if (!fileInput.files || !fileInput.files[0]) { err.textContent = '请选择文件'; return; }
  btn.disabled = true; btn.textContent = '上传中...'; err.textContent = '';
  var form = new FormData();
  form.append('action', 'submit_file');
  form.append('token', getToken());
  form.append('title', title);
  form.append('file', fileInput.files[0]);
  var xhr = new XMLHttpRequest();
  xhr.open('POST', API, true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      btn.disabled = false; btn.textContent = '提交投稿';
      try {
        var d = JSON.parse(xhr.responseText);
        if (d.code === 0) {
          appToast('✅ 投稿成功，等待审核');
          setTimeout(function() { document.body.removeChild(overlay.parentNode ? overlay : overlay.parentNode); }, 1000);
        } else {
          err.textContent = d.msg || '投稿失败';
        }
      } catch(e) { err.textContent = '网络错误'; }
    }
  };
  xhr.onerror = function() { btn.disabled = false; btn.textContent = '提交投稿'; err.textContent = '网络错误'; };
  xhr.send(form);
}

// 延迟到app.js加载后执行
// loadProfile() 在底部调用
</script>
<script src="/assets/app.js?v=20"></script>
<script>try { if (typeof initApp === 'function') initApp('profile'); } catch(e) {}
try { if (typeof loadProfile === 'function') loadProfile(); } catch(e) {}
</script>
</body>
</html>
