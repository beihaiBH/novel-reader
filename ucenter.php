<?php
/**
 * 个人中心 - example.com
 */
$uuid = $_GET['uuid'] ?? '';
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>👤 个人中心 | 小说站</title>
<link rel="stylesheet" href="/assets/app.css?v=26">
<link rel="stylesheet" href="/assets/comment.css">
<link rel="stylesheet" href="/assets/levels.css?v=1">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #f5f0eb; font-family: "Noto Serif SC", "Source Han Serif SC", serif; color: #3a3a3a; min-height: 100vh; }
[data-theme="dark"] body { background: #1a1410; color: #d4c8b8; }
.uc-loading { display: flex; align-items: center; justify-content: center; min-height: 80vh; font-size: 14px; color: #aaa; }
.uc-loading::before { content: ''; width: 20px; height: 20px; border: 2px solid #e0d5c8; border-top-color: #c4a882; border-radius: 50%; animation: spin 0.6s linear infinite; margin-right: 10px; }
@keyframes spin { to { transform: rotate(360deg); } }
.uc-bio { font-size: 13px; color: #888; margin: 4px 0 8px; line-height: 1.5; padding: 0 8px; text-align: center; }
[data-theme="dark"] .uc-bio { color: #b8a898; }
</style>
</head>
<body class="page-with-nav">
<div id="ucApp"></div>

<script>
const API = '/api.php';
function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function getToken() { return localStorage.getItem('novel_token') || ''; }
var loadUserAfterLogin = null;

// ====== 等级计算（新积分等级系统） ======
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
  // 找下一级
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

// ====== 等级阶段判断 ======
function getLevelStage(level) {
  if (level >= 1 && level <= 3) return 'basic';
  if (level >= 4 && level <= 7) return 'gold';
  if (level >= 8 && level <= 9) return 'top';
  if (level >= 10) return 'lv10';
  return 'basic';
}

// ====== 每日签到 ======
function doCheckin() {
  var btn = document.getElementById('checkinBtn');
  if (!btn || btn.disabled) return;
  btn.disabled = true;
  btn.innerHTML = '签到中...';
  
  var token = getToken();
  fetch(API + '?action=checkin&token=' + encodeURIComponent(token))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.code === 0) {
        var points = d.data.total_earned || d.data.points;
        var consecutive = d.data.consecutive;
        appToast('✅ 签到成功！+' + points + '积分（连续' + consecutive + '天）');
        btn.textContent = '已签到 ✓';
        btn.classList.add('checked-in');
        btn.disabled = true;
        // 刷新积分显示
        setTimeout(function() { window.location.reload(); }, 2000);
      } else if (d.code === 1) {
        appToast('😴 ' + d.msg);
        btn.textContent = '已签到 ✓';
        btn.classList.add('checked-in');
        btn.disabled = true;
      } else {
        appToast('❌ ' + (d.msg || '签到失败'));
        btn.disabled = false;
        btn.innerHTML = '📍 签到';
      }
    })
    .catch(function() {
      appToast('❌ 网络错误');
      btn.disabled = false;
      btn.innerHTML = '📍 签到';
    });
}

function render() {
  var app = document.getElementById('ucApp');
  var user = getUser();
  if (!user) {
    app.innerHTML = '<div class="uc-page"><div class="uc-header"><div class="uc-avatar" style="opacity:0.4">📚</div><div class="uc-name">未登录</div><div class="uc-badge">登录后查看个人中心</div></div><div class="uc-body"><div class="uc-card" style="text-align:center;padding:40px 20px"><p style="color:#aaa;margin-bottom:16px">登录后可查看阅读记录<br>收藏的小说和等级成就</p><button class="profile-btn" onclick="showAuth()" style="padding:14px 40px;font-size:16px;background:linear-gradient(135deg,#c4a882,#a08060);color:#fff;border:none;border-radius:24px;font-weight:700">📱 登录 / 注册</button><p style="color:#ccc;font-size:12px;margin-top:12px;cursor:pointer" onclick="showAuth()">已有账号？点此登录</p></div></div></div>';
    loadUserAfterLogin = function() { window.location.reload(); };
    return;
  }
  
  app.innerHTML = '<div class="uc-page"><div class="uc-header" id="ucHeader"><div class="uc-avatar" id="ucAvatar">📷</div><div class="uc-name" id="ucName">加载中...</div><div class="uc-badge" id="ucBadge"></div><div class="uc-tags" id="ucTags"></div><div class="uc-bio" id="ucBio"></div><div class="uc-level-row"><div class="uc-level-bar"><div class="uc-level-fill" id="ucLevelFill" style="width:0%"></div></div><span class="uc-level-text" id="ucLevelText">Lv.0</span></div><div class="uc-gap-row" id="ucGapRow"><span class="uc-gap-text" id="ucGapText">加载中...</span></div><div class="uc-stats"><div class="uc-stat"><div class="uc-stat-num" id="ucScore">0</div><div class="uc-stat-label">积分</div></div><div class="uc-stat"><div class="uc-stat-num" id="ucComments">0</div><div class="uc-stat-label">评论</div></div><div class="uc-stat"><div class="uc-stat-num" id="ucReadingMin">0</div><div class="uc-stat-label">阅读(分钟)</div></div></div></div><div class="uc-body" id="ucBody"><div class="uc-loading">加载中...</div></div></div>';
  
  // 加载用户积分汇总（含等级、签到状态）
  var uuid = user.uuid;
  var token = getToken();
  fetch(API + '?action=get_points_summary&token=' + encodeURIComponent(token) + '&uuid=' + encodeURIComponent(uuid))
    .then(function(r) { return r.json(); })
    .then(function(ps) {
      var pointsData = (ps.code === 0) ? ps.data : null;
      
      // 加载通用用户数据
      return fetch(API + '?action=get_user_data&uuid=' + encodeURIComponent(uuid))
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.code !== 0 || !d.data) return;
          var data = d.data;
          var u = data;
          document.getElementById('ucName').textContent = u.nickname || u.username;
          if (u.avatar) { document.getElementById('ucAvatar').innerHTML = '<img src="' + u.avatar + '">'; }
          var badge = '';
          if (u.is_admin) badge = '👑 站长';
          document.getElementById('ucBadge').textContent = badge;
          
          // 从积分汇总数据获取等级
          var lv, stage;
          if (pointsData && pointsData.level_info) {
            lv = pointsData.level_info;
            stage = pointsData.stage || getLevelStage(lv.level);
          } else {
            var scoreVal = parseInt(pointsData ? pointsData.score : (u.score || 0));
            lv = calcLevel(scoreVal);
            stage = getLevelStage(lv.level);
          }

          // ★兼容后端 level_info(nextLevelPoints/progressPercent) 与前端 calcLevel(next/progress) 两套字段命名，
          // 避免出现进度条 width:undefined% 以及缺口积分算成0导致的"暂未统计"BUG。
          if (lv.next === undefined || lv.next === null) {
            lv.next = (lv.nextLevelPoints !== undefined && lv.nextLevelPoints !== null) ? lv.nextLevelPoints : 999999;
          }
          if (lv.progress === undefined || lv.progress === null || isNaN(lv.progress)) {
            lv.progress = (lv.progressPercent !== undefined && lv.progressPercent !== null) ? lv.progressPercent : 0;
          }
          
          // 应用等级阶段样式到header
          var header = document.getElementById('ucHeader');
          header.className = 'uc-header profile-theme-' + lv.level;
          
          var scoreDisplay = parseInt(pointsData ? pointsData.score : (u.score || 0)) || 0;
          // 升级缺口计算（后端/前端双重兜底）
          var gapEl = document.getElementById('ucGapRow');
          var gapTextEl = document.getElementById('ucGapText');
          var isMaxLevel = (lv.level >= 10);
          
          if (isMaxLevel) {
            // 满级Lv10：隐藏缺口行，进度条拉满
            document.getElementById('ucLevelFill').style.width = '100%';
            if (gapEl) gapEl.style.display = 'none';
          } else {
            // 非满级：计算缺口积分
            var nextTotal = parseInt(lv.next);
            var gapPoints = (nextTotal && nextTotal !== 999999) ? (nextTotal - scoreDisplay) : 0;
            if (gapPoints < 0) gapPoints = 0;
            // 进度条：优先用后端/前端算好的百分比，异常时用积分实时兜底重算
            var pct = parseInt(lv.progress);
            if (isNaN(pct) || pct < 0) {
              pct = (nextTotal && nextTotal !== 999999) ? Math.round(scoreDisplay / nextTotal * 100) : 0;
            }
            if (pct > 100) pct = 100;
            document.getElementById('ucLevelFill').style.width = pct + '%';
            if (gapTextEl) {
              gapTextEl.textContent = '距下一等级还需 ' + gapPoints + ' 积分';
            }
            if (gapEl) gapEl.style.display = '';
          }
          
          document.getElementById('ucLevelText').textContent = 'Lv.' + lv.level;
          document.getElementById('ucScore').textContent = scoreDisplay;
          document.getElementById('ucComments').textContent = u.comment_count || 0;
          
          // 阅读分钟数
          var readingMinEl = document.getElementById('ucReadingMin');
          if (readingMinEl && pointsData) {
            readingMinEl.textContent = pointsData.reading_minutes || 0;
          }
          
          var body = document.getElementById('ucBody');
          var novelNames = {'001': '示例小说', '002': '示例作品'};
          var progressHtml = '';
          if (data.progress && data.progress.length > 0) {
            var shown = {};
            for (var i = data.progress.length - 1; i >= 0; i--) {
              var p = data.progress[i];
              var id = p.novel_id || '';
              if (id && !shown[id]) {
                shown[id] = true;
                var name = novelNames[id] || '小说 #' + id;
                progressHtml += '<div class="uc-menu-item" onclick="location.href=\'/' + id + '/?chapter=' + (p.chapter||0) + '\'">' +
                  '<span class="mi-icon">📖</span><span class="mi-text">继续阅读 · ' + escapeHtml(name) + ' 第' + ((p.chapter||0)+1) + '章</span><span class="mi-arrow">›</span></div>';
              }
            }
          }
          
          var followsHtml = '';
          if (data.follows && data.follows.length > 0) {
            followsHtml = data.follows.map(function(f) {
              return '<div class="uc-menu-item" onclick="location.href=\'/' + f.id + '/\'">' +
                '<span class="mi-icon">❤️</span><span class="mi-text">' + escapeHtml(f.name) + '</span><span class="mi-arrow">›</span></div>';
            }).join('');
          } else {
            followsHtml = '<div class="uc-menu-item"><span class="mi-icon">❤️</span><span class="mi-text" style="color:#aaa">暂无追更</span></div>';
          }
          
          // 签到按钮状态
          var todayCheckedIn = pointsData ? pointsData.today_checked_in : false;
          var consecutiveDays = pointsData ? pointsData.consecutive_checkin_days : 0;
          var checkinHtml = todayCheckedIn
            ? '<div class="uc-menu-item"><span class="mi-icon">✅</span><span class="mi-text" style="color:#4caf50">今日已签到（连续' + consecutiveDays + '天）</span></div>'
            : '<div class="uc-menu-item" onclick="doCheckin()" style="cursor:pointer">' +
              '<span class="mi-icon">📍</span><span class="mi-text" id="checkinText">每日签到</span>' +
              '<button class="uc-checkin-btn" id="checkinBtn" onclick="event.stopPropagation();doCheckin()">签到领积分</button></div>';
          
          body.innerHTML =
            '<div class="uc-card">' +
              '<div class="uc-card-title"><span class="icon">📍</span>每日签到</div>' +
              checkinHtml +
              (consecutiveDays > 0 ? '<div class="uc-menu-item" style="border-bottom:none"><span class="mi-icon">🔥</span><span class="mi-text" style="color:#e67e22">已连续签到 ' + consecutiveDays + ' 天</span></div>' : '') +
            '</div>' +
            '<div class="uc-card">' +
              '<div class="uc-card-title"><span class="icon">📖</span>最近阅读</div>' +
              (progressHtml || '<div class="uc-menu-item"><span class="mi-icon">📖</span><span class="mi-text" style="color:#aaa">还没有阅读记录</span></div>') +
            '</div>' +
            '<div class="uc-card">' +
              '<div class="uc-card-title"><span class="icon">❤️</span>我的追更</div>' + followsHtml +
            '</div>' +
            '<div class="uc-card">' +
              '<div class="uc-card-title"><span class="icon">⚙️</span>设置</div>' +
              '<div class="uc-menu-item" onclick="showSettings()">' +
                '<span class="mi-icon">✏️</span><span class="mi-text">编辑个人资料</span><span class="mi-arrow">›</span></div>' +
              '<div class="uc-menu-item" onclick="showBindEmail()">' +
                '<span class="mi-icon">📧</span><span class="mi-text" id="ucEmailStatus">' + (u.bind_email ? '已绑定邮箱' : '绑定邮箱') + '</span><span class="mi-arrow">›</span></div>' +
              ((u.is_admin || (u.tags || '').split(',').map(function(t){return t.trim();}).indexOf('测试组') !== -1) ? '<div class="uc-menu-item" onclick="location.href=\'/dashboard.php\'"><span class="mi-icon">📊</span><span class="mi-text">数据看板</span><span class="mi-arrow">›</span></div>' : '') +
            '</div>' +
            '<button class="uc-logout-btn" onclick="if(confirm(\'确定退出登录吗？\')){logout();window.location.reload();}">退出登录</button>';
            
          // 标记标签
          var tagsEl = document.getElementById('ucTags');
          if (u.tags) {
            var tagArr = u.tags.split(',').map(function(t){return t.trim();}).filter(function(t){return t;});
            if (tagArr.length) {
              tagsEl.innerHTML = tagArr.map(function(t){return '<span class="uc-tag">' + escapeHtml(t) + '</span>';}).join('');
              tagsEl.style.display = 'flex';
            }
          }
          var bioEl = document.getElementById('ucBio');
          if (bioEl) {
            bioEl.textContent = u.bio || '这个人很懒，什么都没写...';
            bioEl.style.display = u.bio ? '' : 'none';
          }
        });
    })
    .catch(function() {
      document.getElementById('ucBody').innerHTML = '<div class="uc-card" style="text-align:center;padding:30px;color:#aaa">加载失败，请刷新重试</div>';
    });
}

