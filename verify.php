<?php
require_once 'config.php';

if (isset($_GET['user']) && isset($_GET['code'])) {
    $username = urldecode($_GET['user']);
    $verificationCode = $_GET['code'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND verification_code = ?");
        $stmt->execute([$username, $verificationCode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $createdTime = strtotime($user['created_at']);
            $currentTime = time();

            if (($currentTime - $createdTime) <= 3600) {
                if (!$user['verified']) {
                    $stmt = $pdo->prepare("UPDATE users SET verified = TRUE WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>激活成功</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message success" style="background-color:#d4edda;color:#155724;padding:15px;border-radius:5px;"><h3>激活成功！</h3><p>您的账户已成功激活。</p></div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回首页</a></body></html>';
                } else {
                    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>账户已激活</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message" style="background-color:#d1ecf1;color:#0c5460;padding:15px;border-radius:5px;"><h3>账户已激活</h3><p>该账户已经激活过了。</p></div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回首页</a></body></html>';
                }
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>链接已过期</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message error" style="background-color:#f8d7da;color:#721c24;padding:15px;border-radius:5px;"><h3>链接已过期</h3><p>激活链接已超过1小时有效期，昵称已释放。请重新注册。</p></div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">重新注册</a></body></html>';
            }
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>无效链接</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message error" style="background-color:#f8d7da;color:#721c24;padding:15px;border-radius:5px;"><h3>无效链接</h3><p>激活链接无效或已失效。</p></div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></body></html>';
        }
    } catch (Exception $e) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>错误</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message error" style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;">错误：' . $e->getMessage() . '</div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></body></html>';
    }
} else {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>无效请求</title></head><body style="font-family:Arial,max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div class="message error" style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;">无效的请求参数。</div><a href="register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></body></html>';
}
?>
