export default defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore()
  const { hasAnyRole } = usePermissions()
  
  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    return navigateTo('/login')
  }

  // Get required roles from route meta
  const requiredRoles = to.meta.roles as string[] | undefined
  
  if (requiredRoles && requiredRoles.length > 0) {
    if (!hasAnyRole(requiredRoles)) {
      throw createError({
        statusCode: 403,
        statusMessage: '您沒有權限訪問此頁面'
      })
    }
  }
})