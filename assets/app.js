/**
 * 小说站 - 全局功能
 * 登录/注册 · 导航 · 个人中心
 */
(function() {
'use strict';

const API = '/api.php';

// ====== 用户管理 ======
function getUUID() {
  let uuid = localStorage.getItem('novel_uuid');
  if (!uuid) {
    uuid = 'u_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('novel_uuid', uuid);
  }
  return uuid;
}

function getToken() {
  return localStorage.getItem('novel_token') || '';
}

function isLoggedIn() {
  return !!getToken();
}

function setUser(data) {
  if (data) {
    // 仅当返回里带了 token / uuid 时才覆盖，避免 check_login 等不返回 token 的接口
    // 把本地已保存的登录态清空（否则会出现“明明登录了却被当成未登录”）。
    if (data.token) localStorage.setItem('novel_token', data.token);
    if (data.uuid) localStorage.setItem('novel_uuid', data.uuid);
    localStorage.setItem('novel_username', data.username || '');
    localStorage.setItem('novel_nickname', data.nickname || '');
  }
}

function getUser() {
  const token = getToken();
  if (!token) return null;
  return {
    token: token,
    uuid: localStorage.getItem('novel_uuid') || '',
    username: localStorage.getItem('novel_username') || '',
    nickname: localStorage.getItem('novel_nickname') || ''
  };
}

function logout() {
  localStorage.removeItem('novel_token');
  localStorage.removeItem('novel_uuid');
  localStorage.removeItem('novel_username');
  localStorage.removeItem('novel_nickname');
  updateNavUserState();
}

function checkLogin() {
  const token = getToken();
  if (!token) return Promise.resolve(null);
  return fetch(API + '?action=check_login&token=' + encodeURIComponent(token))
    .then(r => r.json())
    .then(d => {
      if (d.code === 0 && d.data) {
        setUser(d.data);
        return d.data;
      }
      logout();
      return null;
    })
    .catch(() => null);
}

// ====== Toast ======
function appToast(msg) {
  let el = document.getElementById('appToast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'appToast';
    el.className = 'app-toast';
    document.body.appendChild(el);
  }
  el.textContent = msg;
  el.classList.add('show');
  clearTimeout(el._timer);
  el._timer = setTimeout(() => el.classList.remove('show'), 2500);
}

// ====== 登录/注册模态框 ======
var showAuthCallback = null;

function showAuth(callback) {
  showAuthCallback = callback || null;
  var overlay = document.getElementById('authOverlay');
  if (!overlay) createAuthModal();
  document.getElementById('authOverlay').classList.add('show');
}

function hideAuth() {
  document.getElementById('authOverlay').classList.remove('show');
}

function switchAuthTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('show'));
  document.querySelector('.auth-tab[data-tab="' + tab + '"]').classList.add('active');
  document.getElementById('authForm' + (tab === 'login' ? 'Login' : 'Register')).classList.add('show');
  document.getElementById('authTitle2').textContent = tab === 'login' ? '欢迎回来 ✨' : '加入我们 📚';
}

function apiPost(data, cb) {
  var xhr = new XMLHttpRequest();
  xhr.open('POST', API, true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      try { cb(JSON.parse(xhr.responseText)); }
      catch(e) { cb({code:-1, msg:'网络错误'}); }
    }
  };
  xhr.onerror = function() { cb({code:-1, msg:'网络错误'}); };
  xhr.send(JSON.stringify(data));
}

