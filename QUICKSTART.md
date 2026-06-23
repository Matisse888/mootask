# MooTask 快速入门指南

## 🚀 快速启动（5分钟）

### 方式一：Docker 部署（推荐）

```bash
# 1. 克隆项目
git clone https://your-repo/mootask.git
cd mootask

# 2. 启动服务
chmod +x start-docker.sh
./start-docker.sh start

# 3. 访问应用
# 应用: http://localhost:8080
# API:  http://localhost:8080/api
```

### 方式二：手动部署

```bash
# 1. 克隆项目
git clone https://your-repo/mootask.git
cd mootask

# 2. 安装依赖
composer install
npm install

# 3. 配置环境
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 4. 配置数据库（在 .env 中设置数据库信息）

# 5. 初始化数据库
php artisan migrate
php artisan db:seed

# 6. 构建前端
npm run build

# 7. 启动服务
php artisan serve
```

访问 `http://localhost:8000`

---

## 📋 首次使用

### 1. 创建管理员账户

```bash
php artisan tinker

# 在 Tinker 中执行
$user = new \App\Models\User();
$user->username = 'admin';
$user->email = 'admin@example.com';
$user->password = bcrypt('YourPassword123!');
$user->name = 'Administrator';
$user->save();
```

### 2. 登录系统

- 访问应用首页
- 使用管理员账户登录
- 用户名: `admin`
- 密码: `YourPassword123!`

### 3. 创建第一个项目

1. 点击侧边栏 "项目"
2. 点击 "新建项目"
3. 填写项目名称和描述
4. 添加项目成员
5. 配置项目列（如：待办、进行中、已完成）
6. 创建项目标签
7. 开始添加任务

---

## 🔧 常用命令

### 应用命令

```bash
# 启动开发服务器
php artisan serve

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 优化命令
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 数据库命令
php artisan migrate              # 运行迁移
php artisan migrate:rollback     # 回滚迁移
php artisan migrate:fresh       # 清空并重新运行迁移
php artisan db:seed             # 填充数据
php artisan tinker              # 交互式 PHP 控制台
```

### 前端命令

```bash
# 开发模式
npm run dev

# 构建生产版本
npm run build

# 代码检查
npm run lint

# 运行测试
npm run test
```

### Docker 命令

```bash
# 启动
docker-compose up -d

# 停止
docker-compose down

# 查看日志
docker-compose logs -f app

# 进入容器
docker-compose exec app bash

# 重新构建
docker-compose up -d --build
```

---

## 📁 目录结构

```
mootask/
├── app/                    # 应用代码
│   ├── Console/           # 命令行
│   ├── Http/              # HTTP 层
│   │   ├── Controllers/  # 控制器
│   │   └── Middleware/    # 中间件
│   ├── Models/            # 数据模型
│   ├── Module/            # 业务模块
│   └── Providers/         # 服务提供者
├── bootstrap/            # 框架引导
├── config/               # 配置文件
├── database/             # 数据库相关
│   ├── migrations/       # 数据迁移
│   └── seeders/          # 数据填充
├── docker/               # Docker 配置
├── docs/                 # 文档
├── public/               # 公共资源
├── resources/            # 前端资源
│   └── assets/           # 前端资产
├── routes/               # 路由定义
├── scripts/              # 脚本工具
├── storage/              # 存储文件
├── tests/                # 测试文件
├── .env                  # 环境配置
├── .env.example          # 环境配置模板
└── composer.json         # PHP 依赖
```

---

## 🔐 默认配置

### 数据库
- Host: `127.0.0.1`
- Port: `3306`
- Database: `mootask`
- Username: `mootask`
- Password: `your_password`

### Redis
- Host: `127.0.0.1`
- Port: `6379`
- Password: `null`

### 应用
- Port: `8000`
- Debug: `true`（开发环境）
- Timezone: `UTC`

---

## 🌐 API 端点

### 认证接口
- `POST /api/auth/register` - 用户注册
- `POST /api/auth/login` - 用户登录
- `POST /api/auth/logout` - 用户登出
- `POST /api/auth/refresh` - 刷新令牌

### 用户接口
- `GET /api/user/info` - 获取用户信息
- `POST /api/user/update` - 更新用户信息
- `GET /api/user/list` - 获取用户列表

### 项目接口
- `GET /api/project/lists` - 获取项目列表
- `POST /api/project/create` - 创建项目
- `GET /api/project/{id}` - 获取项目详情
- `POST /api/project/{id}` - 更新项目

### 任务接口
- `GET /api/task/my` - 获取我的任务
- `POST /api/task/create/{projectId}` - 创建任务
- `POST /api/task/{projectId}/{id}` - 更新任务

### 即时通讯接口
- `GET /api/dialog/lists` - 获取会话列表
- `POST /api/dialog/create` - 创建会话
- `POST /api/dialog/{id}/message` - 发送消息

### 文件接口
- `POST /api/file/upload` - 上传文件
- `GET /api/file/list` - 获取文件列表
- `GET /api/file/{id}/download` - 下载文件

### 健康检查
- `GET /api/health` - API 健康检查

---

## 🐛 故障排除

### 问题：页面空白
```bash
php artisan config:clear
php artisan cache:clear
npm run build
```

### 问题：数据库连接失败
```bash
# 检查 .env 配置
cat .env | grep DB_

# 测试数据库连接
php artisan migrate:status
```

### 问题：权限错误
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 问题：API 返回 500
```bash
# 查看日志
tail -f storage/logs/laravel.log

# 启用调试
# 在 .env 中设置 APP_DEBUG=true
```

### 问题：前端资源 404
```bash
npm run build
# 确保 public/assets 目录存在
```

---

## 📞 获取帮助

- 文档: 查看 `/docs` 目录
- 部署指南: 查看 `DEPLOYMENT_GUIDE.md`
- 常见问题: 查看 `DEPLOYMENT_CHECKLIST.md`
- 日志: 查看 `storage/logs/laravel.log`

---

## 🔄 更新和升级

### 更新代码
```bash
git pull origin main
composer update
npm update
php artisan migrate
npm run build
php artisan config:cache
php artisan route:cache
```

### Docker 更新
```bash
docker-compose pull
docker-compose up -d --build
docker-compose exec app php artisan migrate
```

---

**版本**: 1.0.0
**最后更新**: $(date)
