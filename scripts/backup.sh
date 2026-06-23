#!/bin/bash
#
# MooTask 数据库备份脚本
#

set -e

# 配置
APP_NAME="mootask"
APP_DIR="/var/www/$APP_NAME"
BACKUP_DIR="/var/backups/$APP_NAME"
DB_NAME="${DB_NAME:-mootask}"
DB_USER="${DB_USER:-mootask}"
DB_PASS="${DB_PASS:-mootask_pass}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] 开始备份...${NC}"

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份文件名
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/${APP_NAME}_db_${TIMESTAMP}.sql.gz"

# 备份数据库
echo -e "${YELLOW}备份数据库到: $BACKUP_FILE${NC}"
mysqldump -u$DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    $DB_NAME | gzip > $BACKUP_FILE

# 备份存储文件
STORAGE_BACKUP="$BACKUP_DIR/${APP_NAME}_storage_${TIMESTAMP}.tar.gz"
echo -e "${YELLOW}备份存储文件到: $STORAGE_BACKUP${NC}"
tar -czf $STORAGE_BACKUP -C $APP_DIR/storage app/public 2>/dev/null || true

# 清理旧备份
echo -e "${YELLOW}清理 ${RETENTION_DAYS} 天前的备份...${NC}"
find $BACKUP_DIR -name "${APP_NAME}_db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "${APP_NAME}_storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# 显示备份信息
echo -e "${GREEN}备份完成！${NC}"
ls -lh $BACKUP_DIR/${APP_NAME}_*_${TIMESTAMP}.* | awk '{print $5, $9}'
echo ""
