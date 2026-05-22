import sqlite3
import hashlib
import time
import threading
from datetime import datetime, timedelta
from flask import Flask, request, redirect

app = Flask(__name__)
DATABASE = 'users.db'


def get_db():
    db = sqlite3.connect(DATABASE)
    db.row_factory = sqlite3.Row
    return db


def init_db():
    db = get_db()
    db.execute('''CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT NOT NULL,
        verification_code TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified INTEGER DEFAULT 0
    )''')
    db.commit()
    db.close()


def cleanup_unverified():
    """定期清理超过1小时未激活的用户，释放昵称"""
    while True:
        time.sleep(60)  # 每60秒检查一次
        try:
            db = get_db()
            db.execute("DELETE FROM users WHERE verified = 0 AND created_at < datetime('now', '-1 hour')")
            db.commit()
            db.close()
        except:
            pass


init_db()
# 启动后台清理线程
threading.Thread(target=cleanup_unverified, daemon=True).start()


@app.route('/')
def index():
    return redirect('/register.html')


@app.route('/register.html')
def register_page():
    return '''
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>用户注册</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        h2 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h2>用户注册</h2>
    <form action="/register" method="post">
        <div class="form-group">
            <label for="username">昵称：</label>
            <input type="text" id="username" name="username" placeholder="请输入昵称" required>
        </div>
        <div class="form-group">
            <label for="password">口令：</label>
            <input type="password" id="password" name="password" placeholder="请设置密码" required>
        </div>
        <div class="form-group">
            <label for="email">邮箱：</label>
            <input type="email" id="email" name="email" placeholder="请输入邮箱地址" required>
        </div>
        <button type="submit">注册</button>
    </form>
</body>
</html>'''


@app.route('/register', methods=['POST'])
def register():
    username = request.form.get('username', '').strip()
    password = request.form.get('password', '')
    email = request.form.get('email', '').strip()

    db = get_db()
    try:
        # 删除该昵称下超过1小时未激活的旧记录
        db.execute("DELETE FROM users WHERE username = ? AND verified = 0 AND created_at < datetime('now', '-1 hour')", [username])

        # 检查昵称是否被占用
        row = db.execute("SELECT id FROM users WHERE username = ?", [username]).fetchone()
        if row:
            return '<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;">昵称已被占用，请选择其他昵称。</div><a href="/register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></div>'

        hashed_pw = hashlib.sha256(password.encode()).hexdigest()
        code = hashlib.md5((username + str(time.time())).encode()).hexdigest()

        db.execute("INSERT INTO users (username, password, email, verification_code) VALUES (?, ?, ?, ?)",
                   [username, hashed_pw, email, code])
        db.commit()

        link = f"http://127.0.0.1:5000/verify?user={username}&code={code}"

        return f'''
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>注册成功</title></head>
<body style="font-family:Arial;max-width:500px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;">
    <div style="background-color:#d4edda;color:#155724;padding:15px;border-radius:5px;">
        <h3>注册成功！</h3>
        <p>激活邮件已发送至：<strong>{email}</strong></p>
        <p style="margin-top:20px;word-break:break-all;text-align:left;background:#f5f5f5;padding:10px;border-radius:3px;">
            激活链接：<a href="{link}">{link}</a>
        </p>
        <p style="font-size:12px;color:#666;">（点击链接即可激活，1小时内有效）</p>
    </div>
    <a href="/register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a>
</body>
</html>'''
    except Exception as e:
        return f'<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;text-align:center;color:red;">错误：{e}</div>'
    finally:
        db.close()


@app.route('/verify')
def verify():
    username = request.args.get('user', '')
    code = request.args.get('code', '')

    db = get_db()
    try:
        user = db.execute("SELECT * FROM users WHERE username = ? AND verification_code = ?",
                          [username, code]).fetchone()

        if not user:
            return '<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div style="background-color:#f8d7da;color:#721c24;padding:15px;border-radius:5px;"><h3>无效链接</h3><p>激活链接无效或已失效。</p></div><a href="/register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回注册</a></div>'

        # 用 SQLite 自带的 datetime 判断是否超过1小时，避免时区问题
        expired = db.execute(
            "SELECT id FROM users WHERE id = ? AND created_at < datetime('now', '-1 hour')",
            [user['id']]
        ).fetchone()
        if expired:
            db.execute("DELETE FROM users WHERE id = ?", [user['id']])
            db.commit()
            return '<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div style="background-color:#f8d7da;color:#721c24;padding:15px;border-radius:5px;"><h3>链接已过期</h3><p>激活链接已超过1小时有效期，昵称已释放。请重新注册。</p></div><a href="/register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">重新注册</a></div>'

        if user['verified']:
            return '<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div style="background-color:#d1ecf1;color:#0c5460;padding:15px;border-radius:5px;"><h3>账户已激活</h3><p>该账户已经激活过了。</p></div><a href="/register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回首页</a></div>'

        db.execute("UPDATE users SET verified = 1 WHERE id = ?", [user['id']])
        db.commit()
        return '<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;border:1px solid #ddd;border-radius:5px;text-align:center;"><div style="background-color:#d4edda;color:#155724;padding:15px;border-radius:5px;"><h3>激活成功！</h3><p>您的账户已成功激活。</p></div><a href="/register.html" style="display:inline-block;margin-top:20px;text-decoration:none;color:#4CAF50;">返回首页</a></div>'
    except Exception as e:
        return f'<div style="font-family:Arial;max-width:400px;margin:50px auto;padding:20px;text-align:center;color:red;">错误：{e}</div>'
    finally:
        db.close()


if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)