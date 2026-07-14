<?php
/**
 * Stats Model - 统计数据层
 */
require_once __DIR__ . '/../config/database.php';

class Stats {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /**
     * 获取小说统计数据
     */
    public function get($novelId) {
        $stmt = $this->pdo->prepare("SELECT stat_key, stat_value FROM stats WHERE novel_id = ?");
        $stmt->execute([$novelId]);
        $rows = $stmt->fetchAll();
        
        $data = ['views' => 0, 'total_likes' => 0];
        foreach ($rows as $r) {
            $data[$r['stat_key']] = (int)$r['stat_value'];
        }
        return $data;
    }

    /**
     * 增加阅读量
     */
    public function incrementView($novelId) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO stats (stat_key, stat_value, novel_id) VALUES ('views', 1, ?) 
             ON DUPLICATE KEY UPDATE stat_value = stat_value + 1"
        );
        $stmt->execute([$novelId]);
        return $this->get($novelId);
    }
}
