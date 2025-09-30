// Global permissions utility composable
export const usePermissions = () => {
  // Use the permission management composable
  const {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    getUserLevel,
    canManageUser,
    userPermissions,
    userRoles,
    fetchUserPermissions
  } = usePermissionManagement()

  // Initialize user permissions if not loaded
  const initializePermissions = async () => {
    if (!userPermissions.value.length && !userRoles.value.length) {
      await fetchUserPermissions()
    }
  }

  /**
   * Check if user can access a specific feature
   */
  const canAccess = (feature) => {
    const featurePermissions = {
      // Dashboard features
      'dashboard': { roles: ['admin', 'super_admin', 'server_owner'] },
      'analytics': { permissions: [{ resource: 'statistics', action: 'view' }] },

      // User management features
      'users.view': { permissions: [{ resource: 'users', action: 'view' }] },
      'users.create': { permissions: [{ resource: 'users', action: 'create' }] },
      'users.update': { permissions: [{ resource: 'users', action: 'update' }] },
      'users.delete': { permissions: [{ resource: 'users', action: 'delete' }] },
      'users.roles': { permissions: [{ resource: 'users', action: 'update' }] },

      // Server management features
      'servers.view': { permissions: [{ resource: 'servers', action: 'view' }] },
      'servers.create': { permissions: [{ resource: 'servers', action: 'create' }] },
      'servers.update': { permissions: [{ resource: 'servers', action: 'update' }] },
      'servers.delete': { permissions: [{ resource: 'servers', action: 'delete' }] },
      'servers.approve': { permissions: [{ resource: 'servers', action: 'approve' }] },
      'servers.suspend': { permissions: [{ resource: 'servers', action: 'suspend' }] },

      // Promotion features
      'promotions.view': { permissions: [{ resource: 'promotions', action: 'view' }] },
      'promotions.create': { permissions: [{ resource: 'promotions', action: 'create' }] },
      'promotions.update': { permissions: [{ resource: 'promotions', action: 'update' }] },
      'promotions.delete': { permissions: [{ resource: 'promotions', action: 'delete' }] },

      // Statistics features
      'statistics.view': { permissions: [{ resource: 'statistics', action: 'view' }] },
      'statistics.export': { permissions: [{ resource: 'statistics', action: 'export' }] },

      // Permission management features
      'permissions.view': { roles: ['super_admin'] },
      'permissions.manage': { roles: ['super_admin'] },
      'roles.view': { roles: ['super_admin'] },
      'roles.manage': { roles: ['super_admin'] },

      // System features
      'system.view': { permissions: [{ resource: 'system', action: 'view' }] },
      'system.config': { permissions: [{ resource: 'system', action: 'update' }] },
      'system.logs': { permissions: [{ resource: 'system', action: 'view' }] }
    }

    const requirements = featurePermissions[feature]
    if (!requirements) return true // No restrictions

    // Check role requirements
    if (requirements.roles) {
      return hasAnyRole(requirements.roles)
    }

    // Check permission requirements
    if (requirements.permissions) {
      return hasAnyPermission(requirements.permissions)
    }

    return false
  }

  /**
   * Check if user can perform action on specific resource
   */
  const canPerformAction = (resource, action) => {
    return hasPermission({ resource, action })
  }

  /**
   * Check if user is admin or higher
   */
  const isAdmin = computed(() => {
    return hasAnyRole(['admin', 'super_admin'])
  })

  /**
   * Check if user is super admin
   */
  const isSuperAdmin = computed(() => {
    return hasRole('super_admin')
  })

  /**
   * Check if user is server owner or higher
   */
  const isServerOwnerOrHigher = computed(() => {
    return hasAnyRole(['server_owner', 'admin', 'super_admin'])
  })

  /**
   * Check if user is moderator or higher
   */
  const isModeratorOrHigher = computed(() => {
    return hasAnyRole(['moderator', 'server_owner', 'admin', 'super_admin'])
  })

  /**
   * Get user's display roles
   */
  const userRoleNames = computed(() => {
    return userRoles.value.map(role => role.display_name).join(', ')
  })

  /**
   * Get user's permission count
   */
  const userPermissionCount = computed(() => {
    return userPermissions.value.length
  })

  /**
   * Check if user can manage another user based on their roles
   */
  const canManageOtherUser = (targetUserRoles) => {
    return canManageUser(targetUserRoles)
  }

  /**
   * Get navigation items based on user permissions
   */
  const getAuthorizedNavigation = () => {
    const baseNavigation = [
      {
        name: '儀表板',
        href: '/',
        icon: 'HomeIcon',
        show: canAccess('dashboard')
      },
      {
        name: '用戶管理',
        href: '/settings/users',
        icon: 'UsersIcon',
        show: canAccess('users.view')
      },
      {
        name: '伺服器管理',
        href: '/servers',
        icon: 'ServerIcon',
        show: canAccess('servers.view')
      },
      {
        name: '推廣管理',
        href: '/promotions',
        icon: 'MegaphoneIcon',
        show: canAccess('promotions.view')
      },
      {
        name: '統計分析',
        href: '/statistics',
        icon: 'ChartBarIcon',
        show: canAccess('statistics.view')
      },
      {
        name: '設定',
        icon: 'CogIcon',
        children: [
          {
            name: '權限管理',
            href: '/settings/permissions',
            show: canAccess('permissions.view')
          },
          {
            name: '系統設定',
            href: '/settings/system',
            show: canAccess('system.view')
          }
        ].filter(item => item.show)
      }
    ]

    return baseNavigation.filter(item => item.show || (item.children && item.children.length > 0))
  }

  /**
   * Permission-based directive for v-show
   */
  const vPermission = {
    mounted(el, binding) {
      const permission = binding.value
      if (!canAccess(permission)) {
        el.style.display = 'none'
      }
    },
    updated(el, binding) {
      const permission = binding.value
      if (!canAccess(permission)) {
        el.style.display = 'none'
      } else {
        el.style.display = ''
      }
    }
  }

  /**
   * Permission-based directive for removing elements
   */
  const vPermissionRemove = {
    mounted(el, binding) {
      const permission = binding.value
      if (!canAccess(permission)) {
        el.remove()
      }
    }
  }

  return {
    // Initialization
    initializePermissions,

    // Permission checks
    canAccess,
    canPerformAction,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    canManageOtherUser,

    // User info
    userPermissions: readonly(userPermissions),
    userRoles: readonly(userRoles),
    userRoleNames,
    userPermissionCount,
    getUserLevel,

    // Convenience checks
    isAdmin,
    isSuperAdmin,
    isServerOwnerOrHigher,
    isModeratorOrHigher,

    // Navigation
    getAuthorizedNavigation,

    // Directives
    vPermission,
    vPermissionRemove
  }
}