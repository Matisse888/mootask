#!/bin/bash
# MooTask 容器启动脚本

set -e

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  MooTask Container Starting...${NC}"
echo -e "${GREEN}========================================${NC}"

# 等待数据库就绪
echo -e "${YELLOW}Waiting for database...${NC}"
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    sleep 2
done
echo -e "${GREEN}Database is ready!${NC}"

# 等待 Redis 就绪
echo -e "${YELLOW}Waiting for Redis...${NC}"
while ! redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping 2>/dev/null | grep -q PONG; do
    sleep 1
done
echo -e "${GREEN}Redis is ready!${NC}"

# 设置目录权限
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# 生成应用密钥（如果未设置）
if [ -z "$APP_KEY" ]; then
    echo -e "${YELLOW}Generating APP_KEY...${NC}"
    php artisan key:generate --force
fi

# 缓存配置
if [ "$APP_ENV" = "production" ]; then
    echo -e "${YELLOW}Caching configuration...${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# 运行数据库迁移
echo -e "${YELLOW}Running migrations...${NC}"
php artisan migrate --force

# 清理旧缓存
php artisan cache:clear

# 创建存储链接
php artisan storage:link

# 启动服务
echo -e "${GREEN}Starting services...${NC}"
exec "$@"
