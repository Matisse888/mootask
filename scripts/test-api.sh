#!/bin/bash
#
# MooTask API 测试脚本
# 用于验证核心功能是否正常工作
#

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 配置
BASE_URL="${BASE_URL:-http://localhost:8000/api}"
EMAIL="test_$(date +%s)@mootask.com"
PASSWORD="Test123456"
NAME="测试用户"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  MooTask API 测试${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}API 地址: $BASE_URL${NC}"
echo ""

# 计数器
TOTAL=0
PASSED=0
FAILED=0

# 测试函数
test_api() {
    local name=$1
    local method=$2
    local url=$3
    local data=$4
    local token=$5

    TOTAL=$((TOTAL+1))

    local headers=()
    if [ -n "$token" ]; then
        headers+=("-H" "Authorization: Bearer $token")
    fi
    headers+=("-H" "Accept: application/json")
    headers+=("-H" "Content-Type: application/json")

    if [ -n "$data" ]; then
        RESPONSE=$(curl -s -X $method "${headers[@]}" -d "$data" "$BASE_URL$url")
    else
        RESPONSE=$(curl -s -X $method "${headers[@]}" "$BASE_URL$url")
    fi

    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X $method "${headers[@]}" \
        ${data:+-d "$data"} "$BASE_URL$url")

    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "201" ]; then
        echo -e "${GREEN}✓${NC} $name [$HTTP_CODE]"
        PASSED=$((PASSED+1))
        echo "  $RESPONSE" | head -c 200
        echo ""
    else
        echo -e "${RED}✗${NC} $name [$HTTP_CODE]"
        FAILED=$((FAILED+1))
        echo "  $RESPONSE" | head -c 200
        echo ""
    fi
}

# 1. 健康检查
echo -e "${YELLOW}[1] 健康检查${NC}"
test_api "健康检查" "GET" "/health" "" ""

# 2. 获取验证码
echo ""
echo -e "${YELLOW}[2] 用户注册${NC}"
REGISTER_DATA="{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"password_confirmation\":\"$PASSWORD\",\"name\":\"$NAME\"}"
REGISTER_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "$REGISTER_DATA" "$BASE_URL/auth/register")
echo "  响应: $REGISTER_RESPONSE" | head -c 200
echo ""

TOKEN=$(echo $REGISTER_RESPONSE | grep -oP '"token":"[^"]+' | sed 's/"token":"//')
USER_ID=$(echo $REGISTER_RESPONSE | grep -oP '"id":[0-9]+' | head -1 | sed 's/"id"://')

if [ -n "$TOKEN" ]; then
    echo -e "${GREEN}✓${NC} 注册成功，Token: ${TOKEN:0:20}..."
    PASSED=$((PASSED+1))
else
    echo -e "${RED}✗${NC} 注册失败"
    FAILED=$((FAILED+1))
    exit 1
fi

# 3. 登录测试
echo ""
echo -e "${YELLOW}[3] 用户登录${NC}"
LOGIN_DATA="{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}"
LOGIN_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "$LOGIN_DATA" "$BASE_URL/auth/login")
TOKEN=$(echo $LOGIN_RESPONSE | grep -oP '"token":"[^"]+' | sed 's/"token":"//')
if [ -n "$TOKEN" ]; then
    echo -e "${GREEN}✓${NC} 登录成功"
    PASSED=$((PASSED+1))
else
    echo -e "${RED}✗${NC} 登录失败"
    FAILED=$((FAILED+1))
fi

# 4. 获取用户信息
echo ""
echo -e "${YELLOW}[4] 获取用户信息${NC}"
test_api "获取用户信息" "GET" "/user/info" "" "$TOKEN"

# 5. 更新用户信息
echo ""
echo -e "${YELLOW}[5] 更新用户信息${NC}"
test_api "更新用户信息" "POST" "/user/update" '{"name":"更新后的昵称"}' "$TOKEN"

# 6. 修改密码
echo ""
echo -e "${YELLOW}[6] 修改密码${NC}"
test_api "修改密码" "POST" "/user/password" "{\"old_password\":\"$PASSWORD\",\"password\":\"NewPass123\",\"password_confirmation\":\"NewPass123\"}" "$TOKEN"

# 恢复密码
LOGIN_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"NewPass123\"}" "$BASE_URL/auth/login")
TOKEN=$(echo $LOGIN_RESPONSE | grep -oP '"token":"[^"]+' | sed 's/"token":"//')

# 7. 创建项目
echo ""
echo -e "${YELLOW}[7] 项目管理${NC}"
test_api "创建项目" "POST" "/project/create" '{"name":"测试项目","desc":"这是一个测试项目","color":"#409EFF"}' "$TOKEN"

PROJECT_ID=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
    "$BASE_URL/project/lists?page=1&page_size=1" | grep -oP '"id":[0-9]+' | head -1 | sed 's/"id"://')

echo "  项目ID: $PROJECT_ID"
PASSED=$((PASSED+1))

