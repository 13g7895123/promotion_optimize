// API 相關類型定義

export interface ApiError {
  message: string
  status: number
  code?: string
  details?: Record<string, any>
}

export interface RequestConfig {
  headers?: Record<string, string>
  timeout?: number
  retries?: number
}

export interface ApiClient {
  get<T>(url: string, config?: RequestConfig): Promise<T>
  post<T>(url: string, data?: any, config?: RequestConfig): Promise<T>
  put<T>(url: string, data?: any, config?: RequestConfig): Promise<T>
  delete<T>(url: string, config?: RequestConfig): Promise<T>
  patch<T>(url: string, data?: any, config?: RequestConfig): Promise<T>
}

// JWT Token 相關
export interface JWTPayload {
  user_id: number
  role: string
  permissions: string[]
  exp: number
  iat: number
}

// 文件上傳相關
export interface UploadProgress {
  loaded: number
  total: number
  percentage: number
}

export interface UploadResponse {
  url: string
  filename: string
  size: number
  mime_type: string
}

// 批量操作相關
export interface BatchOperation<T> {
  action: 'create' | 'update' | 'delete'
  data: T[]
}

export interface BatchResult<T> {
  success: T[]
  failed: Array<{
    data: T
    error: string
  }>
}

// 分頁參數
export interface PaginationParams {
  page?: number
  per_page?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

// 搜索參數
export interface SearchParams {
  q?: string
  filters?: Record<string, any>
  date_range?: {
    start: string
    end: string
  }
}

// WebSocket 相關 (預留)
export interface WebSocketMessage {
  type: string
  data: any
  timestamp: string
}

export interface WebSocketConfig {
  url: string
  protocols?: string[]
  reconnect?: boolean
  reconnect_interval?: number
  max_reconnect_attempts?: number
}