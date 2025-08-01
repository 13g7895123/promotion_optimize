/**
 * Client-side auth initialization plugin
 * Handles authentication state initialization and token refresh setup
 */
export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore()
  
  // Initialize authentication state
  try {
    await authStore.initializeAuth()
  } catch (error) {
    console.error('Failed to initialize auth:', error)
  }

  // Handle page visibility changes to refresh tokens
  if (process.client) {
    const { checkAndRefreshToken } = useTokenRefresh()
    
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden && authStore.isAuthenticated) {
        // Check and refresh token when page becomes visible
        checkAndRefreshToken()
      }
    })

    // Handle focus events
    window.addEventListener('focus', () => {
      if (authStore.isAuthenticated) {
        checkAndRefreshToken()
      }
    })

    // Handle online/offline events
    window.addEventListener('online', () => {
      if (authStore.isAuthenticated) {
        checkAndRefreshToken()
      }
    })
  }
})