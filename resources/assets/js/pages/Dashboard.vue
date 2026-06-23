<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h1>欢迎回来，{{ currentUser?.name }}</h1>
      <p class="subtitle">这是您的工作台</p>
    </div>

    <!-- Stats Cards -->
    <el-row :gutter="20" class="stats-row">
      <el-col :xs="24" :sm="12" :md="6">
        <div class="stat-card stat-projects">
          <div class="stat-icon">
            <i class="el-icon-folder-opened"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.projects }}</div>
            <div class="stat-label">我的项目</div>
          </div>
        </div>
      </el-col>

      <el-col :xs="24" :sm="12" :md="6">
        <div class="stat-card stat-tasks">
          <div class="stat-icon">
            <i class="el-icon-tickets"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.tasks }}</div>
            <div class="stat-label">进行中任务</div>
          </div>
        </div>
      </el-col>

      <el-col :xs="24" :sm="12" :md="6">
        <div class="stat-card stat-done">
          <div class="stat-icon">
            <i class="el-icon-circle-check"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.completed }}</div>
            <div class="stat-label">已完成</div>
          </div>
        </div>
      </el-col>

      <el-col :xs="24" :sm="12" :md="6">
        <div class="stat-card stat-messages">
          <div class="stat-icon">
            <i class="el-icon-chat-line-round"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.messages }}</div>
            <div class="stat-label">未读消息</div>
          </div>
        </div>
      </el-col>
    </el-row>

    <el-row :gutter="20">
      <!-- Recent Projects -->
      <el-col :xs="24" :lg="16">
        <el-card class="dashboard-card">
          <div slot="header" class="card-header">
            <span>最近项目</span>
            <el-button type="text" @click="$router.push('/projects')">查看全部</el-button>
          </div>

          <div v-loading="loadingProjects" class="project-list">
            <div
              v-for="project in recentProjects"
              :key="project.id"
              class="project-item"
              @click="$router.push(`/projects/${project.id}`)"
            >
              <div class="project-icon" :style="{ backgroundColor: project.color }">
                <i class="el-icon-folder-opened"></i>
              </div>
              <div class="project-info">
                <div class="project-name">{{ project.name }}</div>
                <div class="project-meta">
                  {{ project.task_count }} 个任务 · {{ project.member_count }} 个成员
                </div>
              </div>
              <div class="project-arrow">
                <i class="el-icon-arrow-right"></i>
              </div>
            </div>

            <el-empty v-if="!loadingProjects && recentProjects.length === 0" description="暂无项目">
              <el-button type="primary" @click="$router.push('/projects')">创建项目</el-button>
            </el-empty>
          </div>
        </el-card>
      </el-col>

      <!-- My Tasks -->
      <el-col :xs="24" :lg="8">
        <el-card class="dashboard-card">
          <div slot="header" class="card-header">
            <span>我的任务</span>
            <el-button type="text" @click="$router.push('/my-tasks')">查看全部</el-button>
          </div>

          <div v-loading="loadingTasks" class="task-list">
            <div v-for="task in recentTasks" :key="task.id" class="task-item">
              <div class="task-priority" :class="`priority-${task.priority}`"></div>
              <div class="task-content">
                <div class="task-name">{{ task.name }}</div>
                <div class="task-project">
                  <el-tag size="mini" :color="task.project?.color">{{ task.project?.name }}</el-tag>
                </div>
              </div>
            </div>

            <el-empty v-if="!loadingTasks && recentTasks.length === 0" description="暂无任务" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="mt-20">
      <!-- Recent Dialogs -->
      <el-col :xs="24" :lg="12">
        <el-card class="dashboard-card">
          <div slot="header" class="card-header">
            <span>最近对话</span>
            <el-button type="text" @click="$router.push('/messages')">查看全部</el-button>
          </div>

          <div v-loading="loadingDialogs" class="dialog-list">
            <div
              v-for="dialog in recentDialogs"
              :key="dialog.id"
              class="dialog-item"
              @click="$router.push(`/messages/${dialog.id}`)"
            >
              <el-avatar :size="40" :src="dialog.avatar">
                {{ dialog.name?.charAt(0) }}
              </el-avatar>
              <div class="dialog-info">
                <div class="dialog-name">{{ dialog.name }}</div>
                <div class="dialog-preview">{{ dialog.last_message?.content }}</div>
              </div>
              <div class="dialog-meta">
                <div v-if="dialog.unread_count > 0" class="unread-badge">{{ dialog.unread_count }}</div>
                <div class="dialog-time">{{ formatTime(dialog.last_msg_at) }}</div>
              </div>
            </div>

            <el-empty v-if="!loadingDialogs && recentDialogs.length === 0" description="暂无对话" />
          </div>
        </el-card>
      </el-col>

      <!-- Activity -->
      <el-col :xs="24" :lg="12">
        <el-card class="dashboard-card">
          <div slot="header" class="card-header">
            <span>快捷操作</span>
          </div>

          <div class="quick-actions">
            <el-button type="primary" icon="el-icon-plus" @click="$router.push('/projects')">
              新建项目
            </el-button>
            <el-button type="success" icon="el-icon-folder-add" @click="$router.push('/projects')">
              创建任务
            </el-button>
            <el-button type="info" icon="el-icon-chat-dot-round" @click="$router.push('/messages')">
              发消息
            </el-button>
            <el-button type="warning" icon="el-icon-upload" @click="$router.push('/files')">
              上传文件
            </el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

