import axios from 'axios'
import router from '@/router'
import Cookies from 'js-cookie'

const api = axios.create({
  baseURL: process.env.VUE_APP_API_URL || 'http://localhost/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  response => {
    return response.data
  },
  error => {
    if (error.response) {
      const { status, data } = error.response

      if (status === 401) {
        // Token expired or invalid
        localStorage.removeItem('token')
        localStorage.removeItem('refresh_token')
        router.push('/login')
      }

      return Promise.reject({
        code: status,
        message: data.msg || '请求失败',
        data: data.data
      })
    }

    return Promise.reject({
      code: -1,
      message: error.message || '网络错误',
      data: null
    })
  }
)

export default api

// API modules
export const authApi = {
  login(data) {
    return api.post('/auth/login', data)
  },
  register(data) {
    return api.post('/auth/register', data)
  },
  logout() {
    return api.post('/auth/logout')
  },
  refresh(data) {
    return api.post('/auth/refresh', data)
  },
  captcha() {
    return api.get('/auth/captcha')
  },
  sendCode(data) {
    return api.post('/auth/send-code', data)
  }
}

export const userApi = {
  info() {
    return api.get('/user/info')
  },
  update(data) {
    return api.post('/user/update', data)
  },
  updatePassword(data) {
    return api.post('/user/password', data)
  },
  list(params) {
    return api.get('/user/list', { params })
  },
  search(params) {
    return api.get('/user/search', { params })
  },
  show(id) {
    return api.get(`/user/show/${id}`)
  },
  departments() {
    return api.get('/user/departments')
  }
}

export const projectApi = {
  lists(params) {
    return api.get('/project/lists', { params })
  },
  create(data) {
    return api.post('/project/create', data)
  },
  show(id) {
    return api.get(`/project/${id}`)
  },
  update(id, data) {
    return api.post(`/${id}`, data)
  },
  delete(id) {
    return api.delete(`/project/${id}`)
  },
  archive(id) {
    return api.post(`/project/${id}/archive`)
  },
  unarchive(id) {
    return api.post(`/project/${id}/unarchive`)
  },
  addMember(id, data) {
    return api.post(`/project/${id}/member/add`, data)
  },
  removeMember(id, data) {
    return api.post(`/project/${id}/member/remove`, data)
  },
  createColumn(id, data) {
    return api.post(`/project/${id}/column/create`, data)
  },
  updateColumn(id, columnId, data) {
    return api.post(`/project/${id}/column/${columnId}`, data)
  },
  deleteColumn(id, columnId) {
    return api.delete(`/project/${id}/column/${columnId}`)
  },
  createTag(id, data) {
    return api.post(`/project/${id}/tag/create`, data)
  },
  tags(id) {
    return api.get(`/project/${id}/tags`)
  }
}

export const taskApi = {
  create(projectId, data) {
    return api.post(`/task/create/${projectId}`, data)
  },
  show(projectId, id) {
    return api.get(`/task/${projectId}/${id}`)
  },
  update(projectId, id, data) {
    return api.post(`/task/${projectId}/${id}`, data)
  },
  delete(projectId, id) {
    return api.delete(`/task/${projectId}/${id}`)
  },
  move(projectId, id, data) {
    return api.post(`/task/${projectId}/${id}/move`, data)
  },
  assign(projectId, id, data) {
    return api.post(`/task/${projectId}/${id}/assign`, data)
  },
  columnTasks(projectId, columnId) {
    return api.get(`/task/${projectId}/column/${columnId}`)
  },
  myTasks(params) {
    return api.get('/task/my', { params })
  }
}

export const dialogApi = {
  lists() {
    return api.get('/dialog/lists')
  },
  create(data) {
    return api.post('/dialog/create', data)
  },
  show(id) {
    return api.get(`/dialog/${id}`)
  },
  messages(id, params) {
    return api.get(`/dialog/${id}/messages`, { params })
  },
  sendMessage(id, data) {
    return api.post(`/dialog/${id}/message`, data)
  },
  recallMessage(id, msgId) {
    return api.post(`/dialog/${id}/message/${msgId}/recall`)
  },
  deleteMessage(id, msgId) {
    return api.delete(`/dialog/${id}/message/${msgId}`)
  },
  addMember(id, data) {
    return api.post(`/dialog/${id}/member/add`, data)
  },
  removeMember(id, data) {
    return api.post(`/dialog/${id}/member/remove`, data)
  },
  leave(id) {
    return api.post(`/dialog/${id}/leave`)
  }
}

export const fileApi = {
  upload(data) {
    const formData = new FormData()
    formData.append('file', data.file)

    if (data.project_id) formData.append('project_id', data.project_id)
    if (data.task_id) formData.append('task_id', data.task_id)
    if (data.dialog_id) formData.append('dialog_id', data.dialog_id)
    if (data.message_id) formData.append('message_id', data.message_id)

    return api.post('/file/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  uploadMultiple(data) {
    const formData = new FormData()
    data.files.forEach(file => {
      formData.append('files[]', file)
    })

    if (data.project_id) formData.append('project_id', data.project_id)
    if (data.task_id) formData.append('task_id', data.task_id)

    return api.post('/file/upload-multiple', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  list(params) {
    return api.get('/file/list', { params })
  },
  show(id) {
    return api.get(`/file/${id}`)
  },
  download(id) {
    return api.get(`/file/${id}/download`, { responseType: 'blob' })
  },
  preview(id) {
    return api.get(`/file/${id}/preview`)
  },
  delete(id) {
    return api.delete(`/file/${id}`)
  },
  batchDelete(ids) {
    return api.post('/file/batch-delete', { ids })
  }
}
