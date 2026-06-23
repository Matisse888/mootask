#!/bin/bash

# ===========================================
# MooTask 自动化部署脚本
# ===========================================

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 项目目录
PROJECT_DIR="/var/www/mootask"
BACKUP_DIR="/var/www/mootask_backups"

# 函数定义
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查是否为 root 用户
check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "请使用 root 用户或 sudo 运行此脚本"
        exit 1
    fi
}

# 检查必要的依赖
check_dependencies() {
    log_info "检查系统依赖..."

    # 检查 PHP
    if ! command -v php &> /dev/null; then
        log_error "PHP 未安装，请先安装 PHP 8.0+"
        exit 1
    fi

    PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    log_success "PHP 版本: $PHP_VERSION"

    # 检查 Composer
    if ! command -v composer &> /dev/null; then
        log_error "Composer 未安装，请先安装 Composer"
        exit 1
    fi
    log_success "Composer 已安装"

    # 检查 MySQL
    if ! command -v mysql &> /dev/null; then
        log_warning "MySQL 客户端未安装"
    else
        log_success "MySQL 已安装"
    fi

    # 检查 Redis
    if ! command -v redis-cli &> /dev/null; then
        log_warning "Redis CLI 未安装"
    else
        log_success "Redis 已安装"
    fi

    # 检查 Node.js
    if ! command -v node &> /dev/null; then
        log_error "Node.js 未安装，请先安装 Node.js 16+"
        exit 1
    fi

    NODE_VERSION=$(node -v)
    log_success "Node.js 版本: $NODE_VERSION"

    # 检查 NPM
    if ! command -v npm &> /dev/null; then
        log_error "NPM 未安装"
        exit 1
    fi

    NPM_VERSION=$(npm -v)
    log_success "NPM 版本: $NPM_VERSION"
}

# 创建项目目录
create_directories() {
    log_info "创建项目目录..."

    mkdir -p $PROJECT_DIR
    mkdir -p $BACKUP_DIR

    log_success "目录创建完成"
}

# 克隆或更新代码
deploy_code() {
    log_info "部署代码..."

    cd $PROJECT_DIR

    # 如果是 Git 仓库，则拉取最新代码
    if [ -d ".git" ]; then
        log_info "检测到 Git 仓库，正在拉取最新代码..."
        git pull origin main
    else
        log_warning "非 Git 仓库，请手动上传代码到 $PROJECT_DIR"
    fi

    log_success "代码部署完成"
}

# 安装 PHP 依赖
install_php_dependencies() {
    log_info "安装 PHP 依赖..."

    cd $PROJECT_DIR

    # 设置权限
    chown -R www-data:www-data $PROJECT_DIR
    chmod -R 755 $PROJECT_DIR
    chmod -R 775 $PROJECT_DIR/storage
    chmod -R 775 $PROJECT_DIR/bootstrap/cache

    # 安装依赖
    sudo -u www-data composer install --no-dev --optimize-autoloader --prefer-dist

    log_success "PHP 依赖安装完成"
}

# 安装 Node 依赖和构建前端
build_frontend() {
    log_info "构建前端资源..."

    cd $PROJECT_DIR

    # 安装 Node 依赖
    sudo -u www-data npm install

    # 构建生产版本
    sudo -u www-data npm run build

    log_success "前端构建完成"
}

# 配置环境变量
configure_env() {
    log_info "配置环境变量..."

    cd $PROJECT_DIR

    # 复制环境配置文件
    if [ ! -f ".env" ]; then
        if [ -f ".env.production" ]; then
            cp .env.production .env
            log_info "已从 .env.production 复制配置文件"
        elif [ -f ".env.example" ]; then
            cp .env.example .env
            log_info "已从 .env.example 复制配置文件"
        else
            log_error "未找到环境配置文件"
            exit 1
        fi
    fi

    # 生成应用密钥
    sudo -u www-data php artisan key:generate --force

    # 生成 JWT 密钥
    sudo -u www-data php artisan jwt:secret --force

    log_success "环境变量配置完成"
}

# 配置数据库
setup_database() {
    log_info "配置数据库..."

    cd $PROJECT_DIR

    # 运行数据库迁移
    sudo -u www-data php artisan migrate --force

    log_success "数据库配置完成"
}

