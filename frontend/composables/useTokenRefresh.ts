/**
 * JWT Token refresh composable
 * Handles automatic token refresh and session management
 */
export const useTokenRefresh = () => {
  const authStore = useAuthStore()
  
  // Token refresh interval (15 minutes)
  const REFRESH_INTERVAL = 15 * 60 * 1000
  
  // Refresh threshold (5 minutes before expiry)
  const REFRESH_THRESHOLD = 5 * 60 * 1000
  
  let refreshTimer: NodeJS.Timeout | null = null
  let isRefreshing = false
  let refreshPromise: Promise<void> | null = null

  /**
   * Start automatic token refresh
   */
  const startTokenRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer)
    }

    refreshTimer = setInterval(() => {
      checkAndRefreshToken()
    }, REFRESH_INTERVAL)
  }

  /**
   * Stop automatic token refresh
   */
  const stopTokenRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer)
      refreshTimer = null
    }
  }

  /**
   * Check if token needs refresh and refresh if necessary
   */
  const checkAndRefreshToken = async (): Promise<void> => {
    if (!authStore.token || !authStore.isAuthenticated) {
      return
    }

    try {
      const tokenData = parseJWTPayload(authStore.token)
      if (!tokenData) {
        console.warn('Invalid token format')
        return
      }

      const now = Date.now() / 1000
      const expiryTime = tokenData.exp
      const timeUntilExpiry = (expiryTime - now) * 1000

      // Refresh if token expires within threshold
      if (timeUntilExpiry <= REFRESH_THRESHOLD) {
        await refreshToken()
      }
    } catch (error) {
      console.error('Token check failed:', error)
      // Optionally logout on critical errors
      if (isTokenExpired()) {
        await authStore.logout()
      }
    }
  }

  /**
   * Manually refresh token
   */
  const refreshToken = async (): Promise<void> => {
    // Prevent concurrent refresh requests
    if (isRefreshing) {
      return refreshPromise || Promise.resolve()
    }

    isRefreshing = true
    refreshPromise = performTokenRefresh()

    try {
      await refreshPromise
    } finally {
      isRefreshing = false
      refreshPromise = null
    }
  }

  /**
   * Perform the actual token refresh
   */
  const performTokenRefresh = async (): Promise<void> => {
    try {
      const { $api } = useNuxtApp()
      
      const response = await $api('/auth/refresh', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${authStore.token}`
        }
      })

      if (response.success && response.data) {
        // Update token in store
        authStore.setAuth({
          user: response.data.user || authStore.user!,
          token: response.data.token,
          permissions: response.data.permissions || authStore.permissions,
          roles: response.data.roles || authStore.roles
        })

        console.log('Token refreshed successfully')
      } else {
        throw new Error(response.message || 'Token refresh failed')
      }
    } catch (error) {
      console.error('Token refresh failed:', error)
      
      // If refresh fails, logout user
      await authStore.logout()
      
      // Show notification
      ElMessage.error('登入已過期，請重新登入')
      
      // Redirect to login
      await navigateTo('/login')
      
      throw error
    }
  }

  /**
   * Check if current token is expired
   */
  const isTokenExpired = (): boolean => {
    if (!authStore.token) return true

    try {
      const tokenData = parseJWTPayload(authStore.token)
      if (!tokenData) return true

      const now = Date.now() / 1000
      return tokenData.exp <= now
    } catch {
      return true
    }
  }

  /**
   * Get token expiry time
   */
  const getTokenExpiry = (): Date | null => {
    if (!authStore.token) return null

    try {
      const tokenData = parseJWTPayload(authStore.token)
      if (!tokenData) return null

      return new Date(tokenData.exp * 1000)
    } catch {
      return null
    }
  }

  /**
   * Get time until token expires (in milliseconds)
   */
  const getTimeUntilExpiry = (): number => {
    const expiry = getTokenExpiry()
    if (!expiry) return 0

    return expiry.getTime() - Date.now()
  }

  /**
   * Check if token needs refresh soon
   */
  const needsRefresh = (): boolean => {
    const timeUntilExpiry = getTimeUntilExpiry()
    return timeUntilExpiry <= REFRESH_THRESHOLD
  }

  return {
    startTokenRefresh,
    stopTokenRefresh,
    checkAndRefreshToken,
    refreshToken,
    isTokenExpired,
    getTokenExpiry,
    getTimeUntilExpiry,
    needsRefresh,
    isRefreshing: readonly(ref(isRefreshing))
  }
}

/**
 * Parse JWT payload without verification
 */
function parseJWTPayload(token: string): any {
  try {
    const parts = token.split('.')
    if (parts.length !== 3) {
      throw new Error('Invalid JWT format')
    }

    const payload = parts[1]
    const decoded = atob(payload.replace(/-/g, '+').replace(/_/g, '/'))
    return JSON.parse(decoded)
  } catch (error) {
    console.error('Failed to parse JWT:', error)
    return null
  }
}