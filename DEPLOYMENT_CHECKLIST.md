# MooTask 部署检查清单

## 部署前检查 ☐

### 服务器环境 ☐
- [ ] 服务器系统已更新至最新版本
- [ ] 服务器时区已正确设置
- [ ] 服务器防火墙已配置（允许 80/443 端口）
- [ ] 服务器 DNS 已配置（如果使用域名）

### 必需软件 ☐
- [ ] PHP 8.0+ 已安装
- [ ] Composer 已安装
- [ ] Node.js 16+ 已安装
- [ ] NPM 已安装
- [ ] MySQL 8.0+ 已安装
- [ ] Redis 已安装
- [ ] Nginx 已安装

### PHP 扩展 ☐
- [ ] pdo_mysql
- [ ] mbstring
- [ ] exif
- [ ] pcntl
- [ ] bcmath
- [ ] gd
- [ ] zip
- [ ] curl
- [ ] xml
- [ ] json
- [ ] openssl

## 项目准备 ☐

### 代码部署 ☐
- [ ] 项目代码已上传到服务器
- [ ] Git 仓库已配置（如适用）
- [ ] 代码权限已设置（www-data:www-data）
- [ ] .env 文件已创建并配置

### 依赖安装 ☐
- [ ] Composer 依赖已安装
- [ ] Node 依赖已安装
- [ ] 前端资源已构建

### 环境变量 ☐
- [ ] APP_NAME 已配置
- [ ] APP_ENV=production 已设置
- [ ] APP_DEBUG=false 已设置
- [ ] APP_URL 已配置
- [ ] APP_KEY 已生成

### 数据库配置 ☐
- [ ] DB_CONNECTION 已配置
- [ ] DB_HOST 已配置
- [ ] DB_PORT 已配置
- [ ] DB_DATABASE 已创建
- [ ] DB_USERNAME 已配置
- [ ] DB_PASSWORD 已配置
- [ ] 数据库连接测试通过

### Redis 配置 ☐
- [ ] REDIS_HOST 已配置
- [ ] REDIS_PORT 已配置
- [ ] REDIS_PASSWORD 已配置（如使用）
- [ ] Redis 连接测试通过

### JWT 配置 ☐
- [ ] JWT_SECRET 已生成
- [ ] JWT_TTL 已配置

## 数据库设置 ☐

### 数据库迁移 ☐
- [ ] php artisan migrate 已运行
- [ ] 所有表已成功创建
- [ ] 外键约束已正确设置

### 初始数据 ☐
- [ ] 数据库 Seeders 已运行（如需要）
- [ ] 管理员账户已创建
- [ ] 测试数据已填充（如需要）

## Web 服务器配置 ☐

### Nginx 配置 ☐
- [ ] Nginx 配置文件已创建
- [ ] 网站根目录已正确设置
- [ ] PHP-FPM 配置已优化
- [ ] SSL 证书已配置（如使用 HTTPS）
- [ ] Nginx 配置测试通过
- [ ] Nginx 已重启

### PHP-FPM 配置 ☐
- [ ] PHP-FPM 进程数已优化
- [ ] OPcache 已启用
- [ ] 内存限制已调整
- [ ] 上传大小限制已调整

## 安全配置 ☐

### 文件权限 ☐
- [ ] storage 目录权限已设置为 775
- [ ] bootstrap/cache 目录权限已设置为 775
- [ ] 所有文件所有者已设置为 www-data
- [ ] 敏感文件已保护（.env）

### HTTPS 配置 ☐
- [ ] SSL 证书已申请
- [ ] SSL 证书已配置
- [ ] HTTP 自动跳转 HTTPS 已启用
- [ ] SSL 证书自动续期已配置

### 防火墙 ☐
- [ ] 80 端口已开放
- [ ] 443 端口已开放
- [ ] SSH 端口已开放
- [ ] 其他不必要的端口已关闭

