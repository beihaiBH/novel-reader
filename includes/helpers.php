<?php
/**
 * 通用辅助函数
 */

/**
 * 获取小说ID（从URI或参数）
 */
function getNovelId() {
    // 优先 GET/POST 参数
    if (isset($_REQUEST['novel_id'])) {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $_REQUEST['novel_id']);
    }
    // 从 URL 路径推断：/novel/001/... 或 /001/
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/(\d{3,})/#', $uri, $m)) {
        return $m[1];
    }
    // 从 Referer 推断（前端 API 请求不带 novel_id 时回退）
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer && preg_match('#/(\d{3,})/#', $referer, $m)) {
        return $m[1];
    }
    return '001'; // 默认
}

/**
 * 获取客户端 UUID
 */
function getClientUUID() {
    return $_REQUEST['uuid'] ?? $_COOKIE['novel_uuid'] ?? '';
}

/**
 * 检测是否为管理员
 */
function isAdminUser() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $whitelist = @file(dirname(__DIR__) . '/config/admin_whitelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    if (in_array($ip, $whitelist)) return true;
    if ($forwarded && in_array($forwarded, $whitelist)) return true;
    return false;
}

/**
 * JSON 响应
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 注册/登录时合并游客数据到新账号
 * 将 old_uuid 的评论、偏好设置合并到 new_uuid
 */
function mergeGuestData($pdo, $oldUuid, $newUuid) {
    if (!$oldUuid || !$newUuid || $oldUuid === $newUuid) return;
    // 1. 合并评论uuid
    $stmt = $pdo->prepare('UPDATE comments SET uuid = ? WHERE uuid = ?');
    $stmt->execute([$newUuid, $oldUuid]);
    // 2. 合并 user_prefs
    $stmt = $pdo->prepare('SELECT * FROM user_prefs WHERE uuid = ?');
    $stmt->execute([$oldUuid]);
    $oldPrefs = $stmt->fetch();
    if ($oldPrefs) {
        $stmt = $pdo->prepare('SELECT * FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$newUuid]);
        $newPrefs = $stmt->fetch();
        if ($newPrefs) {
            // 合并设置
            $oldSettings = $oldPrefs['settings'] ? json_decode($oldPrefs['settings'], true) : [];
            $newSettings = $newPrefs['settings'] ? json_decode($newPrefs['settings'], true) : [];
            if (!empty($oldSettings)) {
                foreach ($oldSettings as $k => $v) {
                    if (!isset($newSettings[$k])) $newSettings[$k] = $v;
                }
                $stmt = $pdo->prepare('UPDATE user_prefs SET settings = ? WHERE uuid = ?');
                $stmt->execute([json_encode($newSettings, JSON_UNESCAPED_UNICODE), $newUuid]);
            }
            // 合并点赞评论（取并集）
            $oldLiked = $oldPrefs['liked_comments'] ? json_decode($oldPrefs['liked_comments'], true) : [];
            if (!empty($oldLiked)) {
                $newLiked = $newPrefs['liked_comments'] ? json_decode($newPrefs['liked_comments'], true) : [];
                $merged = array_unique(array_merge($newLiked, $oldLiked));
                $stmt = $pdo->prepare('UPDATE user_prefs SET liked_comments = ? WHERE uuid = ?');
                $stmt->execute([json_encode($merged, JSON_UNESCAPED_UNICODE), $newUuid]);
            }
            // 合并标签
            $oldTags = $oldPrefs['tags'] ? json_decode($oldPrefs['tags'], true) : [];
            if (!empty($oldTags)) {
                $newTags = $newPrefs['tags'] ? json_decode($newPrefs['tags'], true) : [];
                foreach ($oldTags as $ot) {
                    $found = false;
                    foreach ($newTags as &$nt) {
                        if ($nt['tag'] === $ot['tag']) {
                            $nt['level'] = max($nt['level'] ?? 0, $ot['level'] ?? 0);
                            $nt['count'] = ($nt['count'] ?? 0) + ($ot['count'] ?? 0);
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) $newTags[] = $ot;
                }
                $stmt = $pdo->prepare('UPDATE user_prefs SET tags = ? WHERE uuid = ?');
                $stmt->execute([json_encode($newTags, JSON_UNESCAPED_UNICODE), $newUuid]);
            }
        } else {
            // 新用户没有prefs，直接把旧的搬过来
            $oldPrefs['uuid'] = $newUuid;
            unset($oldPrefs['id'], $oldPrefs['created_at'], $oldPrefs['updated_at']);
            $cols = implode(', ', array_keys($oldPrefs));
            $vals = array_values($oldPrefs);
            $placeholders = implode(', ', array_fill(0, count($vals), '?'));
            $stmt = $pdo->prepare("INSERT INTO user_prefs ($cols) VALUES ($placeholders)");
            $stmt->execute($vals);
        }
        // 3. 删除旧prefs
        $pdo->prepare('DELETE FROM user_prefs WHERE uuid = ?')->execute([$oldUuid]);
    }
}
