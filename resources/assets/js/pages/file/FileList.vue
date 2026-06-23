<template>
  <div class="file-list-page">
    <div class="page-header">
      <h1>文件管理</h1>
    </div>

    <el-card class="filter-card">
      <div class="filters">
        <el-select v-model="filters.type" placeholder="文件类型" clearable @change="fetchFiles">
          <el-option label="全部" value="" />
          <el-option label="图片" value="image" />
          <el-option label="文档" value="document" />
          <el-option label="视频" value="video" />
          <el-option label="音频" value="audio" />
        </el-select>

        <el-select v-model="filters.project_id" placeholder="所属项目" clearable @change="fetchFiles">
          <el-option label="全部" value="" />
          <el-option
            v-for="project in projects"
            :key="project.id"
            :label="project.name"
            :value="project.id"
          />
        </el-select>

        <div class="storage-info">
          <span>已使用: {{ totalSizeFormatted }}</span>
        </div>
      </div>
    </el-card>

    <el-button type="primary" icon="el-icon-upload" @click="handleUpload" class="upload-btn">
      上传文件
    </el-button>

    <div v-loading="loading" class="file-grid">
      <el-row :gutter="20">
        <el-col
          v-for="file in fileList"
          :key="file.id"
          :xs="24"
          :sm="12"
          :md="8"
          :lg="6"
        >
          <div class="file-card" @click="handlePreview(file)">
            <div class="file-preview">
              <img v-if="isImage(file.mime_type)" :src="file.url" :alt="file.original_name" />
              <i v-else :class="getFileIcon(file.mime_type)" class="file-icon-large"></i>
            </div>

            <div class="file-info">
              <div class="file-name" :title="file.original_name">
                {{ file.original_name }}
              </div>
              <div class="file-meta">
                <span>{{ file.size_formatted }}</span>
                <span>{{ formatTime(file.created_at) }}</span>
              </div>
            </div>

            <div class="file-actions">
              <el-button type="text" icon="el-icon-download" @click.stop="handleDownload(file)" />
              <el-button type="text" icon="el-icon-delete" @click.stop="handleDelete(file)" />
            </div>
          </div>
        </el-col>
      </el-row>

      <el-empty v-if="!loading && fileList.length === 0" description="暂无文件">
        <el-button type="primary" @click="handleUpload">上传文件</el-button>
      </el-empty>
    </div>

    <div v-if="total > 0" class="pagination-container">
      <el-pagination
        :current-page="currentPage"
        :page-size="pageSize"
        :total="total"
        layout="total, prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>

    <input
      type="file"
      ref="fileInput"
      multiple
      style="display: none"
      @change="handleFilesSelected"
    />

    <!-- Preview Dialog -->
    <el-dialog
      :visible.sync="previewVisible"
      :title="previewFile?.original_name"
      width="800px"
      class="preview-dialog"
    >
      <div class="preview-content">
        <img v-if="isImage(previewFile?.mime_type)" :src="previewFile?.url" class="preview-image" />
        <div v-else class="preview-info">
          <i :class="getFileIcon(previewFile?.mime_type)" class="preview-icon"></i>
          <p>{{ previewFile?.original_name }}</p>
          <p>大小: {{ previewFile?.size_formatted }}</p>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