// ====== 设置弹窗 ======
function showSettings() {
  var user = getUser();
  if (!user) { showAuth(); return; }
  checkLogin().then(function(data) {
    if (!data) { showAuth(); return; }
    loadSettingsData();
  });
}

function loadSettingsData() {
  var user = getUser();
  var data = { nickname: user.nickname || '', bio: '' };
  fetch(API + '?action=get_user_data&uuid=' + encodeURIComponent(user.uuid))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.code === 0 && d.data) { data = d.data; }
      showSettingsModal(data);
    })
    .catch(function() { showSettingsModal(data); });
}

function showSettingsModal(data) {
  var overlay = document.createElement('div');
  overlay.className = 'auth-overlay';
  overlay.style.cssText = 'display:flex;z-index:100000';
  overlay.onclick = function(e) { if (e.target === this) document.body.removeChild(overlay); };
  overlay.innerHTML =
    '<div class="auth-modal">' +
      '<button class="auth-close" onclick="document.body.removeChild(this.parentNode.parentNode)">✕</button>' +
      '<div class="auth-title">⚙️ 设置</div>' +
      '<div class="auth-subtitle">修改个人资料</div>' +
      '<div class="auth-form show">' +
        '<div class="auth-avatar-upload">' +
          '<div class="auth-avatar-preview" id="setAvatarPreview" onclick="document.getElementById(\'setAvatarInput\').click()">' +
            (data.avatar ? '<img src="' + data.avatar + '">' : '📷') +
          '</div>' +
          '<label class="auth-avatar-btn">更换头像<input type="file" accept="image/*" id="setAvatarInput" style="display:none" onchange="previewSetAvatar(this.files[0])"></label>' +
        '</div>' +
        '<div class="auth-input-group"><label>昵称</label><input type="text" id="setNickname" value="' + (data.nickname||'') + '" placeholder="输入昵称" maxlength="20"></div>' +
        '<div class="auth-input-group"><label>个人简介</label><textarea id="setBio" rows="3" placeholder="介绍一下自己吧～" maxlength="200" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;outline:none;resize:vertical;box-sizing:border-box">' + (data.bio||'') + '</textarea></div>' +
        '<button class="auth-btn auth-btn-primary" id="saveSettingsBtn" onclick="saveSettings(this)">保存修改</button>' +
        '<div class="auth-error" id="settingsError"></div>' +
      '</div>' +
    '</div>';
  document.body.appendChild(overlay);
}

