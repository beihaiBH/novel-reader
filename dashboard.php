<?php
/**
 * 数据看板 - example.com
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>📊 数据看板 | 小说站</title>
<link rel="stylesheet" href="/assets/app.css?v=3">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #f5f0eb; font-family: "Noto Serif SC", serif; color: #3a3a3a; min-height: 100vh; }
[data-theme="dark"] body { background: #1a1410; color: #d4c8b8; }
.db-header { background: linear-gradient(135deg, #2c1810, #4a2c20); padding: 40px 20px 24px; text-align: center; color: #f0e6d3; }
.db-title { font-size: 22px; font-weight: 700; }
.db-sub { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px; }
.db-body { padding: 16px; max-width: 600px; margin: 0 auto; }
.db-card { background: #fff; border-radius: 16px; padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
[data-theme="dark"] .db-card { background: #2c2620; color: #e8ddd0; }
.db-card-title { font-size: 14px; font-weight: 700; margin-bottom: 12px; }
.db-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.db-stat { text-align: center; padding: 16px; background: #faf8f5; border-radius: 12px; }
[data-theme="dark"] .db-stat { background: #3a342e; }
.db-stat-num { font-size: 28px; font-weight: 700; color: #c4a882; }
.db-stat-label { font-size: 11px; color: #aaa; margin-top: 2px; }
.db-loading { text-align: center; padding: 40px; color: #aaa; font-size: 13px; }
.db-error { text-align: center; padding: 40px; color: #e74c3c; font-size: 13px; }
</style>
</head>
<body class="page-with-nav">
<div id="dbApp"><div class="db-loading">📊 加载数据中...</div></div>

<script>
const API = '/api.php';
(function() {
  var token = localStorage.getItem('novel_token') || '';
  if (!token) {
    document.getElementById('dbApp').innerHTML = '<div class="db-error">请先登录</div>';
    return;
  }
  
  fetch(API + '?action=get_author_stats&token=' + encodeURIComponent(token) + '&novel_id=001')
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.code !== 0) {
        document.getElementById('dbApp').innerHTML = '<div class="db-error">' + (d.msg || '无权限') + '</div>';
        return;
      }
      var s = d.data;
      var html =
        '<div class="db-header">' +
          '<div class="db-title">📊 数据看板</div>' +
          '<div class="db-sub">《示例小说》· 佚名</div>' +
        '</div>' +
        '<div class="db-body">' +
          '<div class="db-card">' +
            '<div class="db-card-title">📈 核心数据</div>' +
            '<div class="db-grid">' +
              '<div class="db-stat"><div class="db-stat-num">' + (s.total_views||0) + '</div><div class="db-stat-label">总浏览量</div></div>' +
              '<div class="db-stat"><div class="db-stat-num">' + (s.total_readers||0) + '</div><div class="db-stat-label">读者数</div></div>' +
              '<div class="db-stat"><div class="db-stat-num">' + (s.total_comments||0) + '</div><div class="db-stat-label">评论数</div></div>' +
              '<div class="db-stat"><div class="db-stat-num">' + (s.total_follows||0) + '</div><div class="db-stat-label">追更数</div></div>' +
            '</div>' +
          '</div>' +
          '<div class="db-card">' +
            '<div class="db-card-title">📖 作品信息</div>' +
            '<div class="db-grid">' +
              '<div class="db-stat"><div class="db-stat-num">' + (s.chapter_count||0) + '</div><div class="db-stat-label">总章节</div></div>' +
              '<div class="db-stat"><div class="db-stat-num">' + (s.submissions||0) + '</div><div class="db-stat-label">投稿数</div></div>' +
            '</div>' +
            '<p style="font-size:12px;color:#aaa;margin-top:12px">最近评论: ' + (s.last_comment_time || '暂无') + '</p>' +
          '</div>' +
        '</div>';
      document.getElementById('dbApp').innerHTML = html;
    })
    .catch(function() {
      document.getElementById('dbApp').innerHTML = '<div class="db-error">加载失败</div>';
    });
})();
</script>
<script src="/assets/app.js?v=5"></script>
<script>try { if (typeof initApp === 'function') initApp('dashboard'); } catch(e) {}</script>
</body>
</html>
