<template>
  <div class="auth-container">
    <div class="auth-box">
      <div class="auth-header">
        <h1 class="auth-title">MooTask</h1>
        <p class="auth-subtitle">项目任务管理工具</p>
      </div>

      <el-form
        ref="loginForm"
        :model="form"
        :rules="rules"
        class="auth-form"
        @submit.native.prevent="handleLogin"
      >
        <el-form-item prop="email">
          <el-input
            v-model="form.email"
            placeholder="请输入邮箱"
            prefix-icon="el-icon-user"
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
            @keyup.enter.native="handleLogin"
          />
        </el-form-item>

        <el-form-item>
          <el-checkbox v-model="form.remember">记住我</el-checkbox>
          <el-link type="primary" class="forgot-link">忘记密码？</el-link>
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="loading"
            class="auth-button"
            native-type="submit"
            @click="handleLogin"
          >
            登录
          </el-button>
        </el-form-item>
      </el-form>

      <div class="auth-footer">
        <span>还没有账号？</span>
        <el-link type="primary" @click="$router.push('/register')">立即注册</el-link>
      </div>
    </div>

    <div class="auth-decoration">
      <div class="decoration-content">
        <h2>高效的项目管理</h2>
        <p>看板视图、任务分配、即时通讯，让团队协作更简单</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Login',

  data() {
    return {
      loading: false,
      form: {
        email: '',
        password: '',
        remember: false
      },
      rules: {
        email: [
          { required: true, message: '请输入邮箱', trigger: 'blur' },
          { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
        ],
        password: [
          { required: true, message: '请输入密码', trigger: 'blur' },
          { min: 6, message: '密码至少6位', trigger: 'blur' }
        ]
      }
    }
  },

  methods: {
    async handleLogin() {
      try {
        await this.$refs.loginForm.validate()

        this.loading = true

        const result = await this.$store.dispatch('login', this.form)

        if (result.success) {
          this.$message.success('登录成功')
          this.$router.push('/')
        } else {
          this.$message.error(result.message)
        }
      } catch (error) {
        console.error('Login error:', error)
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

.forgot-link {
  float: right;
}

.auth-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: var(--text-secondary);
}

.auth-decoration {
  flex: 1;
  background: linear-gradient(135deg, var(--primary-color) 0%, #66b1ff 100%);
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
