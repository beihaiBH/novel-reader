<?php
/**
 * SMTP 邮件配置（用于评论回复/点赞通知、邮箱验证码）
 * ---------------------------------------------------------------
 * 按你使用的邮箱服务商填写下面的占位符。
 * 若不需要邮件功能，保持占位符即可，发信失败不会影响站点其它功能。
 */
define('MAIL_HOST', 'smtp.example.com');   // SMTP 服务器
define('MAIL_PORT', 465);                  // SSL 端口
define('MAIL_USER', 'your-email@example.com');
define('MAIL_PASS', 'your_smtp_authorization_code'); // 邮箱授权码/密码
define('MAIL_FROM', 'your-email@example.com');
define('MAIL_FROM_NAME', '小说站');

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
