<?php
/**
 * Comment Controller - 评论路由
 * 处理 api.php 转发的评论相关请求
 */
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Stats.php';
require_once __DIR__ . '/../includes/helpers.php';

class CommentController {
    
    /**
     * GET - 获取评论列表
     */
    public static function list() {
        $comment = new Comment();
        $stats = new Stats();
        $novelId = getNovelId();
        
        $comments = $comment->getList($novelId);
        $statData = $stats->get($novelId);
        
        jsonResponse([
            'code' => 0,
            'data' => [
                'comments' => $comments,
                'views' => $statData['views'],
                'total_likes' => $statData['total_likes'],
            ],
            'is_admin' => isAdminUser()
        ]);
    }

    /**
     * POST - 添加评论
     */
    public static function add() {
        $comment = new Comment();
        $novelId = getNovelId();
        
        $newId = $comment->add($novelId, $_POST);
        jsonResponse(['code' => 0, 'msg' => '评论成功~', 'id' => $newId]);
    }

    /**
     * POST - 点赞
     */
    public static function like() {
        $comment = new Comment();
        $novelId = getNovelId();
        $comment->like($_POST['id'] ?? 0, $novelId);
    }

    /**
     * GET - 获取用户标签历史
     */
    public static function getUserTags() {
        $comment = new Comment();
        $uuid = getClientUUID();
        $tags = $comment->getUserTags($uuid);
        jsonResponse(['code' => 0, 'tags' => $tags]);
    }
}
