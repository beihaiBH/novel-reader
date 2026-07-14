<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$text = trim($_POST['text'] ?? $_GET['text'] ?? '');
if (!$text) {
    die(json_encode(['code' => 1, 'msg' => 'text is empty']));
}

// 限制长度
$text = mb_substr($text, 0, 200);

// 缓存目录
$cacheDir = __DIR__ . '/tts_cache';
if (!is_dir($cacheDir)) mkdir($cacheDir, 755, true);

// 用文本md5做缓存文件名
$hash = md5($text);
$cacheFile = $cacheDir . '/' . $hash . '.mp3';

// 如果缓存存在直接返回
if (file_exists($cacheFile) && filesize($cacheFile) > 1000) {
    header('Content-Type: audio/mpeg');
    readfile($cacheFile);
    exit;
}

// 用 edge-tts 生成语音（微软高音质中文）
$safeText = escapeshellarg($text);
$cmd = "python3 -m edge_tts --voice zh-CN-XiaoxiaoNeural --text $safeText --write-media " . escapeshellarg($cacheFile) . " 2>&1";
exec($cmd, $output, $ret);

if ($ret === 0 && file_exists($cacheFile) && filesize($cacheFile) > 1000) {
    header('Content-Type: audio/mpeg');
    readfile($cacheFile);
    exit;
}

// edge-tts 失败，降级到 gTTS
$cmd2 = "python3 -m gtts.cli --lang zh-CN --output " . escapeshellarg($cacheFile) . " " . $safeText . " 2>&1";
exec($cmd2, $output2, $ret2);

if ($ret2 === 0 && file_exists($cacheFile) && filesize($cacheFile) > 1000) {
    header('Content-Type: audio/mpeg');
    readfile($cacheFile);
    exit;
}

// 都失败了
http_response_code(500);
echo json_encode(['code' => -1, 'msg' => 'TTS failed', 'detail' => implode("\n", $output)]);
