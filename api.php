<?php
/**
 * API 路由入口 - example.com
 * MVC 架构，转发到对应的控制器
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ====== 数据库连接（保留供旧功能使用） ======
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

// --- 解析JSON Body ---
$jsonBody = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw && $raw[0] === '{') {
        $jsonBody = json_decode($raw, true);
    }
}

$action = $_REQUEST['action'] ?? ($jsonBody['action'] ?? '');

// ====== 白名单 IP 检测 ======
function isAdmin() {
    $whitelistFile = __DIR__ . '/config/admin_whitelist.txt';
    if (!file_exists($whitelistFile)) return false;
    $lines = file($whitelistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $ips = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $line)) {
            $ips[] = $line;
        }
    }
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $remoteIp = trim($forwarded[0]);
    }
    return in_array($remoteIp, $ips);
}

// ====== 日志 ======
function writeLog($pdo, $action, $detail = '') {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwarded[0]);
        }
        $page = $_SERVER['HTTP_REFERER'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO logs (ip, action, detail, page) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $action, $detail, $page]);
    } catch (Exception $e) {}
}

// ====== 加载 MVC 控制器 ======
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/controllers/CommentController.php';

// ====== 路由分发 ======
switch ($action) {
    // ---- 评论系统 (MVC) ----
    case 'list':
        CommentController::list();
        break;

    case 'add':
        CommentController::add();
        break;

    case 'like':
        CommentController::like();
        break;

    case 'get_user_tags':
        CommentController::getUserTags();
        break;

        // ---- 管理员检测 ----
    case 'check_admin':
        $isAdmin = isAdmin();
        // 也检查admin用户的token
        if (!$isAdmin) {
            $token = $_GET['token'] ?? $_POST['token'] ?? '';
            if ($token) {
                $stmt = $pdo->prepare('SELECT username FROM users WHERE token = ?');
                $stmt->execute([$token]);
                $u = $stmt->fetch();
                $isAdmin = $u && $u['username'] === 'admin';
            }
        }
        jsonResponse(['code' => 0, 'is_admin' => $isAdmin]);
        break;

    // ---- 表情列表 ----
    case 'emoji_list':
        jsonResponse(['code' => 0, 'data' => getEmojiData()]);
        break;

    // ---- 全局点赞 ----
    case 'like_toggle':
        $stmt = $pdo->prepare("INSERT INTO stats (stat_key, stat_value, novel_id) VALUES ('total_likes', 1, ?) ON DUPLICATE KEY UPDATE stat_value = stat_value + 1");
        $stmt->execute([getNovelId()]);
        $stmt = $pdo->prepare("SELECT stat_value FROM stats WHERE stat_key='total_likes' AND novel_id = ?");
        $stmt->execute([getNovelId()]);
        jsonResponse(['code' => 0, 'total_likes' => (int)$stmt->fetchColumn()]);
        break;

    // ---- 阅读量统计 ----
    case 'view':
        require_once __DIR__ . '/models/Stats.php';
        $stats = new Stats();
        $novelId = getNovelId();
        $data = $stats->incrementView($novelId);
        jsonResponse(['code' => 0, 'views' => $data['views']]);
        break;

    // ---- 获取/生成UUID ----
    case 'get_uuid':
        $cookieUuid = $_COOKIE['novel_uuid'] ?? '';
        if (!$cookieUuid) {
            $cookieUuid = 'u_' . bin2hex(random_bytes(8)) . '_' . dechex(time());
        }
        // 尝试从cookie中读取，如果有就返回原有的
        jsonResponse(['code' => 0, 'uuid' => $cookieUuid]);
        break;

    // ---- 旧版游客数据（保留兼容） ----
    case 'load_user_data':
        $uuid = $_GET['uuid'] ?? $_COOKIE['novel_uuid'] ?? '';
        if (!$uuid) { echo json_encode(['code' => -1, 'msg' => '缺少uuid']); break; }
        $stmt = $pdo->prepare('SELECT * FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $data = $stmt->fetch();
        if ($data) {
            $data['liked_comments'] = $data['liked_comments'] ? json_decode($data['liked_comments'], true) : [];
            $data['settings'] = $data['settings'] ? json_decode($data['settings'], true) : [];
            $data['tags'] = $data['tags'] ? json_decode($data['tags'], true) : [];
            // 追更列表：将 ID 数组转为带小说名的对象
            $novelNames = ['001' => '示例小说', '002' => '示例作品'];
            $follows = $data['follows'] ? (is_string($data['follows']) ? json_decode($data['follows'], true) : $data['follows']) : [];
            if (!is_array($follows)) $follows = [];
            $data['follows'] = array_map(function($id) use ($novelNames) {
                return ['id' => $id, 'name' => $novelNames[$id] ?? '小说' . $id];
            }, $follows);
            echo json_encode(['code' => 0, 'data' => $data]);
        } else {
            echo json_encode(['code' => 0, 'data' => null]);
        }
        break;

    // ---- 保存用户数据（设置等）----
    case 'save_user_data':
        $uuid = $jsonBody['uuid'] ?? $_POST['uuid'] ?? '';
        if (!$uuid) { jsonResponse(['code' => -1, 'msg' => '缺少uuid']); break; }
        $nickname = isset($jsonBody['nickname']) ? trim($jsonBody['nickname']) : null;
        $avatarUrl = isset($jsonBody['avatar']) ? trim($jsonBody['avatar']) : null;
        $likedNovel = isset($jsonBody['liked_novel']) ? ($jsonBody['liked_novel'] ? 1 : 0) : null;
        $settings = isset($jsonBody['settings']) ? json_encode($jsonBody['settings'], JSON_UNESCAPED_UNICODE) : null;
        $likedComments = isset($jsonBody['liked_comments']) ? json_encode($jsonBody['liked_comments'], JSON_UNESCAPED_UNICODE) : null;
        // 先检查记录是否存在
        $stmt = $pdo->prepare('SELECT id FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        if ($stmt->fetch()) {
            // 更新已有记录
            $updates = []; $params = [];
            if ($settings !== null) { $updates[] = 'settings = ?'; $params[] = $settings; }
            if ($likedNovel !== null) { $updates[] = 'liked_novel = ?'; $params[] = $likedNovel; }
            if ($likedComments !== null) { $updates[] = 'liked_comments = ?'; $params[] = $likedComments; }
            if ($nickname !== null) { $updates[] = 'nickname = ?'; $params[] = $nickname; }
            if ($avatarUrl !== null) { $updates[] = 'avatar = ?'; $params[] = $avatarUrl; }
            if (!empty($updates)) {
                $params[] = $uuid;
                $stmt = $pdo->prepare('UPDATE user_prefs SET ' . implode(', ', $updates) . ' WHERE uuid = ?');
                $stmt->execute($params);
            }
        } else {
            // 插入新记录
            $updates = ['uuid = ?']; $params = [$uuid];
            if ($settings !== null) { $updates[] = 'settings = ?'; $params[] = $settings; }
            if ($likedNovel !== null) { $updates[] = 'liked_novel = ?'; $params[] = $likedNovel; }
            if ($likedComments !== null) { $updates[] = 'liked_comments = ?'; $params[] = $likedComments; }
            if ($nickname !== null) { $updates[] = 'nickname = ?'; $params[] = $nickname; }
            if ($avatarUrl !== null) { $updates[] = 'avatar = ?'; $params[] = $avatarUrl; }
            $stmt = $pdo->prepare('INSERT INTO user_prefs SET ' . implode(', ', $updates));
            $stmt->execute($params);
        }
        jsonResponse(['code' => 0, 'msg' => '保存成功']);
        break;

    // ---- 用户认证 ----
    case 'register':
        if (!$jsonBody || empty($jsonBody['nickname']) || empty($jsonBody['password'])) {
            jsonResponse(['code' => -1, 'msg' => '昵称和密码不能为空']); break;
        }
        $nickname = trim($jsonBody['nickname']);
        $username = $nickname;
        $password = password_hash($jsonBody['password'], PASSWORD_BCRYPT);
        $uuid = md5(uniqid(mt_rand(), true));
        $token = md5($uuid . time() . mt_rand());
        $avatarPath = '';
        if (!empty($jsonBody['avatar'])) {
            $avatarData = $jsonBody['avatar'];
            if (preg_match('/^data:image\/(\w+);base64,/', $avatarData, $m)) {
                $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                $data = base64_decode(substr($avatarData, strpos($avatarData, ',') + 1));
                if ($data !== false) {
                    $filename = 'avatar_' . $uuid . '.' . $ext;
                    $dir = __DIR__ . '/avatars';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    if (file_put_contents($dir . '/' . $filename, $data) !== false) {
                        $avatarPath = '/avatars/' . $filename;
                    }
                }
            }
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (username, password, nickname, uuid, token, avatar) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$username, $password, $nickname, $uuid, $token, $avatarPath]);
            $stmt = $pdo->prepare('INSERT IGNORE INTO user_prefs (uuid, nickname, avatar) VALUES (?,?,?)');
            $stmt->execute([$uuid, $nickname, $avatarPath]);
            $pdo->commit();
            // 合并游客数据（旧评论、偏好设置同步到新账号）
            if (!empty($jsonBody['old_uuid'])) {
                mergeGuestData($pdo, $jsonBody['old_uuid'], $uuid);
            }
            jsonResponse(['code' => 0, 'msg' => '注册成功', 'data' => [
                'token' => $token, 'uuid' => $uuid, 'username' => $username,
                'nickname' => $nickname, 'avatar' => $avatarPath,
                'bio' => '', 'score' => 0, 'level' => 0
            ]]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            jsonResponse(['code' => -1, 'msg' => $e->getCode() == 23000 ? '用户名已存在' : '注册失败']);
        }
        break;
        
    case 'login':
        if (!$jsonBody || empty($jsonBody['username']) || empty($jsonBody['password'])) {
            jsonResponse(['code' => -1, 'msg' => '用户名和密码不能为空']); break;
        }
        // 支持用户名或已绑定邮箱登录
        $loginId = trim($jsonBody['username']);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR (bind_email <> "" AND bind_email = ?) LIMIT 1');
        $stmt->execute([$loginId, $loginId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($jsonBody['password'], $user['password'])) {
            jsonResponse(['code' => -1, 'msg' => '用户名或密码错误']); break;
        }
        $token = md5($user['uuid'] . time() . mt_rand());
        $pdo->prepare('UPDATE users SET token = ? WHERE id = ?')->execute([$token, $user['id']]);
        // 合并游客数据
        if (!empty($jsonBody['old_uuid'])) {
            mergeGuestData($pdo, $jsonBody['old_uuid'], $user['uuid']);
        }
        // 使用新积分等级系统计算等级
        require_once __DIR__ . '/includes/points_system.php';
        $loginScore = (int)($user['total_points'] ?? $user['score'] ?? 0);
        $levelInfo = calcLevelByPoints($loginScore);
        jsonResponse(['code' => 0, 'msg' => '登录成功', 'data' => [
            'token' => $token, 'uuid' => $user['uuid'], 'username' => $user['username'],
            'nickname' => $user['nickname'], 'avatar' => $user['avatar'] ?? '',
            'bio' => $user['bio'] ?? '', 'score' => $loginScore,
            'level' => $levelInfo['level'],
            'level_info' => $levelInfo
        ]]);
        break;


    // ---- 检查登录状态 ----
    case 'check_login':
        $token = $_GET['token'] ?? '';
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        $stmt = $pdo->prepare('SELECT uuid, username, nickname, avatar, bio, bind_email, score, total_points, token FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $u = $stmt->fetch();
        if (!$u) { jsonResponse(['code' => -1, 'msg' => '登录已过期']); break; }
        // 动态计算等级（新积分等级系统）
        require_once __DIR__ . '/includes/points_system.php';
        $totalPoints = (int)($u['total_points'] ?? $u['score'] ?? 0);
        $levelInfo = calcLevelByPoints($totalPoints);
        $u['level'] = $levelInfo['level'];
        $u['level_info'] = $levelInfo;
        unset($u['total_points']);
        jsonResponse(['code' => 0, 'data' => $u]);
        break;

    // ---- 更新个人资料 ----
    case 'update_profile':
        $token = $jsonBody['token'] ?? '';
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        $stmt = $pdo->prepare('SELECT id, uuid FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '登录已过期']); break; }
        $updates = []; $params = [];
        if (!empty($jsonBody['nickname'])) {
            $nicknameVal = trim($jsonBody['nickname']);
            $stmt2 = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt2->execute([$nicknameVal, $user['id']]);
            if ($stmt2->fetch()) { jsonResponse(['code' => -1, 'msg' => '该昵称已被使用']); break; }
            $updates[] = 'nickname = ?'; $params[] = $nicknameVal;
            $updates[] = 'username = ?'; $params[] = $nicknameVal;
        }
        if (isset($jsonBody['bio'])) { $updates[] = 'bio = ?'; $params[] = trim($jsonBody['bio']); }
        if (!empty($jsonBody['avatar'])) {
            $avatarData = $jsonBody['avatar'];
            if (preg_match('/^data:image\/(\w+);base64,/', $avatarData, $m)) {
                $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                $data = base64_decode(substr($avatarData, strpos($avatarData, ',') + 1));
                $filename = 'avatar_' . $user['uuid'] . '.' . $ext;
                $dir = __DIR__ . '/avatars';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                file_put_contents($dir . '/' . $filename, $data);
                $updates[] = 'avatar = ?';
                $params[] = '/avatars/' . $filename;
            }
        }
        if (!empty($updates)) {
            $params[] = $user['id'];
            $pdo->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
            // 同步更新到 user_prefs 表
            $prefUpdates = []; $prefParams = [];
            if (!empty($jsonBody['nickname'])) {
                $prefUpdates[] = 'nickname = ?'; $prefParams[] = trim($jsonBody['nickname']);
            }
            if (!empty($jsonBody['avatar']) && !empty($filename)) {
                $prefUpdates[] = 'avatar = ?'; $prefParams[] = '/avatars/' . $filename;
            }
            if (!empty($prefUpdates)) {
                $prefParams[] = $user['uuid'];
                // 检查 user_prefs 是否存在
                $check = $pdo->prepare('SELECT id FROM user_prefs WHERE uuid = ?');
                $check->execute([$user['uuid']]);
                if ($check->fetch()) {
                    $pdo->prepare('UPDATE user_prefs SET ' . implode(', ', $prefUpdates) . ' WHERE uuid = ?')->execute($prefParams);
                }
            }
        }
        $stmt = $pdo->prepare('SELECT uuid, username, nickname, avatar, bio, score, level FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        jsonResponse(['code' => 0, 'msg' => '更新成功', 'data' => $stmt->fetch()]);
        break;

    // ---- 头像上传（评论区裁剪上传）----
    case 'upload_avatar':
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['code' => -1, 'msg' => '没有收到图片或上传失败']);
        }
        $file = $_FILES['avatar'];
        if ($file['size'] > 5 * 1024 * 1024) {
            jsonResponse(['code' => -1, 'msg' => '图片不能超过5MB']);
        }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedTypes)) {
            jsonResponse(['code' => -1, 'msg' => '不支持的图片格式，请上传JPG/PNG/GIF/WebP']);
        }
        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'png'
        };
        $filename = 'avatar_' . uniqid() . '.' . $ext;
        $uploadDir = __DIR__ . '/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $url = '/avatars/' . $filename;
            jsonResponse(['code' => 0, 'url' => $url]);
        } else {
            jsonResponse(['code' => -1, 'msg' => '保存文件失败']);
        }
        break;

    // ---- 获取用户公开资料 ----
    case 'get_user_profile':
        $uuid = $_GET['uuid'] ?? '';
        if (!$uuid) { jsonResponse(['code' => -1, 'msg' => '缺少uuid']); break; }
        $stmt = $pdo->prepare('SELECT username, nickname, avatar, bio, bind_email, score, level, tags FROM users WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $u = $stmt->fetch();
        if (!$u) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        // 计算等级
        $score = (int)$u['score'];
        // 使用新积分等级系统
        require_once __DIR__ . '/includes/points_system.php';
        $totalPts = (int)($u['total_points'] ?? $u['score'] ?? 0);
        $levelInfo = calcLevelByPoints($totalPts);
        $u['level'] = $levelInfo['level'];
        $u['level_next'] = $levelInfo['nextLevelPoints'];
        $u['level_progress'] = $levelInfo['progressPercent'];
        $u['is_admin'] = ($u['username'] === 'admin');
        $u['tags'] = $u['tags'] ?? '';
        // 投稿数
        $subDir = __DIR__ . '/submissions';
        $subCount = 0;
        if (is_dir($subDir)) {
            $files = scandir($subDir);
            foreach ($files as $f) {
                if (strpos($f, $uuid) !== false) $subCount++;
            }
        }
        $u['submission_count'] = $subCount;
        // 评论数
        $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE uuid = ?');
        $stmt2->execute([$uuid]);
        $u['comment_count'] = (int)$stmt2->fetchColumn();
        // 标记目标用户是否为站长
        // 当前查看者信息（用于前端权限判断）
        $viewerToken = $_GET['token'] ?? $_COOKIE['token'] ?? '';
        $viewer = ['is_admin' => false, 'username' => '', 'tags' => ''];
        if ($viewerToken) {
            $vStmt = $pdo->prepare('SELECT username, tags FROM users WHERE token = ?');
            $vStmt->execute([$viewerToken]);
            $vRow = $vStmt->fetch();
            if ($vRow) {
                $viewer['is_admin'] = ($vRow['username'] === 'admin');
                $viewer['username'] = $vRow['username'];
                $viewer['tags'] = $vRow['tags'] ?? '';
            }
        }
        jsonResponse(['code' => 0, 'data' => $u, 'viewer' => $viewer]);
        break;

    // ---- 阅读心跳（1分钟=1分） ----
    // ---- 积分系统：阅读心跳（防刷） ----
    case 'heartbeat':
        $token = $_GET['token'] ?? '';
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        $stmt = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $u = $stmt->fetch();
        if (!$u) { jsonResponse(['code' => -1, 'msg' => '登录已过期']); break; }
        require_once __DIR__ . '/includes/points_system.php';
        $result = processHeartbeat($pdo, $u['uuid']);
        if ($result['status'] === 'skip') {
            jsonResponse(['code' => 0, 'data' => ['score' => null, 'msg' => $result['msg']]]);
            break;
        }
        // 返回最新积分和等级信息
        $stmt2 = $pdo->prepare('SELECT total_points, score FROM users WHERE uuid = ?');
        $stmt2->execute([$u['uuid']]);
        $u2 = $stmt2->fetch();
        $newScore = (int)($u2['total_points'] ?? $u2['score'] ?? 0);
        $levelInfo = calcLevelByPoints($newScore);
        jsonResponse(['code' => 0, 'data' => [
            'score' => $newScore,
            'level' => $levelInfo['level'],
            'progress' => $levelInfo['progressPercent'],
            'msg' => $result['msg']
        ]]);
        break;

    // ---- 追更（关注）----
    case 'follow_novel':
    case 'unfollow_novel':
    case 'toggle_follow':
        // 优先用 token 定位账号（追更归属登录账号），否则退回 uuid
        $token = $jsonBody['token'] ?? $_POST['token'] ?? '';
        $uuid = $jsonBody['uuid'] ?? $_POST['uuid'] ?? '';
        $novelId = (string)($jsonBody['novel_id'] ?? $_POST['novel_id'] ?? '');
        if ($token) {
            $ust = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
            $ust->execute([$token]);
            $ur = $ust->fetch();
            if (!$ur) { jsonResponse(['code' => -1, 'msg' => '登录已过期，请重新登录']); break; }
            $uuid = $ur['uuid'];
        }
        if (!$uuid || !$novelId) { jsonResponse(['code' => -1, 'msg' => '请先登录后再追更']); break; }
        // 读取当前 follows（无记录则创建）
        $stmt = $pdo->prepare('SELECT follows FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $pref = $stmt->fetch();
        if (!$pref) {
            $pdo->prepare("INSERT INTO user_prefs (uuid, follows) VALUES (?, '[]')")->execute([$uuid]);
            $follows = [];
        } else {
            $follows = $pref['follows'] ? json_decode($pref['follows'], true) : [];
            if (!is_array($follows)) $follows = [];
        }
        $follows = array_values(array_map('strval', $follows));
        $isFollowing = in_array($novelId, $follows, true);
        // 目标状态：follow=加，unfollow=删，toggle=取反
        if ($action === 'follow_novel') $want = true;
        elseif ($action === 'unfollow_novel') $want = false;
        else $want = !$isFollowing;
        $justFollowed = false;
        if ($want && !$isFollowing) {
            $follows[] = $novelId;
            $justFollowed = true;
        } elseif (!$want && $isFollowing) {
            $follows = array_values(array_filter($follows, function($x) use ($novelId) { return $x !== $novelId; }));
        }
        $pdo->prepare('UPDATE user_prefs SET follows = ? WHERE uuid = ?')->execute([json_encode($follows, JSON_UNESCAPED_UNICODE), $uuid]);
        // 收藏（追更）积分：首次收藏该书 +10（同一本书只发一次，防刷）
        $followMsg = $want ? '已追更～有更新会通知你' : '已取消追更';
        if ($justFollowed) {
            require_once __DIR__ . '/includes/points_system.php';
            try {
                $bp = processBookmarkPoints($pdo, $uuid, $novelId);
                if (!empty($bp['points'])) $followMsg = '已追更～ +' . $bp['points'] . '积分';
            } catch (Exception $e) {}
        }
        jsonResponse(['code' => 0, 'following' => $want, 'msg' => $followMsg]);
        break;

    // ---- 查询追更状态 ----
    case 'follow_status':
        $token = $_GET['token'] ?? '';
        $uuid = $_GET['uuid'] ?? '';
        $novelId = (string)($_GET['novel_id'] ?? '001');
        if ($token) {
            $ust = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
            $ust->execute([$token]);
            $ur = $ust->fetch();
            if ($ur) $uuid = $ur['uuid'];
        }
        $following = false;
        if ($uuid) {
            $stmt = $pdo->prepare('SELECT follows FROM user_prefs WHERE uuid = ?');
            $stmt->execute([$uuid]);
            $p = $stmt->fetch();
            if ($p && $p['follows']) {
                $arr = json_decode($p['follows'], true);
                if (is_array($arr)) $following = in_array($novelId, array_map('strval', $arr), true);
            }
        }
        jsonResponse(['code' => 0, 'following' => $following]);
        break;

    // ---- 收藏（+10积分） ----
    case 'bookmark_novel':
        $uuid = $jsonBody['uuid'] ?? $jsonBody['uuid'] ?? '';
        $novelId = $jsonBody['novel_id'] ?? '';
        if (!$uuid || !$novelId) { jsonResponse(['code' => -1, 'msg' => '参数不足']); break; }
        $stmt = $pdo->prepare('SELECT id FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $pref = $stmt->fetch();
        if ($pref) {
            $pdo->prepare("UPDATE user_prefs SET bookmarks = IFNULL(bookmarks, '[]'), bookmarks = JSON_ARRAY_APPEND(bookmarks, '$', CAST(? AS JSON)) WHERE uuid = ? AND NOT JSON_CONTAINS(bookmarks, CAST(? AS JSON))")->execute([$novelId, $uuid, json_encode($novelId)]);
        }
        // 收藏积分
        require_once __DIR__ . '/includes/points_system.php';
        $pointsResult = processBookmarkPoints($pdo, $uuid, $novelId);
        jsonResponse(['code' => 0, 'msg' => $pointsResult['points'] > 0 ? '已加入收藏 +' . $pointsResult['points'] . '积分' : '已加入收藏']);
        break;

    // ---- 保存阅读进度 ----
    case 'save_progress':
        $uuid = $jsonBody['uuid'] ?? '';
        $novelId = $jsonBody['novel_id'] ?? '';
        $chapterIdx = $jsonBody['chapter_index'] ?? 0;
        $scrollPos = $jsonBody['scroll_pos'] ?? 0;
        if (!$uuid || !$novelId) { jsonResponse(['code' => -1, 'msg' => '参数不足']); break; }
        $progress = json_encode(['novel_id' => $novelId, 'chapter' => (int)$chapterIdx, 'scroll' => (int)$scrollPos, 'time' => time()]);
        $stmt = $pdo->prepare('SELECT id FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE user_prefs SET progress = JSON_ARRAY_APPEND(IFNULL(progress, '[]'), '$', CAST(? AS JSON)) WHERE uuid = ?")->execute([$progress, $uuid]);
        }
        jsonResponse(['code' => 0]);
        break;

    // ---- 获取阅读进度 ----
    case 'get_progress':
        $uuid = $_GET['uuid'] ?? '';
        $novelId = $_GET['novel_id'] ?? '';
        if (!$uuid || !$novelId) { jsonResponse(['code' => -1, 'msg' => '参数不足']); break; }
        $stmt = $pdo->prepare('SELECT progress FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $data = $stmt->fetch();
        $progress = [];
        if ($data && $data['progress']) {
            $all = json_decode($data['progress'], true);
            foreach ($all as $p) { if ($p['novel_id'] === $novelId) $progress = $p; }
        }
        jsonResponse(['code' => 0, 'data' => $progress ?: null]);
        break;

    // ---- 投稿 ----
    case 'submit_file':
        $token = $_POST['token'] ?? '';
        $title = trim($_POST['title'] ?? '');
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        if (!$title) { jsonResponse(['code' => -1, 'msg' => '请输入标题']); break; }
        $stmt = $pdo->prepare('SELECT uuid, username FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '登录已过期']); break; }
        if (empty($_FILES['file'])) { jsonResponse(['code' => -1, 'msg' => '请选择文件']); break; }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) { jsonResponse(['code' => -1, 'msg' => '上传失败']); break; }
        if ($file['size'] > 100 * 1024 * 1024) {
            jsonResponse(['code' => -1, 'msg' => '文件不能超过100MB']);
            break;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = $user['uuid'] . '_' . time() . '.' . $ext;
        $dir = __DIR__ . '/submissions';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dest = $dir . '/' . $newName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) { jsonResponse(['code' => -1, 'msg' => '文件保存失败']); break; }
        $stmt2 = $pdo->prepare('INSERT INTO submissions (uuid, username, title, filename, original_name, created_at) VALUES (?,?,?,?,?,NOW())');
        $stmt2->execute([$user['uuid'], $user['username'], $title, $newName, $file['name']]);
        jsonResponse(['code' => 0, 'msg' => '投稿成功，等待审核']);
        break;

    // ---- 获取用户完整数据（个人中心用） ----
    case 'get_user_data':
        $uuid = $_GET['uuid'] ?? '';
        if (!$uuid) { jsonResponse(['code' => -1, 'msg' => '缺少uuid']); break; }
        $stmt = $pdo->prepare('SELECT username, nickname, avatar, bio, bind_email, score, level, created_at, tags FROM users WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        $user['is_admin'] = ($user['username'] === 'admin');
        $user['tags'] = $user['tags'] ?? '';
        // 使用新积分等级系统
        require_once __DIR__ . '/includes/points_system.php';
        $totalPts = (int)($user['total_points'] ?? $user['score'] ?? 0);
        $levelInfo = calcLevelByPoints($totalPts);
        $user['level'] = $levelInfo['level'];
        // 获取用户prefs数据
        $stmt2 = $pdo->prepare('SELECT progress, follows, bookmarks FROM user_prefs WHERE uuid = ?');
        $stmt2->execute([$uuid]);
        $prefs = $stmt2->fetch();
        if ($prefs) {
            $user['progress'] = $prefs['progress'] ? json_decode($prefs['progress'], true) : [];
            $followIds = $prefs['follows'] ? json_decode($prefs['follows'], true) : [];
            $user['bookmarks'] = $prefs['bookmarks'] ? json_decode($prefs['bookmarks'], true) : [];
        } else {
            $user['progress'] = []; $followIds = []; $user['bookmarks'] = [];
        }
        // 小说名称映射（目录名 => 小说名）
        $novelNames = [
            '001' => '示例小说',   // 001/index.php
            '002' => '示例作品',        // 002/index.php
        ];
        // 追更列表带上小说名
        $user['follows'] = [];
        if (is_array($followIds)) {
            foreach ($followIds as $id) {
                $id = (string)$id;
                $user['follows'][] = [
                    'id' => $id,
                    'name' => $novelNames[$id] ?? '小说 #' . $id,
                ];
            }
        }
        // 直接统计评论数（准确）
        $stmt3 = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE uuid = ?');
        $stmt3->execute([$uuid]);
        $user['comment_count'] = (int)$stmt3->fetchColumn();
        jsonResponse(['code' => 0, 'data' => $user]);
        break;

    // ---- 作者数据看板 ----
    case 'get_author_stats':
        $token = $_GET['token'] ?? '';
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        $stmt = $pdo->prepare('SELECT username, tags FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $u = $stmt->fetch();
        if (!$u || ($u['username'] !== 'admin' && strpos($u['tags'] ?? '', '测试组') === false)) { jsonResponse(['code' => -1, 'msg' => '无权限']); break; }
        $novelId = $_GET['novel_id'] ?? '001';
        // 统计
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE novel_id = ?');
        $stmt->execute([$novelId]);
        $totalComments = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT uuid) FROM comments WHERE novel_id = ?');
        $stmt->execute([$novelId]);
        $totalReaders = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_prefs WHERE JSON_SEARCH(follows, 'one', ?) IS NOT NULL");
        $stmt->execute([$novelId]);
        $totalFollows = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT SUM(stat_value) FROM stats WHERE stat_key = 'views' AND novel_id = ?");
        $stmt->execute([$novelId]);
        $totalViews = (int)$stmt->fetchColumn();
        // 章节数
        $txtDir = __DIR__ . '/001/';
        $chapterCount = 0;
        $txtFiles = glob($txtDir . '*.txt');
        if (!empty($txtFiles)) {
            $content = file_get_contents($txtFiles[0]);
            $chapterCount = preg_match_all('/^第[0-9一二三四五六七八九十百千]+章/mu', $content);
        }
        // 最新章节信息
        $stmt = $pdo->prepare("SELECT stat_value FROM stats WHERE stat_key = 'last_comment_time' AND novel_id = ?");
        $stmt->execute([$novelId]);
        $lastCommentTime = $stmt->fetchColumn();
        $subCount = 0;
        $subDir = __DIR__ . '/submissions';
        if (is_dir($subDir)) { $subCount = count(array_diff(scandir($subDir), ['.','..'])); }
        jsonResponse(['code' => 0, 'data' => [
            'total_views' => $totalViews, 'total_comments' => $totalComments,
            'total_readers' => $totalReaders, 'total_follows' => $totalFollows,
            'chapter_count' => $chapterCount, 'submissions' => $subCount,
            'last_comment_time' => $lastCommentTime ?: '暂无'
        ]]);
        break;


    // ---- 获取投稿列表 ----
    case 'get_submissions':
        $uuid = $_GET['uuid'] ?? '';
        if (!$uuid) { jsonResponse(['code' => -1, 'msg' => '缺少uuid']); break; }
        $subDir = __DIR__ . '/submissions';
        $stmt = $pdo->prepare('SELECT id, title, filename, original_name, status, created_at FROM submissions WHERE uuid = ? ORDER BY created_at DESC');
        $stmt->execute([$uuid]);
        $list = $stmt->fetchAll();
        $result = [];
        foreach ($list as $s) {
            $result[] = [
                'id' => (int)$s['id'],
                'title' => $s['title'],
                'filename' => $s['filename'],
                'original_name' => $s['original_name'],
                'status' => (int)$s['status'],
                'created_at' => $s['created_at']
            ];
        }
        jsonResponse(['code' => 0, 'data' => $result]);
        break;

    // ---- 发送验证码 ----
    case 'send_verify_code':
        $email = trim($_POST['email'] ?? $_GET['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['code' => 1, 'msg' => '邮箱格式不正确']); break;
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM verify_codes WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND used = 0');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['code' => 1, 'msg' => '请60秒后再试']); break;
        }
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('INSERT INTO verify_codes (email, code) VALUES (?, ?)');
        $stmt->execute([$email, $code]);
        require_once __DIR__ . '/mail.php';
        $body = buildMailTemplate(
            '🔐 邮箱验证码',
            '<p style="margin:0 0 16px;">您好～您正在绑定小说站的邮箱，验证码如下：</p>'
            . '<div style="background:#f9f6f2;text-align:center;padding:24px;border-radius:12px;margin:16px 0;">'
            . '<span style="font-size:42px;font-weight:700;letter-spacing:8px;color:#8b6f5c;font-family:monospace;">' . $code . '</span></div>'
            . '<p style="color:#999;font-size:13px;">验证码有效期5分钟，请勿泄露给他人～</p>',
            '', ''
        );
        $ok = sendMail($email, '🔐 邮箱验证码 - 示例小说', $body);
        jsonResponse($ok ? ['code' => 0, 'msg' => '验证码已发送'] : ['code' => 1, 'msg' => '发送失败，请检查邮箱地址']);
        break;

    // ---- 绑定邮箱 ----
    case 'bind_email':
        $token = $_POST['token'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { jsonResponse(['code' => 1, 'msg' => '邮箱格式不正确']); break; }
        if (!preg_match('/^\d{6}$/', $code)) { jsonResponse(['code' => 1, 'msg' => '验证码格式不正确']); break; }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token = ?');
        $stmt->execute([$token]);
        if (!$stmt->fetch()) { jsonResponse(['code' => -1, 'msg' => '登录已过期，请重新登录']); break; }
        // 验证验证码
        $stmt = $pdo->prepare('SELECT id FROM verify_codes WHERE email = ? AND code = ? AND used = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
        $stmt->execute([$email, $code]);
        if (!$stmt->fetch()) { jsonResponse(['code' => 1, 'msg' => '验证码错误或已过期']); break; }
        $pdo->prepare('UPDATE verify_codes SET used = 1 WHERE email = ? AND code = ?')->execute([$email, $code]);
        // 邮箱作为账号绑定，需唯一（可用于登录），不能与他人已绑定的邮箱重复
        $chk = $pdo->prepare('SELECT id FROM users WHERE bind_email = ? AND token != ?');
        $chk->execute([$email, $token]);
        if ($chk->fetch()) { jsonResponse(['code' => 1, 'msg' => '该邮箱已被其他账号绑定']); break; }
        $pdo->prepare('UPDATE users SET bind_email = ? WHERE token = ?')->execute([$email, $token]);
        jsonResponse(['code' => 0, 'msg' => '绑定成功']);
        break;

    // ---- 积分系统：每日签到 ----
    case 'checkin':
        require_once __DIR__ . '/includes/points_system.php';
        $token = $_GET['token'] ?? $_POST['token'] ?? ($jsonBody['token'] ?? '');
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        $stmt = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $u = $stmt->fetch();
        if (!$u) { jsonResponse(['code' => -1, 'msg' => '登录已过期']); break; }
        $result = processCheckin($pdo, $u['uuid']);
        if ($result['status'] === 'already') {
            jsonResponse(['code' => 1, 'msg' => $result['msg']]);
        } elseif ($result['status'] === 'ok') {
            $stmt2 = $pdo->prepare('SELECT total_points, score FROM users WHERE uuid = ?');
            $stmt2->execute([$u['uuid']]);
            $u2 = $stmt2->fetch();
            $newScore = (int)($u2['total_points'] ?? $u2['score'] ?? 0);
            $levelInfo = calcLevelByPoints($newScore);
            jsonResponse(['code' => 0, 'data' => [
                'points' => $result['points'],
                'bonus' => $result['bonus'],
                'total_earned' => $result['total_earned'],
                'consecutive' => $result['consecutive'],
                'score' => $newScore,
                'level' => $levelInfo['level'],
                'msg' => $result['msg']
            ]]);
        } else {
            jsonResponse(['code' => -1, 'msg' => $result['msg']]);
        }
        break;

    // ---- 积分系统：书评积分 ----
    case 'review_points':
        require_once __DIR__ . '/includes/points_system.php';
        $token = $_GET['token'] ?? $_POST['token'] ?? ($jsonBody['token'] ?? '');
        $content = $_GET['content'] ?? $_POST['content'] ?? ($jsonBody['content'] ?? '');
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        if (!$content) { jsonResponse(['code' => -1, 'msg' => '内容不能为空']); break; }
        $stmt = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $u = $stmt->fetch();
        if (!$u) { jsonResponse(['code' => -1, 'msg' => '登录已过期']); break; }
        $result = processReviewPoints($pdo, $u['uuid'], $content);
        jsonResponse(['code' => 0, 'data' => $result]);
        break;

    // ---- 积分系统：获取用户积分汇总 ----
    case 'get_points_summary':
        require_once __DIR__ . '/includes/points_system.php';
        $token = $_GET['token'] ?? '';
        $uuid = $_GET['uuid'] ?? '';
        if ($token) {
            $stmt = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
            $stmt->execute([$token]);
            $u = $stmt->fetch();
            if ($u) $uuid = $u['uuid'];
        }
        if (!$uuid) { jsonResponse(['code' => -1, 'msg' => '缺少参数']); break; }
        $summary = getUserPointsSummary($pdo, $uuid);
        if (!$summary) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        jsonResponse(['code' => 0, 'data' => $summary]);
        break;

    // ---- 积分系统：获取积分变动日志 ----
    case 'get_points_log':
        $token = $_GET['token'] ?? '';
        $uuid = $_GET['uuid'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        if ($token) {
            $stmt = $pdo->prepare('SELECT uuid FROM users WHERE token = ?');
            $stmt->execute([$token]);
            $u = $stmt->fetch();
            if ($u) $uuid = $u['uuid'];
        }
        if (!$uuid) { jsonResponse(['code' => -1, 'msg' => '缺少参数']); break; }
        $lmt = (int)$limit; $off = (int)$offset;
        $stmt = $pdo->prepare("SELECT id, points, balance, action_type, detail, created_at FROM user_points_log WHERE uuid = ? ORDER BY created_at DESC LIMIT $lmt OFFSET $off");
        $stmt->execute([$uuid]);
        $logs = $stmt->fetchAll();
        // 获取总数
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_points_log WHERE uuid = ?");
        $stmt->execute([$uuid]);
        $total = (int)$stmt->fetchColumn();
        jsonResponse(['code' => 0, 'data' => $logs, 'total' => $total, 'page' => $page, 'limit' => $limit]);
        break;

    // ---- 阅读笔记（数据库永久保存）----
    case 'add_note':
        $token = $jsonBody['token'] ?? $_POST['token'] ?? '';
        $novel_id = $jsonBody['novel_id'] ?? $_POST['novel_id'] ?? '';
        $chapter = (int)($jsonBody['chapter'] ?? $_POST['chapter'] ?? 0);
        $text = $jsonBody['text'] ?? $_POST['text'] ?? '';
        $note = $jsonBody['note'] ?? $_POST['note'] ?? '';
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        if (!$novel_id || !$text) { jsonResponse(['code' => -1, 'msg' => '参数不足']); break; }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        $stmt = $pdo->prepare('INSERT INTO reading_notes (user_id, novel_id, chapter, text_content, note) VALUES (?,?,?,?,?)');
        $stmt->execute([$user['id'], $novel_id, $chapter, $text, $note]);
        jsonResponse(['code' => 0, 'msg' => '保存成功', 'id' => (int)$pdo->lastInsertId()]);
        break;

    case 'get_notes':
        $token = $_GET['token'] ?? '';
        $novel_id = $_GET['novel_id'] ?? '';
        $chapter = (int)($_GET['chapter'] ?? 0);
        if (!$token) { jsonResponse(['code' => -1, 'msg' => '未登录']); break; }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        $sql = 'SELECT id, novel_id, chapter, text_content, note, created_at FROM reading_notes WHERE user_id = ? AND novel_id = ?';
        $params = [$user['id'], $novel_id];
        if ($chapter > 0) {
            $sql .= ' AND chapter = ?';
            $params[] = $chapter;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['code' => 0, 'data' => $stmt->fetchAll()]);
        break;

    case 'update_note':
        $token = $jsonBody['token'] ?? $_POST['token'] ?? '';
        $id = (int)($jsonBody['id'] ?? $_POST['id'] ?? 0);
        $note = $jsonBody['note'] ?? $_POST['note'] ?? '';
        if (!$token || !$id) { jsonResponse(['code' => -1, 'msg' => '参数不足']); break; }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        $stmt = $pdo->prepare('UPDATE reading_notes SET note = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$note, $id, $user['id']]);
        jsonResponse(['code' => 0, 'msg' => '更新成功']);
        break;

    case 'delete_note':
        $token = $jsonBody['token'] ?? $_POST['token'] ?? '';
        $id = (int)($jsonBody['id'] ?? $_POST['id'] ?? 0);
        if (!$token || !$id) { jsonResponse(['code' => -1, 'msg' => '参数不足']); break; }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) { jsonResponse(['code' => -1, 'msg' => '用户不存在']); break; }
        $stmt = $pdo->prepare('DELETE FROM reading_notes WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        jsonResponse(['code' => 0, 'msg' => '删除成功']);
        break;

    default:
        echo json_encode(['code' => -1, 'msg' => 'unknown action']);
        break;

}

// ====== 表情数据（保留供 001 页面使用） ======
function getEmojiData() {
    $owoBase = '/var/www/html/usr/themes/Xc/assets/owo';
    $webBase = '/usr/themes/Xc/assets/owo';
    $categories = ['QQ' => 'QQ', 'bilibili' => '哔哩哔哩', 'paopao' => 'PaoPao', 'aru' => 'Aru'];
    $result = [];
    foreach ($categories as $dir => $label) {
        $path = $owoBase . '/' . $dir;
        if (!is_dir($path)) continue;
        $files = scandir($path);
        $emojis = [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png','gif','jpg','jpeg','webp','svg'])) continue;
            $name = pathinfo($f, PATHINFO_FILENAME);
            $emojis[] = [
                'name' => $name,
                'url' => $webBase . '/' . $dir . '/' . $f
            ];
        }
        if (!empty($emojis)) {
            $result[] = ['label' => $label, 'emojis' => $emojis];
        }
    }
    return $result;
}

