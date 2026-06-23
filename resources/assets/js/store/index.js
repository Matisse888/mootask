import Vue from 'vue'
import Vuex from 'vuex'
import Cookies from 'js-cookie'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    token: localStorage.getItem('token') || null,
    refreshToken: localStorage.getItem('refresh_token') || null,
    user: null,
    sidebarCollapsed: false,
    theme: localStorage.getItem('theme') || 'light'
  },

  mutations: {
    SET_TOKEN(state, token) {
      state.token = token
      if (token) {
        localStorage.setItem('token', token)
      } else {
        localStorage.removeItem('token')
      }
    },

    SET_REFRESH_TOKEN(state, token) {
      state.refreshToken = token
      if (token) {
        localStorage.setItem('refresh_token', token)
      } else {
        localStorage.removeItem('refresh_token')
      }
    },

    SET_USER(state, user) {
      state.user = user
      if (user) {
        localStorage.setItem('user', JSON.stringify(user))
      } else {
        localStorage.removeItem('user')
      }
    },

    SET_SIDEBAR_COLLAPSED(state, collapsed) {
      state.sidebarCollapsed = collapsed
    },

    SET_THEME(state, theme) {
      state.theme = theme
      localStorage.setItem('theme', theme)
      document.documentElement.setAttribute('data-theme', theme)
    },

    LOGOUT(state) {
      state.token = null
      state.refreshToken = null
      state.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('user')
    }
  },

  actions: {
    async login({ commit }, credentials) {
      try {
        const { authApi } = await import('@/api')
        const response = await authApi.login(credentials)

        if (response.ret === 1) {
          commit('SET_TOKEN', response.data.token)
          commit('SET_REFRESH_TOKEN', response.data.refresh_token)
          commit('SET_USER', response.data.user)
          return { success: true, data: response.data }
        } else {
          return { success: false, message: response.msg }
        }
      } catch (error) {
        return { success: false, message: error.message }
      }
    },

    async register({ commit }, data) {
      try {
        const { authApi } = await import('@/api')
        const response = await authApi.register(data)

        if (response.ret === 1) {
          commit('SET_TOKEN', response.data.token)
          commit('SET_REFRESH_TOKEN', response.data.refresh_token)
          commit('SET_USER', response.data.user)
          return { success: true, data: response.data }
        } else {
          return { success: false, message: response.msg }
        }
      } catch (error) {
        return { success: false, message: error.message }
      }
    },

    async logout({ commit }) {
      try {
        const { authApi } = await import('@/api')
        await authApi.logout()
      } catch (e) {
        // Ignore error
      }

      commit('LOGOUT')
    },

    async fetchUserInfo({ commit, state }) {
      if (!state.token) return null

      try {
        const { userApi } = await import('@/api')
        const response = await userApi.info()

        if (response.ret === 1) {
          commit('SET_USER', response.data)
          return response.data
        }
      } catch (error) {
        console.error('Failed to fetch user info:', error)
      }

      return null
    },

    toggleSidebar({ commit, state }) {
      commit('SET_SIDEBAR_COLLAPSED', !state.sidebarCollapsed)
    },

    setTheme({ commit }, theme) {
      commit('SET_THEME', theme)
    }
  },

  getters: {
    isAuthenticated: state => !!state.token,
    currentUser: state => state.user,
    sidebarCollapsed: state => state.sidebarCollapsed,
    theme: state => state.theme
  },

  modules: {
    // Modules can be added here
  }
})
