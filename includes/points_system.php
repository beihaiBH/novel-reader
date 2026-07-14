<?php
/**
 * 积分等级系统 - 核心逻辑
 * 包含：等级计算、积分变动记录、签到逻辑
 */

// ====== 等级配置表 ======
// 规则：1有效阅读分钟 = 1积分
// 等级基于累计总积分（total_points）判定
function getLevelConfig() {
    return [
        ['level' => 0,  'total_points' => 0,    'need_increment' => 0],
        ['level' => 1,  'total_points' => 10,   'need_increment' => 10],
        ['level' => 2,  'total_points' => 40,   'need_increment' => 30],
        ['level' => 3,  'total_points' => 90,   'need_increment' => 50],
        ['level' => 4,  'total_points' => 180,  'need_increment' => 90],
        ['level' => 5,  'total_points' => 350,  'need_increment' => 170],
        ['level' => 6,  'total_points' => 620,  'need_increment' => 270],
        ['level' => 7,  'total_points' => 980,  'need_increment' => 360],
        ['level' => 8,  'total_points' => 1450, 'need_increment' => 470],
        ['level' => 9,  'total_points' => 2050, 'need_increment' => 600],
        ['level' => 10, 'total_points' => 2800, 'need_increment' => 750],
    ];
}

/**
 * 根据累计总积分计算等级
 * @param int $totalPoints 累计总积分
 * @return array {level, currentTotalPoints, nextLevelPoints, progressPercent, needIncrement}
 */
function calcLevelByPoints($totalPoints) {
    $levels = getLevelConfig();
    $result = ['level' => 0, 'currentTotalPoints' => $totalPoints, 'nextLevelPoints' => 10, 'progressPercent' => 0, 'needIncrement' => 0];
    
    foreach ($levels as $lv) {
        if ($totalPoints >= $lv['total_points']) {
            $result['level'] = $lv['level'];
            $result['needIncrement'] = $lv['need_increment'];
        }
    }
    
    // 计算下一级
    $nextLv = null;
    foreach ($levels as $lv) {
        if ($lv['level'] === $result['level'] + 1) {
            $nextLv = $lv;
            break;
        }
    }
    
    if ($nextLv) {
        $result['nextLevelPoints'] = $nextLv['total_points'];
        $prevPoints = $levels[$result['level']]['total_points'];
        $gap = $nextLv['total_points'] - $prevPoints;
        if ($gap > 0) {
            $progress = $totalPoints - $prevPoints;
            $result['progressPercent'] = min(100, round($progress / $gap * 100));
        }
    } else {
        // 满级
        $result['nextLevelPoints'] = 999999;
        $result['progressPercent'] = 100;
    }
    
    return $result;
}

/**
 * 计算等级对应的视觉阶段
 * @param int $level
 * @return string basic|golden|peak
 */
function getLevelStage($level) {
    if ($level <= 1) return 's1';
    if ($level >= 2 && $level <= 4) return 's2';
    if ($level >= 5 && $level <= 7) return 's3';
    if ($level >= 8 && $level <= 9) return 's4';
    if ($level >= 10) return 's5';
    return 's1';
}

/**
 * 记录积分变动
 * @param PDO $pdo
 * @param string $uuid 用户UUID
 * @param int $points 变动积分（正数增加，负数扣除）
 * @param string $actionType 变动类型
 * @param array $detail 详情信息
 * @return int 变动后余额
 */
function addPointsLog($pdo, $uuid, $points, $actionType, $detail = []) {
    // 获取当前余额
    $stmt = $pdo->prepare("SELECT total_points, score FROM users WHERE uuid = ?");
    $stmt->execute([$uuid]);
    $user = $stmt->fetch();
    if (!$user) return false;
    
    $currentBalance = (int)($user['total_points'] ?? $user['score'] ?? 0);
    $newBalance = $currentBalance + $points;
    if ($newBalance < 0) $newBalance = 0;
    
    // 更新 users 表的 total_points 和 score（向后兼容）
    $pdo->prepare("UPDATE users SET total_points = ?, score = ? WHERE uuid = ?")
        ->execute([$newBalance, $newBalance, $uuid]);
    
    // 写入日志
    $stmt = $pdo->prepare("INSERT INTO user_points_log (uuid, points, balance, action_type, detail, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $uuid,
        $points,
        $newBalance,
        $actionType,
        json_encode($detail, JSON_UNESCAPED_UNICODE)
    ]);
    
    return $newBalance;
}

