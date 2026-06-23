# MooTask Ubuntu 服务器部署指南

## 概述
本文档介绍如何在 Ubuntu 20.04/22.04 LTS 服务器上部署 MooTask 项目。

## 系统要求
- Ubuntu 20.04 LTS 或 22.04 LTS
- 最少 2GB RAM
- 最少 20GB 硬盘
- 拥有 root 权限

## 一键部署（推荐）

### 1. 上传项目文件
将项目文件上传到服务器的 `/var/www/mootask` 目录

```bash
# 本地执行
scp -r ./* root@your-server-ip:/var/www/mootask/
```

### 2. SSH 登录服务器
```bash
ssh root@your-server-ip
```

### 3. 初始化服务器（仅首次）
```bash
cd /var/www/mootask
chmod +x scripts/init-server.sh
sudo bash scripts/init-server.sh
```

### 4. 一键部署
```bash
cd /var/www/mootask
chmod +x deploy.sh
sudo ./deploy.sh
```

## 手动部署

### 1. 安装 LEMP 环境

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装 PHP 8.1
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-cli \
    php8.1-mysql php8.1-mbstring php8.1-xml \
    php8.1-curl php8.1-zip php8.1-gd \
    php8.1-bcmath php8.1-redis php8.1-opcache

# 安装 MySQL
sudo apt install -y mysql-server

# 安装 Redis
sudo apt install -y redis-server

# 安装 Nginx
sudo apt install -y nginx

# 安装 Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### 2. 配置 MySQL

```bash
sudo mysql_secure_installation

sudo mysql -e "CREATE DATABASE mootask CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'mootask'@'localhost' IDENTIFIED BY 'your_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON mootask.* TO 'mootask'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 3. 部署应用

```bash
# 上传项目到 /var/www/mootask
sudo chown -R www-data:www-data /var/www/mootask
cd /var/www/mootask

# 安装依赖
sudo -u www-data composer install --no-dev --optimize-autoloader

# 复制环境配置
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

# 编辑 .env 文件
sudo -u www-data nano .env
```

`.env` 文件关键配置：
```env
APP_NAME=MooTask
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_DATABASE=mootask
DB_USERNAME=mootask
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

JWT_SECRET=your_jwt_secret
```

```bash
# 运行迁移
sudo -u www-data php artisan migrate --force

# 优化
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan storage:link

# 设置权限
sudo chown -R www-data:www-data /var/www/mootask
sudo chmod -R 755 /var/www/mootask
sudo chmod -R 775 /var/www/mootask/storage /var/www/mootask/bootstrap/cache
```

### 4. 配置 Nginx

```bash
sudo nano /etc/nginx/sites-available/mootask
```

配置内容：
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/mootask/public;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/mootask /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 5. 配置 Supervisor（队列 worker）

```bash
sudo nano /etc/supervisor/conf.d/mootask-worker.conf
```

```ini
[program:mootask-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mootask/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/mootask/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

## 配置 SSL 证书（推荐）

使用 Let's Encrypt 免费证书：

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## 配置自动备份

```bash
chmod +x scripts/backup.sh
sudo mv scripts/backup.sh /etc/cron.daily/mootask-backup
```

## 防火墙配置

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## 更新部署

修改代码后，使用更新脚本：

```bash
cd /var/www/mootask
chmod +x scripts/deploy-update.sh
sudo bash scripts/deploy-update.sh
```

或者手动更新：

```bash
cd /var/www/mootask
sudo -u www-data git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo supervisorctl restart mootask-worker:*
```

## 常用命令

```bash
# 查看应用日志
tail -f /var/www/mootask/storage/logs/laravel.log

# 查看 Nginx 错误日志
tail -f /var/log/nginx/mootask_error.log

# 查看队列日志
tail -f /var/www/mootask/storage/logs/worker.log

# 重启服务
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo supervisorctl restart mootask-worker:*

# 清理缓存
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 进入项目
cd /var/www/mootask
```

## 故障排查

### 502 Bad Gateway
- 检查 PHP-FPM 是否运行：`sudo systemctl status php8.1-fpm`
- 检查 socket 路径是否正确
- 检查 .env 中的 APP_KEY 是否设置

### 文件权限问题
```bash
sudo chown -R www-data:www-data /var/www/mootask
sudo chmod -R 775 /var/www/mootask/storage
sudo chmod -R 775 /var/www/mootask/bootstrap/cache
```

### 数据库连接失败
- 检查 MySQL 是否运行
- 验证 .env 中的数据库配置
- 测试连接：`mysql -u mootask -p`

## 性能优化

### PHP OPcache
确保 OPcache 已启用并配置优化：
```bash
php -m | grep -i opcache
```

### Redis 缓存
将 `CACHE_DRIVER=redis` 设置在 .env 中

### CDN 加速
将静态资源接入 CDN 服务

## 安全建议

1. 修改所有默认密码
2. 启用防火墙 (ufw)
3. 配置 SSL 证书
4. 定期更新系统和依赖
5. 启用 fail2ban 防暴力破解
6. 配置自动备份
7. 监控服务器状态

## 联系支持

- 项目文档：https://github.com/mootask/mootask
- 问题反馈：https://github.com/mootask/mootask/issues
