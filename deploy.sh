#!/bin/bash
#
# MooTask 一键部署脚本
# 适用于 Ubuntu 20.04/22.04 LTS
#
# 使用方法：
#   chmod +x deploy.sh
#   sudo ./deploy.sh
#

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 配置变量
APP_NAME="mootask"
APP_DIR="/var/www/$APP_NAME"
APP_USER="www-data"
PHP_VERSION="8.1"
DOMAIN="${DOMAIN:-localhost}"
DB_NAME="${DB_NAME:-mootask}"
DB_USER="${DB_USER:-mootask}"
DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  MooTask 部署脚本${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# 检查是否为 root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}请使用 root 权限运行此脚本：${NC}"
    echo -e "  sudo $0"
    exit 1
fi

# 1. 系统更新
echo -e "${BLUE}[1/10] 更新系统包...${NC}"
apt-get update -y && apt-get upgrade -y

# 2. 安装基础工具
echo -e "${BLUE}[2/10] 安装基础工具...${NC}"
apt-get install -y software-properties-common curl wget git unzip \
    supervisor cron ufw software-properties-common apt-transport-https \
    ca-certificates gnupg2 lsb-release

# 3. 添加 PHP PPA
echo -e "${BLUE}[3/10] 添加 PHP PPA 源...${NC}"
add-apt-repository -y ppa:ondrej/php || true
apt-get update -y

# 4. 安装 PHP 及扩展
echo -e "${BLUE}[4/10] 安装 PHP ${PHP_VERSION} 及扩展...${NC}"
apt-get install -y php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-gd \
    php${PHP_VERSION}-bcmath php${PHP_VERSION}-redis php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-intl php${PHP_VERSION}-readline

# 5. 安装 MySQL
echo -e "${BLUE}[5/10] 安装 MySQL...${NC}"
if ! command -v mysql &> /dev/null; then
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y mysql-server
    systemctl enable mysql
    systemctl start mysql

    # 创建数据库和用户
    mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
fi

# 6. 安装 Redis
echo -e "${BLUE}[6/10] 安装 Redis...${NC}"
if ! command -v redis-cli &> /dev/null; then
    apt-get install -y redis-server
    systemctl enable redis-server
    systemctl start redis-server
fi

# 7. 安装 Nginx
echo -e "${BLUE}[7/10] 安装 Nginx...${NC}"
if ! command -v nginx &> /dev/null; then
    apt-get install -y nginx
    systemctl enable nginx
fi

# 8. 安装 Composer
echo -e "${BLUE}[8/10] 安装 Composer...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer
fi

# 9. 部署应用
echo -e "${BLUE}[9/10] 部署应用...${NC}"

# 创建项目目录
mkdir -p $APP_DIR

# 如果项目目录为空，则克隆代码
if [ -z "$(ls -A $APP_DIR 2>/dev/null)" ]; then
    echo -e "${YELLOW}项目目录为空，开始克隆代码...${NC}"
    echo -e "${YELLOW}请手动将代码上传到 $APP_DIR${NC}"
    echo -e "${YELLOW}或配置 git 仓库地址：${NC}"
    read -p "Git 仓库地址 (留空跳过): " GIT_REPO
    if [ -n "$GIT_REPO" ]; then
        git clone $GIT_REPO $APP_DIR
    fi
fi

# 设置目录权限
chown -R $APP_USER:$APP_USER $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

# 创建 .env 文件
if [ ! -f "$APP_DIR/.env" ]; then
    echo -e "${YELLOW}创建 .env 文件...${NC}"
    if [ -f "$APP_DIR/.env.example" ]; then
        cp $APP_DIR/.env.example $APP_DIR/.env
    else
        touch $APP_DIR/.env
    fi

    # 生成 APP_KEY
    APP_KEY="base64:$(openssl rand -base64 32)"
    JWT_SECRET="$(openssl rand -base64 32)"

    cat >> $APP_DIR/.env <<EOF

APP_NAME=MooTask
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=http://${DOMAIN}

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

JWT_SECRET=${JWT_SECRET}
JWT_TTL=1440

CORS_ALLOWED_ORIGINS=http://${DOMAIN}
EOF

    chown $APP_USER:$APP_USER $APP_DIR/.env
    chmod 644 $APP_DIR/.env
fi

# 安装依赖
cd $APP_DIR
if [ -f "composer.json" ]; then
    echo -e "${YELLOW}安装 PHP 依赖...${NC}"
    sudo -u $APP_USER composer install --no-dev --optimize-autoloader --no-interaction

    echo -e "${YELLOW}运行数据库迁移...${NC}"
    sudo -u $APP_USER php artisan migrate --force

    echo -e "${YELLOW}优化应用...${NC}"
    sudo -u $APP_USER php artisan config:cache
    sudo -u $APP_USER php artisan route:cache
    sudo -u $APP_USER php artisan view:cache
    sudo -u $APP_USER php artisan storage:link
fi

# 10. 配置 Nginx
echo -e "${BLUE}[10/10] 配置 Nginx...${NC}"
cat > /etc/nginx/sites-available/$APP_NAME <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php index.html;

    access_log /var/log/nginx/${APP_NAME}_access.log;
    error_log /var/log/nginx/${APP_NAME}_error.log;

    charset utf-8;

    # 上传文件大小限制
    client_max_body_size 100M;

    # Laravel 路由
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # 静态资源
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
        access_log off;
    }

    # 存储文件
    location /storage {
        alias ${APP_DIR}/storage/app/public;
        expires 30d;
    }

    # PHP-FPM
    location ~ \.php\$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)\$;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # 禁止访问敏感文件
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 安全头部
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
}
EOF

# 启用站点
ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# 测试 Nginx 配置
nginx -t

# 重启服务
systemctl restart nginx
systemctl restart php${PHP_VERSION}-fpm

# 配置 Supervisor（队列 worker）
cat > /etc/supervisor/conf.d/${APP_NAME}-worker.conf <<EOF
[program:${APP_NAME}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
user=${APP_USER}
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update
supervisorctl restart ${APP_NAME}-worker:* || true

# 配置防火墙
echo -e "${YELLOW}配置防火墙...${NC}"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# 完成
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  部署完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}应用信息：${NC}"
echo -e "  域名:    http://${DOMAIN}"
echo -e "  应用目录: ${APP_DIR}"
echo -e "  数据库名: ${DB_NAME}"
echo -e "  数据库用户: ${DB_USER}"
echo -e "  数据库密码: ${DB_PASS}"
echo ""
echo -e "${BLUE}常用命令：${NC}"
echo -e "  查看应用日志:    tail -f ${APP_DIR}/storage/logs/laravel.log"
echo -e "  查看 Nginx 日志: tail -f /var/log/nginx/${APP_NAME}_error.log"
echo -e "  重启 PHP-FPM:   systemctl restart php${PHP_VERSION}-fpm"
echo -e "  重启 Nginx:     systemctl restart nginx"
echo -e "  进入项目目录:   cd ${APP_DIR}"
echo -e "  Artisan 命令:   php artisan [command]"
echo ""
echo -e "${YELLOW}下一步：${NC}"
echo -e "  1. 配置域名 DNS 解析"
echo -e "  2. 安装 SSL 证书（推荐使用 Let's Encrypt）"
echo -e "  3. 访问 http://${DOMAIN} 验证应用"
echo ""
