<template>
  <div class="settings-page">
    <div class="page-header">
      <h1>设置</h1>
    </div>

    <el-tabs v-model="activeTab" class="settings-tabs">
      <el-tab-pane label="个人资料" name="profile">
        <el-card>
          <el-form
            ref="profileForm"
            :model="profileForm"
            :rules="profileRules"
            label-width="100px"
          >
            <el-form-item label="头像">
              <div class="avatar-upload">
                <el-avatar :size="80" :src="profileForm.avatar">
                  {{ profileForm.name?.charAt(0) }}
                </el-avatar>
                <el-button size="small" @click="handleAvatarUpload" style="margin-left: 16px">
                  更换头像
                </el-button>
              </div>
            </el-form-item>

            <el-form-item label="昵称" prop="name">
              <el-input v-model="profileForm.name" placeholder="请输入昵称" />
            </el-form-item>

            <el-form-item label="邮箱">
              <el-input :value="currentUser?.email" disabled />
            </el-form-item>

            <el-form-item label="手机号">
              <el-input v-model="profileForm.phone" placeholder="请输入手机号" />
            </el-form-item>

            <el-form-item label="部门">
              <el-select v-model="profileForm.department_id" placeholder="选择部门" clearable>
                <el-option
                  v-for="dept in departments"
                  :key="dept.id"
                  :label="dept.name"
                  :value="dept.id"
                />
              </el-select>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" :loading="savingProfile" @click="handleSaveProfile">
                保存修改
              </el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="修改密码" name="password">
        <el-card>
          <el-form
            ref="passwordForm"
            :model="passwordForm"
            :rules="passwordRules"
            label-width="100px"
          >
            <el-form-item label="当前密码" prop="old_password">
              <el-input
                v-model="passwordForm.old_password"
                type="password"
                placeholder="请输入当前密码"
                show-password
              />
            </el-form-item>

            <el-form-item label="新密码" prop="password">
              <el-input
                v-model="passwordForm.password"
                type="password"
                placeholder="请输入新密码"
                show-password
              />
            </el-form-item>

            <el-form-item label="确认密码" prop="password_confirmation">
              <el-input
                v-model="passwordForm.password_confirmation"
                type="password"
                placeholder="请再次输入新密码"
                show-password
              />
            </el-form-item>

            <el-form-item>
              <el-button type="primary" :loading="savingPassword" @click="handleChangePassword">
                修改密码
              </el-button>
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="主题设置" name="theme">
        <el-card>
          <el-form label-width="100px">
            <el-form-item label="外观">
              <el-radio-group v-model="theme">
                <el-radio label="light">浅色</el-radio>
                <el-radio label="dark">深色</el-radio>
              </el-radio-group>
            </el-form-item>

            <el-form-item label="主题色">
              <el-color-picker v-model="primaryColor" />
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>

      <el-tab-pane label="通知设置" name="notification">
        <el-card>
          <el-form label-width="150px">
            <el-form-item label="邮件通知">
              <el-switch v-model="notificationSettings.email" />
            </el-form-item>

            <el-form-item label="任务分配通知">
              <el-switch v-model="notificationSettings.task_assign" />
            </el-form-item>

            <el-form-item label="任务评论通知">
              <el-switch v-model="notificationSettings.task_comment" />
            </el-form-item>

            <el-form-item label="新消息通知">
              <el-switch v-model="notificationSettings.new_message" />
            </el-form-item>
          </el-form>
        </el-card>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script>
export default {
  name: 'Settings',

  data() {
    const validatePass = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('请输入新密码'))
      } else {
        if (this.passwordForm.password_confirmation !== '') {
          this.$refs.passwordForm.validateField('password_confirmation')
        }
        callback()
      }
    }

    const validatePass2 = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('请再次输入新密码'))
      } else if (value !== this.passwordForm.password) {
        callback(new Error('两次输入密码不一致'))
      } else {
        callback()
      }
    }

    return {
      activeTab: 'profile',
      savingProfile: false,
      savingPassword: false,
      departments: [],
      profileForm: {
        name: '',
        avatar: '',
        phone: '',
        department_id: ''
      },
      profileRules: {
        name: [
          { required: true, message: '请输入昵称', trigger: 'blur' },
          { min: 2, max: 50, message: '昵称长度在 2 到 50 个字符', trigger: 'blur' }
        ]
      },
      passwordForm: {
        old_password: '',
        password: '',
        password_confirmation: ''
      },
      passwordRules: {
        old_password: [
          { required: true, message: '请输入当前密码', trigger: 'blur' }
        ],
        password: [
          { required: true, validator: validatePass, trigger: 'blur' },
          { min: 6, message: '密码至少6位', trigger: 'blur' }
        ],
        password_confirmation: [
          { required: true, validator: validatePass2, trigger: 'blur' }
        ]
      },
      theme: 'light',
      primaryColor: '#409EFF',
      notificationSettings: {
        email: true,
        task_assign: true,
        task_comment: true,
        new_message: true
      }
    }
  },

  computed: {
    currentUser() {
      return this.$store.getters.currentUser
    }
  },

  watch: {
    currentUser: {
      handler(user) {
        if (user) {
          this.profileForm = {
            name: user.name,
            avatar: user.avatar,
            phone: user.phone,
            department_id: user.department_id
          }
        }
      },
      immediate: true
    },

    theme(value) {
      this.$store.dispatch('setTheme', value)
    }
  },

  async mounted() {
    await this.fetchDepartments()
    this.theme = this.$store.state.theme
  },

  methods: {
    async fetchDepartments() {
      try {
        const { userApi } = await import('@/api')
        const response = await userApi.departments()

        if (response.ret === 1) {
          this.departments = this.flattenDepartments(response.data)
        }
      } catch (error) {
        console.error('Failed to fetch departments:', error)
      }
    },

    flattenDepartments(departments, result = []) {
      departments.forEach(dept => {
        result.push(dept)
        if (dept.children?.length) {
          this.flattenDepartments(dept.children, result)
        }
      })
      return result
    },

    async handleSaveProfile() {
      try {
        await this.$refs.profileForm.validate()

        this.savingProfile = true

        const { userApi } = await import('@/api')
        const response = await userApi.update(this.profileForm)

        if (response.ret === 1) {
          this.$message.success('保存成功')
          this.$store.dispatch('fetchUserInfo')
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        console.error('Failed to save profile:', error)
      } finally {
        this.savingProfile = false
      }
    },

    async handleChangePassword() {
      try {
        await this.$refs.passwordForm.validate()

        this.savingPassword = true

        const { userApi } = await import('@/api')
        const response = await userApi.updatePassword(this.passwordForm)

        if (response.ret === 1) {
          this.$message.success('密码修改成功')
          this.$refs.passwordForm.resetFields()
        } else {
          this.$message.error(response.msg)
        }
      } catch (error) {
        console.error('Failed to change password:', error)
      } finally {
        this.savingPassword = false
      }
    },

    handleAvatarUpload() {
      this.$message.info('头像上传功能开发中')
    }
  }
}
</script>

<style lang="scss" scoped>
.settings-page {
  max-width: 800px;
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

.settings-tabs {
  :deep(.el-tabs__header) {
    background: white;
    padding: 0 20px;
    border-radius: 12px 12px 0 0;
  }

  :deep(.el-tabs__content) {
    padding: 20px;
  }
}

.avatar-upload {
  display: flex;
  align-items: center;
}
</style>