function authLogin() {
  var username = document.getElementById('loginUsername').value.trim();
  var password = document.getElementById('loginPassword').value;
  var errorEl = document.getElementById('authError');
  var btn = document.getElementById('loginBtn');
  if (!username || !password) { errorEl.textContent = '请填写完整'; return; }
btn.disabled = true;
  btn.innerHTML = '<span class="auth-loading"></span>登录中...';
  errorEl.textContent = '';
  
  // 保存游客uuid，登录后合并数据
  var oldUuid = localStorage.getItem('novel_uuid') || '';
  
  apiPost({action:'login', username:username, password:password, old_uuid: oldUuid}, function(d) {
    btn.disabled = false;
    btn.textContent = '登录';
    if (d.code === 0) {
      setUser(d.data);
      hideAuth();
      updateNavUserState();
      appToast('登录成功！欢迎回来～');
      if (showAuthCallback) { showAuthCallback(d.data); showAuthCallback = null; }
      if (typeof loadUserAfterLogin === 'function') loadUserAfterLogin();
    } else {
      errorEl.textContent = d.msg || '登录失败';
    }
  });
}

function authRegister() {
  var nickname = document.getElementById('regNickname').value.trim();
  var password = document.getElementById('regPassword').value;
  var errorEl = document.getElementById('authError2');
  var btn = document.getElementById('registerBtn');
  if (!nickname || !password) { errorEl.textContent = '昵称和密码不能为空'; return; }
  if (nickname.length < 2) { errorEl.textContent = '昵称至少2个字符'; return; }
  if (password.length < 4) { errorEl.textContent = '密码至少4位'; return; }
if (!document.getElementById('agreeCheck').checked) { errorEl.textContent = '请阅读并同意隐私协议和免责声明'; return; }
  btn.disabled = true;
  btn.innerHTML = '<span class="auth-loading"></span>注册中...';
  errorEl.textContent = '';

  var body = {action:'register', nickname:nickname, password:password};
  var avatarInput = document.getElementById('regAvatarInput');
  if (avatarInput && avatarInput._base64) body.avatar = avatarInput._base64;

  var oldUuid = localStorage.getItem('novel_uuid') || '';
  if (oldUuid) body.old_uuid = oldUuid;

  apiPost(body, function(d) {
    btn.disabled = false;
    btn.textContent = '注册';
    if (d.code === 0) {
      setUser(d.data);
      hideAuth();
      updateNavUserState();
      appToast('🎉 注册成功！');
      if (showAuthCallback) { showAuthCallback(d.data); showAuthCallback = null; }
      if (typeof loadUserAfterLogin === 'function') loadUserAfterLogin();
    } else {
      errorEl.textContent = d.msg || '注册失败';
    }
  });
}

function previewRegAvatar(file) {
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var preview = document.getElementById('regAvatarPreview');
    preview.innerHTML = '<img src="' + e.target.result + '">';
    document.getElementById('regAvatarInput')._base64 = e.target.result;
  };
  reader.readAsDataURL(file);
}