export default {
  name: 'FileList',

  data() {
    return {
      loading: false,
      fileList: [],
      projects: [],
      totalSizeFormatted: '0 B',
      currentPage: 1,
      pageSize: 20,
      total: 0,
      filters: {
        type: '',
        project_id: ''
      },
      previewVisible: false,
      previewFile: null
    }
  },

  async mounted() {
    await this.fetchProjects()
    await this.fetchFiles()
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

    async fetchFiles() {
      this.loading = true
      try {
        const { fileApi } = await import('@/api')
        const params = {
          page: this.currentPage,
          page_size: this.pageSize,
          ...this.filters
        }

        const response = await fileApi.list(params)

        if (response.ret === 1) {
          this.fileList = response.data.list || []
          this.total = response.data.total || 0
          this.totalSizeFormatted = response.data.total_size_formatted || '0 B'
        }
      } catch (error) {
        console.error('Failed to fetch files:', error)
        this.$message.error('获取文件列表失败')
      } finally {
        this.loading = false
      }
    },

    handleUpload() {
      this.$refs.fileInput.click()
    },

    async handleFilesSelected(event) {
      const files = Array.from(event.target.files)
      if (files.length === 0) return

      const loading = this.$loading({ text: '上传中...' })

      try {
        const { fileApi } = await import('@/api')

        if (files.length === 1) {
          const response = await fileApi.upload({ file: files[0] })

          if (response.ret === 1) {
            this.$message.success('上传成功')
            await this.fetchFiles()
          } else {
            this.$message.error(response.msg)
          }
        } else {
          const response = await fileApi.uploadMultiple({ files })

          if (response.ret === 1) {
            const successCount = response.data.success?.length || 0
            const errorCount = response.data.errors?.length || 0
            this.$message.success(`${successCount} 个文件上传成功${errorCount > 0 ? `，${errorCount} 个失败` : ''}`)
            await this.fetchFiles()
          } else {
            this.$message.error(response.msg)
          }
        }
      } catch (error) {
        this.$message.error('上传失败')
      } finally {
        loading.close()
        event.target.value = ''
      }
    },

    handlePreview(file) {
      this.previewFile = file
      this.previewVisible = true
    },

    async handleDownload(file) {
      try {
        window.open(file.url, '_blank')
      } catch (error) {
        this.$message.error('下载失败')
      }
    },

    async handleDelete(file) {
      try {
        await this.$confirm('确定要删除该文件吗？', '删除确认', { type: 'warning' })

        const { fileApi } = await import('@/api')
        const response = await fileApi.delete(file.id)

        if (response.ret === 1) {
          this.$message.success('删除成功')
          await this.fetchFiles()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        if (error !== 'cancel') {
          this.$message.error('删除失败')
        }
      }
    },

    handlePageChange(page) {
      this.currentPage = page
      this.fetchFiles()
    },

    isImage(mimeType) {
      return mimeType?.startsWith('image/')
    },

    getFileIcon(mimeType) {
      if (!mimeType) return 'el-icon-document'

      if (mimeType.includes('pdf')) return 'el-icon-document'
      if (mimeType.includes('word') || mimeType.includes('document')) return 'el-icon-document'
      if (mimeType.includes('excel') || mimeType.includes('sheet')) return 'el-icon-s-grid'
      if (mimeType.includes('ppt') || mimeType.includes('presentation')) return 'el-icon-s-data'
      if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('archive')) return 'el-icon-folder-zip'
      if (mimeType.includes('video')) return 'el-icon-video-camera'

      return 'el-icon-document'
    },

    formatTime(time) {
      if (!time) return ''
      return dayjs(time).fromNow()
    }
  }
}
</script>

<style lang="scss" scoped>
.file-list-page {
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
  align-items: center;
  flex-wrap: wrap;
}

.storage-info {
  margin-left: auto;
  font-size: 14px;
  color: var(--text-secondary);
}

.upload-btn {
  margin-bottom: 20px;
}

.file-grid {
  min-height: 400px;
}

.file-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 20px;
  cursor: pointer;
  transition: transform 0.3s, box-shadow 0.3s;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

    .file-actions {
      opacity: 1;
    }
  }
}

.file-preview {
  height: 120px;
  background: var(--bg-color);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.file-icon-large {
  font-size: 48px;
  color: var(--text-secondary);
}

.file-info {
  padding: 16px;
}

.file-name {
  font-weight: 500;
  margin-bottom: 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-meta {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--text-secondary);
}

.file-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 0 16px 16px;
  opacity: 0;
  transition: opacity 0.3s;
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.preview-dialog {
  :deep(.el-dialog__body) {
    padding: 20px;
  }
}

.preview-content {
  text-align: center;
}

.preview-image {
  max-width: 100%;
  max-height: 70vh;
  border-radius: 8px;
}

.preview-info {
  padding: 40px;

  .preview-icon {
    font-size: 80px;
    color: var(--text-secondary);
    margin-bottom: 20px;
  }

  p {
    margin: 8px 0;
    color: var(--text-secondary);
  }
}
</style>
