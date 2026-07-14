/**
 * 小说评论系统 - 共享 JS
 * 用于 example.com 下所有小说页面
 * 
 * 用法：
 *   <script src="/assets/comment.js"></script>
 *   <script>initCommentSystem('002');</script>  ← 传入小说ID
 */

// 全局配置
const COMMENT_CFG = {
    apiBase: (window.location.hostname === 'example.com') ? '/api.php' : '/novel/api.php',
    novelId: '001',
};

/**
 * 初始化评论系统
 * @param {string} novelId - 小说编号，如 '001', '002'
 */
function initCommentSystem(novelId) {
    COMMENT_CFG.novelId = novelId || '001';
    return {
        loadComments,
        sendComment,
        likeComment,
        loadUserTags,
    };
}

// ====== API 调用基础 ======
function apiUrl(action) {
    return COMMENT_CFG.apiBase + '?action=' + action + '&novel_id=' + COMMENT_CFG.novelId;
}

function apiPost(action, data, callback) {
    const form = new FormData();
    form.append('action', action);
    form.append('novel_id', COMMENT_CFG.novelId);
    for (const [k, v] of Object.entries(data)) {
        form.append(k, v);
    }
    fetch(COMMENT_CFG.apiBase, { method: 'POST', body: form })
        .then(r => r.json())
        .then(d => callback(d))
        .catch(() => {});
}

// ====== 标签解析 ======
function extractTag(text) {
    const m2 = text.match(/^\[!!([^\]]+)\]\s*/);
    if (m2) return { tag: m2[1].trim(), level: 2, content: text.slice(m2[0].length) };
    const m1 = text.match(/^\[!([^\]]+)\]\s*/);
    if (m1) return { tag: m1[1].trim(), level: 1, content: text.slice(m1[0].length) };
    return { tag: null, level: 0, content: text };
}

// ====== 标签持久化 ======
function loadUserTags() {
    const uuid = getUUID ? getUUID() : localStorage.getItem('novel_uuid');
    if (!uuid) return;
    
    const row = document.getElementById('tagHintRow');
    const list = document.getElementById('tagHintList');
    if (!row || !list) return;
    
    const cached = localStorage.getItem('novel_tags');
    let tags = cached ? JSON.parse(cached) : [];
    renderTagHints(tags, list, row);
    
    fetch(apiUrl('get_user_tags') + '&uuid=' + encodeURIComponent(uuid))
        .then(r => r.json())
        .then(d => {
            if (d.code === 0 && d.tags && d.tags.length > 0) {
                localStorage.setItem('novel_tags', JSON.stringify(d.tags));
                renderTagHints(d.tags, list, row);
                autoFillTags(d.tags);
            }
        }).catch(() => {});
}

function autoFillTags(tags) {
    if (!tags || tags.length === 0) return;
    const input = document.getElementById('inputContent');
    if (!input || input.value) return;
    const parts = tags.map(t => {
        const prefix = t.level >= 2 ? '!!' : '!';
        return '[' + prefix + t.tag + ']';
    });
    input.value = parts.join(' ') + ' ';
    if (typeof autoResize === 'function') autoResize(input);
}

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

function insertTag(tagStr) {
    const input = document.getElementById('inputContent');
    if (!input) return;
    if (input.value.includes(tagStr)) return;
    input.value = input.value + tagStr + ' ';
    if (typeof autoResize === 'function') autoResize(input);
    input.focus();
}

// ====== 昵称 / UUID 读取（兼容原有 getUUID）=====
function getNickname() {
    const el = document.getElementById('inputNickname');
    return el ? el.value.trim() || '匿名读者' : '匿名读者';
}

// ====== 导出 ======
window.CommentSystem = {
    initCommentSystem,
    extractTag,
    loadUserTags,
    insertTag,
    apiUrl,
    apiPost,
};