### 安全设置 ☐
- [ ] 目录浏览已禁用
- [ ] .htaccess 或 Nginx 配置已优化
- [ ] X-Frame-Options 头已设置
- [ ] X-XSS-Protection 头已设置
- [ ] Content-Type-Options 头已设置

## 后台任务配置 ☐

### 定时任务 ☐
- [ ] Crontab 已配置
- [ ] schedule:run 命令已添加
- [ ] 定时任务测试通过

### 队列 Worker ☐
- [ ] Supervisor 已安装
- [ ] Laravel Queue Worker 配置已创建
- [ ] Supervisor 已重新加载
- [ ] Queue Worker 已启动
- [ ] Queue Worker 正在运行

### 日志管理 ☐
- [ ] 日志目录已创建
- [ ] 日志权限已设置
- [ ] 日志轮转已配置
- [ ] 错误报告已启用

## 性能优化 ☐

### 缓存配置 ☐
- [ ] 配置缓存已生成
- [ ] 路由缓存已生成
- [ ] 视图缓存已生成
- [ ] 缓存驱动器已配置

### 前端优化 ☐
- [ ] 前端资源已构建
- [ ] 静态资源已压缩
- [ ] Gzip 压缩已启用
- [ ] 浏览器缓存已配置

### 数据库优化 ☐
- [ ] 数据库索引已检查
- [ ] 查询缓存已配置（如适用）
- [ ] 慢查询日志已启用

## 功能测试 ☐

### 核心功能 ☐
- [ ] 首页可访问
- [ ] 用户注册功能正常
- [ ] 用户登录功能正常
- [ ] 密码重置功能正常
- [ ] JWT 认证正常

### API 功能 ☐
- [ ] API 健康检查通过 (/api/health)
- [ ] 项目列表接口正常
- [ ] 任务管理接口正常
- [ ] 文件上传接口正常
- [ ] 即时通讯接口正常

### 前端功能 ☐
- [ ] 页面导航正常
- [ ] 表单提交正常
- [ ] 文件上传正常
- [ ] 响应式布局正常
- [ ] 移动端显示正常

### WebSocket 功能 ☐
- [ ] WebSocket 连接正常
- [ ] 实时消息推送正常
- [ ] 通知功能正常

## 监控和日志 ☐

### 日志检查 ☐
- [ ] 应用日志正常记录
- [ ] 错误日志正常记录
- [ ] 访问日志正常记录
- [ ] 数据库日志正常记录

### 性能监控 ☐
- [ ] 响应时间正常
- [ ] 数据库查询正常
- [ ] 内存使用正常
- [ ] CPU 使用正常

## 备份和恢复 ☐

### 备份策略 ☐
- [ ] 自动备份已配置
- [ ] 代码备份已配置
- [ ] 数据库备份已配置
- [ ] 文件备份已配置
- [ ] 备份恢复测试通过

### 灾难恢复 ☐
- [ ] 恢复文档已准备
- [ ] 紧急联系人已记录
- [ ] 升级回滚方案已准备

## 部署完成 ☐

### 最终检查 ☐
- [ ] 所有服务正在运行
- [ ] HTTPS 证书有效
- [ ] 自动启动已配置
- [ ] 监控告警已配置
- [ ] 文档已更新

### 文档记录 ☐
- [ ] 部署文档已完成
- [ ] 环境配置文档已完成
- [ ] 账户凭据已安全存储
- [ ] 运维手册已完成

## 部署签名

- 部署日期: _________________
- 部署人员: _________________
- 部署版本: _________________
- 部署服务器: _________________
- 备注: _________________

---

## 快速命令参考

```bash
# 环境检查
./scripts/check-env.sh

# 部署（使用脚本）
./scripts/production-deploy.sh

# Docker 部署
docker-compose -f docker-compose.prod.yml up -d

# Docker 启动
./start-docker.sh start

# 数据库迁移
php artisan migrate

# 清除缓存
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 重启服务
sudo systemctl restart php-fpm
sudo systemctl restart nginx
sudo supervisorctl restart laravel-worker:*
```

---

**最后更新**: $(date)
