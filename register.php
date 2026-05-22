<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ? AND verified = FALSE AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$username]);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND (verified = TRUE OR created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>注册失败</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message error" style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;">昵称已被占用，请选择其他昵称。</div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></body></html>';
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verificationCode = md5($username . time());

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, verification_code) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email, $verificationCode]);

        $verificationLink = "http://127.0.0.1/verify.php?user=" . urlencode($username) . "&code=" . $verificationCode;

        $to = $email;
        $subject = "账户激活邮件";
        $message = "您好 " . $username . "，\n\n";
        $message .= "感谢您注册！请点击以下链接激活您的账户：\n";
        $message .= $verificationLink . "\n\n";
        $message .= "此链接在1小时内有效。\n";
        $headers = "From: noreply@example.com\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>注册成功</title></head><body style="font-family:Arial,max-width:500px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message success" style="background-color:#d4edda;color:#155724;padding:15px;border-radius:5px;"><h3>注册成功！</h3><p>激活邮件已发送至您的邮箱：<strong>' . htmlspecialchars($email) . '</strong></p><p style="margin-top:20px;word-break:break-all;text-align:left;background:#f5f5f5;padding:10px;border-radius:3px;">激活链接：<a href="' . $verificationLink . '">' . $verificationLink . '</a></p><p style="font-size:12px;color:#666;margin-top:10px;">（注：实际项目中会通过SMTP发送邮件，此处仅展示链接）</p></div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></body></html>';

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>注册失败</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message error" style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;">注册失败：' . $e->getMessage() . '</div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></body></html>';
    }
}
?>