function previewSetAvatar(file) {
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    showToast('头像不能超过5MB~');
    return;
  }
  var reader = new FileReader();
  reader.onload = function(e) {
    var preview = document.getElementById('setAvatarPreview');
    preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    preview._base64 = e.target.result;
  };
  reader.readAsDataURL(file);
}

function saveSettings(btn) {
  var nickname = document.getElementById('setNickname').value.trim();
  var bio = document.getElementById('setBio').value.trim();
  var err = document.getElementById('settingsError');
  if (!nickname) { err.textContent = '昵称不能为空'; return; }
  if (nickname.length < 2) { err.textContent = '昵称至少2个字符'; return; }
  btn.disabled = true;
  btn.textContent = '保存中...';
  err.textContent = '';
  var body = { action:'update_profile', token: getToken(), nickname:nickname, bio:bio };
  var preview = document.getElementById('setAvatarPreview');
  if (preview && preview._base64) body.avatar = preview._base64;
  
  var xhr = new XMLHttpRequest();
  xhr.open('POST', API, true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      btn.disabled = false; btn.textContent = '保存修改';
      try {
        var d = JSON.parse(xhr.responseText);
        if (d.code === 0) {
          appToast('✅ 保存成功');
          setTimeout(function() { window.location.reload(); }, 800);
        } else {
          err.textContent = d.msg || '保存失败';
        }
      } catch(e) { err.textContent = '网络错误'; }
    }
  };
  xhr.onerror = function() { btn.disabled = false; btn.textContent = '保存修改'; err.textContent = '网络错误'; };
  xhr.send(JSON.stringify(body));
}

