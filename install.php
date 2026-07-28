<?php
/**
 * 图形化安装向导  install.php
 * ---------------------------------------------------------------
 * 打开 http://你的域名/install.php 按提示填写数据库信息与管理员账号，
 * 程序会自动写入 config/database.php、导入数据库结构并创建管理员。
 * 安装完成后请务必删除本文件（install.php）。
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
$root = __DIR__;
$lockFile = $root . '/config/.installed';
$done = false;
$err = '';
$msg = '';

if (file_exists($lockFile)) {
    $err = '系统已安装。如需重新安装，请删除 config/.installed 文件。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? 'novel_reader');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    if (!$dbUser || !$adminUser || !$adminPass) {
        $err = '数据库用户名、管理员用户名与密码均不能为空。';
    } else {
        try {
            // 1. 连接数据库
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `$dbName`");

            // 2. 导入表结构
            $sql = file_get_contents($root . '/install.sql');
            // 去掉建库/USE 语句（此处已手动选库）
            $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b.*$/mi', '', $sql);
            // 生成管理员密码哈希并替换占位符
            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $sql = str_replace('__ADMIN_HASH__', $hash, $sql);
            // 用自定义管理员用户名替换默认 admin 记录
            if ($adminUser !== 'admin') {
                $sql = preg_replace("/\('admin',/", "('" . addslashes($adminUser) . "',", $sql, 1);
            }
            $pdo->exec($sql);

            // 3. 写入配置文件
            $cfg = "<?php\n"
                . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
                . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
                . "define('DB_PASS', " . var_export($dbPass, true) . ");\n"
                . "define('DB_CHARSET', 'utf8mb4');\n\n"
                . "function getDB() {\n"
                . "    static \$pdo = null;\n"
                . "    if (\$pdo === null) {\n"
                . "        try {\n"
                . "            \$pdo = new PDO(\n"
                . "                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET,\n"
                . "                DB_USER, DB_PASS,\n"
                . "                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]\n"
                . "            );\n"
                . "        } catch (PDOException \$e) {\n"
                . "            http_response_code(500); echo json_encode(['code' => -1, 'msg' => '数据库连接失败']); exit;\n"
                . "        }\n"
                . "    }\n"
                . "    return \$pdo;\n"
                . "}\n";
            if (!is_dir($root . '/config')) mkdir($root . '/config', 0755, true);
            file_put_contents($root . '/config/database.php', $cfg);
            @file_put_contents($lockFile, date('c'));

            $done = true;
            $msg = "安装成功！管理员账号：{$adminUser}。请立即删除 install.php 以确保安全。";
        } catch (Throwable $e) {
            $err = '安装失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>安装向导 · 小说阅读平台</title>
<style>
* { box-sizing: border-box; }
body { font-family: -apple-system, "PingFang SC", "Microsoft YaHei", sans-serif; background: #f5f0eb; color: #3a3a3a; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.box { background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,.1); padding: 32px; width: 92%; max-width: 460px; }
h1 { font-size: 22px; margin: 0 0 4px; }
.sub { color: #999; font-size: 13px; margin-bottom: 20px; }
label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; }
input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
.row { display: flex; gap: 12px; }
.row > div { flex: 1; }
button { width: 100%; margin-top: 22px; padding: 12px; border: none; border-radius: 24px; background: linear-gradient(135deg,#c4a882,#a08060); color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; }
.tip { font-size: 12px; color: #a08a6e; margin-top: 6px; }
.alert { padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.alert.err { background: #fdecea; color: #c0392b; }
.alert.ok { background: #eafaf1; color: #27ae60; }
.divider { border: none; border-top: 1px dashed #eee; margin: 20px 0; }
</style>
</head>
<body>
<div class="box">
  <h1>📚 安装向导</h1>
  <div class="sub">小说在线阅读平台 · v3.0</div>
  <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($done): ?>
    <div class="alert ok"><?= htmlspecialchars($msg) ?></div>
    <p style="font-size:14px;line-height:1.7">下一步：<br>1. <strong>删除 install.php</strong>（重要，避免被重复安装）<br>2. 访问首页 <code>/index.php</code> 开始使用<br>3. 用管理员账号登录后修改默认密码</p>
  <?php else: ?>
  <form method="post">
    <div class="alert" style="background:#f7f3ee;color:#8a7860">请先创建好数据库账号（可用 root），程序会自动建库建表。</div>
    <label>数据库主机</label>
    <input name="db_host" value="localhost" required>
    <label>数据库名</label>
    <input name="db_name" value="novel_reader" required>
    <div class="row">
      <div><label>数据库用户名</label><input name="db_user" required></div>
      <div><label>数据库密码</label><input name="db_pass" type="password"></div>
    </div>
    <hr class="divider">
    <div class="row">
      <div><label>管理员用户名</label><input name="admin_user" value="admin" required></div>
      <div><label>管理员密码</label><input name="admin_pass" type="password" required></div>
    </div>
    <div class="tip">管理员用户名将作为站长身份，登录后拥有数据看板等权限。</div>
    <button type="submit">开始安装</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
