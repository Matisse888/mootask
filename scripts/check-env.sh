#!/bin/bash

# ===========================================
# MooTask 环境检查脚本
# ===========================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

log_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASS_COUNT++))
}

log_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAIL_COUNT++))
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    ((WARN_COUNT++))
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# 检查 PHP
check_php() {
    echo ""
    log_info "检查 PHP..."

    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r 'echo PHP_VERSION;')
        log_pass "PHP 已安装: $PHP_VERSION"

        # 检查 PHP 版本
        PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
        PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')

        if [ "$PHP_MAJOR" -ge 8 ]; then
            log_pass "PHP 版本满足要求 (>= 8.0)"
        else
            log_fail "PHP 版本过低，需要 PHP 8.0+"
        fi
    else
        log_fail "PHP 未安装"
    fi
}

# 检查 PHP 扩展
check_php_extensions() {
    echo ""
    log_info "检查 PHP 扩展..."

    REQUIRED_EXTENSIONS=(
        "pdo_mysql"
        "mbstring"
        "exif"
        "pcntl"
        "bcmath"
        "gd"
        "zip"
        "curl"
        "xml"
        "json"
        "openssl"
    )

    RECOMMENDED_EXTENSIONS=(
        "opcache"
        "redis"
    )

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^${ext}$"; then
            log_pass "扩展已安装: $ext"
        else
            log_fail "缺少必需扩展: $ext"
        fi
    done

    for ext in "${RECOMMENDED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^${ext}$"; then
            log_pass "推荐扩展已安装: $ext"
        else
            log_warn "推荐扩展未安装: $ext (建议安装以提升性能)"
        fi
    done
}

# 检查 Composer
check_composer() {
    echo ""
    log_info "检查 Composer..."

    if command -v composer &> /dev/null; then
        COMPOSER_VERSION=$(composer --version | awk '{print $3}' | tr -d ',')
        log_pass "Composer 已安装: $COMPOSER_VERSION"
    else
        log_fail "Composer 未安装"
    fi
}

# 检查 Node.js
check_nodejs() {
    echo ""
    log_info "检查 Node.js..."

    if command -v node &> /dev/null; then
        NODE_VERSION=$(node -v)
        NPM_VERSION=$(npm -v)
        log_pass "Node.js 已安装: $NODE_VERSION"
        log_pass "NPM 已安装: $NPM_VERSION"

        # 检查 Node 版本
        NODE_MAJOR=$(node -v | cut -d'.' -f1 | cut -d'v' -f2)

        if [ "$NODE_MAJOR" -ge 16 ]; then
            log_pass "Node.js 版本满足要求 (>= 16.0)"
        else
            log_warn "Node.js 版本过低，建议升级到 16.0+"
        fi
    else
        log_fail "Node.js 未安装"
    fi
}

# 检查数据库
check_database() {
    echo ""
    log_info "检查数据库..."

    # MySQL
    if command -v mysql &> /dev/null; then
        log_pass "MySQL 客户端已安装"

        # 尝试连接
        if [ -n "$DB_HOST" ] && [ -n "$DB_USERNAME" ]; then
            if mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" &> /dev/null; then
                log_pass "数据库连接成功"
            else
                log_warn "无法连接到数据库，请检查 .env 配置"
            fi
        else
            log_warn "请在 .env 文件中配置数据库连接信息"
        fi
    else
        log_warn "MySQL 客户端未安装"
    fi

    # Redis
    if command -v redis-cli &> /dev/null; then
        log_pass "Redis CLI 已安装"

        # 尝试连接
        if redis-cli ping &> /dev/null; then
            log_pass "Redis 连接成功"
        else
            log_warn "无法连接到 Redis，请确保 Redis 服务正在运行"
        fi
    else
        log_warn "Redis CLI 未安装"
    fi
}

# 检查 Docker
check_docker() {
    echo ""
    log_info "检查 Docker..."

    if command -v docker &> /dev/null; then
        DOCKER_VERSION=$(docker --version | awk '{print $3}' | tr -d ',')
        log_pass "Docker 已安装: $DOCKER_VERSION"

        # 检查 Docker 运行状态
        if docker info &> /dev/null; then
            log_pass "Docker 服务正在运行"
        else
            log_warn "Docker 服务未运行"
        fi
    else
        log_warn "Docker 未安装"
    fi

    if command -v docker-compose &> /dev/null; then
        log_pass "Docker Compose 已安装"
    else
        log_warn "Docker Compose 未安装"
    fi
}