// ====== 邮箱绑定弹窗 ======
var verifyTimer = null;
var verifyCountdown = 0;

function showBindEmail() {
  var user = getUser();
  if (!user) { showAuth(); return; }
  // 先验证token是否还有效
  checkLogin().then(function(data) {
    if (!data) { showAuth(); return; }
    fetch(API + '?action=get_user_data&uuid=' + encodeURIComponent(user.uuid))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var email = (d.data && d.data.bind_email) || '';
      showBindEmailModal(email);
    })
    .catch(function() { showBindEmailModal(''); });
});
}

function showBindEmailModal(currentEmail) {
  var overlay = document.createElement('div');
  overlay.className = 'auth-overlay';
  overlay.style.cssText = 'display:flex;z-index:100000';
  overlay.onclick = function(e) { if (e.target === this) document.body.removeChild(overlay); };
  overlay.innerHTML =
    '<div class="auth-modal" style="max-width:380px">' +
      '<button class="auth-close" onclick="document.body.removeChild(this.parentNode.parentNode)">✕</button>' +
      '<div class="auth-title">📧 绑定邮箱</div>' +
      '<div class="auth-subtitle">绑定后可用「用户名或邮箱」登录，并收到评论回复和点赞通知</div>' +
      '<div class="auth-form show">' +
        '<div class="auth-input-group"><label>邮箱地址</label><input type="email" id="beEmail" value="' + escapeHtml(currentEmail) + '" placeholder="输入邮箱" maxlength="100" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box"></div>' +
        '<div class="auth-input-group" style="position:relative"><label>验证码</label><div style="display:flex;gap:8px"><input type="text" id="beCode" placeholder="6位验证码" maxlength="6" style="flex:1;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box;letter-spacing:4px;text-align:center;font-weight:700"><button id="beSendBtn" onclick="sendVerifyCode()" style="white-space:nowrap;padding:10px 16px;border:1px solid #c4a882;background:#fff;color:#8b6f5c;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600">发送验证码</button></div></div>' +
        '<button class="auth-btn auth-btn-primary" id="beBindBtn" onclick="bindEmail()">' + (currentEmail ? '更新绑定' : '确认绑定') + '</button>' +
        '<div class="auth-error" id="beError"></div>' +
      '</div>' +
    '</div>';
  document.body.appendChild(overlay);
}

