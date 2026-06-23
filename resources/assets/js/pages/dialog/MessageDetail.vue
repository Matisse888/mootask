<template>
  <div class="message-detail-page">
    <div class="chat-header">
      <el-button type="text" icon="el-icon-arrow-left" @click="$router.push('/messages')" />
      <div class="chat-info">
        <el-avatar v-if="dialog.type === 'private'" :size="40" :src="otherUser?.avatar">
          {{ otherUser?.name?.charAt(0) }}
        </el-avatar>
        <el-avatar v-else :size="40" :src="dialog.avatar">
          <i class="el-icon-user-solid"></i>
        </el-avatar>
        <div class="info-text">
          <div class="chat-name">{{ dialog.name || otherUser?.name }}</div>
          <div class="chat-status" v-if="dialog.type === 'group'">
            {{ dialog.members?.length || 0 }} 人
          </div>
        </div>
      </div>
      <el-dropdown @command="handleCommand">
        <el-button type="text" icon="el-icon-more" />
        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item command="members" v-if="dialog.type === 'group'">
            <i class="el-icon-user"></i> 群成员
          </el-dropdown-item>
          <el-dropdown-item command="clear">
            <i class="el-icon-delete"></i> 清空消息
          </el-dropdown-item>
          <el-dropdown-item command="leave" v-if="dialog.type === 'group'">
            <i class="el-icon-circle-close"></i> 退出群聊
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>

    <div class="chat-messages" ref="messagesContainer">
      <div v-if="loading" class="loading-container">
        <i class="el-icon-loading"></i> 加载中...
      </div>

      <div v-else-if="messages.length === 0" class="empty-container">
        <p>暂无消息，开始聊天吧</p>
      </div>

      <div v-else class="message-list">
        <div
          v-for="(msg, index) in messages"
          :key="msg.id"
          class="message-item"
          :class="{ 'is-self': msg.user_id === currentUserId }"
        >
          <el-avatar
            v-if="msg.user_id !== currentUserId"
            :size="36"
            :src="msg.sender?.avatar"
            class="message-avatar"
          >
            {{ msg.sender?.name?.charAt(0) }}
          </el-avatar>

          <div class="message-content-wrapper">
            <div v-if="msg.reply && showReplyDate(index)" class="message-reply">
              <span class="reply-name">{{ msg.reply.sender?.name }}:</span>
              {{ msg.reply.content }}
            </div>

            <div class="message-content">
              <div v-if="msg.type === 'text'" class="message-text">
                {{ msg.content }}
              </div>
              <div v-else-if="msg.type === 'image'" class="message-image">
                <el-image
                  :src="msg.file_url"
                  fit="cover"
                  :preview-src-list="[msg.file_url]"
                  class="image-content"
                />
              </div>
              <div v-else-if="msg.type === 'file'" class="message-file">
                <i class="el-icon-document"></i>
                <span>{{ msg.file_name }}</span>
              </div>
              <div v-else-if="msg.type === 'system'" class="message-system">
                {{ msg.content }}
              </div>
            </div>

            <div class="message-time">{{ formatTime(msg.created_at) }}</div>
          </div>

          <el-avatar
            v-if="msg.user_id === currentUserId"
            :size="36"
            :src="currentUser?.avatar"
            class="message-avatar"
          >
            {{ currentUser?.name?.charAt(0) }}
          </el-avatar>
        </div>
      </div>
    </div>

    <div class="chat-input">
      <div class="input-tools">
        <el-button type="text" icon="el-icon-picture" @click="handleUploadImage" />
        <el-button type="text" icon="el-icon-folder" @click="handleUploadFile" />
        <el-button type="text" icon="el-icon-circle-plus" />
      </div>

      <div class="input-area">
        <el-input
          v-model="messageContent"
          type="textarea"
          :rows="3"
          placeholder="输入消息..."
          @keyup.enter.ctrl="handleSend"
          @keyup.enter.exact="handleSend"
        />
      </div>

      <div class="input-actions">
        <span class="input-hint">按 Enter 发送，Ctrl+Enter 换行</span>
        <el-button type="primary" :loading="sending" @click="handleSend">
          发送
        </el-button>
      </div>
    </div>

    <input
      type="file"
      ref="imageInput"
      accept="image/*"
      style="display: none"
      @change="handleImageSelected"
    />

    <input
      type="file"
      ref="fileInput"
      style="display: none"
      @change="handleFileSelected"
    />
  </div>
</template>

<script>
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

