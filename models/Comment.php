<?php
/**
 * Comment Model - 评论数据层
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Comment {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /**
     * 获取评论列表（含回复）
     */
    /**
     * 根据总积分计算等级（PHP侧）
     */
    private function calcLevelByPoints($totalPoints) {
        $levels = [
            ['level' => 0, 'total' => 0],
            ['level' => 1, 'total' => 10],
            ['level' => 2, 'total' => 40],
            ['level' => 3, 'total' => 90],
            ['level' => 4, 'total' => 180],
            ['level' => 5, 'total' => 350],
            ['level' => 6, 'total' => 620],
            ['level' => 7, 'total' => 980],
            ['level' => 8, 'total' => 1450],
            ['level' => 9, 'total' => 2050],
            ['level' => 10, 'total' => 2800],
        ];
        $level = 0;
        foreach ($levels as $lv) {
            if ($totalPoints >= $lv['total']) $level = $lv['level'];
        }
        return $level;
    }

    /**
     * 获取等级阶段
     */
    private function getLevelStage($level) {
        if ($level >= 1 && $level <= 3) return 'basic';
        if ($level >= 4 && $level <= 7) return 'gold';
        if ($level >= 8 && $level <= 9) return 'top';
        if ($level >= 10) return 'lv10';
        return 'basic';
    }

    public function getList($novelId, $limit = 200) {
        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.nickname, c.avatar, c.content, c.tags, c.likes, c.parent_id, c.created_at, c.uuid, IFNULL(u.tags, '') as user_tags, u.total_points, u.score 
             FROM comments c 
             LEFT JOIN users u ON c.uuid = u.uuid
             WHERE c.novel_id = ? 
             ORDER BY c.created_at DESC LIMIT ?"
        );
        $stmt->execute([$novelId, (int)$limit]);
        $comments = $stmt->fetchAll();

        require_once __DIR__ . '/../includes/points_system.php';
        $formatted = [];
        foreach ($comments as $c) {
            $avatar = $c['avatar'];
            if ($avatar && strpos($avatar, 'http') !== 0) {
                $avatar = '/avatars/' . $avatar;
            }
            $totalPts = (int)($c['total_points'] ?? $c['score'] ?? 0);
            $levelInfo = calcLevelByPoints($totalPts);
            $formatted[] = [
                'id' => (int)$c['id'],
                'nickname' => $c['nickname'],
                'avatar' => $avatar,
                'uuid' => $c['uuid'],
                'content' => $c['content'],
                'tags' => $c['tags'] ? json_decode($c['tags'], true) : [],
                'user_tags' => $c['user_tags'] ?? '',
                'time' => $c['created_at'],
                'likes' => (int)$c['likes'],
                'parent_id' => (int)$c['parent_id'],
                'level' => $levelInfo['level'],
                'level_stage' => getLevelStage($levelInfo['level'])
            ];
        }
        return $formatted;
    }

    /**
     * 添加评论
     */
    public function add($novelId, $data) {
        $nickname = htmlspecialchars(mb_substr(trim($data['nickname'] ?? '匿名读者'), 0, 20));
        $content = htmlspecialchars(mb_substr(trim($data['content'] ?? ''), 0, 500));
        $avatar = isset($data['avatar']) ? basename($data['avatar']) : '';
        $uuid = $data['uuid'] ?? '';
        $email = $data['email'] ?? '';
        $parentId = (int)($data['parent_id'] ?? 0);
        // 已登录用户：以账号的真实 uuid 为准，避免评论落到游客 uuid 上，
        // 否则个人主页跳转会因 uuid 与账号不一致而出错。
        if (!empty($data['token'])) {
            $usr = $this->pdo->prepare('SELECT uuid, nickname, avatar, bind_email FROM users WHERE token = ?');
            $usr->execute([$data['token']]);
            if ($u = $usr->fetch()) {
                $uuid = $u['uuid'];
                if (empty($email) && !empty($u['bind_email'])) $email = $u['bind_email'];
            }
        }

        if (!$content) {
            jsonResponse(['code' => 1, 'msg' => '内容不能为空']);
        }

        // 解析标签
        $parsedTags = $this->parseTags($content);

        $tagsJson = json_encode($parsedTags, JSON_UNESCAPED_UNICODE);
        $stmt = $this->pdo->prepare(
            "INSERT INTO comments (nickname, avatar, content, tags, parent_id, uuid, email, novel_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$nickname, $avatar, $content, $tagsJson, $parentId, $uuid, $email, $novelId]);
        $newId = (int)$this->pdo->lastInsertId();

        // 更新用户评论数
        if ($uuid) {
            $this->pdo->prepare('UPDATE user_prefs SET comment_count = COALESCE(comment_count, 0) + 1 WHERE uuid = ?')->execute([$uuid]);
        }

        writeLog($this->pdo, 'add_comment', "[{$novelId}] {$nickname}: " . mb_substr($content, 0, 50));
        // 书评积分：登录用户发布≥10字有效书评 +30（每日上限3次，含防刷，逻辑见 points_system）
        if ($uuid) {
            require_once __DIR__ . '/../includes/points_system.php';
            try { processReviewPoints($this->pdo, $uuid, $content); } catch (Exception $e) {}
        }

        // 保存用户标签历史
        if ($uuid && !empty($parsedTags)) {
            $this->saveUserTags($uuid, $parsedTags);
        }

        // 回复邮件通知
        if ($parentId > 0) {
            $this->notifyReply($parentId, $nickname, $content);
        }

        return $newId;
    }

    /**
     * 点赞
     */
    public function like($id, $novelId) {
        $id = (int)$id;
        if ($id <= 0) jsonResponse(['code' => 1, 'msg' => '参数错误']);

        $stmt = $this->pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ? AND novel_id = ?");
        $stmt->execute([$id, $novelId]);
        
        $stmt = $this->pdo->prepare("SELECT likes FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        $likes = (int)$stmt->fetchColumn();

        // 点赞邮件通知
        $this->notifyLike($id);
        // 注意：评论点赞与小说首页点赞相互独立，此处不再写入 stats.total_likes，
        // 小说首页点赞仅由 like_toggle 维护，避免互相影响。
        jsonResponse(['code' => 0, 'likes' => $likes]);
    }

    /**
     * 解析标签（与前端 extractTag 一致）
     */
    private function parseTags($content) {
        $tags = [];
        if (preg_match('/^\[!!([^\]]+)\]\s*/u', $content, $m)) {
            $tags[] = ['tag' => trim($m[1]), 'level' => 2];
        } elseif (preg_match('/^\[!([^\]]+)\]\s*/u', $content, $m)) {
            $tags[] = ['tag' => trim($m[1]), 'level' => 1];
        }
        return $tags;
    }

    /**
     * 保存用户标签历史到 user_prefs
     */
    private function saveUserTags($uuid, $parsedTags) {
        $stmt = $this->pdo->prepare('SELECT id, tags FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $up = $stmt->fetch();
        $userTags = $up && $up['tags'] ? json_decode($up['tags'], true) : [];

        foreach ($parsedTags as $t) {
            $found = false;
            foreach ($userTags as &$ut) {
                if ($ut['tag'] === $t['tag']) {
                    $ut['level'] = max($ut['level'], $t['level']);
                    $ut['count'] = ($ut['count'] ?? 0) + 1;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $userTags[] = ['tag' => $t['tag'], 'level' => $t['level'], 'count' => 1];
            }
        }

        $json = json_encode($userTags, JSON_UNESCAPED_UNICODE);
        if ($up) {
            $this->pdo->prepare('UPDATE user_prefs SET tags = ? WHERE uuid = ?')->execute([$json, $uuid]);
        } else {
            $this->pdo->prepare('INSERT INTO user_prefs (uuid, tags) VALUES (?, ?)')->execute([$uuid, $json]);
        }
    }

    /**
     * 获取用户标签历史
     */
    public function getUserTags($uuid) {
        if (!$uuid) return [];
        $stmt = $this->pdo->prepare('SELECT tags FROM user_prefs WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        return $row && $row['tags'] ? json_decode($row['tags'], true) : [];
    }

    /**
     * 回复邮件通知
     */
    private function notifyReply($parentId, $replier, $content) {
        try {
            $stmt = $this->pdo->prepare("SELECT c.nickname, c.email, c.uuid, u.bind_email FROM comments c LEFT JOIN users u ON c.uuid = u.uuid WHERE c.id = ?");
            $stmt->execute([$parentId]);
            $parent = $stmt->fetch();
            if (!$parent) return;
            $to = $parent['bind_email'] ?: $parent['email'];
            if (!$to) return;
            require_once __DIR__ . '/../mail.php';
            $preview = htmlspecialchars(mb_substr($content, 0, 80));
            $body = buildMailTemplate(
                "💬 你的评论收到了回复！",
                "<p style=\"margin:0 0 12px;\"><strong>{$replier}</strong> 回复了你在《示例小说》中的评论：</p>"
                . "<div style=\"background:#f9f6f2;border-left:3px solid #8b6f5c;padding:12px 16px;border-radius:6px;margin:12px 0;color:#555;font-size:14px;\">“" . $preview . "…”</div>",
                "https://example.com/001/",
                "📖 去看看回复"
            );
            sendMail($to, "💬 有人回复了你的评论 - 示例小说", $body);
        } catch (Exception $e) {
            // 邮件失败不影响
        }
    }

    /**
     * 点赞邮件通知
     */
    private function notifyLike($commentId) {
        try {
            $stmt = $this->pdo->prepare("SELECT c.nickname, c.email, c.uuid, u.bind_email FROM comments c LEFT JOIN users u ON c.uuid = u.uuid WHERE c.id = ?");
            $stmt->execute([$commentId]);
            $c = $stmt->fetch();
            if (!$c) return;
            $to = $c['bind_email'] ?: $c['email'];
            if (!$to) return;
            require_once __DIR__ . '/../mail.php';
            $body = buildMailTemplate(
                '❤️ 你的评论收到了点赞！',
                "<p style=\"margin:0 0 12px;\">读者赞了 <strong>{$c['nickname']}</strong> 在《示例小说》中的评论～</p>"
                . "<p style=\"color:#999;font-size:14px;\">快去看看是谁这么有眼光吧 😊</p>",
                "https://example.com/001/",
                '📖 去看看'
            );
            sendMail($to, '❤️ 你的评论被点赞了 - 示例小说', $body);
        } catch (Exception $e) {
            // 邮件失败不影响
        }
    }
}
