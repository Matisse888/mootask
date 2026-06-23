# MooTask 部署指南

## 📋 目录
- [环境要求](#环境要求)
- [快速部署](#快速部署)
- [详细部署步骤](#详细部署步骤)
- [配置说明](#配置说明)
- [数据库设置](#数据库设置)
- [常见问题](#常见问题)

---

## 🖥️ 环境要求

### 服务器要求
- **操作系统**: Ubuntu 20.04+ / CentOS 7+ / Debian 10+
- **PHP**: 8.0+ (推荐 8.1)
- **数据库**: MySQL 8.0+ / MariaDB 10.5+
- **Web服务器**: Nginx 1.18+ 或 Apache 2.4+
- **Redis**: 6.0+ (用于缓存和会话)
- **Composer**: 2.0+
- **Node.js**: 16.0+ (用于构建前端)
- **NPM**: 8.0+

### PHP 扩展要求
```
pdo_mysql
mbstring
exif
pcntl
bcmath
gd
opcache
zip
curl
xml
json
```

---

## 🚀 快速部署

### 方式一：使用 Docker 部署（推荐）

```bash
# 1. 克隆项目
git clone https://your-repo/mootask.git
cd mootask

# 2. 复制环境配置
cp .env.example .env

# 3. 编辑 .env 文件配置数据库和Redis

# 4. 启动容器
docker-compose up -d

# 5. 安装依赖
docker-compose exec app composer install

# 6. 生成密钥
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret

# 7. 运行数据库迁移
docker-compose exec app php artisan migrate

# 8. 构建前端
docker-compose exec app npm install
docker-compose exec app npm run build
```

访问 `http://your-domain.com`

### 方式二：手动部署

```bash
# 1. 克隆项目
git clone https://your-repo/mootask.git
cd mootask

# 2. 安装后端依赖
composer install

# 3. 安装前端依赖
npm install

# 4. 复制并配置环境文件
cp .env.example .env

# 5. 生成密钥
php artisan key:generate
php artisan jwt:secret

# 6. 配置数据库连接
# 编辑 .env 文件，设置 DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 7. 运行数据库迁移
php artisan migrate

# 8. 构建前端资源
npm run build

# 9. 设置权限
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 10. 启动服务
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 📝 详细部署步骤

### 第一步：服务器准备

#### Ubuntu 20.04
```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装基础软件
sudo apt install -y curl wget git unzip zip

# 安装 Nginx
sudo apt install -y nginx

# 安装 PHP 8.1 及扩展
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath php8.1-redis

# 安装 MySQL 8.0
sudo apt install -y mysql-server

# 安装 Redis
sudo apt install -y redis-server

# 安装 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装 Node.js 16
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt install -y nodejs

# 安装 NPM
sudo apt install -y npm
```

#### CentOS 7/8
```bash
# 更新系统
sudo yum update -y

# 安装 EPEL 和 Remi 仓库
sudo yum install -y epel-release yum-utils
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm

# 安装 PHP 8.1
sudo yum-config-manager --enable remi-php81
sudo yum install -y php php-fpm php-mysql php-mbstring \
    php-xml php-curl php-zip php-gd php-bcmath php-redis

# 安装 Nginx
sudo yum install -y nginx

# 安装 MySQL 8.0
sudo yum install -y mysql-server
sudo systemctl start mysqld
sudo systemctl enable mysqld

# 安装 Redis
sudo yum install -y redis
sudo systemctl start redis
sudo systemctl enable redis

# 安装 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装 Node.js 16
curl -fsSL https://rpm.nodesource.com/setup_16.x | sudo bash -
sudo yum install -y nodejs
```

### 第二步：数据库配置

#### MySQL 配置
```bash
# 登录 MySQL
sudo mysql -u root -p

# 创建数据库和用户
CREATE DATABASE mootask CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mootask'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON mootask.* TO 'mootask'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 第三步：Nginx 配置

```bash
# 创建 Nginx 配置文件
sudo nano /etc/nginx/sites-available/mootask
```

添加以下内容：

```nginx
server {
    listen 80;
    server_name your-domain.com;  # 替换为您的域名或IP

    root /var/www/mootask/public;
    index index.php index.html;

    # Laravel 前端路由
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM 配置
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip 压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml;
}
```

```bash
# 启用站点配置
sudo ln -s /etc/nginx/sites-available/mootask /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl restart nginx
```

### 第四步：部署应用

```bash
# 创建目录
sudo mkdir -p /var/www/mootask
cd /var/www/mootask

# 克隆项目（替换为您的仓库地址）
sudo git clone https://your-repo/mootask.git .

# 设置权限
sudo chown -R www-data:www-data /var/www/mootask
sudo chmod -R 755 /var/www/mootask

# 进入目录
cd /var/www/mootask

# 安装 PHP 依赖
sudo -u www-data composer install --no-dev --optimize-autoloader

# 安装 Node 依赖并构建前端
sudo -u www-data npm install
sudo -u www-data npm run build

# 复制并编辑环境配置
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env
```

### 第五步：环境配置

编辑 `.env` 文件，配置以下内容：

```env
APP_NAME=MooTask
APP_ENV=production
APP_KEY=  # 留空，会自动生成
APP_DEBUG=false
APP_URL=http://your-domain.com  # 替换为您的URL

LOG_CHANNEL=daily
LOG_LEVEL=info

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mootask
DB_USERNAME=mootask
DB_PASSWORD=your_strong_password

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# JWT 配置（自动生成）
JWT_SECRET=
JWT_TTL=1440
JWT_REFRESH_TTL=20160

# 会话和缓存
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# 文件上传
FILESYSTEM_DRIVER=local
UPLOAD_MAX_SIZE=104857600
```

### 第六步：生成密钥和数据库迁移

```bash
# 生成应用密钥
sudo -u www-data php artisan key:generate

# 生成 JWT 密钥
sudo -u www-data php artisan jwt:secret

# 运行数据库迁移
sudo -u www-data php artisan migrate

# （可选）填充初始数据
sudo -u www-data php artisan db:seed
```

### 第七步：设置定时任务和队列

```bash
# 编辑 crontab
sudo crontab -e
```

添加以下行：

```cron
* * * * * cd /var/www/mootask && php artisan schedule:run >> /dev/null 2>&1
```

```bash
# 配置 Supervisor（用于队列 worker）
sudo apt install -y supervisor

# 创建 Laravel Queue Worker 配置
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

添加以下内容：

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mootask/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/mootask/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# 启动 Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## ⚙️ 配置说明

### 环境变量详解

| 变量名 | 说明 | 示例值 |
|--------|------|--------|
| APP_NAME | 应用名称 | MooTask |
| APP_ENV | 运行环境 | production |
| APP_DEBUG | 调试模式 | false |
| APP_URL | 应用URL | https://mootask.example.com |
| DB_* | 数据库配置 | 根据实际情况填写 |
| REDIS_* | Redis配置 | 根据实际情况填写 |
| JWT_SECRET | JWT密钥 | 自动生成 |
| SESSION_DRIVER | 会话驱动 | redis |
| CACHE_DRIVER | 缓存驱动 | redis |
| QUEUE_CONNECTION | 队列驱动 | redis |

### 生产环境优化配置

#### PHP-FPM 配置
```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

调整以下参数：
```ini
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

#### OPcache 配置
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
```

---

## 🗄️ 数据库设置

### 数据库迁移

```bash
# 运行所有迁移
php artisan migrate

# 强制运行（生产环境需谨慎）
php artisan migrate --force

# 回滚最近的迁移
php artisan migrate:rollback

# 查看迁移状态
php artisan migrate:status
```

### 数据填充（可选）

项目包含以下 Seeder：

- `DatabaseSeeder` - 主 Seeder
- `DepartmentSeeder` - 部门数据
- `UserSeeder` - 用户数据

```bash
# 运行所有 Seeder
php artisan db:seed

# 运行特定 Seeder
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=UserSeeder
```

### 创建管理员用户

```bash
# 使用 Tinker 创建管理员
php artisan tinker

# 在 Tinker 中执行
$user = new \App\Models\User();
$user->username = 'admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('your_password');
$user->name = 'Administrator';
$user->save();
```

---

## 🔒 安全配置

### 1. 设置目录权限

```bash
# 设置目录权限
sudo chown -R www-data:www-data /var/www/mootask
sudo chmod -R 755 /var/www/mootask
sudo chmod -R 775 /var/www/mootask/storage
sudo chmod -R 775 /var/www/mootask/bootstrap/cache

# 设置日志目录权限
sudo chown -R www-data:www-data /var/www/mootask/storage/logs
```

### 2. 配置 SSL（推荐）

```bash
# 安装 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取 SSL 证书
sudo certbot --nginx -d your-domain.com

# 自动续期设置
sudo certbot renew --dry-run
```

### 3. 配置防火墙

```bash
# 启用防火墙
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable

# 查看状态
sudo ufw status
```

---

## 🔧 常见问题

### Q1: 访问页面显示空白
**解决方案**：
```bash
# 清除缓存
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 重新构建前端
npm run build

# 检查 .env 文件中的 APP_KEY 是否正确
php artisan key:generate
```

### Q2: 数据库连接失败
**解决方案**：
1. 检查 `.env` 中的数据库配置
2. 确认 MySQL 服务正在运行：`sudo systemctl status mysql`
3. 验证数据库用户权限
4. 检查防火墙是否允许 3306 端口

### Q3: 前端资源404
**解决方案**：
```bash
# 重新构建前端
npm run build

# 确保 storage 目录权限正确
chmod -R 775 storage
chown -R www-data:www-data storage
```

### Q4: 权限被拒绝错误
**解决方案**：
```bash
# 设置正确的用户和权限
sudo chown -R www-data:www-data /var/www/mootask/storage
sudo chown -R www-data:www-data /var/www/mootask/bootstrap/cache
chmod -R 775 /var/www/mootask/storage
chmod -R 775 /var/www/mootask/bootstrap/cache
```

### Q5: Redis 连接失败
**解决方案**：
1. 确认 Redis 已安装并运行：`sudo systemctl status redis`
2. 检查 `.env` 中的 Redis 配置
3. 配置 Redis 密码（如已启用认证）

### Q6: JWT 认证失败
**解决方案**：
```bash
# 重新生成 JWT 密钥
php artisan jwt:secret --force

# 清除配置缓存
php artisan config:clear
php artisan cache:clear
```

### Q7: 队列任务不执行
**解决方案**：
1. 确认 Supervisor 已配置并运行
2. 检查队列 worker 状态：`sudo supervisorctl status`
3. 查看日志：`tail -f storage/logs/worker.log`

### Q8: 502 Bad Gateway
**解决方案**：
1. 检查 PHP-FPM 服务状态
2. 确认 Nginx 配置中的 PHP-FPM socket 路径正确
3. 增加 PHP-FPM 进程数
4. 检查错误日志：`tail -f /var/log/nginx/error.log`

---

## 📊 部署检查清单

部署完成后，使用以下检查清单验证：

- [ ] 访问首页正常显示
- [ ] 用户注册/登录功能正常
- [ ] API 接口可访问（/api/health）
- [ ] 数据库连接正常
- [ ] Redis 连接正常
- [ ] 前端资源加载正常
- [ ] 文件上传功能正常
- [ ] 邮件发送功能正常（如果配置了）
- [ ] SSL 证书已配置（生产环境）
- [ ] 定时任务已配置
- [ ] 队列 Worker 已启动
- [ ] 日志文件正常写入

---

## 🆘 获取帮助

如果遇到其他问题，请：

1. 查看应用日志：`tail -f storage/logs/laravel.log`
2. 查看 Nginx 日志：`tail -f /var/log/nginx/error.log`
3. 查看 PHP-FPM 日志：`tail -f /var/log/php8.1-fpm.log`
4. 检查队列日志：`tail -f storage/logs/worker.log`

---

## 📄 许可证

MIT License
