import type { UserRoleLevel } from '~/types/auth'

/**
 * Permission composable for role-based access control
 */
export const usePermissions = () => {
  const authStore = useAuthStore()

  /**
   * Check if user has specific permission
   */
  const hasPermission = (permission: string): boolean => {
    return authStore.hasPermission(permission)
  }

  /**
   * Check if user has specific role
   */
  const hasRole = (role: string): boolean => {
    return authStore.hasRole(role)
  }

  /**
   * Check if user has any of the specified roles
   */
  const hasAnyRole = (roles: string[]): boolean => {
    return authStore.hasAnyRole(roles)
  }

  /**
   * Check if user has all specified roles
   */
  const hasAllRoles = (roles: string[]): boolean => {
    return roles.every(role => authStore.hasRole(role))
  }

  /**
   * Get user role level
   */
  const getUserRoleLevel = (): UserRoleLevel => {
    return authStore.userRoleLevel
  }

  /**
   * Check if user is admin level (超管 or 管理員)
   */
  const isAdmin = (): boolean => {
    return hasAnyRole(['super_admin', 'admin', '超管', '管理員'])
  }

  /**
   * Check if user is super admin
   */
  const isSuperAdmin = (): boolean => {
    return hasAnyRole(['super_admin', '超管'])
  }

  /**
   * Check if user is server owner
   */
  const isServerOwner = (): boolean => {
    return hasAnyRole(['server_owner', '服主'])
  }

  /**
   * Check if user is reviewer
   */
  const isReviewer = (): boolean => {
    return hasAnyRole(['reviewer', '審核員'])
  }

  /**
   * Check if user can manage servers
   */
  const canManageServers = (): boolean => {
    return hasAnyRole(['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主'])
  }

  /**
   * Check if user can review promotions
   */
  const canReviewPromotions = (): boolean => {
    return hasAnyRole(['super_admin', 'admin', 'reviewer', '超管', '管理員', '審核員'])
  }

  /**
   * Check if user can manage users
   */
  const canManageUsers = (): boolean => {
    return hasAnyRole(['super_admin', 'admin', '超管', '管理員'])
  }

  /**
   * Check if user can view admin panel
   */
  const canViewAdminPanel = (): boolean => {
    return hasAnyRole(['super_admin', 'admin', '超管', '管理員'])
  }

  /**
   * Check if user can access route
   */
  const canAccessRoute = (route: string): boolean => {
    return authStore.canAccessRoute(route)
  }

  /**
   * Get role-based menu items
   */
  const getRoleBasedMenuItems = () => {
    const baseItems = [
      {
        title: '個人資料',
        path: '/profile',
        icon: 'User',
        roles: ['super_admin', 'admin', 'server_owner', 'reviewer', 'user', '超管', '管理員', '服主', '審核員', '用戶']
      }
    ]

    const menuItems = [
      ...baseItems,
      {
        title: '儀表板',
        path: '/dashboard',
        icon: 'Odometer',
        roles: ['super_admin', 'admin', 'server_owner', 'reviewer', 'user', '超管', '管理員', '服主', '審核員', '用戶']
      },
      {
        title: '推廣管理',
        path: '/promotion',
        icon: 'Share',
        roles: ['super_admin', 'admin', 'server_owner', 'user', '超管', '管理員', '服主', '用戶']
      },
      {
        title: '伺服器管理',
        path: '/server',
        icon: 'Server',
        roles: ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主']
      },
      {
        title: '審核管理',
        path: '/reviewer',
        icon: 'Document',
        roles: ['super_admin', 'admin', 'reviewer', '超管', '管理員', '審核員']
      },
      {
        title: '系統管理',
        path: '/admin',
        icon: 'Setting',
        roles: ['super_admin', 'admin', '超管', '管理員']
      }
    ]

    // Filter menu items based on user roles
    return menuItems.filter(item => hasAnyRole(item.roles))
  }

  /**
   * Get role display name
   */
  const getRoleDisplayName = (role: string): string => {
    const roleMap: Record<string, string> = {
      'super_admin': '超級管理員',
      'admin': '管理員',
      'server_owner': '伺服器主人',
      'reviewer': '審核員',
      'user': '一般用戶',
      '超管': '超級管理員',
      '管理員': '管理員',
      '服主': '伺服器主人',
      '審核員': '審核員',
      '用戶': '一般用戶'
    }
    return roleMap[role] || role
  }

  /**
   * Get role color for UI display
   */
  const getRoleColor = (role: string): string => {
    const colorMap: Record<string, string> = {
      'super_admin': 'danger',
      'admin': 'warning',
      'server_owner': 'primary',
      'reviewer': 'success',
      'user': 'info',
      '超管': 'danger',
      '管理員': 'warning',
      '服主': 'primary',
      '審核員': 'success',
      '用戶': 'info'
    }
    return colorMap[role] || 'info'
  }

  /**
   * Check if user can perform action on resource
   */
  const canPerformAction = (action: string, resource: string): boolean => {
    // Define permission matrix (support both English and Chinese role names)
    const permissionMatrix: Record<string, Record<string, string[]>> = {
      'create': {
        'server': ['super_admin', 'admin', '超管', '管理員'],
        'user': ['super_admin', 'admin', '超管', '管理員'],
        'promotion': ['super_admin', 'admin', 'server_owner', 'user', '超管', '管理員', '服主', '用戶'],
        'event': ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主']
      },
      'read': {
        'server': ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主'],
        'user': ['super_admin', 'admin', '超管', '管理員'],
        'promotion': ['super_admin', 'admin', 'server_owner', 'reviewer', 'user', '超管', '管理員', '服主', '審核員', '用戶'],
        'event': ['super_admin', 'admin', 'server_owner', 'user', '超管', '管理員', '服主', '用戶']
      },
      'update': {
        'server': ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主'],
        'user': ['super_admin', 'admin', '超管', '管理員'],
        'promotion': ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主'],
        'event': ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主']
      },
      'delete': {
        'server': ['super_admin', 'admin', '超管', '管理員'],
        'user': ['super_admin', 'admin', '超管', '管理員'],
        'promotion': ['super_admin', 'admin', '超管', '管理員'],
        'event': ['super_admin', 'admin', 'server_owner', '超管', '管理員', '服主']
      },
      'review': {
        'promotion': ['super_admin', 'admin', 'reviewer', '超管', '管理員', '審核員'],
        'server': ['super_admin', 'admin', '超管', '管理員']
      }
    }

    const requiredRoles = permissionMatrix[action]?.[resource]
    if (!requiredRoles) return false

    return hasAnyRole(requiredRoles)
  }

  return {
    hasPermission,
    hasRole,
    hasAnyRole,
    hasAllRoles,
    getUserRoleLevel,
    isAdmin,
    isSuperAdmin,
    isServerOwner,
    isReviewer,
    canManageServers,
    canReviewPromotions,
    canManageUsers,
    canViewAdminPanel,
    canAccessRoute,
    getRoleBasedMenuItems,
    getRoleDisplayName,
    getRoleColor,
    canPerformAction
  }
}