/**
 * 每日签到处理
 * @param PDO $pdo
 * @param string $uuid
 * @return array {status, points, consecutive, bonus, msg}
 */
function processCheckin($pdo, $uuid) {
    $today = date('Y-m-d');
    
    // 检查今天是否已签到
    $stmt = $pdo->prepare("SELECT id FROM user_checkins WHERE uuid = ? AND checkin_date = ?");
    $stmt->execute([$uuid, $today]);
    if ($stmt->fetch()) {
        return ['status' => 'already', 'msg' => '今天已经签到过了~'];
    }
    
    // 获取用户信息
    $stmt = $pdo->prepare("SELECT last_checkin_date, consecutive_checkin_days FROM users WHERE uuid = ?");
    $stmt->execute([$uuid]);
    $user = $stmt->fetch();
    if (!$user) return ['status' => 'error', 'msg' => '用户不存在'];
    
    $lastDate = $user['last_checkin_date'];
    $consecutive = (int)$user['consecutive_checkin_days'];
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // 判断是否连续
    if ($lastDate === $yesterday) {
        $consecutive++;
    } else {
        $consecutive = 1; // 断签，重新计算
    }
    
    // 计算签到积分
    $basePoints = 20;
    if ($consecutive > 2) {
        $basePoints = 40;
    }
    
    // 检查是否满7天（周期奖励）
    $bonusPoints = 0;
    // 满7天且是周期的第7天（连续天数能被7整除）
    if ($consecutive % 7 === 0) {
        $bonusPoints = 150;
    }
    
    $totalEarned = $basePoints + $bonusPoints;
    
    // 记录签到
    $stmt = $pdo->prepare("INSERT INTO user_checkins (uuid, checkin_date, consecutive_days, points_earned, bonus_points, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$uuid, $today, $consecutive, $basePoints, $bonusPoints]);
    
    // 更新用户签到状态
    $pdo->prepare("UPDATE users SET last_checkin_date = ?, consecutive_checkin_days = ? WHERE uuid = ?")
        ->execute([$today, $consecutive, $uuid]);
    
    // 增加积分
    addPointsLog($pdo, $uuid, $totalEarned, 'checkin', [
        'base' => $basePoints,
        'bonus' => $bonusPoints,
        'consecutive' => $consecutive,
        'date' => $today
    ]);
    
    return [
        'status' => 'ok',
        'points' => $basePoints,
        'bonus' => $bonusPoints,
        'total_earned' => $totalEarned,
        'consecutive' => $consecutive,
        'msg' => $bonusPoints > 0 ? "🎉 签到成功！连续{$consecutive}天获得满贯奖励！" : '签到成功~'
    ];
}

/**
 * 添加书评积分
 * @param PDO $pdo
 * @param string $uuid
 * @param string $content 书评内容
 * @return array
 */
function processReviewPoints($pdo, $uuid, $content) {
    $content = trim($content);
    // 去掉标签前缀和emoji标签，计算实际文字长度
    $cleanContent = preg_replace('/^\[!!?[^\]]*\]\s*/', '', $content);
    $cleanContent = preg_replace('/!\[\]\([^)]+\)/', '', $cleanContent);
    $len = mb_strlen($cleanContent);
    
    if ($len < 10) {
        return ['status' => 'skip', 'msg' => '内容不足10字，不获得积分', 'points' => 0];
    }
    
    // 防止重复刷分：检查最近是否已有内容相似的评论
    // 这里只做简单限制，同一天同一用户最多通过评论获得3次积分
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_points_log WHERE uuid = ? AND action_type = 'review' AND DATE(created_at) = CURDATE()");
    $stmt->execute([$uuid]);
    $todayReviewCount = (int)$stmt->fetchColumn();
    
    if ($todayReviewCount >= 3) {
        return ['status' => 'limit', 'msg' => '今日评论积分已达上限', 'points' => 0];
    }
    
    $points = 30;
    addPointsLog($pdo, $uuid, $points, 'review', [
        'content_length' => $len,
        'review_date' => date('Y-m-d')
    ]);
    
    return ['status' => 'ok', 'msg' => "获得{$points}积分", 'points' => $points];
}

/**
 * 收藏书籍积分
 * @param PDO $pdo
 * @param string $uuid
 * @param string $novelId
 * @return array
 */
function processBookmarkPoints($pdo, $uuid, $novelId) {
    // 防止重复：同一本书只给一次收藏积分
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_points_log WHERE uuid = ? AND action_type = 'bookmark' AND detail LIKE ?");
    $stmt->execute([$uuid, '%"' . $novelId . '"%']);
    $existing = (int)$stmt->fetchColumn();
    
    if ($existing > 0) {
        return ['status' => 'skip', 'msg' => '这本书已领取过收藏积分', 'points' => 0];
    }
    
    $points = 10;
    addPointsLog($pdo, $uuid, $points, 'bookmark', [
        'novel_id' => $novelId,
        'bookmark_date' => date('Y-m-d')
    ]);
    
    return ['status' => 'ok', 'msg' => "获得{$points}积分", 'points' => $points];
}

/**
 * 阅读心跳处理（防刷）
 * @param PDO $pdo
 * @param string $uuid
 * @return array
 */
function processHeartbeat($pdo, $uuid) {
    // 防刷：检查上次心跳时间
    // 使用 session 级别的检查，用 user_points_log 最近一条阅读记录来判断
    $stmt = $pdo->prepare("SELECT created_at FROM user_points_log WHERE uuid = ? AND action_type = 'reading' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$uuid]);
    $last = $stmt->fetch();
    
    if ($last) {
        $lastTime = strtotime($last['created_at']);
        $now = time();
        $diff = $now - $lastTime;
        // 如果距离上次心跳不足 50 秒，拒绝（必须至少间隔50秒）
        if ($diff < 50) {
            return ['status' => 'skip', 'msg' => '心跳间隔太短', 'points' => 0];
        }
        // 如果超过 3 分钟没有心跳，说明可能换章节/离开了，不算连续阅读
        // 但还是给这一分钟的分（用户确实在读）
    }
    
    $points = 1;
    $newBalance = addPointsLog($pdo, $uuid, $points, 'reading', [
        'heartbeat_time' => date('Y-m-d H:i:s')
    ]);
    
    // 累计阅读分钟数
    $pdo->prepare("UPDATE users SET reading_minutes = reading_minutes + 1 WHERE uuid = ?")->execute([$uuid]);
    
    return ['status' => 'ok', 'msg' => "+1积分", 'points' => $points, 'balance' => $newBalance];
}

/**
 * 获取用户积分汇总
 */
function getUserPointsSummary($pdo, $uuid) {
    $stmt = $pdo->prepare("SELECT id, username, nickname, score, total_points, reading_minutes, consecutive_checkin_days, last_checkin_date FROM users WHERE uuid = ?");
    $stmt->execute([$uuid]);
    $user = $stmt->fetch();
    if (!$user) return null;
    
    $totalPoints = (int)($user['total_points'] ?? $user['score'] ?? 0);
    $levelInfo = calcLevelByPoints($totalPoints);
    
    // 获取各类型积分统计
    $stmt = $pdo->prepare("SELECT action_type, SUM(points) as total FROM user_points_log WHERE uuid = ? GROUP BY action_type");
    $stmt->execute([$uuid]);
    $typeStats = [];
    while ($row = $stmt->fetch()) {
        $typeStats[$row['action_type']] = (int)$row['total'];
    }
    
    // 检查今日是否已签到
    $stmt = $pdo->prepare("SELECT id FROM user_checkins WHERE uuid = ? AND checkin_date = ?");
    $stmt->execute([$uuid, date('Y-m-d')]);
    $todayCheckedIn = $stmt->fetch() ? true : false;
    
    return [
        'uuid' => $uuid,
        'nickname' => $user['nickname'],
        'score' => $totalPoints,
        'total_points' => $totalPoints,
        'reading_minutes' => (int)$user['reading_minutes'],
        'level_info' => $levelInfo,
        'stage' => getLevelStage($levelInfo['level']),
        'consecutive_checkin_days' => (int)$user['consecutive_checkin_days'],
        'last_checkin_date' => $user['last_checkin_date'],
        'today_checked_in' => $todayCheckedIn,
        'points_breakdown' => $typeStats,
    ];
}
