import type { ApiResponse } from '~/types/auth'

interface ApiOptions extends RequestInit {
  params?: Record<string, any>
  timeout?: number
}

class ApiClient {
  private baseURL: string
  private defaultTimeout: number = 10000

  constructor(baseURL: string) {
    this.baseURL = baseURL
  }

  /**
   * Create request URL with query parameters
   */
  private createURL(endpoint: string, params?: Record<string, any>): string {
    const url = new URL(`${this.baseURL}${endpoint}`)
    
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.append(key, String(value))
        }
      })
    }
    
    return url.toString()
  }

  /**
   * Get authorization header
   */
  private getAuthHeader(): Record<string, string> {
    const tokenCookie = useCookie('auth-token')
    const token = tokenCookie.value
    
    return token ? { Authorization: `Bearer ${token}` } : {}
  }

  /**
   * Handle API response with token refresh retry
   */
  private async handleResponse<T>(
    response: Response, 
    originalRequest?: () => Promise<Response>
  ): Promise<ApiResponse<T>> {
    let data: ApiResponse<T>
    
    try {
      data = await response.json()
    } catch (error) {
      throw new Error('無效的響應格式')
    }

    // Handle authentication errors with token refresh retry
    if (response.status === 401) {
      const authStore = useAuthStore()
      
      // Try to refresh token if we have one
      if (authStore.isAuthenticated && originalRequest) {
        try {
          const { refreshToken } = useTokenRefresh()
          await refreshToken()
          
          // Retry the original request with new token
          const retryResponse = await originalRequest()
          return await this.handleResponse<T>(retryResponse)
        } catch (refreshError) {
          console.error('Token refresh failed:', refreshError)
          // Fallback to logout
          authStore.clearAuth()
          await navigateTo('/login')
          throw new Error('認證已過期，請重新登入')
        }
      } else {
        // No token or can't retry, logout
        authStore.clearAuth()
        await navigateTo('/login')
        throw new Error('認證已過期，請重新登入')
      }
    }

    // Handle server errors
    if (!response.ok) {
      const message = data.message || `請求失敗 (${response.status})`
      throw new Error(message)
    }

    return data
  }

  /**
   * Make API request with retry capability
   */
  async request<T = any>(
    endpoint: string, 
    options: ApiOptions = {}
  ): Promise<ApiResponse<T>> {
    const {
      params,
      timeout = this.defaultTimeout,
      headers = {},
      ...fetchOptions
    } = options

    const url = this.createURL(endpoint, params)
    
    // Create request function for retry capability
    const makeRequest = async (): Promise<Response> => {
      const requestHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...this.getAuthHeader(),
        ...headers
      }

      // Create abort controller for timeout
      const controller = new AbortController()
      const timeoutId = setTimeout(() => controller.abort(), timeout)

      try {
        const response = await fetch(url, {
          ...fetchOptions,
          headers: requestHeaders,
          signal: controller.signal
        })

        clearTimeout(timeoutId)
        return response
      } catch (error) {
        clearTimeout(timeoutId)
        
        if (error instanceof Error) {
          if (error.name === 'AbortError') {
            throw new Error('請求超時，請稍後再試')
          }
          throw error
        }
        
        throw new Error('網路錯誤，請檢查連接狀態')
      }
    }

    try {
      const response = await makeRequest()
      return await this.handleResponse<T>(response, makeRequest)
    } catch (error) {
      if (error instanceof Error) {
        throw error
      }
      
      throw new Error('請求失敗，請稍後再試')
    }
  }

  /**
   * GET request
   */
  async get<T = any>(endpoint: string, params?: Record<string, any>): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'GET', params })
  }

  /**
   * POST request
   */
  async post<T = any>(endpoint: string, body?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined
    })
  }

  /**
   * PUT request
   */
  async put<T = any>(endpoint: string, body?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined
    })
  }

  /**
   * PATCH request
   */
  async patch<T = any>(endpoint: string, body?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined
    })
  }

  /**
   * DELETE request
   */
  async delete<T = any>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'DELETE' })
  }

  /**
   * Upload file
   */
  async upload<T = any>(
    endpoint: string, 
    formData: FormData,
    onProgress?: (progress: number) => void
  ): Promise<ApiResponse<T>> {
    const url = this.createURL(endpoint)
    const authHeaders = this.getAuthHeader()

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      
      // Track upload progress
      if (onProgress) {
        xhr.upload.addEventListener('progress', (event) => {
          if (event.lengthComputable) {
            const progress = Math.round((event.loaded / event.total) * 100)
            onProgress(progress)
          }
        })
      }

      xhr.addEventListener('load', async () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const response = JSON.parse(xhr.responseText)
            resolve(response)
          } catch (error) {
            reject(new Error('無效的響應格式'))
          }
        } else {
          reject(new Error(`上傳失敗 (${xhr.status})`))
        }
      })

      xhr.addEventListener('error', () => {
        reject(new Error('上傳過程中發生錯誤'))
      })

      xhr.addEventListener('timeout', () => {
        reject(new Error('上傳超時'))
      })

      xhr.open('POST', url)
      
      // Set auth headers
      Object.entries(authHeaders).forEach(([key, value]) => {
        xhr.setRequestHeader(key, value)
      })
      
      xhr.timeout = this.defaultTimeout
      xhr.send(formData)
    })
  }
}

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const apiClient = new ApiClient(config.public.apiBase)

  return {
    provide: {
      api: apiClient.request.bind(apiClient),
      apiClient
    }
  }
})