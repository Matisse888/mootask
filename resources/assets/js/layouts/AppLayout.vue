<template>
  <div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'is-collapsed': sidebarCollapsed }">
      <div class="sidebar-header">
        <div class="logo">
          <span class="logo-icon">M</span>
          <span v-if="!sidebarCollapsed" class="logo-text">MooTask</span>
        </div>
      </div>

      <el-menu
        :default-active="activeMenu"
        :collapse="sidebarCollapsed"
        :collapse-transition="false"
        class="sidebar-menu"
        @select="handleMenuSelect"
      >
        <el-menu-item index="/">
          <i class="el-icon-s-home"></i>
          <span slot="title">首页</span>
        </el-menu-item>

        <el-menu-item index="/projects">
          <i class="el-icon-folder-opened"></i>
          <span slot="title">项目</span>
        </el-menu-item>

        <el-menu-item index="/my-tasks">
          <i class="el-icon-tickets"></i>
          <span slot="title">我的任务</span>
        </el-menu-item>

        <el-menu-item index="/messages">
          <i class="el-icon-chat-line-round"></i>
          <span slot="title">消息</span>
        </el-menu-item>

        <el-menu-item index="/files">
          <i class="el-icon-folder"></i>
          <span slot="title">文件</span>
        </el-menu-item>

        <el-menu-item index="/settings">
          <i class="el-icon-setting"></i>
          <span slot="title">设置</span>
        </el-menu-item>
      </el-menu>

      <div class="sidebar-footer">
        <div class="user-info" @click="handleUserClick">
          <el-avatar :size="32" :src="currentUser?.avatar">
            {{ currentUser?.name?.charAt(0) }}
          </el-avatar>
          <div v-if="!sidebarCollapsed" class="user-detail">
            <div class="user-name">{{ currentUser?.name }}</div>
            <div class="user-email">{{ currentUser?.email }}</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-container">
      <!-- Header -->
      <header class="main-header">
        <div class="header-left">
          <el-button
            type="text"
            icon="el-icon-s-fold"
            @click="toggleSidebar"
          />
        </div>

        <div class="header-center">
          <el-input
            v-model="searchQuery"
            placeholder="搜索项目、任务..."
            prefix-icon="el-icon-search"
            class="header-search"
            clearable
          />
        </div>

        <div class="header-right">
          <el-button type="text" icon="el-icon-bell" @click="handleNotifications">
            <el-badge :value="3" class="badge"></el-badge>
          </el-button>

          <el-button type="text" icon="el-icon-question" @click="handleHelp" />
        </div>
      </header>

      <!-- Content -->
      <main class="main-content">
        <router-view />
      </main>
    </div>

    <!-- User dropdown -->
    <el-dropdown @command="handleUserCommand">
      <span class="el-dropdown-link">
        <i class="el-icon-arrow-down el-icon--right"></i>
      </span>
      <el-dropdown-menu slot="dropdown">
        <el-dropdown-item command="profile">
          <i class="el-icon-user"></i> 个人资料
        </el-dropdown-item>
        <el-dropdown-item command="settings">
          <i class="el-icon-setting"></i> 设置
        </el-dropdown-item>
        <el-dropdown-item divided command="logout">
          <i class="el-icon-switch-button"></i> 退出登录
        </el-dropdown-item>
      </el-dropdown-menu>
    </el-dropdown>
  </div>
</template>

<script>
export default {
  name: 'AppLayout',

  data() {
    return {
      searchQuery: ''
    }
  },

  computed: {
    sidebarCollapsed() {
      return this.$store.getters.sidebarCollapsed
    },

    currentUser() {
      return this.$store.getters.currentUser
    },

    activeMenu() {
      return this.$route.path
    }
  },

  methods: {
    toggleSidebar() {
      this.$store.dispatch('toggleSidebar')
    },

    handleMenuSelect(index) {
      this.$router.push(index)
    },

    handleUserClick() {
      this.$router.push('/settings')
    },

    handleUserCommand(command) {
      switch (command) {
        case 'profile':
          this.$router.push('/settings')
          break
        case 'settings':
          this.$router.push('/settings')
          break
        case 'logout':
          this.handleLogout()
          break
      }
    },

    async handleLogout() {
      try {
        await this.$store.dispatch('logout')
        this.$message.success('已退出登录')
        this.$router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
      }
    },

    handleNotifications() {
      this.$message.info('通知功能开发中')
    },

    handleHelp() {
      this.$message.info('帮助文档开发中')
    }
  }
}
</script>

<style lang="scss" scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: var(--sidebar-width);
  background: white;
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  transition: width 0.3s;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 100;

  &.is-collapsed {
    width: var(--sidebar-collapsed-width);
  }
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid var(--border-color);
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;

  .logo-icon {
    width: 32px;
    height: 32px;
    background: var(--primary-color);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
  }

  .logo-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-color);
  }
}

.sidebar-menu {
  flex: 1;
  border-right: none;

  &:not(.el-menu--collapse) {
    width: 220px;
  }
}

.sidebar-footer {
  padding: 20px;
  border-top: 1px solid var(--border-color);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: background 0.3s;

  &:hover {
    background: var(--bg-color);
  }
}

.user-detail {
  flex: 1;
  overflow: hidden;

  .user-name {
    font-weight: 500;
    color: var(--text-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .user-email {
    font-size: 12px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}

.main-container {
  flex: 1;
  margin-left: var(--sidebar-width);
  transition: margin-left 0.3s;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.sidebar.is-collapsed + .main-container {
  margin-left: var(--sidebar-collapsed-width);
}

.main-header {
  height: var(--header-height);
  background: white;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  padding: 0 20px;
  position: sticky;
  top: 0;
  z-index: 99;
}

.header-left {
  flex: 0 0 100px;
}

.header-center {
  flex: 1;
  max-width: 500px;
  margin: 0 auto;
}

.header-search {
  width: 100%;

  :deep(.el-input__inner) {
    border-radius: 20px;
  }
}

.header-right {
  flex: 0 0 100px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.main-content {
  flex: 1;
  padding: 20px;
  background: var(--bg-color);
}

.badge {
  :deep(.el-badge__content) {
    top: -5px;
    right: -5px;
  }
}
</style>
