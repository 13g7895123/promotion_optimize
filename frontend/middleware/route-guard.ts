import type { RouteGuardResult } from '~/types/route'

/**
 * Enhanced route guard middleware for comprehensive permission control
 */
export default defineNuxtRouteMiddleware(async (to): Promise<RouteGuardResult> => {
  const authStore = useAuthStore()
  const { hasAnyRole, canPerformAction } = usePermissions()
  
  // Initialize auth if not already done
  if (!authStore.isAuthenticated && process.client) {
    try {
      await authStore.initializeAuth()
    } catch (error) {
      console.warn('Failed to initialize auth:', error)
    }
  }

  // Get route meta configuration
  const meta = to.meta as any
  const requireAuth = meta.requireAuth !== false // Default to true unless explicitly false
  const requiredRoles = meta.roles as string[] | undefined
  const requiredPermissions = meta.permissions as string[] | undefined
  const redirectTo = meta.redirectTo as string | undefined

  // Check authentication requirement
  if (requireAuth && !authStore.isAuthenticated) {
    // Store intended route for redirect after login
    const intendedRoute = useCookie('intended-route', {
      default: () => null,
      maxAge: 60 * 10 // 10 minutes
    })
    intendedRoute.value = to.fullPath
    
    return navigateTo('/login')
  }

  // Skip permission checks for non-authenticated routes
  if (!requireAuth || !authStore.isAuthenticated) {
    return
  }

  // Check role requirements
  if (requiredRoles && requiredRoles.length > 0) {
    if (!hasAnyRole(requiredRoles)) {
      return handleAccessDenied(redirectTo)
    }
  }

  // Check permission requirements
  if (requiredPermissions && requiredPermissions.length > 0) {
    const hasRequiredPermissions = requiredPermissions.some(permission => 
      authStore.hasPermission(permission)
    )
    
    if (!hasRequiredPermissions) {
      return handleAccessDenied(redirectTo)
    }
  }

  // Check dynamic permissions based on route path
  if (!checkDynamicPermissions(to.path)) {
    return handleAccessDenied(redirectTo)
  }

  // Clear intended route if we successfully accessed a protected route
  if (process.client) {
    const intendedRoute = useCookie('intended-route')
    if (intendedRoute.value) {
      intendedRoute.value = null
    }
  }
})

/**
 * Handle access denied scenarios
 */
function handleAccessDenied(redirectTo?: string): RouteGuardResult {
  if (redirectTo) {
    return navigateTo(redirectTo)
  }

  // Default access denied handling
  throw createError({
    statusCode: 403,
    statusMessage: '您沒有權限訪問此頁面',
    data: {
      showLogin: false,
      redirectTo: '/dashboard'
    }
  })
}

/**
 * Check dynamic permissions based on route patterns
 */
function checkDynamicPermissions(path: string): boolean {
  const authStore = useAuthStore()
  const { hasAnyRole, canPerformAction } = usePermissions()

  // Define dynamic route patterns and their requirements
  const routePatterns = [
    {
      pattern: /^\/admin/,
      check: () => hasAnyRole(['超管', '管理員'])
    },
    {
      pattern: /^\/server/,
      check: () => hasAnyRole(['超管', '管理員', '服主'])
    },
    {
      pattern: /^\/reviewer/,
      check: () => hasAnyRole(['超管', '管理員', '審核員'])
    },
    {
      pattern: /^\/promotion/,
      check: () => hasAnyRole(['超管', '管理員', '服主', '用戶'])
    },
    {
      pattern: /^\/reports/,
      check: () => hasAnyRole(['超管', '管理員', '服主'])
    },
    {
      pattern: /^\/admin\/users/,
      check: () => canPerformAction('read', 'user')
    },
    {
      pattern: /^\/server\/\d+\/edit/,
      check: () => canPerformAction('update', 'server')  
    },
    {
      pattern: /^\/admin\/settings/,
      check: () => hasAnyRole(['超管'])
    }
  ]

  // Find matching pattern
  const matchedPattern = routePatterns.find(({ pattern }) => pattern.test(path))
  
  if (matchedPattern) {
    return matchedPattern.check()
  }

  // Default to allow if no specific pattern matches
  return true
}