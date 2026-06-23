# MooTask 部署包清单

## 文档文件

### 部署指南
- **DEPLOYMENT_GUIDE.md** - 完整的部署指南文档
  - 包含环境要求、快速部署、详细步骤、配置说明、数据库设置、常见问题等
  - 适合生产环境部署参考

### 快速入门
- **QUICKSTART.md** - 快速入门指南
  - 5分钟快速启动指南
  - 常用命令参考
  - 故障排除
  - API 端点说明

### 部署检查清单
- **DEPLOYMENT_CHECKLIST.md** - 部署检查清单
  - 部署前检查
  - 配置检查
  - 功能测试
  - 安全配置
  - 快速命令参考

## 脚本文件

### 部署脚本
- **scripts/production-deploy.sh** - 生产环境自动化部署脚本
  - 自动检查系统依赖
  - 一键部署代码
  - 自动配置数据库
  - 设置定时任务
  - 配置队列 Worker

- **scripts/check-env.sh** - 环境检查脚本
  - 检查 PHP、Composer、Node.js
  - 检查数据库连接
  - 检查 Docker 环境
  - 检查项目文件
  - 检查环境配置

- **scripts/verify-deploy.sh** - 部署验证脚本
  - 验证 API 健康状态
  - 验证数据库连接
  - 验证 Redis 连接
  - 验证前端页面
  - 生成测试请求

### Docker 脚本
- **start-docker.sh** - Docker 环境快速启动脚本
  - 支持 start/stop/restart
  - 自动配置环境变量
  - 初始化数据库
  - 构建前端资源
  - 查看服务状态

## 配置文件

### 环境配置模板
- **.env.production** - 生产环境配置模板
  - 完整的生产环境变量配置
  - 数据库、Redis、JWT 等配置
  - 邮件、缓存、队列等配置

- **.env.docker** - Docker 环境配置模板
  - Docker Compose 环境变量配置
  - 容器化部署配置

### Docker 配置
- **docker-compose.prod.yml** - Docker 生产环境配置
  - 生产级别的 Docker Compose 配置
  - 包含 MySQL、Redis、PHP、Nginx
  - WebSocket、队列 Worker、定时任务
  - 健康检查、自动重启

- **docker/nginx/nginx.prod.conf** - Nginx 生产环境配置模板
  - SSL/TLS 配置
  - 安全头配置
  - Gzip 压缩配置
  - 静态资源缓存

## 使用说明

### 快速开始

1. **阅读文档**
   ```bash
   # 查看快速入门
   cat QUICKSTART.md

   # 查看完整部署指南
   cat DEPLOYMENT_GUIDE.md

   # 查看部署检查清单
   cat DEPLOYMENT_CHECKLIST.md
   ```

2. **检查环境**
   ```bash
   # 运行环境检查
   ./scripts/check-env.sh
   ```

3. **部署应用**

   **方式一：使用 Docker**
   ```bash
   # 快速启动
   chmod +x start-docker.sh
   ./start-docker.sh start

   # 或使用生产配置
   docker-compose -f docker-compose.prod.yml up -d
   ```

   **方式二：手动部署**
   ```bash
   # 使用部署脚本
   chmod +x scripts/production-deploy.sh
   sudo ./scripts/production-deploy.sh

   # 或手动部署
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   php artisan migrate
   npm run build
   ```

4. **验证部署**
   ```bash
   # 运行部署验证
   chmod +x scripts/verify-deploy.sh
   ./scripts/verify-deploy.sh
   ```

### 部署流程

1. **准备阶段**
   - 阅读 DEPLOYMENT_GUIDE.md
   - 运行 check-env.sh 检查环境
   - 配置服务器环境

2. **部署阶段**
   - 选择部署方式（Docker 或 手动）
   - 使用对应的部署脚本
   - 配置数据库和环境变量

3. **验证阶段**
   - 使用 verify-deploy.sh 验证
   - 使用 DEPLOYMENT_CHECKLIST.md 检查
   - 测试核心功能

4. **上线阶段**
   - 配置 HTTPS（生产环境）
   - 配置定时任务
   - 配置队列 Worker
   - 设置备份策略

## 文件统计

- 文档文件: 3 个
- 部署脚本: 3 个
- Docker 脚本: 1 个
- 验证脚本: 1 个
- 环境配置: 2 个
- Docker 配置: 1 个
- Nginx 配置: 1 个

**总计**: 12 个新文件

## 技术支持

- 详细文档: DEPLOYMENT_GUIDE.md
- 快速入门: QUICKSTART.md
- 检查清单: DEPLOYMENT_CHECKLIST.md
- 应用日志: storage/logs/laravel.log
- Nginx 日志: /var/log/nginx/mootask_access/error.log

---

**版本**: 1.0.0
**创建日期**: $(date)
**包含内容**: 完整的 MooTask 部署包
