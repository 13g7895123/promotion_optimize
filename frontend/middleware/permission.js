export default defineNuxtRouteMiddleware((to) => {
  // Get permission management composable
  const { hasPermission, hasRole, hasAnyPermission, userPermissions, userRoles } = usePermissionManagement()

  // Check if user permissions are loaded
  if (!userPermissions.value.length && !userRoles.value.length) {
    // Redirect to login if no permissions loaded
    throw createError({
      statusCode: 401,
      statusMessage: 'Unauthorized - Please login'
    })
  }

  // Define route permission requirements
  const routePermissions = {
    // Dashboard
    '/': { roles: ['admin', 'super_admin', 'server_owner'] },
    '/dashboard': { roles: ['admin', 'super_admin', 'server_owner'] },

    // User Management
    '/settings/users': {
      permissions: [{ resource: 'users', action: 'view' }],
      roles: ['admin', 'super_admin']
    },

    // Server Management
    '/servers': {
      permissions: [{ resource: 'servers', action: 'view' }],
      roles: ['admin', 'super_admin', 'server_owner']
    },

    // Permission Management
    '/settings/permissions': {
      permissions: [{ resource: 'permissions', action: 'view' }],
      roles: ['super_admin']
    },

    // Statistics
    '/statistics': {
      permissions: [{ resource: 'statistics', action: 'view' }],
      roles: ['admin', 'super_admin', 'server_owner']
    },

    // Promotion Management
    '/promotions': {
      permissions: [{ resource: 'promotions', action: 'view' }],
      roles: ['admin', 'super_admin', 'server_owner', 'moderator']
    },

    // System Settings
    '/settings/system': {
      permissions: [{ resource: 'system', action: 'view' }],
      roles: ['super_admin']
    }
  }

  // Get permission requirements for current route
  const requirements = routePermissions[to.path]

  if (!requirements) {
    // No specific requirements, allow access
    return
  }

  // Check role requirements
  if (requirements.roles && requirements.roles.length > 0) {
    const hasRequiredRole = requirements.roles.some(role => hasRole(role))
    if (!hasRequiredRole) {
      throw createError({
        statusCode: 403,
        statusMessage: 'Forbidden - Insufficient role permissions'
      })
    }
  }

  // Check specific permission requirements
  if (requirements.permissions && requirements.permissions.length > 0) {
    const hasRequiredPermission = requirements.permissions.some(permission => hasPermission(permission))
    if (!hasRequiredPermission) {
      throw createError({
        statusCode: 403,
        statusMessage: 'Forbidden - Insufficient permissions'
      })
    }
  }

  // If we get here, user has required permissions
  return
})