# 8. 获取项目列表
test_api "获取项目列表" "GET" "/project/lists" "" "$TOKEN"

# 9. 获取项目详情
test_api "获取项目详情" "GET" "/project/$PROJECT_ID" "" "$TOKEN"

# 10. 创建任务
echo ""
echo -e "${YELLOW}[8] 任务管理${NC}"
COLUMN_ID=$(curl -s -X GET -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
    "$BASE_URL/project/$PROJECT_ID" | grep -oP '"column_id":[0-9]+' | head -1 | sed 's/"column_id"://')

if [ -z "$COLUMN_ID" ]; then
    COLUMN_ID=1
fi

TASK_DATA="{\"column_id\":$COLUMN_ID,\"name\":\"测试任务\",\"desc\":\"这是一个测试任务\",\"priority\":\"high\",\"type\":\"task\"}"
TASK_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d "$TASK_DATA" "$BASE_URL/task/create/$PROJECT_ID")
TASK_ID=$(echo $TASK_RESPONSE | grep -oP '"id":[0-9]+' | head -1 | sed 's/"id"://')

if [ -n "$TASK_ID" ]; then
    echo -e "${GREEN}✓${NC} 创建任务成功 (ID: $TASK_ID)"
    PASSED=$((PASSED+1))
else
    echo -e "${RED}✗${NC} 创建任务失败"
    FAILED=$((FAILED+1))
fi

# 11. 获取我的任务
test_api "获取我的任务" "GET" "/task/my" "" "$TOKEN"

# 12. 移动任务
if [ -n "$TASK_ID" ] && [ -n "$COLUMN_ID" ]; then
    NEXT_COLUMN_ID=$((COLUMN_ID + 1))
    test_api "移动任务" "POST" "/task/$PROJECT_ID/$TASK_ID/move" "{\"column_id\":$NEXT_COLUMN_ID,\"sort\":0}" "$TOKEN"
fi

# 13. 创建对话
echo ""
echo -e "${YELLOW}[9] 即时通讯${NC}"
DIALOG_DATA='{"type":"private","user_ids":[1]}'
DIALOG_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d "$DIALOG_DATA" "$BASE_URL/dialog/create")
DIALOG_ID=$(echo $DIALOG_RESPONSE | grep -oP '"id":[0-9]+' | head -1 | sed 's/"id"://')

if [ -n "$DIALOG_ID" ]; then
    echo -e "${GREEN}✓${NC} 创建对话成功 (ID: $DIALOG_ID)"
    PASSED=$((PASSED+1))
else
    echo -e "${RED}✗${NC} 创建对话失败"
    FAILED=$((FAILED+1))
fi

# 14. 获取对话列表
test_api "获取对话列表" "GET" "/dialog/lists" "" "$TOKEN"

# 15. 发送消息
if [ -n "$DIALOG_ID" ]; then
    MSG_DATA='{"type":"text","content":"Hello MooTask!"}'
    MSG_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
        -d "$MSG_DATA" "$BASE_URL/dialog/$DIALOG_ID/message")

    if echo "$MSG_RESPONSE" | grep -q '"ret":1'; then
        echo -e "${GREEN}✓${NC} 发送消息成功"
        PASSED=$((PASSED+1))
    else
        echo -e "${RED}✗${NC} 发送消息失败"
        FAILED=$((FAILED+1))
    fi
fi

# 16. 获取消息列表
if [ -n "$DIALOG_ID" ]; then
    test_api "获取消息列表" "GET" "/dialog/$DIALOG_ID/messages" "" "$TOKEN"
fi

# 17. 文件上传
echo ""
echo -e "${YELLOW}[10] 文件管理${NC}"
echo "test content" > /tmp/test-upload.txt
FILE_RESPONSE=$(curl -s -X POST -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
    -F "file=@/tmp/test-upload.txt" \
    -F "project_id=$PROJECT_ID" \
    "$BASE_URL/file/upload")
FILE_ID=$(echo $FILE_RESPONSE | grep -oP '"id":[0-9]+' | head -1 | sed 's/"id"://')

if [ -n "$FILE_ID" ]; then
    echo -e "${GREEN}✓${NC} 文件上传成功 (ID: $FILE_ID)"
    PASSED=$((PASSED+1))
else
    echo -e "${RED}✗${NC} 文件上传失败"
    FAILED=$((FAILED+1))
fi

# 18. 获取文件列表
test_api "获取文件列表" "GET" "/file/list" "" "$TOKEN"

# 19. 退出登录
echo ""
echo -e "${YELLOW}[11] 退出登录${NC}"
test_api "退出登录" "POST" "/auth/logout" "" "$TOKEN"

# 测试结果汇总
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  测试结果${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "  总计: $TOTAL"
echo -e "  ${GREEN}通过: $PASSED${NC}"
echo -e "  ${RED}失败: $FAILED${NC}"
echo ""

if [ $FAILED -gt 0 ]; then
    exit 1
fi

echo -e "${GREEN}所有核心功能测试通过！${NC}"
