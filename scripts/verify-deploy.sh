#!/bin/bash

# ===========================================
# MooTask 部署验证脚本
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

# 获取应用URL
APP_URL="${APP_URL:-http://localhost:8000}"

# 检查 API 健康状态
check_api_health() {
    echo ""
    log_info "检查 API 健康状态..."

    if curl -sf "${APP_URL}/api/health" > /dev/null; then
        RESPONSE=$(curl -s "${APP_URL}/api/health")
        if echo "$RESPONSE" | grep -q '"ret":1'; then
            log_pass "API 健康检查通过"
        else
            log_fail "API 返回异常响应"
        fi
    else
        log_fail "无法连接到 API，请确保应用正在运行"
    fi
}

# 检查前端页面
check_frontend() {
    echo ""
    log_info "检查前端页面..."

    if curl -sf "${APP_URL}/" > /dev/null; then
        log_pass "前端页面可访问"
    else
        log_fail "前端页面不可访问"
    fi
}

# 检查数据库连接
check_database() {
    echo ""
    log_info "检查数据库连接..."

    if php artisan migrate:status &> /dev/null; then
        log_pass "数据库连接正常"
    else
        log_fail "数据库连接失败"
    fi
}

# 检查 Redis 连接
check_redis() {
    echo ""
    log_info "检查 Redis 连接..."

    if php artisan tinker --execute="Cache::store('redis')->put('test', 'value', 1); Cache::store('redis')->forget('test');" &> /dev/null; then
        log_pass "Redis 连接正常"
    else
        log_warn "Redis 连接失败（缓存可能不可用）"
    fi
}

# 检查必要文件
check_files() {
    echo ""
    log_info "检查必要文件..."

    # 检查 vendor 目录
    if [ -d "vendor" ]; then
        log_pass "Composer 依赖已安装"
    else
        log_fail "Composer 依赖未安装（vendor 目录不存在）"
    fi

    # 检查 node_modules 目录
    if [ -d "node_modules" ]; then
        log_pass "Node 依赖已安装"
    else
        log_warn "Node 依赖未安装"
    fi

    # 检查 .env 文件
    if [ -f ".env" ]; then
        log_pass ".env 文件存在"
    else
        log_fail ".env 文件不存在"
    fi

    # 检查 storage 目录
    if [ -d "storage" ] && [ -d "storage/logs" ]; then
        log_pass "Storage 目录配置正确"
    else
        log_fail "Storage 目录配置错误"
    fi
}

# 检查环境变量
check_env_vars() {
    echo ""
    log_info "检查环境变量配置..."

    # APP_KEY
    APP_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2)
    if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "base64:" ]; then
        log_pass "APP_KEY 已配置"
    else
        log_fail "APP_KEY 未配置"
    fi

    # DB 配置
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d'=' -f2)
    DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d'=' -f2)

    if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
        log_pass "数据库配置完整"
    else
        log_fail "数据库配置不完整"
    fi

    # JWT_SECRET
    JWT_SECRET=$(grep "^JWT_SECRET=" .env | cut -d'=' -f2)
    if [ -n "$JWT_SECRET" ]; then
        log_pass "JWT_SECRET 已配置"
    else
        log_fail "JWT_SECRET 未配置"
    fi
}

# 检查数据库迁移状态
check_migrations() {
    echo ""
    log_info "检查数据库迁移状态..."

    MIGRATION_STATUS=$(php artisan migrate:status 2>&1)

    # 检查是否有未运行的迁移
    if echo "$MIGRATION_STATUS" | grep -q "No migrations"; then
        log_warn "没有找到迁移文件"
    elif echo "$MIGRATION_STATUS" | grep -q "Ran"; then
        MIGRATED_COUNT=$(echo "$MIGRATION_STATUS" | grep "Ran" | wc -l)
        log_pass "数据库迁移已完成 ($MIGRATED_COUNT 个表)"
    else
        log_fail "数据库迁移未完成或存在问题"
    fi
}

# 检查队列 Worker
check_queue_worker() {
    echo ""
    log_info "检查队列 Worker..."

    if command -v supervisorctl &> /dev/null; then
        WORKER_STATUS=$(supervisorctl status laravel-worker:* 2>/dev/null || echo "")

        if [ -n "$WORKER_STATUS" ]; then
            log_pass "队列 Worker 已配置"
        else
            log_warn "队列 Worker 未运行"
        fi
    else
        log_warn "Supervisor 未安装"
    fi
}