function sendVerifyCode() {
  var btn = document.getElementById('beSendBtn');
  if (btn.disabled) return;
  var email = document.getElementById('beEmail').value.trim();
  if (!email || email.indexOf('@') === -1) {
    document.getElementById('beError').textContent = '请输入正确的邮箱地址';
    return;
  }
  btn.disabled = true;
  btn.textContent = '发送中...';
  var xhr = new XMLHttpRequest();
  xhr.open('POST', API, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      var d = JSON.parse(xhr.responseText);
      if (d.code === 0) {
        document.getElementById('beError').textContent = '✅ 验证码已发送，请查收邮件';
        document.getElementById('beError').style.color = '#4caf50';
        startVerifyCountdown(btn);
      } else {
        btn.disabled = false;
        btn.textContent = '发送验证码';
        document.getElementById('beError').textContent = d.msg || '发送失败';
        document.getElementById('beError').style.color = '';
      }
    }
  };
  xhr.send('action=send_verify_code&email=' + encodeURIComponent(email));
}

function startVerifyCountdown(btn) {
  var seconds = 60;
  btn.textContent = seconds + '秒后重试';
  if (verifyTimer) clearInterval(verifyTimer);
  verifyTimer = setInterval(function() {
    seconds--;
    if (seconds <= 0) {
      clearInterval(verifyTimer);
      verifyTimer = null;
      btn.disabled = false;
      btn.textContent = '发送验证码';
    } else {
      btn.textContent = seconds + '秒后重试';
    }
  }, 1000);
}

