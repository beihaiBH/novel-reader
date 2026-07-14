<?php
/**
 * 数据库配置
 * ---------------------------------------------------------------
 * 首次部署：把下面的占位符改成你自己的数据库信息即可。
 * 也可以直接运行根目录下的 install.php 图形化安装向导自动生成。
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'novel_reader');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['code' => -1, 'msg' => '数据库连接失败']);
            exit;
        }
    }
    return $pdo;
}
