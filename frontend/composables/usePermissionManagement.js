export const usePermissionManagement = () => {
  const api = useApi()
  const loading = ref(false)
  const error = ref(null)

  // Permissions data
  const permissions = ref([])
  const groupedPermissions = ref({})
  const roles = ref([])
  const rolePermissions = ref([])

  // User permissions (for current user)
  const userPermissions = ref([])
  const userRoles = ref([])

  // Selected items
  const selectedRole = ref(null)
  const selectedPermissions = ref([])

  // Modal states
  const showRoleModal = ref(false)
  const showPermissionModal = ref(false)
  const showAssignModal = ref(false)

  // Form data
  const roleForm = ref({
    id: null,
    name: '',
    display_name: '',
    description: '',
    level: 1,
    is_active: true
  })

  const permissionForm = ref({
    id: null,
    name: '',
    display_name: '',
    description: '',
    resource: '',
    action: '',
    is_active: true
  })

  /**
   * Fetch all permissions
   */
  const fetchPermissions = async (params = {}) => {
    try {
      loading.value = true
      error.value = null

      const queryParams = {
        group_by_resource: params.groupByResource || false,
        resource: params.resource || '',
        search: params.search || '',
        ...params
      }

      const response = await api.getPermissions(queryParams)

      if (response.status === 'success') {
        if (params.groupByResource) {
          groupedPermissions.value = response.data.permissions
        } else {
          permissions.value = response.data.permissions || []
        }
      } else {
        throw new Error(response.message || 'Failed to fetch permissions')
      }
    } catch (err) {
      console.error('Failed to fetch permissions:', err)
      error.value = err.message || 'Failed to load permissions'
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch all roles
   */
  const fetchRoles = async (params = {}) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.getRoles(params)

      if (response.status === 'success') {
        roles.value = response.data.roles || []
      } else {
        throw new Error(response.message || 'Failed to fetch roles')
      }
    } catch (err) {
      console.error('Failed to fetch roles:', err)
      error.value = err.message || 'Failed to load roles'
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch permissions for a specific role
   */
  const fetchRolePermissions = async (roleId) => {
    try {
      loading.value = true
      const response = await api.getRolePermissions(roleId)

      if (response.status === 'success') {
        rolePermissions.value = response.data.permissions || []
        return response.data.permissions
      } else {
        throw new Error(response.message || 'Failed to fetch role permissions')
      }
    } catch (err) {
      console.error('Failed to fetch role permissions:', err)
      error.value = err.message || 'Failed to load role permissions'
      return []
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch current user permissions
   */
  const fetchUserPermissions = async () => {
    try {
      const response = await api.getUserPermissions()

      if (response.status === 'success') {
        userPermissions.value = response.data.permissions || []
        userRoles.value = response.data.roles || []
        return {
          permissions: response.data.permissions,
          roles: response.data.roles
        }
      } else {
        throw new Error(response.message || 'Failed to fetch user permissions')
      }
    } catch (err) {
      console.error('Failed to fetch user permissions:', err)
      return { permissions: [], roles: [] }
    }
  }

  /**
   * Create new role
   */
  const createRole = async (roleData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.createRole(roleData)

      if (response.status === 'success') {
        await fetchRoles()
        resetRoleForm()
        showRoleModal.value = false
        return { success: true, role: response.data.role }
      } else {
        throw new Error(response.message || 'Failed to create role')
      }
    } catch (err) {
      console.error('Failed to create role:', err)
      error.value = err.message || 'Failed to create role'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Update role
   */
  const updateRole = async (roleId, roleData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.updateRole(roleId, roleData)

      if (response.status === 'success') {
        // Update local role data
        const roleIndex = roles.value.findIndex(r => r.id === roleId)
        if (roleIndex !== -1) {
          roles.value[roleIndex] = { ...roles.value[roleIndex], ...response.data.role }
        }

        resetRoleForm()
        showRoleModal.value = false
        return { success: true, role: response.data.role }
      } else {
        throw new Error(response.message || 'Failed to update role')
      }
    } catch (err) {
      console.error('Failed to update role:', err)
      error.value = err.message || 'Failed to update role'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete role
   */
  const deleteRole = async (roleId) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.deleteRole(roleId)

      if (response.status === 'success') {
        roles.value = roles.value.filter(r => r.id !== roleId)
        return { success: true }
      } else {
        throw new Error(response.message || 'Failed to delete role')
      }
    } catch (err) {
      console.error('Failed to delete role:', err)
      error.value = err.message || 'Failed to delete role'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new permission
   */
  const createPermission = async (permissionData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.createPermission(permissionData)

      if (response.status === 'success') {
        await fetchPermissions()
        resetPermissionForm()
        showPermissionModal.value = false
        return { success: true, permission: response.data.permission }
      } else {
        throw new Error(response.message || 'Failed to create permission')
      }
    } catch (err) {
      console.error('Failed to create permission:', err)
      error.value = err.message || 'Failed to create permission'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Update permission
   */
  const updatePermission = async (permissionId, permissionData) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.updatePermission(permissionId, permissionData)

      if (response.status === 'success') {
        await fetchPermissions()
        resetPermissionForm()
        showPermissionModal.value = false
        return { success: true, permission: response.data.permission }
      } else {
        throw new Error(response.message || 'Failed to update permission')
      }
    } catch (err) {
      console.error('Failed to update permission:', err)
      error.value = err.message || 'Failed to update permission'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete permission
   */
  const deletePermission = async (permissionId) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.deletePermission(permissionId)

      if (response.status === 'success') {
        permissions.value = permissions.value.filter(p => p.id !== permissionId)
        return { success: true }
      } else {
        throw new Error(response.message || 'Failed to delete permission')
      }
    } catch (err) {
      console.error('Failed to delete permission:', err)
      error.value = err.message || 'Failed to delete permission'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Assign permissions to role
   */
  const assignPermissionsToRole = async (roleId, permissionIds) => {
    try {
      loading.value = true
      error.value = null

      const response = await api.assignRolePermissions(roleId, permissionIds)

      if (response.status === 'success') {
        await fetchRolePermissions(roleId)
        showAssignModal.value = false
        return { success: true }
      } else {
        throw new Error(response.message || 'Failed to assign permissions')
      }
    } catch (err) {
      console.error('Failed to assign permissions:', err)
      error.value = err.message || 'Failed to assign permissions'
      return { success: false, error: err.message }
    } finally {
      loading.value = false
    }
  }

  /**
   * Check if user has permission
   */
  const hasPermission = (permission) => {
    if (!userPermissions.value.length) return false

    // Check by permission name
    if (typeof permission === 'string') {
      return userPermissions.value.some(p => p.name === permission)
    }

    // Check by resource and action
    if (permission.resource && permission.action) {
      return userPermissions.value.some(p =>
        p.resource === permission.resource && p.action === permission.action
      )
    }

    return false
  }

  /**
   * Check if user has any of the permissions
   */
  const hasAnyPermission = (permissions) => {
    return permissions.some(permission => hasPermission(permission))
  }

  /**
   * Check if user has all permissions
   */
  const hasAllPermissions = (permissions) => {
    return permissions.every(permission => hasPermission(permission))
  }

  /**
   * Check if user has role
   */
  const hasRole = (roleName) => {
    return userRoles.value.some(role => role.name === roleName)
  }

  /**
   * Check if user has any of the roles
   */
  const hasAnyRole = (roleNames) => {
    return roleNames.some(roleName => hasRole(roleName))
  }

  /**
   * Get user's highest role level
   */
  const getUserLevel = () => {
    if (!userRoles.value.length) return 0
    return Math.max(...userRoles.value.map(role => role.level || 0))
  }

  /**
   * Check if user can manage another user (based on role levels)
   */
  const canManageUser = (targetUserRoles) => {
    const userLevel = getUserLevel()
    const targetLevel = targetUserRoles.length ?
      Math.max(...targetUserRoles.map(role => role.level || 0)) : 0

    return userLevel > targetLevel
  }

  /**
   * Reset role form
   */
  const resetRoleForm = () => {
    roleForm.value = {
      id: null,
      name: '',
      display_name: '',
      description: '',
      level: 1,
      is_active: true
    }
  }

  /**
   * Reset permission form
   */
  const resetPermissionForm = () => {
    permissionForm.value = {
      id: null,
      name: '',
      display_name: '',
      description: '',
      resource: '',
      action: '',
      is_active: true
    }
  }

  /**
   * Open role modal for create/edit
   */
  const openRoleModal = (role = null) => {
    if (role) {
      roleForm.value = { ...role }
    } else {
      resetRoleForm()
    }
    showRoleModal.value = true
  }

  /**
   * Open permission modal for create/edit
   */
  const openPermissionModal = (permission = null) => {
    if (permission) {
      permissionForm.value = { ...permission }
    } else {
      resetPermissionForm()
    }
    showPermissionModal.value = true
  }

  /**
   * Open assign permissions modal
   */
  const openAssignModal = async (role) => {
    selectedRole.value = role
    const currentPermissions = await fetchRolePermissions(role.id)
    selectedPermissions.value = currentPermissions.map(p => p.id)
    showAssignModal.value = true
  }

  /**
   * Format role level display
   */
  const formatRoleLevel = (level) => {
    const levelMap = {
      5: '超級管理員',
      4: '管理員',
      3: '服主',
      2: '審核員',
      1: '一般用戶'
    }
    return levelMap[level] || `等級 ${level}`
  }

  /**
   * Get role level color
   */
  const getRoleLevelColor = (level) => {
    switch (level) {
      case 5:
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
      case 4:
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400'
      case 3:
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
      case 2:
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
      case 1:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    }
  }

  /**
   * Format permission resource display
   */
  const formatResource = (resource) => {
    const resourceMap = {
      'users': '用戶管理',
      'servers': '伺服器管理',
      'promotions': '推廣管理',
      'rewards': '獎勵管理',
      'statistics': '統計報表',
      'system': '系統設定',
      'permissions': '權限管理',
      'roles': '角色管理'
    }
    return resourceMap[resource] || resource
  }

  /**
   * Format permission action display
   */
  const formatAction = (action) => {
    const actionMap = {
      'view': '查看',
      'create': '創建',
      'update': '更新',
      'delete': '刪除',
      'approve': '審核',
      'suspend': '暫停',
      'export': '匯出',
      'import': '匯入'
    }
    return actionMap[action] || action
  }

  return {
    // Data
    permissions,
    groupedPermissions,
    roles,
    rolePermissions,
    userPermissions,
    userRoles,
    selectedRole,
    selectedPermissions,
    loading,
    error,

    // Modal states
    showRoleModal,
    showPermissionModal,
    showAssignModal,

    // Forms
    roleForm,
    permissionForm,

    // Methods
    fetchPermissions,
    fetchRoles,
    fetchRolePermissions,
    fetchUserPermissions,
    createRole,
    updateRole,
    deleteRole,
    createPermission,
    updatePermission,
    deletePermission,
    assignPermissionsToRole,

    // Permission checks
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    getUserLevel,
    canManageUser,

    // Modals
    openRoleModal,
    openPermissionModal,
    openAssignModal,

    // Formatters
    formatRoleLevel,
    getRoleLevelColor,
    formatResource,
    formatAction
  }
}