function bindEmail() {
  var email = document.getElementById('beEmail').value.trim();
  var code = document.getElementById('beCode').value.trim();
  var err = document.getElementById('beError');
  if (!email || email.indexOf('@') === -1) { err.textContent = '请输入正确的邮箱地址'; return; }
  if (code.length !== 6) { err.textContent = '请输入6位验证码'; return; }
  var btn = document.getElementById('beBindBtn');
  btn.disabled = true;
  btn.textContent = '绑定中...';
  var xhr = new XMLHttpRequest();
  xhr.open('POST', API, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      btn.disabled = false;
      btn.textContent = '确认绑定';
      try {
        var d = JSON.parse(xhr.responseText);
        if (d.code === 0) {
          appToast('✅ 邮箱绑定成功');
          setTimeout(function() { window.location.reload(); }, 1000);
        } else {
          err.textContent = d.msg || '绑定失败';
        }
      } catch(e) { err.textContent = '网络错误'; }
    }
  };
  xhr.send('action=bind_email&token=' + encodeURIComponent(getToken()) + '&email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code));
}

// render() 在 app.js 加载后调用（在底部）
</script>
<script src="/assets/app.js?v=25"></script>
<script>try { if (typeof initApp === 'function') initApp('ucenter'); } catch(e) {}
try { render(); } catch(e) { console.warn(e); document.getElementById('ucApp').innerHTML = '<div class="uc-page" style="text-align:center;padding:40px 20px"><p style="color:#aaa;margin-bottom:16px">加载失败，<a href="javascript:location.reload()" style="color:#c4a882">点击刷新</a></p></div>'; }
</script>
<script>
// 覆盖阅读按钮：从哪本小说来的就跳回哪本
var lastRead = localStorage.getItem('lastReading');
if (lastRead) {
  setTimeout(function() {
    document.querySelectorAll('.bottom-nav a').forEach(function(a) {
      var label = a.querySelector('.nav-label');
      if (label && label.textContent === '阅读') {
        a.href = lastRead;
      }
    });
  }, 150);
}
// 来自002小说 → 书架按钮显示暂未开放
if (lastRead === '/002/') {
  setTimeout(function() {
    document.querySelectorAll('.bottom-nav a').forEach(function(a) {
      var label = a.querySelector('.nav-label');
      if (label && label.textContent === '书架') {
        a.href = 'javascript:void(0)';
        a.onclick = function(e) { e.preventDefault(); if (typeof showModal === 'function') showModal(); };
      }
    });
  }, 200);
}
</script>

<!-- 暂未开放弹窗 -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-icon">🔒</div>
    <div class="modal-title">暂未开放</div>
    <div class="modal-desc">该功能正在建设中，敬请期待～</div>
    <button class="modal-btn" onclick="closeModal()">知道了</button>
  </div>
</div>

<style>
.modal-overlay {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
  z-index: 10000; justify-content: center; align-items: center;
}
.modal-overlay.show { display: flex; }
.modal-box {
  background: #fff; border-radius: 20px; padding: 40px 36px 32px;
  text-align: center; max-width: 340px; width: 88%;
  animation: modalPop 0.35s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes modalPop {
  0% { transform: scale(0.7); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
.modal-icon { font-size: 52px; margin-bottom: 12px; }
.modal-title { font-size: 22px; font-weight: 700; color: #1a1410; margin-bottom: 8px; }
.modal-desc { font-size: 14px; color: #666; margin-bottom: 24px; line-height: 1.6; }
.modal-btn {
  background: linear-gradient(135deg, #8b6f5c, #6d5544); color: #fff;
  border: none; border-radius: 40px; padding: 10px 40px;
  font-size: 15px; cursor: pointer; transition: transform .2s;
}
.modal-btn:hover { transform: scale(1.05); }
[data-theme="dark"] .modal-box { background: #2a221e; }
[data-theme="dark"] .modal-title { color: #e8ddd0; }
[data-theme="dark"] .modal-desc { color: #b0a090; }
</style>

<script>
function showModal() { document.getElementById('modalOverlay').classList.add('show'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('show'); }
</script>
</body>
</html>
