import { defineStore } from 'pinia'
import type { User, LoginCredentials, RegisterData, ApiResponse } from '~/types/auth'

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  permissions: string[]
  roles: string[]
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false,
    isLoading: false,
    permissions: [],
    roles: []
  }),

  getters: {
    /**
     * Get current user information
     */
    currentUser: (state) => state.user,
    
    /**
     * Check if user has specific permission
     */
    hasPermission: (state) => (permission: string) => {
      return state.permissions.includes(permission)
    },
    
    /**
     * Check if user has specific role
     */
    hasRole: (state) => (role: string) => {
      return state.roles.includes(role)
    },
    
    /**
     * Check if user has any of the specified roles
     */
    hasAnyRole: (state) => (roles: string[]) => {
      return roles.some(role => state.roles.includes(role))
    },
    
    /**
     * Get user role level for dashboard routing
     */
    userRoleLevel: (state) => {
      // Support both English and Chinese role names
      if (state.roles.includes('super_admin') || state.roles.includes('超管')) return 'super_admin'
      if (state.roles.includes('admin') || state.roles.includes('管理員')) return 'admin'
      if (state.roles.includes('server_owner') || state.roles.includes('服主')) return 'server_owner'
      if (state.roles.includes('reviewer') || state.roles.includes('審核員')) return 'reviewer'
      return 'user'
    }
  },

  actions: {
    /**
     * Login user with credentials
     */
    async login(credentials: LoginCredentials): Promise<void> {
      this.isLoading = true
      
      try {
        const { $api } = useNuxtApp()
        // Map frontend credentials to backend expected format
        const loginData = {
          login: credentials.username, // Backend expects 'login' field
          password: credentials.password,
          remember: credentials.remember
        }

        const response = await $api('/auth/login', {
          method: 'POST',
          body: JSON.stringify(loginData)
        })

        if (response.status === 'success' && response.data) {
          // Backend returns { user, tokens } structure
          const backendRoles = response.data.user.roles || []
          const roleNames = backendRoles.map((role: any) => role.name || role)

          const authData = {
            user: response.data.user,
            token: response.data.tokens.access_token,
            permissions: response.data.user.permissions || [],
            roles: roleNames
          }
          this.setAuth(authData)
          
          // Start token refresh mechanism
          if (process.client) {
            const { startTokenRefresh } = useTokenRefresh()
            startTokenRefresh()
          }
          
          // Navigate to appropriate dashboard based on role
          await navigateTo(this.getDashboardRoute())
        } else {
          throw new Error(response.message || '登入失敗')
        }
      } catch (error) {
        this.clearAuth()
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Register new user
     */
    async register(userData: RegisterData): Promise<void> {
      this.isLoading = true
      
      try {
        const { $api } = useNuxtApp()
        const response: ApiResponse<{
          user: User
          token: string
          permissions: string[]
          roles: string[]
        }> = await $api('/auth/register', {
          method: 'POST',
          body: userData
        })

        if (response.success && response.data) {
          this.setAuth(response.data)
          await navigateTo('/dashboard')
        } else {
          throw new Error(response.message || '註冊失敗')
        }
      } catch (error) {
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Logout user
     */
    async logout(): Promise<void> {
      try {
        const { $api } = useNuxtApp()
        await $api('/auth/logout', {
          method: 'POST'
        })
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        // Stop token refresh
        if (process.client) {
          const { stopTokenRefresh } = useTokenRefresh()
          stopTokenRefresh()
        }
        
        this.clearAuth()
        await navigateTo('/login')
      }
    },

    /**
     * Refresh user data and permissions
     */
    async refreshUser(): Promise<void> {
      if (!this.token) return

      try {
        const { $api } = useNuxtApp()
        const response: ApiResponse<{
          user: User
          permissions: string[]
          roles: string[]
        }> = await $api('/auth/me')

        if (response.success && response.data) {
          this.user = response.data.user
          this.permissions = response.data.permissions
          this.roles = response.data.roles
        } else {
          this.clearAuth()
        }
      } catch (error) {
        console.error('Refresh user error:', error)
        this.clearAuth()
      }
    },

    /**
     * Initialize auth state from stored token
     */
    async initializeAuth(): Promise<void> {
      const token = useCookie('auth-token', {
        default: () => null,
        secure: true,
        sameSite: 'lax'
      })

      if (token.value) {
        this.token = token.value
        this.isAuthenticated = true
        
        try {
          await this.refreshUser()
          
          // Start token refresh mechanism
          if (process.client) {
            const { startTokenRefresh } = useTokenRefresh()
            startTokenRefresh()
          }
        } catch (error) {
          console.error('Failed to initialize auth:', error)
          this.clearAuth()
        }
      }
    },

    /**
     * Set authentication data
     */
    setAuth(data: {
      user: User
      token: string
      permissions: string[]
      roles: string[]
    }): void {
      this.user = data.user
      this.token = data.token
      this.permissions = data.permissions
      this.roles = data.roles
      this.isAuthenticated = true

      // Store token in cookie
      const tokenCookie = useCookie('auth-token', {
        default: () => null,
        secure: true,
        sameSite: 'lax',
        maxAge: 60 * 60 * 24 * 7 // 7 days
      })
      tokenCookie.value = data.token
    },

    /**
     * Clear authentication data
     */
    clearAuth(): void {
      this.user = null
      this.token = null
      this.permissions = []
      this.roles = []
      this.isAuthenticated = false

      // Clear token cookie
      const tokenCookie = useCookie('auth-token')
      tokenCookie.value = null
    },

    /**
     * Get appropriate dashboard route based on user role
     */
    getDashboardRoute(): string {
      const roleLevel = this.userRoleLevel
      
      switch (roleLevel) {
        case 'super_admin':
          return '/admin/dashboard'
        case 'admin':
          return '/admin/dashboard'
        case 'server_owner':
          return '/server/dashboard'
        case 'reviewer':
          return '/reviewer/dashboard'
        default:
          return '/dashboard'
      }
    },

    /**
     * Check if user can access specific route
     */
    canAccessRoute(route: string): boolean {
      if (!this.isAuthenticated) return false
      
      // Define route permissions
      const routePermissions: Record<string, string[]> = {
        '/admin': ['超管', '管理員'],
        '/server': ['超管', '管理員', '服主'],
        '/reviewer': ['超管', '管理員', '審核員'],
        '/promotion': ['超管', '管理員', '服主', '用戶'],
        '/dashboard': ['超管', '管理員', '服主', '審核員', '用戶']
      }
      
      // Check if route requires specific roles
      for (const [path, requiredRoles] of Object.entries(routePermissions)) {
        if (route.startsWith(path)) {
          return this.hasAnyRole(requiredRoles)
        }
      }
      
      return true
    }
  }
})