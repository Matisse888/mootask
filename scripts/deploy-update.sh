#!/bin/bash
#
# MooTask 更新部署脚本
# 用于从 Git 拉取最新代码并重新部署
#

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 配置
APP_NAME="mootask"
APP_DIR="/var/www/$APP_NAME"
APP_USER="www-data"
BRANCH="${BRANCH:-main}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  MooTask 更新部署${NC}"
echo -e "${GREEN}========================================${NC}"

# 进入项目目录
cd $APP_DIR

# 1. 拉取最新代码
echo -e "${YELLOW}[1/7] 拉取最新代码...${NC}"
sudo -u $APP_USER git fetch origin
sudo -u $APP_USER git reset --hard origin/$BRANCH
sudo -u $APP_USER git pull origin $BRANCH

# 2. 进入维护模式
echo -e "${YELLOW}[2/7] 进入维护模式...${NC}"
sudo -u $APP_USER php artisan down --retry=60 --refresh=15

# 3. 安装依赖
echo -e "${YELLOW}[3/7] 安装依赖...${NC}"
sudo -u $APP_USER composer install --no-dev --optimize-autoloader --no-interaction

# 4. 缓存清理
echo -e "${YELLOW}[4/7] 清理缓存...${NC}"
sudo -u $APP_USER php artisan cache:clear
sudo -u $APP_USER php artisan view:clear
sudo -u $APP_USER php artisan config:clear
sudo -u $APP_USER php artisan route:clear

# 5. 重新缓存
echo -e "${YELLOW}[5/7] 重新生成缓存...${NC}"
sudo -u $APP_USER php artisan config:cache
sudo -u $APP_USER php artisan route:cache
sudo -u $APP_USER php artisan view:cache

# 6. 数据库迁移
echo -e "${YELLOW}[6/7] 运行数据库迁移...${NC}"
sudo -u $APP_USER php artisan migrate --force

# 7. 重启服务
echo -e "${YELLOW}[7/7] 重启服务...${NC}"
sudo -u $APP_USER php artisan storage:link
supervisorctl restart ${APP_NAME}-worker:*
systemctl reload php8.1-fpm
systemctl reload nginx

# 退出维护模式
sudo -u $APP_USER php artisan up

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  更新成功！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}部署信息：${NC}"
echo -e "  当前版本: $(sudo -u $APP_USER git describe --tags --always)"
echo -e "  部署时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
