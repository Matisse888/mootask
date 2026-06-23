<template>
  <div class="project-list-page">
    <div class="page-header">
      <div class="header-left">
        <h1>我的项目</h1>
        <el-radio-group v-model="filterType" size="small" class="filter-tabs">
          <el-radio-button label="all">全部</el-radio-button>
          <el-radio-button label="active">进行中</el-radio-button>
          <el-radio-button label="archived">已归档</el-radio-button>
        </el-radio-group>
      </div>

      <div class="header-right">
        <el-input
          v-model="keyword"
          placeholder="搜索项目..."
          prefix-icon="el-icon-search"
          clearable
          class="search-input"
          @input="handleSearch"
        />
        <el-button type="primary" icon="el-icon-plus" @click="showCreateDialog">
          新建项目
        </el-button>
      </div>
    </div>

    <div v-loading="loading" class="project-grid">
      <el-row :gutter="20">
        <el-col
          v-for="project in projectList"
          :key="project.id"
          :xs="24"
          :sm="12"
          :md="8"
          :lg="6"
        >
          <div class="project-card" @click="$router.push(`/projects/${project.id}`)">
            <div class="project-banner" :style="{ backgroundColor: project.color }">
              <div class="project-icon">
                <i :class="getIconClass(project.icon)"></i>
              </div>
              <div class="project-actions">
                <el-dropdown @command="handleCommand($event, project)">
                  <el-button type="text" icon="el-icon-more" class="more-btn" />
                  <el-dropdown-menu slot="dropdown">
                    <el-dropdown-item command="edit">
                      <i class="el-icon-edit"></i> 编辑
                    </el-dropdown-item>
                    <el-dropdown-item command="archive" v-if="!project.archived_at">
                      <i class="el-icon-folder-delete"></i> 归档
                    </el-dropdown-item>
                    <el-dropdown-item command="unarchive" v-else>
                      <i class="el-icon-folder-add"></i> 取消归档
                    </el-dropdown-item>
                    <el-dropdown-item command="delete" divided>
                      <i class="el-icon-delete" style="color: #f56c6c"></i> 删除
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </el-dropdown>
              </div>
            </div>

            <div class="project-content">
              <h3 class="project-name">{{ project.name }}</h3>
              <p class="project-desc">{{ project.desc || '暂无描述' }}</p>

              <div class="project-stats">
                <span><i class="el-icon-tickets"></i> {{ project.task_count }} 任务</span>
                <span><i class="el-icon-user"></i> {{ project.member_count }} 成员</span>
              </div>

              <div class="project-footer">
                <el-avatar
                  v-if="project.owner"
                  :size="24"
                  :src="project.owner.avatar"
                >
                  {{ project.owner.name?.charAt(0) }}
                </el-avatar>
                <span class="project-time">{{ formatTime(project.updated_at) }}</span>
              </div>
            </div>
          </div>
        </el-col>
      </el-row>

      <el-empty
        v-if="!loading && projectList.length === 0"
        :description="keyword ? '未找到匹配的项目' : '暂无项目'"
      >
        <el-button v-if="!keyword" type="primary" @click="showCreateDialog">创建第一个项目</el-button>
      </el-empty>
    </div>

    <!-- Pagination -->
    <div v-if="total > 0" class="pagination-container">
      <el-pagination
        :current-page="currentPage"
        :page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>

    <!-- Create Dialog -->
    <el-dialog
      title="创建项目"
      :visible.sync="createDialogVisible"
      width="500px"
      @close="resetForm"
    >
      <el-form ref="createForm" :model="createForm" :rules="rules" label-width="80px">
        <el-form-item label="项目名称" prop="name">
          <el-input v-model="createForm.name" placeholder="请输入项目名称" />
        </el-form-item>

        <el-form-item label="项目描述" prop="desc">
          <el-input
            v-model="createForm.desc"
            type="textarea"
            :rows="3"
            placeholder="请输入项目描述"
          />
        </el-form-item>

        <el-form-item label="颜色">
          <el-color-picker v-model="createForm.color" />
        </el-form-item>

        <el-form-item label="图标">
          <el-select v-model="createForm.icon" placeholder="选择图标">
            <el-option label="文件夹" value="folder">
              <i class="el-icon-folder-opened"></i> 文件夹
            </el-option>
            <el-option label="任务" value="task">
              <i class="el-icon-tickets"></i> 任务
            </el-option>
            <el-option label="图表" value="chart">
              <i class="el-icon-data-analysis"></i> 图表
            </el-option>
            <el-option label="代码" value="code">
              <i class="el-icon-code"></i> 代码
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>

      <span slot="footer" class="dialog-footer">
        <el-button @click="createDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="creating" @click="handleCreate">创建</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

