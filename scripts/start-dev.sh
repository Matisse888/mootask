#!/bin/bash
#
# MooTask 开发环境启动脚本
# 一键启动所有开发服务
#

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  MooTask 开发环境启动${NC}"
echo -e "${GREEN}========================================${NC}"

# 选择启动方式
echo -e "${YELLOW}请选择启动方式：${NC}"
echo -e "  1) Docker (推荐)"
echo -e "  2) 本地 (PHP + MySQL + Redis)"
echo ""
read -p "请输入选项 [1-2]: " CHOICE

case $CHOICE in
    1)
        echo -e "${BLUE}使用 Docker 启动...${NC}"

        # 检查 Docker
        if ! command -v docker &> /dev/null; then
            echo -e "${RED}Docker 未安装，请先安装 Docker${NC}"
            exit 1
        fi

        # 检查 docker-compose
        if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
            echo -e "${RED}docker-compose 未安装${NC}"
            exit 1
        fi

        # 启动容器
        echo -e "${YELLOW}启动 Docker 容器...${NC}"
        docker-compose up -d

        # 等待服务就绪
        echo -e "${YELLOW}等待服务启动...${NC}"
        sleep 15

        # 运行迁移
        echo -e "${YELLOW}运行数据库迁移...${NC}"
        docker-compose exec app php artisan migrate --force

        # 优化
        docker-compose exec app php artisan config:clear
        docker-compose exec app php artisan cache:clear

        echo ""
        echo -e "${GREEN}========================================${NC}"
        echo -e "${GREEN}  启动成功！${NC}"
        echo -e "${GREEN}========================================${NC}"
        echo ""
        echo -e "${BLUE}访问地址：${NC}"
        echo -e "  前端: http://localhost:8080"
        echo -e "  API:  http://localhost:8080/api"
        echo ""
        echo -e "${BLUE}常用命令：${NC}"
        echo -e "  查看日志:     docker-compose logs -f"
        echo -e "  进入容器:     docker-compose exec app bash"
        echo -e "  停止服务:     docker-compose down"
        echo -e "  重启服务:     docker-compose restart"
        ;;

    2)
        echo -e "${BLUE}使用本地环境启动...${NC}"

        # 检查 PHP
        if ! command -v php &> /dev/null; then
            echo -e "${RED}PHP 未安装${NC}"
            exit 1
        fi

        # 检查 composer
        if ! command -v composer &> /dev/null; then
            echo -e "${RED}Composer 未安装${NC}"
            exit 1
        fi

        # 复制环境配置
        if [ ! -f ".env" ]; then
            cp .env.example .env
            php artisan key:generate
        fi

        # 安装依赖
        echo -e "${YELLOW}安装 PHP 依赖...${NC}"
        composer install

        # 运行迁移
        echo -e "${YELLOW}运行数据库迁移...${NC}"
        php artisan migrate --force

        # 启动开发服务器
        echo -e "${YELLOW}启动开发服务器...${NC}"
        echo -e "${GREEN}访问地址: http://localhost:8000${NC}"
        php artisan serve
        ;;
esac