# 检查项目文件
check_project_files() {
    echo ""
    log_info "检查项目文件..."

    if [ -f "composer.json" ]; then
        log_pass "composer.json 存在"
    else
        log_fail "composer.json 不存在"
    fi

    if [ -f ".env" ] || [ -f ".env.example" ]; then
        log_pass "环境配置文件存在"
    else
        log_fail "环境配置文件不存在"
    fi

    if [ -d "app" ]; then
        log_pass "应用目录存在"
    else
        log_fail "应用目录不存在"
    fi

    if [ -d "resources" ]; then
        log_pass "资源目录存在"
    else
        log_fail "资源目录不存在"
    fi
}

# 检查依赖安装
check_dependencies_installed() {
    echo ""
    log_info "检查依赖安装..."

    if [ -d "vendor" ]; then
        log_pass "PHP 依赖已安装 (vendor 目录存在)"
    else
        log_warn "PHP 依赖未安装，运行 'composer install'"
    fi

    if [ -d "node_modules" ]; then
        log_pass "Node 依赖已安装 (node_modules 目录存在)"
    else
        log_warn "Node 依赖未安装，运行 'npm install'"
    fi
}

# 检查 .env 配置
check_env_config() {
    echo ""
    log_info "检查 .env 配置..."

    if [ ! -f ".env" ]; then
        log_fail ".env 文件不存在"
        return
    fi

    # 检查必需的配置项
    APP_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2)
    if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "base64:" ]; then
        log_pass "APP_KEY 已配置"
    else
        log_fail "APP_KEY 未配置或无效"
    fi

    DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
    if [ -n "$DB_HOST" ]; then
        log_pass "DB_HOST 已配置: $DB_HOST"
    else
        log_fail "DB_HOST 未配置"
    fi

    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)
    if [ -n "$DB_DATABASE" ]; then
        log_pass "DB_DATABASE 已配置: $DB_DATABASE"
    else
        log_fail "DB_DATABASE 未配置"
    fi

    JWT_SECRET=$(grep "^JWT_SECRET=" .env | cut -d'=' -f2)
    if [ -n "$JWT_SECRET" ]; then
        log_pass "JWT_SECRET 已配置"
    else
        log_fail "JWT_SECRET 未配置"
    fi
}

# 检查端口占用
check_ports() {
    echo ""
    log_info "检查端口占用..."

    PORTS=(3306 6379 8080 5200)

    for port in "${PORTS[@]}"; do
        if netstat -tuln 2>/dev/null | grep -q ":$port "; then
            log_warn "端口 $port 已被占用"
        else
            log_pass "端口 $port 可用"
        fi
    done
}

# 显示总结
show_summary() {
    echo ""
    echo "========================================"
    echo "环境检查总结"
    echo "========================================"
    echo -e "通过: ${GREEN}$PASS_COUNT${NC}"
    echo -e "失败: ${RED}$FAIL_COUNT${NC}"
    echo -e "警告: ${YELLOW}$WARN_COUNT${NC}"
    echo ""

    if [ $FAIL_COUNT -eq 0 ]; then
        echo -e "${GREEN}环境检查通过！可以开始部署。${NC}"
        echo ""
        echo "下一步："
        echo "1. 运行 'composer install' 安装 PHP 依赖"
        echo "2. 运行 'npm install' 安装 Node 依赖"
        echo "3. 配置数据库连接"
        echo "4. 运行 'php artisan migrate' 创建数据库表"
        echo "5. 运行 'php artisan serve' 启动开发服务器"
    else
        echo -e "${RED}环境检查未通过，请修复失败项后重试。${NC}"
    fi
}

# 主函数
main() {
    echo ""
    echo "========================================"
    echo "    MooTask 环境检查脚本"
    echo "========================================"
    echo ""

    check_php
    check_php_extensions
    check_composer
    check_nodejs
    check_database
    check_docker
    check_project_files
    check_dependencies_installed
    check_env_config
    check_ports

    show_summary
}

main "$@"
