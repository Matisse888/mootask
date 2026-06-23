# MooTask - 项目任务管理工具

> 一个基于 Laravel + Vue.js 的现代化项目任务管理工具

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-8.x-red.svg)](https://laravel.com)
[![Vue Version](https://img.shields.io/badge/Vue-2.6+-green.svg)](https://vuejs.org)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## 项目简介

MooTask 是一个完整的项目任务管理工具，提供：
- 📋 项目管理（项目、看板、成员管理）
- ✅ 任务管理（CRUD、拖拽看板、子任务）
- 💬 即时通讯（私聊、群聊、文件传输）
- 📁 文件管理（上传、下载、预览）
- 👥 团队协作（成员、权限、通知）

## 技术栈

### 后端
- **Laravel 8.x** - PHP Web 框架
- **PHP 8.1+** - 编程语言
- **MySQL 8.0** - 数据库
- **Redis 7** - 缓存
- **JWT** - 身份认证
- **Swoole + LaravelS** - WebSocket 服务
- **Manticore** - 搜索引擎

### 前端
- **Vue.js 2.6** - JavaScript 框架
- **Vue Router** - 路由管理
- **Vuex** - 状态管理
- **Element UI** - UI 组件库
- **Vite** - 构建工具
- **Axios** - HTTP 客户端
- **Vue Draggable** - 拖拽组件

## 项目结构

```
mootask/
├── app/                    # Laravel 应用核心
│   ├── Console/           # 控制台命令
│   ├── Exceptions/        # 异常处理
│   ├── Http/              # HTTP 层
│   │   ├── Controllers/   # 控制器
│   │   ├── Middleware/    # 中间件
│   │   └── Kernel.php     # HTTP 内核
│   ├── Models/            # 数据模型
│   ├── Module/            # 业务模块
│   └── Providers/         # 服务提供者
├── bootstrap/              # 启动文件
├── config/                 # 配置文件
├── database/              # 数据库
│   ├── factories/         # 模型工厂
│   ├── migrations/        # 数据库迁移
│   └── seeders/           # 数据填充
├── docker/                # Docker 配置
│   ├── nginx/             # Nginx 配置
│   ├── php/               # PHP 配置
│   ├── mysql/             # MySQL 配置
│   └── supervisor/        # Supervisor 配置
├── public/                # Web 入口
├── resources/             # 资源文件
│   ├── assets/            # 前端源码
│   └── views/             # Blade 模板
├── routes/                # 路由
├── scripts/               # 脚本文件
│   ├── backup.sh          # 备份脚本
│   ├── deploy-update.sh   # 更新脚本
│   ├── init-server.sh     # 服务器初始化
│   ├── start-dev.sh       # 启动开发环境
│   └── test-api.sh        # API 测试
├── storage/               # 存储目录
├── tests/                 # 测试文件
│   ├── Feature/           # 功能测试
│   └── Unit/              # 单元测试
├── docker-compose.yml     # Docker Compose
├── Dockerfile             # 生产环境 Docker 镜像
├── Dockerfile.dev         # 开发环境 Docker 镜像
├── deploy.sh              # 一键部署脚本
└── README.md
```

## 快速开始

### 方式一：使用 Docker（推荐）

#### 1. 启动开发环境

```bash
# 克隆项目
git clone <repository-url>
cd mootask

# 启动所有服务
docker-compose up -d

# 运行数据库迁移
docker-compose exec app php artisan migrate

# 安装前端依赖
docker-compose exec app npm install
docker-compose exec app npm run dev

# 访问应用
# 前端：http://localhost:8080
# API：http://localhost:8080/api
```

#### 2. 一键启动脚本

```bash
chmod +x scripts/start-dev.sh
./scripts/start-dev.sh
```

### 方式二：本地安装

#### 1. 环境要求
- PHP 8.1+
- Composer
- MySQL 5.7+ / 8.0
- Redis 6.0+
- Node.js 16+
- npm 8+

#### 2. 安装步骤

```bash
# 克隆项目
git clone <repository-url>
cd mootask

# 安装 PHP 依赖
composer install

# 复制环境配置
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 配置数据库（编辑 .env 文件）
# DB_DATABASE=mootask
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 运行迁移
php artisan migrate --seed

# 创建存储链接
php artisan storage:link

# 安装前端依赖
npm install

# 启动后端
php artisan serve

# 启动前端（新终端）
npm run dev
```

访问：
- 后端 API：http://localhost:8000
- 前端开发：http://localhost:3000

## 默认账号

```
管理员：
邮箱：admin@mootask.com
密码：admin123

测试账号：
邮箱：test@mootask.com
密码：test123
```

## 部署到 Ubuntu 服务器

### 一键部署

```bash
# 上传项目到服务器
scp -r ./* root@your-server-ip:/var/www/mootask/

# SSH 到服务器
ssh root@your-server-ip

# 进入项目目录
cd /var/www/mootask

# 初始化服务器（仅首次）
chmod +x scripts/init-server.sh
bash scripts/init-server.sh

# 一键部署
chmod +x deploy.sh
./deploy.sh
```

### 详细部署文档

查看 [Ubuntu 部署文档](docs/UBUNTU_DEPLOY.md)

## API 文档

### 认证接口

```
POST   /api/auth/register    # 用户注册
POST   /api/auth/login       # 用户登录
POST   /api/auth/logout      # 退出登录
POST   /api/auth/refresh     # 刷新令牌
```

### 用户接口

```
GET    /api/user/info        # 获取当前用户信息
POST   /api/user/update      # 更新用户信息
POST   /api/user/password    # 修改密码
GET    /api/user/list        # 用户列表
GET    /api/user/search      # 搜索用户
GET    /api/user/departments # 部门列表
```

### 项目接口

```
GET    /api/project/lists            # 项目列表
POST   /api/project/create           # 创建项目
GET    /api/project/{id}             # 项目详情
POST   /api/project/{id}             # 更新项目
DELETE /api/project/{id}             # 删除项目
POST   /api/project/{id}/archive     # 归档项目
POST   /api/project/{id}/member/add  # 添加成员
POST   /api/project/{id}/column/create  # 创建列
```

### 任务接口

```
POST   /api/task/create/{projectId}        # 创建任务
GET    /api/task/{projectId}/{id}          # 任务详情
POST   /api/task/{projectId}/{id}          # 更新任务
DELETE /api/task/{projectId}/{id}          # 删除任务
POST   /api/task/{projectId}/{id}/move     # 移动任务
GET    /api/task/my                         # 我的任务
```

### 对话接口

```
GET    /api/dialog/lists                    # 对话列表
POST   /api/dialog/create                   # 创建对话
GET    /api/dialog/{id}/messages            # 消息列表
POST   /api/dialog/{id}/message             # 发送消息
```

### 文件接口

```
POST   /api/file/upload                     # 上传文件
GET    /api/file/list                       # 文件列表
GET    /api/file/{id}/download              # 下载文件
GET    /api/file/{id}/preview               # 预览文件
```

## 测试

### 运行单元测试
```bash
php artisan test
```

### API 集成测试
```bash
chmod +x scripts/test-api.sh
./scripts/test-api.sh
```

## 常用命令

```bash
# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 重新生成缓存（生产环境）
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 数据库相关
php artisan migrate              # 运行迁移
php artisan migrate:rollback     # 回滚迁移
php artisan db:seed              # 填充数据
php artisan migrate:fresh --seed # 重建数据库

# 创建模型
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:migration create_table_name
```

## 性能优化

- **OPcache** - PHP 字节码缓存
- **Redis** - 会话和缓存存储
- **CDN** - 静态资源加速
- **Gzip** - Nginx 启用 Gzip 压缩
- **数据库索引** - 关键字段建立索引

## 安全建议

1. 修改所有默认密码
2. 启用 HTTPS（Let's Encrypt）
3. 配置防火墙规则
4. 定期更新系统和依赖
5. 启用 fail2ban
6. 配置自动备份
7. 监控服务器状态

## 开发规范

查看项目代码规范 [STANDARDS.md](.uploads/597c8096-e181-4693-b82e-eccf7cf03733_STANDARDS.md)
查看 CI/CD 配置 [CICD.md](.uploads/c69f09c6-d9c8-4469-b865-77e5203c8dc7_CICD.md)

## 许可证

MIT License

## 联系方式

- 项目主页：https://github.com/mootask/mootask
- 问题反馈：https://github.com/mootask/mootask/issues
- 邮箱：team@mootask.com
