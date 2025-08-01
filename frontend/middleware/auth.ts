export default defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore()
  
  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    return navigateTo('/login')
  }

  // Check if user can access the route
  if (!authStore.canAccessRoute(to.path)) {
    throw createError({
      statusCode: 403,
      statusMessage: '您沒有權限訪問此頁面'
    })
  }
})