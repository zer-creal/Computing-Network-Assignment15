# 用户注册激活系统

这是一个简单的PHP用户注册系统，包含邮箱验证功能。

## 功能特性

1. 用户注册（昵称、口令、邮箱）
2. 注册后显示激活链接
3. 点击链接激活账户
4. 1小时内不激活则昵称自动释放

## 目录结构

```
.
├── database.sql      # 数据库初始化脚本
├── config.php        # 数据库配置文件
├── register.html     # 注册页面
├── register.php      # 注册处理脚本
├── verify.php        # 激活验证脚本
└── index.php         # 入口文件
```

## 安装部署

### 1. 数据库配置

确保已安装MySQL，执行以下步骤：

```bash
# 使用MySQL命令行或图形化工具执行 database.sql
mysql -u root -p < database.sql
```

### 2. 修改数据库配置

编辑 `config.php`，根据实际情况修改数据库连接信息：

```php
$host = 'localhost';
$dbname = 'user_system';
$username = 'root';
$password = '';
```

### 3. 部署到Web服务器

将所有文件放到Web服务器目录（如 `htdocs`、`www` 等）。

### 4. 访问系统

在浏览器中访问 `http://127.0.0.1/` 或 `http://localhost/`。

## 使用说明

1. 填写昵称、口令和邮箱进行注册
2. 系统会显示激活链接（实际项目中会通过SMTP发送邮件）
3. 点击激活链接验证账户
4. 若超过1小时未激活，昵称会自动释放

## 激活链接格式

```
http://127.0.0.1/verify.php?user=用户名&code=验证码
```

其中验证码使用 `md5(用户名 + 时间戳)` 生成。
