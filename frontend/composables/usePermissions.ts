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
    return hasAnyRole(['超管', '管理員'])
  }

  /**
   * Check if user is super admin
   */
  const isSuperAdmin = (): boolean => {
    return hasRole('超管')
  }

  /**
   * Check if user is server owner
   */
  const isServerOwner = (): boolean => {
    return hasRole('服主')
  }

  /**
   * Check if user is reviewer
   */
  const isReviewer = (): boolean => {
    return hasRole('審核員')
  }

  /**
   * Check if user can manage servers
   */
  const canManageServers = (): boolean => {
    return hasAnyRole(['超管', '管理員', '服主'])
  }

  /**
   * Check if user can review promotions
   */
  const canReviewPromotions = (): boolean => {
    return hasAnyRole(['超管', '管理員', '審核員'])
  }

  /**
   * Check if user can manage users
   */
  const canManageUsers = (): boolean => {
    return hasAnyRole(['超管', '管理員'])
  }

  /**
   * Check if user can view admin panel
   */
  const canViewAdminPanel = (): boolean => {
    return hasAnyRole(['超管', '管理員'])
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
        roles: ['超管', '管理員', '服主', '審核員', '用戶']
      }
    ]

    const menuItems = [
      ...baseItems,
      {
        title: '儀表板',
        path: '/dashboard',
        icon: 'Odometer',
        roles: ['超管', '管理員', '服主', '審核員', '用戶']
      },
      {
        title: '推廣管理',
        path: '/promotion',
        icon: 'Share',
        roles: ['超管', '管理員', '服主', '用戶']
      },
      {
        title: '伺服器管理',
        path: '/server',
        icon: 'Server',
        roles: ['超管', '管理員', '服主']
      },
      {
        title: '審核管理',
        path: '/reviewer',
        icon: 'Document',
        roles: ['超管', '管理員', '審核員']
      },
      {
        title: '系統管理',
        path: '/admin',
        icon: 'Setting',
        roles: ['超管', '管理員']
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
    // Define permission matrix
    const permissionMatrix: Record<string, Record<string, string[]>> = {
      'create': {
        'server': ['超管', '管理員'],
        'user': ['超管', '管理員'],
        'promotion': ['超管', '管理員', '服主', '用戶'],
        'event': ['超管', '管理員', '服主']
      },
      'read': {
        'server': ['超管', '管理員', '服主'],
        'user': ['超管', '管理員'],
        'promotion': ['超管', '管理員', '服主', '審核員', '用戶'],
        'event': ['超管', '管理員', '服主', '用戶']
      },
      'update': {
        'server': ['超管', '管理員', '服主'],
        'user': ['超管', '管理員'],
        'promotion': ['超管', '管理員', '服主'],
        'event': ['超管', '管理員', '服主']
      },
      'delete': {
        'server': ['超管', '管理員'],
        'user': ['超管', '管理員'],
        'promotion': ['超管', '管理員'],
        'event': ['超管', '管理員', '服主']
      },
      'review': {
        'promotion': ['超管', '管理員', '審核員'],
        'server': ['超管', '管理員']
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