#!/bin/bash
#
# MooTask 服务器初始化脚本
# Ubuntu 20.04/22.04
#

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  MooTask 服务器初始化${NC}"
echo -e "${GREEN}========================================${NC}"

# 检查 root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}请使用 root 权限运行此脚本${NC}"
    exit 1
fi

# 配置时区
echo -e "${YELLOW}[1/8] 设置时区为 Asia/Shanghai...${NC}"
timedatectl set-timezone Asia/Shanghai

# 优化系统参数
echo -e "${YELLOW}[2/8] 优化系统参数...${NC}"
cat >> /etc/sysctl.conf <<EOF

# MooTask 优化
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 30
net.ipv4.ip_local_port_range = 10000 65000
fs.file-max = 2097152
vm.swappiness = 10
EOF

sysctl -p

# 增加文件描述符限制
cat >> /etc/security/limits.conf <<EOF
* soft nofile 65535
* hard nofile 65535
EOF

# 配置 PHP
echo -e "${YELLOW}[3/8] 配置 PHP...${NC}"
PHP_INI="/etc/php/8.1/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i 's/^memory_limit = .*/memory_limit = 512M/' $PHP_INI
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' $PHP_INI
    sed -i 's/^post_max_size = .*/post_max_size = 100M/' $PHP_INI
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/' $PHP_INI
    sed -i 's/^;date.timezone =.*/date.timezone = Asia\/Shanghai/' $PHP_INI
    sed -i 's/^expose_php = On/expose_php = Off/' $PHP_INI
fi

# 配置 OPcache
echo -e "${YELLOW}[4/8] 配置 OPcache...${NC}"
cat > /etc/php/8.1/mods-available/opcache-custom.ini <<EOF
[opcache]
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.save_comments=1
opcache.jit=tracing
opcache.jit_buffer_size=64M
EOF

# 配置 MySQL
echo -e "${YELLOW}[5/8] 配置 MySQL...${NC}"
MYSQL_CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [ -f "$MYSQL_CNF" ]; then
    cat >> $MYSQL_CNF <<EOF

# MooTask 优化
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 500
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
EOF
    systemctl restart mysql
fi

# 配置 Redis
echo -e "${YELLOW}[6/8] 配置 Redis...${NC}"
REDIS_CONF="/etc/redis/redis.conf"
if [ -f "$REDIS_CONF" ]; then
    sed -i 's/^maxmemory .*/maxmemory 256mb/' $REDIS_CONF
    sed -i 's/^maxmemory-policy .*/maxmemory-policy allkeys-lru/' $REDIS_CONF
    sed -i 's/^# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' $REDIS_CONF
    systemctl restart redis-server
fi

# 创建项目目录
echo -e "${YELLOW}[7/8] 创建项目目录...${NC}"
mkdir -p /var/www/mootask
mkdir -p /var/backups/mootask
mkdir -p /var/log/mootask
chown -R www-data:www-data /var/www/mootask
chown -R www-data:www-data /var/backups/mootask
chown -R www-data:www-data /var/log/mootask

# 配置 swap（如果没有）
echo -e "${YELLOW}[8/8] 检查并配置 Swap...${NC}"
if [ $(free -m | awk '/^Swap:/ {print $2}') -eq 0 ]; then
    echo -e "${YELLOW}创建 2GB Swap 文件...${NC}"
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# 配置自动备份
echo -e "${YELLOW}配置每日自动备份...${NC}"
cat > /etc/cron.daily/mootask-backup <<EOF
#!/bin/bash
/var/www/mootask/scripts/backup.sh >> /var/log/mootask/backup.log 2>&1
EOF
chmod +x /etc/cron.daily/mootask-backup

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  服务器初始化完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}下一步：${NC}"
echo -e "  1. 将代码上传到 /var/www/mootask"
echo -e "  2. 运行部署脚本: cd /var/www/mootask && sudo bash deploy.sh"
echo ""
