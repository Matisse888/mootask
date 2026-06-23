#!/bin/bash

# ===========================================
# MooTask Docker 快速启动脚本
# ===========================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# 检查 Docker 和 Docker Compose
check_docker() {
    log_info "检查 Docker 环境..."

    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安装。请先安装 Docker: https://docs.docker.com/get-docker/"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose 未安装。请先安装 Docker Compose: https://docs.docker.com/compose/install/"
        exit 1
    fi

    log_success "Docker 环境检查通过"
}

# 创建 .env 文件
setup_env() {
    log_info "配置环境变量..."

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            log_info "已创建 .env 文件"

            # 生成密钥
            docker-compose exec app php artisan key:generate --force
            docker-compose exec app php artisan jwt:secret --force

            log_success "密钥已生成"
        else
            log_error "未找到 .env.example 文件"
            exit 1
        fi
    else
        log_warning ".env 文件已存在，跳过创建"
    fi
}

# 启动服务
start_services() {
    log_info "启动 Docker 服务..."

    docker-compose up -d --build

    log_success "服务启动成功"
}

# 等待服务就绪
wait_for_services() {
    log_info "等待服务就绪..."

    echo "等待 MySQL..."
    sleep 5

    echo "等待 Redis..."
    sleep 2

    echo "等待应用容器..."
    sleep 5

    log_success "所有服务已就绪"
}

# 初始化数据库
init_database() {
    log_info "初始化数据库..."

    # 运行数据库迁移
    docker-compose exec app php artisan migrate --force
    log_success "数据库迁移完成"

    # 询问是否填充数据
    read -p "是否填充初始数据? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker-compose exec app php artisan db:seed --force
        log_success "初始数据填充完成"
    fi
}

# 构建前端
build_frontend() {
    log_info "构建前端资源..."

    docker-compose exec app npm install
    docker-compose exec app npm run build

    log_success "前端构建完成"
}

# 显示服务状态
show_status() {
    echo ""
    log_info "==================================="
    log_info "Docker 服务状态"
    log_info "==================================="
    docker-compose ps
    echo ""

    log_info "==================================="
    log_info "访问地址"
    log_info "==================================="
    log_info "应用:   http://localhost:8080"
    log_info "API:    http://localhost:8080/api"
    log_info "WebSocket: ws://localhost:5200"
    echo ""
}

# 显示帮助信息
show_help() {
    echo ""
    echo "MooTask Docker 管理脚本"
    echo ""
    echo "用法: $0 [命令]"
    echo ""
    echo "可用命令:"
    echo "  start       启动所有服务"
    echo "  stop        停止所有服务"
    echo "  restart     重启所有服务"
    echo "  logs        查看日志"
    echo "  status      查看服务状态"
    echo "  shell       进入应用容器"
    echo "  db          进入数据库容器"
    echo "  migrate     运行数据库迁移"
    echo "  seed        填充数据"
    echo "  rebuild     重新构建镜像"
    echo "  clean       清理 Docker 资源"
    echo "  help        显示帮助信息"
    echo ""
}

# 主函数
case "${1:-start}" in
    start)
        check_docker
        setup_env
        start_services
        wait_for_services
        init_database
        build_frontend
        show_status
        log_success "启动完成！"
        ;;
    stop)
        log_info "停止 Docker 服务..."
        docker-compose stop
        log_success "服务已停止"
        ;;
    restart)
        log_info "重启 Docker 服务..."
        docker-compose restart
        log_success "服务已重启"
        ;;
    logs)
        docker-compose logs -f
        ;;
    status)
        docker-compose ps
        ;;
    shell)
        docker-compose exec app bash
        ;;
    db)
        docker-compose exec mysql mysql -u mootask -pmootask mootask
        ;;
    migrate)
        docker-compose exec app php artisan migrate
        ;;
    seed)
        docker-compose exec app php artisan db:seed
        ;;
    rebuild)
        log_info "重新构建镜像..."
        docker-compose down
        docker-compose up -d --build
        ;;
    clean)
        log_warning "清理 Docker 资源..."
        read -p "这将删除所有容器和数据卷，是否继续? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker-compose down -v
            log_success "清理完成"
        else
            log_info "取消清理"
        fi
        ;;
    help|*)
        show_help
        ;;
esac