# 检查定时任务
check_cron() {
    echo ""
    log_info "检查定时任务..."

    CRON_ENTRY=$(crontab -l 2>/dev/null | grep "schedule:run")

    if [ -n "$CRON_ENTRY" ]; then
        log_pass "定时任务已配置"
    else
        log_warn "定时任务未配置"
    fi
}

# 检查端口占用
check_port() {
    echo ""
    log_info "检查端口 8000..."

    if netstat -tuln 2>/dev/null | grep -q ":8000 "; then
        log_pass "端口 8000 已被占用（应用可能正在运行）"
    else
        log_warn "端口 8000 未被占用（应用可能未启动）"
    fi
}

# 检查日志文件
check_logs() {
    echo ""
    log_info "检查日志文件..."

    if [ -f "storage/logs/laravel.log" ]; then
        log_pass "应用日志文件存在"

        # 检查最近是否有错误
        LAST_ERRORS=$(tail -n 100 storage/logs/laravel.log | grep -i error | tail -5)

        if [ -n "$LAST_ERRORS" ]; then
            log_warn "最近日志中存在错误："
            echo "$LAST_ERRORS" | while read line; do
                echo "  $line"
            done
        else
            log_pass "最近日志中没有错误"
        fi
    else
        log_warn "应用日志文件不存在"
    fi
}

# 检查前端构建
check_frontend_build() {
    echo ""
    log_info "检查前端构建..."

    if [ -d "public/assets" ]; then
        BUILD_FILES=$(find public/assets -type f 2>/dev/null | wc -l)

        if [ "$BUILD_FILES" -gt 0 ]; then
            log_pass "前端资源已构建 ($BUILD_FILES 个文件)"
        else
            log_warn "前端资源目录为空"
        fi
    else
        log_warn "前端资源目录不存在"
    fi
}

# 生成测试请求
generate_test_request() {
    echo ""
    log_info "生成测试请求..."

    # 测试用户注册 API
    TEST_EMAIL="test_$(date +%s)@example.com"
    TEST_RESPONSE=$(curl -s -X POST "${APP_URL}/api/auth/register" \
        -H "Content-Type: application/json" \
        -d "{\"username\":\"testuser\",\"email\":\"${TEST_EMAIL}\",\"password\":\"Test123456\",\"name\":\"Test User\"}" \
        2>&1)

    if echo "$TEST_RESPONSE" | grep -q "ret"; then
        log_pass "API 请求测试成功"
    else
        log_warn "API 请求测试失败或返回异常"
    fi
}

# 显示总结
show_summary() {
    echo ""
    echo "========================================"
    echo "部署验证总结"
    echo "========================================"
    echo -e "通过: ${GREEN}$PASS_COUNT${NC}"
    echo -e "失败: ${RED}$FAIL_COUNT${NC}"
    echo -e "警告: ${YELLOW}$WARN_COUNT${NC}"
    echo ""

    if [ $FAIL_COUNT -eq 0 ]; then
        echo -e "${GREEN}✓ 部署验证通过！${NC}"
        echo ""
        echo "应用已成功部署并运行在: ${APP_URL}"
        echo ""
        echo "下一步："
        echo "1. 创建管理员账户"
        echo "2. 访问应用首页"
        echo "3. 开始使用 MooTask"
    else
        echo -e "${RED}✗ 部署验证未通过，请修复失败项。${NC}"
        echo ""
        echo "建议操作："
        echo "1. 运行 'php artisan config:clear'"
        echo "2. 检查 .env 配置"
        echo "3. 运行 'php artisan migrate'"
        echo "4. 运行 'npm run build'"
        echo "5. 重启应用: 'php artisan serve'"
    fi

    echo ""
}

# 主函数
main() {
    echo ""
    echo "========================================"
    echo "   MooTask 部署验证脚本"
    echo "========================================"
    echo ""

    # 检查前提条件
    if [ ! -f "artisan" ]; then
        log_error "请在项目根目录运行此脚本"
        exit 1
    fi

    # 运行所有检查
    check_files
    check_env_vars
    check_database
    check_migrations
    check_redis
    check_queue_worker
    check_cron
    check_port
    check_logs
    check_frontend_build
    check_frontend
    check_api_health
    generate_test_request

    # 显示总结
    show_summary
}

main "$@"