function createAuthModal() {
  if (document.getElementById('authOverlay')) return;
  var html =
'<div class="auth-overlay" id="authOverlay" onclick="if(event.target===this)hideAuth()">' +
  '<div class="auth-modal">' +
    '<button class="auth-close" onclick="hideAuth()">✕</button>' +
    '<div class="auth-title">📚 小说站</div>' +
    '<div class="auth-subtitle" id="authTitle2">欢迎回来 ✨</div>' +
    '<div class="auth-tabs">' +
      '<button class="auth-tab active" data-tab="login" onclick="switchAuthTab(\'login\')">登录</button>' +
      '<button class="auth-tab" data-tab="register" onclick="switchAuthTab(\'register\')">注册</button>' +
    '</div>' +
    '<div class="auth-form show" id="authFormLogin">' +
      '<div class="auth-input-group"><label>用户名 / 邮箱</label>' +
        '<input type="text" id="loginUsername" placeholder="输入用户名或已绑定的邮箱" autocomplete="username">' +
      '</div>' +
      '<div class="auth-input-group"><label>密码</label>' +
        '<input type="password" id="loginPassword" placeholder="输入密码" autocomplete="current-password" onkeydown="if(event.key===\'Enter\')authLogin()">' +
      '</div>' +
      '<button class="auth-btn auth-btn-primary" id="loginBtn" onclick="authLogin()">登录</button>' +
      '<div class="auth-error" id="authError"></div>' +
    '</div>' +
    '<div class="auth-form" id="authFormRegister">' +
      '<div class="auth-avatar-upload">' +
        '<div class="auth-avatar-preview" id="regAvatarPreview" onclick="document.getElementById(\'regAvatarFileInput\').click()">📷</div>' +
        '<input type="file" accept="image/*" id="regAvatarFileInput" style="display:none" onchange="previewRegAvatar(this.files[0])">' +
        '<label class="auth-avatar-btn">上传头像<input type="file" accept="image/*" id="regAvatarInput" onchange="previewRegAvatar(this.files[0])"></label>' +
      '</div>' +
      '<div class="auth-input-group"><label>昵称</label>' +
        '<input type="text" id="regNickname" placeholder="输入昵称（2-20个字符）" autocomplete="off">' +
      '</div>' +
      '<div class="auth-input-group"><label>密码</label>' +
        '<input type="password" id="regPassword" placeholder="至少4位" autocomplete="new-password" onkeydown="if(event.key===\'Enter\')authRegister()">' +
      '</div>' +
      '<div class="auth-agreement"><input type="checkbox" id="agreeCheck"><label for="agreeCheck">我已阅读并同意 <a href="/privacy.php" target="_blank" onclick="event.stopPropagation()">隐私协议</a> 和 <a href="/disclaimer.php" target="_blank" onclick="event.stopPropagation()">免责声明</a></label></div>' +
      '<button class="auth-btn auth-btn-primary" id="registerBtn" onclick="authRegister()">注册</button>' +
      '<div class="auth-error" id="authError2"></div>' +
    '</div>' +
  '</div>' +
'</div>';
  document.body.insertAdjacentHTML('beforeend', html);
}

// ====== 底部导航 ======
function initBottomNav(currentPage) {
  var pages = [
    { id:'home', icon:'📚', label:'书架', url:'/' },
    { id:'reading', icon:'📖', label:'阅读', url: localStorage.getItem('lastReading') || '/001/' },
    { id:'ucenter', icon:'👤', label:'我的', url:'/ucenter.php' },
  ];
  var html = '<nav class="bottom-nav">';
  pages.forEach(function(p) {
    var active = p.id === currentPage ? ' active' : '';
    html += '<a href="' + p.url + '" class="nav-item' + active + '">';
    html += '<span class="nav-icon">' + p.icon + '</span>';
    html += '<span class="nav-label">' + p.label + '</span>';
    html += '</a>';
  });
  html += '</nav>';
  var existing = document.querySelector('.bottom-nav');
  if (existing) existing.remove();
  document.body.insertAdjacentHTML('beforeend', html);
  document.body.classList.add('page-with-nav');
}

function updateNavUserState() {
  var user = getUser();
  document.querySelectorAll('.nav-user-state').forEach(function(el) {
    el.textContent = user ? (user.nickname || user.username) : '未登录';
  });
}

// ====== 初始化 ======
function initApp(currentPage) {
  // 注入CSS
  if (!document.querySelector('link[href*="app.css"]')) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = '/assets/app.css?v=26';
    document.head.appendChild(link);
  }
  // 注入登录模态框
  createAuthModal();
  // 初始化底部导航
  initBottomNav(currentPage);
}

// ====== 导出全局 ======
window.getToken = getToken;
window.getUUID = getUUID;
window.getUser = getUser;
window.setUser = setUser;
window.appToast = appToast;
window.isLoggedIn = isLoggedIn;
window.checkLogin = checkLogin;
window.logout = logout;
window.showAuth = showAuth;
window.hideAuth = hideAuth;
window.authLogin = authLogin;
window.authRegister = authRegister;
window.switchAuthTab = switchAuthTab;
window.previewRegAvatar = previewRegAvatar;
window.initApp = initApp;
window.updateNavUserState = updateNavUserState;

})();