export default {
  name: 'ProjectList',

  data() {
    return {
      loading: false,
      creating: false,
      filterType: 'all',
      keyword: '',
      currentPage: 1,
      pageSize: 12,
      total: 0,
      projectList: [],
      createDialogVisible: false,
      createForm: {
        name: '',
        desc: '',
        color: '#409EFF',
        icon: 'folder'
      },
      rules: {
        name: [
          { required: true, message: '请输入项目名称', trigger: 'blur' },
          { max: 100, message: '项目名称最多100个字符', trigger: 'blur' }
        ]
      }
    }
  },

  watch: {
    filterType() {
      this.currentPage = 1
      this.fetchProjects()
    }
  },

  async mounted() {
    await this.fetchProjects()
  },

  methods: {
    async fetchProjects() {
      this.loading = true
      try {
        const { projectApi } = await import('@/api')
        const params = {
          page: this.currentPage,
          page_size: this.pageSize,
          keyword: this.keyword || undefined,
          archived: this.filterType === 'archived' ? 1 : undefined
        }

        const response = await projectApi.lists(params)

        if (response.ret === 1) {
          this.projectList = response.data.list || []
          this.total = response.data.total || 0
        }
      } catch (error) {
        console.error('Failed to fetch projects:', error)
        this.$message.error('获取项目列表失败')
      } finally {
        this.loading = false
      }
    },

    handleSearch() {
      this.currentPage = 1
      this.fetchProjects()
    },

    handlePageChange(page) {
      this.currentPage = page
      this.fetchProjects()
    },

    showCreateDialog() {
      this.createDialogVisible = true
    },

    resetForm() {
      this.$refs.createForm?.resetFields()
      this.createForm = {
        name: '',
        desc: '',
        color: '#409EFF',
        icon: 'folder'
      }
    },

    async handleCreate() {
      try {
        await this.$refs.createForm.validate()

        this.creating = true

        const { projectApi } = await import('@/api')
        const response = await projectApi.create(this.createForm)

        if (response.ret === 1) {
          this.$message.success('项目创建成功')
          this.createDialogVisible = false
          this.resetForm()
          await this.fetchProjects()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        console.error('Failed to create project:', error)
      } finally {
        this.creating = false
      }
    },

    async handleCommand(command, project) {
      switch (command) {
        case 'edit':
          this.$router.push(`/projects/${project.id}`)
          break
        case 'archive':
          await this.handleArchive(project)
          break
        case 'unarchive':
          await this.handleUnarchive(project)
          break
        case 'delete':
          await this.handleDelete(project)
          break
      }
    },

    async handleArchive(project) {
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.archive(project.id)

        if (response.ret === 1) {
          this.$message.success('项目已归档')
          await this.fetchProjects()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        this.$message.error('归档失败')
      }
    },

    async handleUnarchive(project) {
      try {
        const { projectApi } = await import('@/api')
        const response = await projectApi.unarchive(project.id)

        if (response.ret === 1) {
          this.$message.success('项目已取消归档')
          await this.fetchProjects()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        this.$message.error('取消归档失败')
      }
    },

    async handleDelete(project) {
      try {
        await this.$confirm('确定要删除该项目吗？删除后不可恢复。', '删除确认', {
          type: 'warning'
        })

        const { projectApi } = await import('@/api')
        const response = await projectApi.delete(project.id)

        if (response.ret === 1) {
          this.$message.success('项目已删除')
          await this.fetchProjects()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        if (error !== 'cancel') {
          this.$message.error('删除失败')
        }
      }
    },

    getIconClass(icon) {
      const iconMap = {
        folder: 'el-icon-folder-opened',
        task: 'el-icon-tickets',
        chart: 'el-icon-data-analysis',
        code: 'el-icon-code'
      }
      return iconMap[icon] || 'el-icon-folder-opened'
    },

    formatTime(time) {
      if (!time) return ''
      return dayjs(time).fromNow()
    }
  }
}
</script>

<style lang="scss" scoped>
.project-list-page {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;

  h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
  }
}

.header-right {
  display: flex;
  gap: 12px;
}

.search-input {
  width: 250px;
}

.project-grid {
  min-height: 400px;
}

.project-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 20px;
  cursor: pointer;
  transition: transform 0.3s, box-shadow 0.3s;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }
}

.project-banner {
  height: 100px;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
}

.project-icon {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  backdrop-filter: blur(10px);
}

.project-actions {
  position: absolute;
  top: 12px;
  right: 12px;
}

.more-btn {
  color: white;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;

  &:hover {
    background: rgba(255, 255, 255, 0.3);
  }
}

.project-content {
  padding: 20px;
}

.project-name {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: var(--text-color);
}

.project-desc {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0 0 16px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 42px;
}

.project-stats {
  display: flex;
  gap: 20px;
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 16px;

  span {
    display: flex;
    align-items: center;
    gap: 4px;
  }
}

.project-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 16px;
  border-top: 1px solid var(--border-color);
}

.project-time {
  font-size: 12px;
  color: var(--text-secondary);
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}
</style>
