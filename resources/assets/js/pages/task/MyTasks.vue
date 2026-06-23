<template>
  <div class="my-tasks-page">
    <div class="page-header">
      <h1>我的任务</h1>
    </div>

    <el-card class="filter-card">
      <div class="filters">
        <el-select v-model="filters.status" placeholder="任务状态" clearable @change="fetchTasks">
          <el-option label="全部" value="" />
          <el-option label="待办" value="todo" />
          <el-option label="进行中" value="in_progress" />
          <el-option label="已完成" value="done" />
        </el-select>

        <el-select v-model="filters.priority" placeholder="优先级" clearable @change="fetchTasks">
          <el-option label="全部" value="" />
          <el-option label="紧急" value="urgent" />
          <el-option label="高" value="high" />
          <el-option label="中" value="medium" />
          <el-option label="低" value="low" />
        </el-select>

        <el-select v-model="filters.project_id" placeholder="所属项目" clearable @change="fetchTasks">
          <el-option label="全部" value="" />
          <el-option
            v-for="project in projects"
            :key="project.id"
            :label="project.name"
            :value="project.id"
          />
        </el-select>
      </div>
    </el-card>

    <el-table
      v-loading="loading"
      :data="taskList"
      stripe
      style="width: 100%"
      @row-click="handleRowClick"
    >
      <el-table-column type="selection" width="55" />

      <el-table-column prop="name" label="任务" min-width="300">
        <template #default="{ row }">
          <div class="task-cell">
            <span class="task-priority" :class="`priority-${row.priority}`"></span>
            <div class="task-info">
              <div class="task-name">{{ row.name }}</div>
              <div class="task-project">
                <el-tag size="mini" :color="row.project?.color">{{ row.project?.name }}</el-tag>
                <el-tag size="mini" :type="getColumnType(row.column)">{{ row.column?.name }}</el-tag>
              </div>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column prop="status" label="状态" width="120">
        <template #default="{ row }">
          <el-tag :type="getStatusType(row.status)">
            {{ getStatusName(row.status) }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="priority" label="优先级" width="100">
        <template #default="{ row }">
          <span class="priority-text" :class="`priority-${row.priority}`">
            {{ getPriorityName(row.priority) }}
          </span>
        </template>
      </el-table-column>

      <el-table-column prop="due_date" label="截止日期" width="120">
        <template #default="{ row }">
          <span v-if="row.due_date" :class="{ 'overdue': isOverdue(row.due_date) }">
            {{ formatDate(row.due_date) }}
          </span>
          <span v-else class="text-muted">--</span>
        </template>
      </el-table-column>

      <el-table-column prop="progress" label="进度" width="150">
        <template #default="{ row }">
          <el-progress
            :percentage="row.progress"
            :status="getProgressStatus(row.status)"
            :stroke-width="8"
          />
        </template>
      </el-table-column>

      <el-table-column label="操作" width="100" fixed="right">
        <template #default="{ row }">
          <el-button type="text" size="small" @click.stop="handleComplete(row)">
            {{ row.status === 'done' ? '已完成' : '完成' }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div v-if="total > 0" class="pagination-container">
      <el-pagination
        :current-page="currentPage"
        :page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<script>
import dayjs from 'dayjs'

export default {
  name: 'MyTasks',

  data() {
    return {
      loading: false,
      taskList: [],
      projects: [],
      currentPage: 1,
      pageSize: 20,
      total: 0,
      filters: {
        status: '',
        priority: '',
        project_id: ''
      }
    }
  },

  async mounted() {
    await this.fetchProjects()
    await this.fetchTasks()
  },

  methods: {
    async fetchProjects() {
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.lists({ page: 1, page_size: 100 })

        if (response.ret === 1) {
          this.projects = response.data.list || []
        }
      } catch (error) {
        console.error('Failed to fetch projects:', error)
      }
    },

    async fetchTasks() {
      this.loading = true
      try {
        const { taskApi } = await import('@/api')
        const params = {
          page: this.currentPage,
          page_size: this.pageSize,
          ...this.filters
        }

        const response = await taskApi.myTasks(params)

        if (response.ret === 1) {
          this.taskList = response.data.list || []
          this.total = response.data.total || 0
        }
      } catch (error) {
        console.error('Failed to fetch tasks:', error)
        this.$message.error('获取任务列表失败')
      } finally {
        this.loading = false
      }
    },

    handlePageChange(page) {
      this.currentPage = page
      this.fetchTasks()
    },

    handleRowClick(row) {
      this.$router.push(`/projects/${row.project_id}`)
    },

    async handleComplete(task) {
      if (task.status === 'done') return

      try {
        const { taskApi } = await import('@/api')
        const response = await taskApi.update(task.project_id, task.id, {
          status: 'done'
        })

        if (response.ret === 1) {
          this.$message.success('任务已完成')
          await this.fetchTasks()
        }
      } catch (error) {
        this.$message.error('操作失败')
      }
    },

    getStatusName(status) {
      const statusMap = {
        todo: '待办',
        in_progress: '进行中',
        done: '已完成',
        cancelled: '已取消'
      }
      return statusMap[status] || status
    },

    getStatusType(status) {
      const typeMap = {
        todo: 'info',
        in_progress: 'warning',
        done: 'success',
        cancelled: 'info'
      }
      return typeMap[status] || 'info'
    },

    getColumnType(column) {
      if (!column) return 'info'

      const colorMap = {
        '#909399': 'info',
        '#409EFF': 'primary',
        '#67C23A': 'success'
      }
      return ''
    },

    getPriorityName(priority) {
      const priorityMap = {
        low: '低',
        medium: '中',
        high: '高',
        urgent: '紧急'
      }
      return priorityMap[priority] || '中'
    },

    getProgressStatus(status) {
      if (status === 'done') return 'success'
      return ''
    },

    formatDate(date) {
      return dayjs(date).format('MM/DD')
    },

    isOverdue(date) {
      return dayjs(date).isBefore(dayjs(), 'day')
    }
  }
}
</script>

<style lang="scss" scoped>
.my-tasks-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;

  h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
  }
}

.filter-card {
  margin-bottom: 20px;

  :deep(.el-card__body) {
    padding: 16px;
  }
}

.filters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.task-cell {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.task-priority {
  width: 4px;
  height: 40px;
  border-radius: 2px;
  margin-top: 2px;

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

.task-info {
  flex: 1;
}

.task-name {
  font-weight: 500;
  margin-bottom: 6px;
  color: var(--text-color);
}

.task-project {
  display: flex;
  gap: 6px;
}

.priority-text {
  font-size: 13px;

  &.priority-low {
    color: #909399;
  }

  &.priority-medium {
    color: #409eff;
  }

  &.priority-high {
    color: #e6a23c;
  }

  &.priority-urgent {
    color: #f56c6c;
  }
}

.overdue {
  color: #f56c6c;
}

.text-muted {
  color: var(--text-secondary);
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
</style>