export default {
  name: 'MessageDetail',

  data() {
    return {
      loading: false,
      sending: false,
      dialog: {},
      messages: [],
      messageContent: '',
      page: 1,
      pageSize: 20,
      hasMore: true
    }
  },

  computed: {
    currentUserId() {
      return this.$store.getters.currentUser?.id
    },

    currentUser() {
      return this.$store.getters.currentUser
    },

    otherUser() {
      if (this.dialog.type !== 'private') return null

      return this.dialog.members?.find(m => m.id !== this.currentUserId)
    }
  },

  async mounted() {
    await this.fetchDialog()
    await this.fetchMessages()
    this.scrollToBottom()
  },

  methods: {
    async fetchDialog() {
      try {
        const { dialogApi } = await import('@/api')
        const response = await dialogApi.show(this.$route.params.id)

        if (response.ret === 1) {
          this.dialog = response.data
        }
      } catch (error) {
        console.error('Failed to fetch dialog:', error)
        this.$message.error('获取对话信息失败')
      }
    },

    async fetchMessages() {
      if (this.loading) return

      this.loading = true
      try {
        const { dialogApi } = await import('@/api')
        const response = await dialogApi.messages(this.$route.params.id, {
          page: this.page,
          page_size: this.pageSize
        })

        if (response.ret === 1) {
          if (this.page === 1) {
            this.messages = response.data.list || []
          } else {
            this.messages = [...(response.data.list || []), ...this.messages]
          }
          this.hasMore = this.page < response.data.total_pages
        }
      } catch (error) {
        console.error('Failed to fetch messages:', error)
      } finally {
        this.loading = false
      }
    },

    async handleSend() {
      if (!this.messageContent.trim()) return

      this.sending = true
      try {
        const { dialogApi } = await import('@/api')
        const response = await dialogApi.sendMessage(this.$route.params.id, {
          type: 'text',
          content: this.messageContent.trim()
        })

        if (response.ret === 1) {
          this.messages.push(response.data)
          this.messageContent = ''
          this.$nextTick(() => this.scrollToBottom())
        }
      } catch (error) {
        this.$message.error('发送消息失败')
      } finally {
        this.sending = false
      }
    },

    handleUploadImage() {
      this.$refs.imageInput.click()
    },

    async handleImageSelected(event) {
      const file = event.target.files[0]
      if (!file) return

      await this.uploadFile(file, 'image')
      event.target.value = ''
    },

    handleUploadFile() {
      this.$refs.fileInput.click()
    },

    async handleFileSelected(event) {
      const file = event.target.files[0]
      if (!file) return

      await this.uploadFile(file, 'file')
      event.target.value = ''
    },

    async uploadFile(file, type) {
      const loading = this.$loading({ text: '上传中...' })

      try {
        const { fileApi, dialogApi } = await import('@/api')

        const uploadResponse = await fileApi.upload({
          file,
          dialog_id: this.$route.params.id
        })

        if (uploadResponse.ret === 1) {
          const messageResponse = await dialogApi.sendMessage(this.$route.params.id, {
            type,
            content: '',
            file_url: uploadResponse.data.url,
            file_name: file.name,
            file_size: file.size
          })

          if (messageResponse.ret === 1) {
            this.messages.push(messageResponse.data)
            this.$nextTick(() => this.scrollToBottom())
          }
        }
      } catch (error) {
        this.$message.error('上传失败')
      } finally {
        loading.close()
      }
    },

    handleCommand(command) {
      switch (command) {
        case 'members':
          this.$message.info('群成员管理开发中')
          break
        case 'clear':
          this.handleClear()
          break
        case 'leave':
          this.handleLeave()
          break
      }
    },

    handleClear() {
      this.$message.info('清空消息功能开发中')
    },

    async handleLeave() {
      try {
        await this.$confirm('确定要退出该群聊吗？', '退出确认', { type: 'warning' })

        const { dialogApi } = await import('@/api')
        const response = await dialogApi.leave(this.$route.params.id)

        if (response.ret === 1) {
          this.$message.success('已退出群聊')
          this.$router.push('/messages')
        }
      } catch (error) {
        if (error !== 'cancel') {
          this.$message.error('退出失败')
        }
      }
    },

    scrollToBottom() {
      const container = this.$refs.messagesContainer
      if (container) {
        container.scrollTop = container.scrollHeight
      }
    },

    formatTime(time) {
      if (!time) return ''
      return dayjs(time).format('HH:mm')
    },

    showReplyDate(index) {
      if (index === 0) return true
      const current = dayjs(this.messages[index].created_at)
      const previous = dayjs(this.messages[index - 1].created_at)
      return current.diff(previous, 'minute') > 5
    }
  }
}
</script>

<style lang="scss" scoped>
.message-detail-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 140px);
  background: white;
  border-radius: 12px;
  overflow: hidden;
}

.chat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-color);
}

.chat-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-text {
  .chat-name {
    font-weight: 600;
    font-size: 16px;
  }

  .chat-status {
    font-size: 12px;
    color: var(--text-secondary);
  }
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.loading-container,
.empty-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--text-secondary);
}

.message-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.message-item {
  display: flex;
  gap: 12px;
  max-width: 70%;

  &.is-self {
    flex-direction: row-reverse;
    margin-left: auto;
  }
}

.message-avatar {
  flex-shrink: 0;
}

.message-content-wrapper {
  flex: 1;
}

.message-reply {
  background: var(--bg-color);
  padding: 8px 12px;
  border-radius: 8px;
  margin-bottom: 4px;
  font-size: 13px;
  color: var(--text-secondary);

  .reply-name {
    font-weight: 500;
    color: var(--primary-color);
    margin-right: 4px;
  }
}

.message-content {
  background: var(--bg-color);
  padding: 12px 16px;
  border-radius: 12px;
  display: inline-block;
  max-width: 100%;
}

.is-self .message-content {
  background: var(--primary-color);
  color: white;
}

.message-text {
  word-break: break-word;
}

.message-image {
  .image-content {
    max-width: 300px;
    max-height: 300px;
    border-radius: 8px;
  }
}

.message-file {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;

  &:hover {
    text-decoration: underline;
  }
}

.message-system {
  font-size: 12px;
  color: var(--text-secondary);
  text-align: center;
  padding: 8px;
}

.message-time {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 4px;

  .is-self & {
    text-align: right;
  }
}

.chat-input {
  padding: 16px 20px;
  border-top: 1px solid var(--border-color);
}

.input-tools {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.input-area {
  :deep(.el-textarea__inner) {
    resize: none;
    border: none;
    background: var(--bg-color);
    border-radius: 8px;
  }
}

.input-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
}

.input-hint {
  font-size: 12px;
  color: var(--text-secondary);
}
</style>
