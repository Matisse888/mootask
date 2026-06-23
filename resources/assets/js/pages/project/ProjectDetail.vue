<template>
  <div class="project-detail-page">
    <div v-loading="loading" class="project-container">
      <!-- Header -->
      <div class="project-header" :style="{ backgroundColor: project.color }">
        <div class="header-content">
          <div class="header-info">
            <el-button type="text" icon="el-icon-arrow-left" class="back-btn" @click="$router.push('/projects')" />
            <div class="project-info">
              <h1>{{ project.name }}</h1>
              <p>{{ project.desc || '暂无描述' }}</p>
            </div>
          </div>

          <div class="header-actions">
            <el-button type="primary" icon="el-icon-plus" @click="showTaskDialog">
              新建任务
            </el-button>
            <el-dropdown @command="handleCommand">
              <el-button icon="el-icon-more"></el-button>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item command="settings">
                  <i class="el-icon-setting"></i> 项目设置
                </el-dropdown-item>
                <el-dropdown-item command="members">
                  <i class="el-icon-user"></i> 成员管理
                </el-dropdown-item>
                <el-dropdown-item command="archive" v-if="!project.archived_at">
                  <i class="el-icon-folder-delete"></i> 归档
                </el-dropdown-item>
                <el-dropdown-item command="unarchive" v-else>
                  <i class="el-icon-folder-add"></i> 取消归档
                </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </div>
        </div>
      </div>

      <!-- Kanban Board -->
      <div class="kanban-board" v-if="!loading">
        <div
          v-for="column in columns"
          :key="column.id"
          class="kanban-column"
        >
          <div class="column-header">
            <div class="column-title">
              <span class="column-color" :style="{ backgroundColor: column.color }"></span>
              {{ column.name }}
              <span class="column-count">{{ column.tasks?.length || 0 }}</span>
            </div>
            <el-dropdown @command="handleColumnCommand($event, column)">
              <el-button type="text" icon="el-icon-more" size="small" />
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item command="add">
                  <i class="el-icon-plus"></i> 添加任务
                </el-dropdown-item>
                <el-dropdown-item command="edit">
                  <i class="el-icon-edit"></i> 编辑列
                </el-dropdown-item>
                <el-dropdown-item command="delete" v-if="column.tasks?.length === 0">
                  <i class="el-icon-delete"></i> 删除列
                </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </div>

          <div class="column-content">
            <draggable
              v-model="column.tasks"
              group="tasks"
              item-key="id"
              class="task-list"
              @change="handleDragChange($event, column)"
            >
              <template #item="{ element: task }">
                <div class="task-card" @click="showTaskDetail(task)">
                  <div class="task-header">
                    <span class="task-type" :class="`type-${task.type}`">
                      {{ getTypeName(task.type) }}
                    </span>
                    <span class="task-priority" :class="`priority-${task.priority}`">
                      {{ getPriorityName(task.priority) }}
                    </span>
                  </div>

                  <h4 class="task-name">{{ task.name }}</h4>

                  <div v-if="task.labels?.length" class="task-labels">
                    <el-tag
                      v-for="tag in task.tags"
                      :key="tag.id"
                      size="mini"
                      :color="tag.color"
                      class="task-tag"
                    >
                      {{ tag.name }}
                    </el-tag>
                  </div>

                  <div class="task-footer">
                    <div class="task-meta">
                      <span v-if="task.due_date" class="task-date">
                        <i class="el-icon-calendar"></i>
                        {{ formatDate(task.due_date) }}
                      </span>
                      <span v-if="task.sub_task_count > 0" class="task-subtasks">
                        <i class="el-icon-document"></i>
                        {{ task.completed_sub_task_count }}/{{ task.sub_task_count }}
                      </span>
                    </div>

                    <div class="task-assignee">
                      <el-avatar
                        v-if="task.assignee"
                        :size="24"
                        :src="task.assignee.avatar"
                      >
                        {{ task.assignee.name?.charAt(0) }}
                      </el-avatar>
                    </div>
                  </div>
                </div>
              </template>
            </draggable>

            <div class="add-task-btn" @click="showTaskDialog(column.id)">
              <i class="el-icon-plus"></i>
              添加任务
            </div>
          </div>
        </div>

        <div class="add-column-btn" @click="showAddColumn">
          <i class="el-icon-plus"></i>
          添加列
        </div>
      </div>

      <el-empty v-if="!loading && columns.length === 0" description="暂无列">
        <el-button type="primary" @click="showAddColumn">添加列</el-button>
      </el-empty>
    </div>

    <!-- Task Dialog -->
    <el-dialog
      :title="taskDialogTitle"
      :visible.sync="taskDialogVisible"
      width="700px"
      class="task-dialog"
      @close="resetTaskForm"
    >
      <el-form ref="taskForm" :model="taskForm" :rules="taskRules" label-width="100px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="taskForm.name" placeholder="请输入任务名称" />
        </el-form-item>

        <el-form-item label="任务描述">
          <el-input
            v-model="taskForm.desc"
            type="textarea"
            :rows="4"
            placeholder="请输入任务描述"
          />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="指派给">
              <el-select
                v-model="taskForm.assignee_user_id"
                placeholder="选择成员"
                filterable
                clearable
              >
                <el-option
                  v-for="member in project.members"
                  :key="member.id"
                  :label="member.name"
                  :value="member.id"
                >
                  <div class="member-option">
                    <el-avatar :size="24" :src="member.avatar">
                      {{ member.name?.charAt(0) }}
                    </el-avatar>
                    <span>{{ member.name }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="优先级">
              <el-select v-model="taskForm.priority" placeholder="选择优先级">
                <el-option label="紧急" value="urgent" />
                <el-option label="高" value="high" />
                <el-option label="中" value="medium" />
                <el-option label="低" value="low" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="开始日期">
              <el-date-picker
                v-model="taskForm.start_date"
                type="date"
                placeholder="选择开始日期"
                value-format="yyyy-MM-dd"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item label="截止日期">
              <el-date-picker
                v-model="taskForm.due_date"
                type="date"
                placeholder="选择截止日期"
                value-format="yyyy-MM-dd"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <span slot="footer" class="dialog-footer">
        <el-button @click="taskDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="savingTask" @click="handleSaveTask">
          保存
        </el-button>
      </span>
    </el-dialog>

    <!-- Add Column Dialog -->
    <el-dialog
      title="添加列"
      :visible.sync="columnDialogVisible"
      width="400px"
      @close="columnForm = { name: '', color: '#909399' }"
    >
      <el-form :model="columnForm" label-width="80px">
        <el-form-item label="列名称">
          <el-input v-model="columnForm.name" placeholder="请输入列名称" />
        </el-form-item>

        <el-form-item label="颜色">
          <el-color-picker v-model="columnForm.color" />
        </el-form-item>
      </el-form>

      <span slot="footer" class="dialog-footer">
        <el-button @click="columnDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="savingColumn" @click="handleAddColumn">
          添加
        </el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import dayjs from 'dayjs'

export default {
  name: 'ProjectDetail',

  components: {
    draggable
  },

  data() {
    return {
      loading: false,
      project: {},
      columns: [],
      taskDialogVisible: false,
      taskDialogTitle: '新建任务',
      savingTask: false,
      taskForm: {
        name: '',
        desc: '',
        assignee_user_id: null,
        priority: 'medium',
        start_date: '',
        due_date: '',
        column_id: null
      },
      taskRules: {
        name: [
          { required: true, message: '请输入任务名称', trigger: 'blur' }
        ]
      },
      columnDialogVisible: false,
      savingColumn: false,
      columnForm: {
        name: '',
        color: '#909399'
      }
    }
  },

  async mounted() {
    await this.fetchProject()
  },

  methods: {
    async fetchProject() {
      this.loading = true
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.show(this.$route.params.id)

        if (response.ret === 1) {
          this.project = response.data
          this.columns = response.data.columns || []
        } else {
          this.$message.error(response.msg)
          this.$router.push('/projects')
        }
      } catch (error) {
        console.error('Failed to fetch project:', error)
        this.$message.error('获取项目详情失败')
      } finally {
        this.loading = false
      }
    },

    showTaskDialog(columnId = null) {
      this.taskDialogTitle = '新建任务'
      this.taskForm.column_id = columnId
      this.taskDialogVisible = true
    },

    showTaskDetail(task) {
      this.$message.info('任务详情页面开发中')
    },

    resetTaskForm() {
      this.$refs.taskForm?.resetFields()
      this.taskForm = {
        name: '',
        desc: '',
        assignee_user_id: null,
        priority: 'medium',
        start_date: '',
        due_date: '',
        column_id: null
      }
    },

    async handleSaveTask() {
      try {
        await this.$refs.taskForm.validate()

        this.savingTask = true

        const { taskApi } = await import('@/api')
        const response = await taskApi.create(this.project.id, this.taskForm)

        if (response.ret === 1) {
          this.$message.success('任务创建成功')
          this.taskDialogVisible = false
          this.resetTaskForm()
          await this.fetchProject()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        console.error('Failed to create task:', error)
      } finally {
        this.savingTask = false
      }
    },

    showAddColumn() {
      this.columnDialogVisible = true
    },

    async handleAddColumn() {
      if (!this.columnForm.name.trim()) {
        this.$message.warning('请输入列名称')
        return
      }

      this.savingColumn = true
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.createColumn(this.project.id, this.columnForm)

        if (response.ret === 1) {
          this.$message.success('列添加成功')
          this.columnDialogVisible = false
          await this.fetchProject()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        this.$message.error('添加列失败')
      } finally {
        this.savingColumn = false
      }
    },

    handleColumnCommand(command, column) {
      switch (command) {
        case 'add':
          this.showTaskDialog(column.id)
          break
        case 'edit':
          this.$message.info('编辑列功能开发中')
          break
        case 'delete':
          this.handleDeleteColumn(column)
          break
      }
    },

    async handleDeleteColumn(column) {
      try {
        await this.$confirm('确定要删除该列吗？', '删除确认', { type: 'warning' })

        const { projectApi } = await import('@/api')
        const response = await projectApi.deleteColumn(this.project.id, column.id)

        if (response.ret === 1) {
          this.$message.success('列已删除')
          await this.fetchProject()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        if (error !== 'cancel') {
          this.$message.error('删除列失败')
        }
      }
    },

    async handleDragChange(event, column) {
      if (event.added) {
        const task = event.added.element
        try {
          const { taskApi } = await import('@/api')
          await taskApi.move(this.project.id, task.id, {
            column_id: column.id,
            sort: event.added.newIndex
          })
        } catch (error) {
          console.error('Failed to move task:', error)
        }
      }
    },

    handleCommand(command) {
      switch (command) {
        case 'settings':
          this.$message.info('项目设置开发中')
          break
        case 'members':
          this.$message.info('成员管理开发中')
          break
        case 'archive':
          this.handleArchive()
          break
        case 'unarchive':
          this.handleUnarchive()
          break
      }
    },

    async handleArchive() {
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.archive(this.project.id)

        if (response.ret === 1) {
          this.$message.success('项目已归档')
          this.$router.push('/projects')
        }
      } catch (error) {
        this.$message.error('归档失败')
      }
    },

    async handleUnarchive() {
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.unarchive(this.project.id)

        if (response.ret === 1) {
          this.$message.success('项目已取消归档')
          await this.fetchProject()
        }
      } catch (error) {
        this.$message.error('取消归档失败')
      }
    },

    getTypeName(type) {
      const typeMap = {
        task: '任务',
        bug: 'Bug',
        improvement: '改进',
        epic: '史诗'
      }
      return typeMap[type] || '任务'
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

    formatDate(date) {
      return dayjs(date).format('MM/DD')
    }
  }
}
</script>

<style lang="scss" scoped>
.project-detail-page {
  margin: -20px;
}

.project-container {
  min-height: calc(100vh - 40px);
}

.project-header {
  padding: 30px;
  color: white;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-info {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.back-btn {
  color: white;
  font-size: 20px;
  margin-top: 4px;

  &:hover {
    background: rgba(255, 255, 255, 0.2);
  }
}

.project-info {
  h1 {
    font-size: 28px;
    margin: 0 0 8px 0;
  }

  p {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
  }
}

.header-actions {
  display: flex;
  gap: 12px;
}

.kanban-board {
  display: flex;
  gap: 16px;
  padding: 20px;
  overflow-x: auto;
  min-height: calc(100vh - 200px);
}

.kanban-column {
  flex: 0 0 300px;
  background: var(--bg-color);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
}

.column-header {
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.column-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 15px;
}

.column-color {
  width: 12px;
  height: 12px;
  border-radius: 3px;
}

.column-count {
  background: rgba(0, 0, 0, 0.1);
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
}

.column-content {
  flex: 1;
  padding: 0 12px 12px;
  overflow-y: auto;
}

.task-list {
  min-height: 100px;
}

.task-card {
  background: white;
  border-radius: 8px;
  padding: 14px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.task-type {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 3px;
  background: #f0f0f0;
  color: #666;

  &.type-bug {
    background: #fef0f0;
    color: #f56c6c;
  }

  &.type-improvement {
    background: #f0f9ff;
    color: #409eff;
  }

  &.type-epic {
    background: #f6ffed;
    color: #67c23a;
  }
}

.task-priority {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 3px;

  &.priority-low {
    background: #f4f4f5;
    color: #909399;
  }

  &.priority-medium {
    background: #ecf5ff;
    color: #409eff;
  }

  &.priority-high {
    background: #fdf6ec;
    color: #e6a23c;
  }

  &.priority-urgent {
    background: #fef0f0;
    color: #f56c6c;
  }
}

.task-name {
  font-size: 14px;
  font-weight: 500;
  margin: 0 0 10px 0;
  color: var(--text-color);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.task-labels {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 10px;
}

.task-tag {
  color: white;
  border: none;
}

.task-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.task-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: var(--text-secondary);

  span {
    display: flex;
    align-items: center;
    gap: 4px;
  }
}

.add-task-btn {
  padding: 12px;
  text-align: center;
  color: var(--text-secondary);
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.3s;

  &:hover {
    background: rgba(0, 0, 0, 0.05);
    color: var(--primary-color);
  }
}

.add-column-btn {
  flex: 0 0 300px;
  background: rgba(255, 255, 255, 0.5);
  border: 2px dashed var(--border-color);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.3s;

  &:hover {
    background: rgba(255, 255, 255, 0.8);
    border-color: var(--primary-color);
    color: var(--primary-color);
  }
}

.member-option {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