export default {
  name: 'Dashboard',

  data() {
    return {
      loadingProjects: false,
      loadingTasks: false,
      loadingDialogs: false,
      recentProjects: [],
      recentTasks: [],
      recentDialogs: [],
      stats: {
        projects: 0,
        tasks: 0,
        completed: 0,
        messages: 0
      }
    }
  },

  computed: {
    currentUser() {
      return this.$store.getters.currentUser
    }
  },

  async mounted() {
    await this.fetchData()
  },

  methods: {
    async fetchData() {
      await Promise.all([
        this.fetchProjects(),
        this.fetchTasks(),
        this.fetchDialogs()
      ])
    },

    async fetchProjects() {
      this.loadingProjects = true
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.lists({ page: 1, page_size: 5 })

        if (response.ret === 1) {
          this.recentProjects = response.data.list || []
          this.stats.projects = response.data.total || 0
        }
      } catch (error) {
        console.error('Failed to fetch projects:', error)
      } finally {
        this.loadingProjects = false
      }
    },

    async fetchTasks() {
      this.loadingTasks = true
      try {
        const { taskApi } = await import('@/api')
        const response = await taskApi.myTasks({ status: 'in_progress', page: 1, page_size: 5 })

        if (response.ret === 1) {
          this.recentTasks = response.data.list || []
          this.stats.tasks = response.data.total || 0
        }
      } catch (error) {
        console.error('Failed to fetch tasks:', error)
      } finally {
        this.loadingTasks = false
      }
    },

    async fetchDialogs() {
      this.loadingDialogs = true
      try {
        const { dialogApi } = await import('@/api')
        const response = await dialogApi.lists()

        if (response.ret === 1) {
          this.recentDialogs = response.data.slice(0, 5) || []
          this.stats.messages = response.data.reduce((sum, d) => sum + (d.unread_count || 0), 0)
        }
      } catch (error) {
        console.error('Failed to fetch dialogs:', error)
      } finally {
        this.loadingDialogs = false
      }
    },

    formatTime(time) {
      if (!time) return ''
      return dayjs(time).fromNow()
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  margin-bottom: 30px;

  h1 {
    font-size: 28px;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 8px;
  }

  .subtitle {
    color: var(--text-secondary);
    font-size: 14px;
  }
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s, box-shadow 0.3s;
  cursor: pointer;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  }
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
}

.stat-projects .stat-icon {
  background: linear-gradient(135deg, #409eff 0%, #66b1ff 100%);
}

.stat-tasks .stat-icon {
  background: linear-gradient(135deg, #e6a23c 0%, #ebb563 100%);
}

.stat-done .stat-icon {
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
}

.stat-messages .stat-icon {
  background: linear-gradient(135deg, #909399 0%, #a6a9ad 100%);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-color);
  line-height: 1.2;
}

.stat-label {
  font-size: 14px;
  color: var(--text-secondary);
  margin-top: 4px;
}

.dashboard-card {
  border-radius: 12px;
  margin-bottom: 20px;

  :deep(.el-card__header) {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
  }

  :deep(.el-card__body) {
    padding: 0;
  }
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.project-list {
  padding: 10px;
}

.project-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s;

  &:hover {
    background: var(--bg-color);
  }
}

.project-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
}

.project-info {
  flex: 1;
}

.project-name {
  font-weight: 500;
  color: var(--text-color);
  margin-bottom: 4px;
}

.project-meta {
  font-size: 13px;
  color: var(--text-secondary);
}

.project-arrow {
  color: var(--text-secondary);
}

.task-list {
  padding: 10px;
}

.task-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s;

  &:hover {
    background: var(--bg-color);
  }
}

.task-priority {
  width: 4px;
  height: 36px;
  border-radius: 2px;

  &.priority-low {
    background: #909399;
  }

  &.priority-medium {
    background: #409eff;
  }

  &.priority-high {
    background: #e6a23c;
  }

  &.priority-urgent {
    background: #f56c6c;
  }
}

.task-content {
  flex: 1;
}

.task-name {
  font-weight: 500;
  color: var(--text-color);
  margin-bottom: 6px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.dialog-list {
  padding: 10px;
}

.dialog-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s;

  &:hover {
    background: var(--bg-color);
  }
}

.dialog-info {
  flex: 1;
  overflow: hidden;
}

.dialog-name {
  font-weight: 500;
  color: var(--text-color);
  margin-bottom: 4px;
}

.dialog-preview {
  font-size: 13px;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dialog-meta {
  text-align: right;
}

.unread-badge {
  background: var(--primary-color);
  color: white;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
  margin-bottom: 4px;
}

.dialog-time {
  font-size: 12px;
  color: var(--text-secondary);
}

.quick-actions {
  padding: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;

  .el-button {
    flex: 1;
    min-width: 120px;
  }
}
</style>
