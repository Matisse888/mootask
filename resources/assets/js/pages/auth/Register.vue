<template>
  <div class="auth-container">
    <div class="auth-box">
      <div class="auth-header">
        <h1 class="auth-title">MooTask</h1>
        <p class="auth-subtitle">创建您的账户</p>
      </div>

      <el-form
        ref="registerForm"
        :model="form"
        :rules="rules"
        class="auth-form"
        @submit.native.prevent="handleRegister"
      >
        <el-form-item prop="name">
          <el-input
            v-model="form.name"
            placeholder="请输入昵称"
            prefix-icon="el-icon-user"
            size="large"
          />
        </el-form-item>

        <el-form-item prop="email">
          <el-input
            v-model="form.email"
            placeholder="请输入邮箱"
            prefix-icon="el-icon-message"
            size="large"
          />
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="form.password"
            type="password"
            placeholder="请输入密码"
            prefix-icon="el-icon-lock"
            size="large"
            show-password
          />
        </el-form-item>

        <el-form-item prop="password_confirmation">
          <el-input
            v-model="form.password_confirmation"
            type="password"
            placeholder="请确认密码"
            prefix-icon="el-icon-lock"
            size="large"
            show-password
            @keyup.enter.native="handleRegister"
          />
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="loading"
            class="auth-button"
            native-type="submit"
            @click="handleRegister"
          >
            注册
          </el-button>
        </el-form-item>
      </el-form>

      <div class="auth-footer">
        <span>已有账号？</span>
        <el-link type="primary" @click="$router.push('/login')">立即登录</el-link>
      </div>
    </div>

    <div class="auth-decoration">
      <div class="decoration-content">
        <h2>开始高效协作</h2>
        <p>注册 MooTask，开启您的团队协作之旅</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Register',

  data() {
    const validatePass = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('请输入密码'))
      } else {
        if (this.form.password_confirmation !== '') {
          this.$refs.registerForm.validateField('password_confirmation')
        }
        callback()
      }
    }

    const validatePass2 = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('请再次输入密码'))
      } else if (value !== this.form.password) {
        callback(new Error('两次输入密码不一致'))
      } else {
        callback()
      }
    }

    return {
      loading: false,
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      rules: {
        name: [
          { required: true, message: '请输入昵称', trigger: 'blur' },
          { min: 2, max: 50, message: '昵称长度在 2 到 50 个字符', trigger: 'blur' }
        ],
        email: [
          { required: true, message: '请输入邮箱', trigger: 'blur' },
          { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
        ],
        password: [
          { required: true, validator: validatePass, trigger: 'blur' },
          { min: 6, message: '密码至少6位', trigger: 'blur' }
        ],
        password_confirmation: [
          { required: true, validator: validatePass2, trigger: 'blur' }
        ]
      }
    }
  },

  methods: {
    async handleRegister() {
      try {
        await this.$refs.registerForm.validate()

        this.loading = true

        const result = await this.$store.dispatch('register', this.form)

        if (result.success) {
          this.$message.success('注册成功')
          this.$router.push('/')
        } else {
          this.$message.error(result.message)
        }
      } catch (error) {
        console.error('Register error:', error)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.auth-container {
  display: flex;
  min-height: 100vh;
}

.auth-box {
  flex: 0 0 480px;
  padding: 60px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: white;
}

.auth-header {
  text-align: center;
  margin-bottom: 40px;
}

.auth-title {
  font-size: 32px;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 8px;
}

.auth-subtitle {
  font-size: 14px;
  color: var(--text-secondary);
}

.auth-form {
  max-width: 320px;
  margin: 0 auto;
  width: 100%;
}

.auth-button {
  width: 100%;
}

.auth-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: var(--text-secondary);
}

.auth-decoration {
  flex: 1;
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  padding: 40px;
}

.decoration-content {
  max-width: 500px;
  text-align: center;

  h2 {
    font-size: 36px;
    font-weight: 600;
    margin-bottom: 20px;
  }

  p {
    font-size: 18px;
    opacity: 0.9;
    line-height: 1.8;
  }
}

@media (max-width: 768px) {
  .auth-decoration {
    display: none;
  }

  .auth-box {
    flex: 1;
  }
}
</style>
