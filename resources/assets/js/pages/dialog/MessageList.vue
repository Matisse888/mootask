<template>
  <div class="message-list-page">
    <div class="page-header">
      <h1>消息</h1>
      <el-button type="primary" icon="el-icon-plus" @click="showCreateDialog">
        新建对话
      </el-button>
    </div>

    <div v-loading="loading" class="dialog-list">
      <el-row :gutter="20">
        <el-col :xs="24" :lg="12" v-for="dialog in dialogList" :key="dialog.id">
          <div class="dialog-card" @click="$router.push(`/messages/${dialog.id}`)">
            <div class="dialog-avatar">
              <el-avatar v-if="dialog.type === 'private'" :size="50" :src="getOtherAvatar(dialog)">
                {{ getOtherName(dialog)?.charAt(0) }}
              </el-avatar>
              <el-avatar v-else :size="50" :src="dialog.avatar">
                <i class="el-icon-user-solid"></i>
              </el-avatar>
            </div>

            <div class="dialog-info">
              <div class="dialog-header">
                <span class="dialog-name">{{ getOtherName(dialog) || dialog.name }}</span>
                <span class="dialog-time">{{ formatTime(dialog.last_msg_at) }}</span>
              </div>

              <div class="dialog-preview">
                <span v-if="dialog.last_message">
                  {{ getMessagePreview(dialog.last_message) }}
                </span>
                <span v-else class="text-muted">暂无消息</span>
              </div>

              <div class="dialog-members" v-if="dialog.type === 'group'">
                <el-avatar
                  v-for="(member, index) in dialog.members?.slice(0, 3)"
                  :key="member.id"
                  :size="20"
                  :src="member.avatar"
                  class="member-avatar"
                  :style="{ zIndex: 3 - index }"
                >
                  {{ member.name?.charAt(0) }}
                </el-avatar>
                <span v-if="dialog.members?.length > 3" class="member-count">
                  +{{ dialog.members.length - 3 }}
                </span>
                <span class="member-text">{{ dialog.members?.length }} 人</span>
              </div>
            </div>

            <div v-if="dialog.unread_count > 0" class="unread-badge">
              {{ dialog.unread_count > 99 ? '99+' : dialog.unread_count }}
            </div>
          </div>
        </el-col>
      </el-row>

      <el-empty v-if="!loading && dialogList.length === 0" description="暂无对话">
        <el-button type="primary" @click="showCreateDialog">发起对话</el-button>
      </el-empty>
    </div>

    <!-- Create Dialog -->
    <el-dialog title="新建对话" :visible.sync="createDialogVisible" width="500px">
      <el-form :model="createForm" label-width="80px">
        <el-form-item label="对话类型">
          <el-radio-group v-model="createForm.type">
            <el-radio label="private">私聊</el-radio>
            <el-radio label="group">群聊</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="群名称" v-if="createForm.type === 'group'" required>
          <el-input v-model="createForm.name" placeholder="请输入群名称" />
        </el-form-item>

        <el-form-item label="选择成员" required>
          <el-select
            v-model="createForm.user_ids"
            multiple
            filterable
            placeholder="选择成员"
            style="width: 100%"
          >
            <el-option
              v-for="user in availableUsers"
              :key="user.id"
              :label="user.name"
              :value="user.id"
            >
              <div class="user-option">
                <el-avatar :size="24" :src="user.avatar">
                  {{ user.name?.charAt(0) }}
                </el-avatar>
                <span>{{ user.name }}</span>
              </div>
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
  name: 'MessageList',

  data() {
    return {
      loading: false,
      dialogList: [],
      createDialogVisible: false,
      creating: false,
      createForm: {
        type: 'private',
        name: '',
        user_ids: []
      },
      availableUsers: []
    }
  },

  async mounted() {
    await this.fetchDialogs()
  },

  methods: {
    async fetchDialogs() {
      this.loading = true
      try {
        const { dialogApi } = await import('@/api')
        const response = await dialogApi.lists()

        if (response.ret === 1) {
          this.dialogList = response.data || []
        }
      } catch (error) {
        console.error('Failed to fetch dialogs:', error)
        this.$message.error('获取对话列表失败')
      } finally {
        this.loading = false
      }
    },

    async showCreateDialog() {
      this.createDialogVisible = true
      await this.fetchAvailableUsers()
    },

    async fetchAvailableUsers() {
      try {
        const { userApi } = await import('@/api')
        const response = await userApi.list({ page: 1, page_size: 100 })

        if (response.ret === 1) {
          const currentUserId = this.$store.getters.currentUser?.id
          this.availableUsers = response.data.list?.filter(u => u.id !== currentUserId) || []
        }
      } catch (error) {
        console.error('Failed to fetch users:', error)
      }
    },

    async handleCreate() {
      if (this.createForm.user_ids.length === 0) {
        this.$message.warning('请选择成员')
        return
      }

      if (this.createForm.type === 'group' && !this.createForm.name.trim()) {
        this.$message.warning('请输入群名称')
        return
      }

      this.creating = true
      try {
        const { dialogApi } = await import('@/api')
        const response = await dialogApi.create(this.createForm)

        if (response.ret === 1) {
          this.$message.success('对话创建成功')
          this.createDialogVisible = false
          await this.fetchDialogs()

          if (response.data) {
            this.$router.push(`/messages/${response.data.id}`)
          }
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        this.$message.error('创建对话失败')
      } finally {
        this.creating = false
      }
    },

    getOtherAvatar(dialog) {
      const currentUserId = this.$store.getters.currentUser?.id
      const other = dialog.members?.find(m => m.id !== currentUserId)
      return other?.avatar
    },

    getOtherName(dialog) {
      const currentUserId = this.$store.getters.currentUser?.id
      const other = dialog.members?.find(m => m.id !== currentUserId)
      return other?.name
    },

    getMessagePreview(message) {
      if (!message) return ''

      const content = message.content || ''
      const senderName = message.sender?.name || ''

      if (message.type === 'image') {
        return `${senderName}: [图片]`
      }

      if (message.type === 'file') {
        return `${senderName}: [文件] ${message.file_name || ''}`
      }

      return `${senderName}: ${content}`
    },

    formatTime(time) {
      if (!time) return ''
      return dayjs(time).fromNow()
    }
  }
}
</script>

<style lang="scss" scoped>
.message-list-page {
  max-width: 1000px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;

  h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
  }
}

.dialog-list {
  min-height: 400px;
}

.dialog-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  transition: transform 0.3s, box-shadow 0.3s;
  position: relative;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.dialog-avatar {
  flex-shrink: 0;
}

.dialog-info {
  flex: 1;
  overflow: hidden;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.dialog-name {
  font-weight: 600;
  color: var(--text-color);
}

.dialog-time {
  font-size: 12px;
  color: var(--text-secondary);
}

.dialog-preview {
  font-size: 14px;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.text-muted {
  color: var(--text-secondary);
}

.dialog-members {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
}

.member-avatar {
  margin-left: -8px;
  border: 2px solid white;

  &:first-child {
    margin-left: 0;
  }
}

.member-count {
  margin-left: 4px;
  font-size: 12px;
  color: var(--text-secondary);
}

.member-text {
  margin-left: 8px;
  font-size: 12px;
  color: var(--text-secondary);
}

.unread-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  background: var(--primary-color);
  color: white;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
  min-width: 20px;
  text-align: center;
}

.user-option {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