# 设置权限
set_permissions() {
    log_info "设置目录权限..."

    chown -R www-data:www-data $PROJECT_DIR/storage
    chown -R www-data:www-data $PROJECT_DIR/bootstrap/cache
    chown -R www-data:www-data $PROJECT_DIR/public

    chmod -R 775 $PROJECT_DIR/storage
    chmod -R 775 $PROJECT_DIR/bootstrap/cache

    log_success "权限设置完成"
}

# 配置定时任务
setup_cron() {
    log_info "配置定时任务..."

    # 添加定时任务
    (crontab -l 2>/dev/null | grep -v "artisan schedule:run"; echo "* * * * * cd $PROJECT_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

    log_success "定时任务配置完成"
}

# 配置队列 Worker
setup_queue_worker() {
    log_info "配置队列 Worker..."

    # 创建 Supervisor 配置
    cat > /etc/supervisor/conf.d/laravel-worker.conf <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$PROJECT_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF

    # 重新加载 Supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start laravel-worker:*

    log_success "队列 Worker 配置完成"
}

# 清除缓存
clear_cache() {
    log_info "清除缓存..."

    cd $PROJECT_DIR

    sudo -u www-data php artisan config:clear
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan view:clear
    sudo -u www-data php artisan route:clear

    # 生产环境优化
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache

    log_success "缓存清除完成"
}

# 重启服务
restart_services() {
    log_info "重启服务..."

    systemctl restart php*-fpm
    systemctl restart nginx

    if command -v systemctl &> /dev/null; then
        systemctl restart supervisord
    fi

    log_success "服务重启完成"
}

# 创建备份
create_backup() {
    log_info "创建备份..."

    BACKUP_NAME="mootask_backup_$(date +%Y%m%d_%H%M%S)"
    BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"

    mkdir -p $BACKUP_PATH

    # 备份代码
    if [ -d "$PROJECT_DIR" ]; then
        tar -czf "$BACKUP_PATH/code.tar.gz" -C "$PROJECT_DIR" . --exclude='vendor' --exclude='node_modules'
    fi

    # 备份环境配置
    if [ -f "$PROJECT_DIR/.env" ]; then
        cp "$PROJECT_DIR/.env" "$BACKUP_PATH/.env"
    fi

    # 备份存储文件
    if [ -d "$PROJECT_DIR/storage" ]; then
        tar -czf "$BACKUP_PATH/storage.tar.gz" -C "$PROJECT_DIR/storage" .
    fi

    log_success "备份已创建: $BACKUP_PATH"
}

# 显示部署状态
show_status() {
    log_info "==================================="
    log_info "部署状态检查"
    log_info "==================================="

    # 检查服务状态
    echo ""
    log_info "PHP-FPM 状态:"
    systemctl status php*-fpm | grep "Active:" || echo "  未运行"

    echo ""
    log_info "Nginx 状态:"
    systemctl status nginx | grep "Active:" || echo "  未运行"

    echo ""
    log_info "队列 Worker 状态:"
    supervisorctl status laravel-worker:* 2>/dev/null || echo "  未运行"

    echo ""
    log_info "==================================="
    log_info "访问地址: http://$(hostname -I | awk '{print $1}')"
    log_info "==================================="
}

# 主函数
main() {
    echo ""
    log_info "========================================"
    log_info "       MooTask 自动化部署脚本"
    log_info "========================================"
    echo ""

    # 检查 root 权限
    check_root

    # 检查依赖
    check_dependencies

    # 询问是否创建备份
    read -p "是否创建备份? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        create_backup
    fi

    # 部署步骤
    create_directories
    deploy_code
    install_php_dependencies
    build_frontend
    configure_env
    setup_database
    set_permissions
    setup_cron

    # 询问是否配置队列 Worker
    read -p "是否配置队列 Worker? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        setup_queue_worker
    fi

    clear_cache
    restart_services

    # 显示状态
    show_status

    echo ""
    log_success "部署完成！"
    echo ""
    log_info "请访问: http://your-domain.com"
    log_info "配置文件: $PROJECT_DIR/.env"
    echo ""
}

# 运行主函数
main "$@"
