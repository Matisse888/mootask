<template>
  <div id="app" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <router-view />
  </div>
</template>

<script>
export default {
  name: 'App',
  computed: {
    sidebarCollapsed() {
      return this.$store.getters.sidebarCollapsed
    }
  },
  mounted() {
    // Initialize theme
    const theme = this.$store.state.theme
    document.documentElement.setAttribute('data-theme', theme)

    // Try to restore user info if logged in
    if (this.$store.getters.isAuthenticated) {
      this.$store.dispatch('fetchUserInfo')
    }
  }
}
</script>

<style lang="scss">
@import 'normalize.css/normalize.css';

:root {
  --primary-color: #409eff;
  --success-color: #67c23a;
  --warning-color: #e6a23c;
  --danger-color: #f56c6c;
  --info-color: #909399;
  --bg-color: #f5f7fa;
  --text-color: #303133;
  --text-secondary: #909399;
  --border-color: #dcdfe6;
  --sidebar-width: 220px;
  --sidebar-collapsed-width: 64px;
  --header-height: 60px;
}

[data-theme="dark"] {
  --bg-color: #1a1a1a;
  --text-color: #e0e0e0;
  --text-secondary: #a0a0a0;
  --border-color: #404040;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  font-size: 14px;
  color: var(--text-color);
  background-color: var(--bg-color);
  line-height: 1.6;
}

#app {
  min-height: 100vh;
}

// Scrollbar
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 4px;

  &:hover {
    background: var(--text-secondary);
  }
}

// Transitions
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s;
}

.slide-enter,
.slide-leave-to {
  transform: translateX(-100%);
}

// Utility classes
.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.text-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.flex {
  display: flex;
}

.flex-center {
  display: flex;
  align-items: center;
  justify-content: center;
}

.flex-between {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.flex-1 {
  flex: 1;
}

.mt-10 {
  margin-top: 10px;
}

.mt-20 {
  margin-top: 20px;
}

.mb-10 {
  margin-bottom: 10px;
}

.mb-20 {
  margin-bottom: 20px;
}

.p-20 {
  padding: 20px;
}
</style>
