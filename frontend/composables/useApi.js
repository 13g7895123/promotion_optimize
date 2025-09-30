import { $fetch } from 'ofetch'

export const useApi = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase || 'https://promotion.mercylife.cc/api'

  const api = $fetch.create({
    baseURL,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    onRequest({ request, options }) {
      // Add auth token if available
      const token = useCookie('auth-token').value
      if (token) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${token}`
        }
      }
    },
    onResponseError({ response }) {
      console.error('API Error:', response.status, response._data)
      // Handle auth errors
      if (response.status === 401) {
        // Redirect to login or refresh token
        navigateTo('/auth/login')
      }
    }
  })

  return {
    // Dashboard Statistics
    getDashboardStats: (params = {}) =>
      api('/statistics/dashboard', {
        method: 'GET',
        query: params
      }),

    // Promotion Statistics
    getPromotionStats: (params = {}) =>
      api('/statistics/promotions', {
        method: 'GET',
        query: params
      }),

    // Reward Statistics
    getRewardStats: (params = {}) =>
      api('/statistics/rewards', {
        method: 'GET',
        query: params
      }),

    // User Management
    getUsers: (params = {}) =>
      api('/users', {
        method: 'GET',
        query: params
      }),

    getUserDetails: (userId) =>
      api(`/users/${userId}`, {
        method: 'GET'
      }),

    createUser: (userData) =>
      api('/users', {
        method: 'POST',
        body: userData
      }),

    updateUser: (userId, userData) =>
      api(`/users/${userId}`, {
        method: 'PUT',
        body: userData
      }),

    deleteUser: (userId) =>
      api(`/users/${userId}`, {
        method: 'DELETE'
      }),

    // User Roles Management
    getUserRoles: (userId) =>
      api(`/users/${userId}/roles`, {
        method: 'GET'
      }),

    updateUserRoles: (userId, roles) =>
      api(`/users/${userId}/roles`, {
        method: 'PUT',
        body: { roles }
      }),

    // User Sessions
    getUserSessions: (userId) =>
      api(`/users/${userId}/sessions`, {
        method: 'GET'
      }),

    revokeUserSessions: (userId) =>
      api(`/users/${userId}/sessions`, {
        method: 'DELETE'
      }),

    // Server Management
    getServers: (params = {}) =>
      api('/servers', {
        method: 'GET',
        query: params
      }),

    getServerDetails: (serverId) =>
      api(`/servers/${serverId}`, {
        method: 'GET'
      }),

    createServer: (serverData) =>
      api('/servers', {
        method: 'POST',
        body: serverData
      }),

    updateServer: (serverId, serverData) =>
      api(`/servers/${serverId}`, {
        method: 'PUT',
        body: serverData
      }),

    deleteServer: (serverId) =>
      api(`/servers/${serverId}`, {
        method: 'DELETE'
      }),

    updateServerStatus: (serverId, statusData) =>
      api(`/servers/${serverId}/status`, {
        method: 'PUT',
        body: statusData
      }),

    testServerConnection: (serverId) =>
      api(`/servers/${serverId}/test-connection`, {
        method: 'POST'
      }),

    uploadServerImage: (serverId, imageFile, imageType) => {
      const formData = new FormData()
      formData.append('image', imageFile)
      formData.append('type', imageType) // 'logo', 'background', 'banner'

      return api(`/servers/${serverId}/upload-image`, {
        method: 'POST',
        body: formData,
        headers: {
          // Remove Content-Type to allow browser to set boundary for FormData
          'Content-Type': undefined
        }
      })
    },

    // Promotion Management
    getPromotions: (params = {}) =>
      api('/promotions', {
        method: 'GET',
        query: params
      }),

    getPromotionDetails: (promotionId) =>
      api(`/promotions/${promotionId}`, {
        method: 'GET'
      }),

    createPromotion: (promotionData) =>
      api('/promotions', {
        method: 'POST',
        body: promotionData
      }),

    updatePromotion: (promotionId, promotionData) =>
      api(`/promotions/${promotionId}`, {
        method: 'PUT',
        body: promotionData
      }),

    deletePromotion: (promotionId) =>
      api(`/promotions/${promotionId}`, {
        method: 'DELETE'
      }),

    getPromotionStatistics: (promotionId, params = {}) =>
      api(`/promotions/${promotionId}/statistics`, {
        method: 'GET',
        query: params
      }),

    getPromotionClicks: (promotionId, params = {}) =>
      api(`/promotions/${promotionId}/clicks`, {
        method: 'GET',
        query: params
      }),

    generatePromotionLink: (linkData) =>
      api('/promotions/generate-link', {
        method: 'POST',
        body: linkData
      }),

    generatePromotionQR: (promotionId, options = {}) =>
      api(`/promotions/${promotionId}/qr-code`, {
        method: 'POST',
        body: options
      }),

    trackPromotionClick: (promotionId, clickData) =>
      api(`/promotions/${promotionId}/track-click`, {
        method: 'POST',
        body: clickData
      }),

    // Permission Management
    getPermissions: (params = {}) =>
      api('/permissions', {
        method: 'GET',
        query: params
      }),

    createPermission: (permissionData) =>
      api('/permissions', {
        method: 'POST',
        body: permissionData
      }),

    updatePermission: (permissionId, permissionData) =>
      api(`/permissions/${permissionId}`, {
        method: 'PUT',
        body: permissionData
      }),

    deletePermission: (permissionId) =>
      api(`/permissions/${permissionId}`, {
        method: 'DELETE'
      }),

    // Role Management
    getRoles: (params = {}) =>
      api('/roles', {
        method: 'GET',
        query: params
      }),

    createRole: (roleData) =>
      api('/roles', {
        method: 'POST',
        body: roleData
      }),

    updateRole: (roleId, roleData) =>
      api(`/roles/${roleId}`, {
        method: 'PUT',
        body: roleData
      }),

    deleteRole: (roleId) =>
      api(`/roles/${roleId}`, {
        method: 'DELETE'
      }),

    // Role-Permission Assignment
    getRolePermissions: (roleId) =>
      api(`/roles/${roleId}/permissions`, {
        method: 'GET'
      }),

    assignRolePermissions: (roleId, permissionIds) =>
      api(`/roles/${roleId}/permissions`, {
        method: 'PUT',
        body: { permission_ids: permissionIds }
      }),

    // User Permissions
    getUserPermissions: (userId = null) =>
      api(`/users/${userId || 'me'}/permissions`, {
        method: 'GET'
      }),

    assignUserRoles: (userId, roleIds) =>
      api(`/users/${userId}/roles`, {
        method: 'PUT',
        body: { role_ids: roleIds }
      }),

    // System Management
    getSystemStats: () =>
      api('/system/stats', {
        method: 'GET'
      }),

    getSystemLogs: (params = {}) =>
      api('/system/logs', {
        method: 'GET',
        query: params
      })
  }
}