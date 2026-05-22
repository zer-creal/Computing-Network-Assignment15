# Computing-Network-Assignment15

这是一个简单的用户注册系统，包含邮箱验证功能。

## 功能特性

1. 用户注册（昵称、口令、邮箱）
2. 注册后显示激活链接
3. 点击链接激活账户
4. 1小时内不激活则昵称自动释放

## 目录结构

```
.
├── app.py            # Flask 主应用
├── database.sql      # 数据库初始化脚本(PHP版)
├── config.php        # 数据库配置(PHP版)
├── register.html     # 注册页面(PHP版)
├── register.php      # 注册处理(PHP版)
├── verify.php        # 激活验证(PHP版)
└── index.php         # 入口文件(PHP版)
```

## 使用方法（Python Flask版）

```bash
pip install flask
python app.py
```

访问 http://127.0.0.1:5000

## 使用方法（PHP版）

1. 创建数据库：执行 database.sql
2. 修改 config.php 数据库配置
3. 部署到Web服务器
4. 访问 http://127.0.0.1

## 激活链接格式

```
http://127.0.0.1:5000/verify?user=用户名&code=验证码
```

验证码使用 `md5(用户名 + 时间戳)` 生成。