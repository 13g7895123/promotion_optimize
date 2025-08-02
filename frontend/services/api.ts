// API 客戶端基礎服務

import type { ApiClient, ApiError, RequestConfig } from '~/types/api'

class ApiService implements ApiClient {
  private baseURL: string
  private defaultConfig: RequestConfig

  constructor(baseURL: string) {
    this.baseURL = baseURL
    this.defaultConfig = {
      headers: {
        'Content-Type': 'application/json',
      },
      timeout: 10000,
      retries: 3,
    }
  }

  private async request<T>(
    method: string,
    url: string,
    data?: any,
    config?: RequestConfig
  ): Promise<T> {
    const fullURL = `${this.baseURL}${url}`
    const mergedConfig = { ...this.defaultConfig, ...config }
    
    // 獲取認證 token
    const token = useCookie('auth-token')
    if (token.value) {
      mergedConfig.headers = {
        ...mergedConfig.headers,
        Authorization: `Bearer ${token.value}`,
      }
    }

    try {
      const response = await $fetch<T>(fullURL, {
        method: method as any,
        body: data,
        headers: mergedConfig.headers,
        timeout: mergedConfig.timeout,
        retry: mergedConfig.retries,
        onRequestError({ error }) {
          console.error('Request Error:', error)
          throw new ApiError('網路請求失敗', 0)
        },
        onResponseError({ response }) {
          const apiError = new ApiError(
            response._data?.message || '請求失敗',
            response.status,
            response._data?.code,
            response._data
          )
          throw apiError
        },
      })

      return response
    } catch (error: any) {
      if (error instanceof ApiError) {
        throw error
      }
      
      // 處理其他類型的錯誤
      throw new ApiError(
        error.message || '未知錯誤',
        error.statusCode || 500
      )
    }
  }

  async get<T>(url: string, config?: RequestConfig): Promise<T> {
    return this.request<T>('GET', url, undefined, config)
  }

  async post<T>(url: string, data?: any, config?: RequestConfig): Promise<T> {
    return this.request<T>('POST', url, data, config)
  }

  async put<T>(url: string, data?: any, config?: RequestConfig): Promise<T> {
    return this.request<T>('PUT', url, data, config)
  }

  async delete<T>(url: string, config?: RequestConfig): Promise<T> {
    return this.request<T>('DELETE', url, undefined, config)
  }

  async patch<T>(url: string, data?: any, config?: RequestConfig): Promise<T> {
    return this.request<T>('PATCH', url, data, config)
  }

  // 文件上傳
  async upload<T>(
    url: string,
    file: File,
    onProgress?: (progress: { loaded: number; total: number; percentage: number }) => void,
    config?: RequestConfig
  ): Promise<T> {
    const formData = new FormData()
    formData.append('file', file)

    const uploadConfig = {
      ...config,
      headers: {
        ...config?.headers,
        // 不設置 Content-Type，讓瀏覽器自動設置
      },
    }

    // 如果需要進度回調，使用 XMLHttpRequest
    if (onProgress) {
      return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest()
        
        xhr.upload.onprogress = (event) => {
          if (event.lengthComputable) {
            const percentage = Math.round((event.loaded / event.total) * 100)
            onProgress({
              loaded: event.loaded,
              total: event.total,
              percentage,
            })
          }
        }

        xhr.onload = () => {
          if (xhr.status >= 200 && xhr.status < 300) {
            try {
              const response = JSON.parse(xhr.responseText)
              resolve(response)
            } catch (error) {
              reject(new ApiError('響應解析失敗', xhr.status))
            }
          } else {
            reject(new ApiError('上傳失敗', xhr.status))
          }
        }

        xhr.onerror = () => {
          reject(new ApiError('網路錯誤', 0))
        }

        xhr.open('POST', `${this.baseURL}${url}`)
        
        // 設置認證頭
        const token = useCookie('auth-token')
        if (token.value) {
          xhr.setRequestHeader('Authorization', `Bearer ${token.value}`)
        }

        xhr.send(formData)
      })
    }

    return this.request<T>('POST', url, formData, uploadConfig)
  }
}

// 錯誤類
export class ApiError extends Error {
  public status: number
  public code?: string
  public details?: Record<string, any>

  constructor(message: string, status: number, code?: string, details?: Record<string, any>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.details = details
  }
}

// 創建 API 實例
export const createApiService = () => {
  const config = useRuntimeConfig()
  return new ApiService(config.public.apiBase)
}

// 全局 API 實例
let apiInstance: ApiService | null = null

export const useApi = () => {
  if (!apiInstance) {
    apiInstance = createApiService()
  }
  return apiInstance
}