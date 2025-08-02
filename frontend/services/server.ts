// 伺服器相關 API 服務

import type {
  Server,
  ApiResponse,
  PaginatedResponse,
  PaginationParams,
} from '~/types'
import { useApi } from './api'

export interface ServerFilter {
  game_type?: string
  status?: 'active' | 'inactive' | 'maintenance'
  owner_id?: number
  search?: string
}

export interface ServerForm {
  name: string
  code: string
  game_type: string
  description?: string
  image?: File
  database_config?: {
    host: string
    port: number
    database: string
    username: string
    password: string
    table_prefix?: string
  }
  notification_config?: {
    email_enabled: boolean
    line_enabled: boolean
    webhook_url?: string
  }
}

export class ServerService {
  private api = useApi()

  // 獲取伺服器列表
  async getServers(
    filters?: ServerFilter,
    pagination?: PaginationParams
  ): Promise<PaginatedResponse<Server>> {
    const params = new URLSearchParams()
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value))
        }
      })
    }

    if (pagination) {
      Object.entries(pagination).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          params.append(key, String(value))
        }
      })
    }

    const queryString = params.toString()
    const url = `/servers${queryString ? `?${queryString}` : ''}`
    
    return this.api.get<PaginatedResponse<Server>>(url)
  }

  // 獲取用戶有權限的伺服器列表
  async getMyServers(): Promise<ApiResponse<Server[]>> {
    return this.api.get<ApiResponse<Server[]>>('/servers/my')
  }

  // 獲取單個伺服器詳情
  async getServer(id: number): Promise<ApiResponse<Server>> {
    return this.api.get<ApiResponse<Server>>(`/servers/${id}`)
  }

  // 創建伺服器
  async createServer(data: ServerForm): Promise<ApiResponse<Server>> {
    // 如果有圖片，先上傳
    if (data.image) {
      const imageResponse = await this.uploadServerImage(data.image)
      const serverData = {
        ...data,
        image_url: imageResponse.data.url,
      }
      delete serverData.image
      return this.api.post<ApiResponse<Server>>('/servers', serverData)
    }

    return this.api.post<ApiResponse<Server>>('/servers', data)
  }

  // 更新伺服器
  async updateServer(id: number, data: Partial<ServerForm>): Promise<ApiResponse<Server>> {
    // 如果有圖片，先上傳
    if (data.image) {
      const imageResponse = await this.uploadServerImage(data.image)
      const serverData = {
        ...data,
        image_url: imageResponse.data.url,
      }
      delete serverData.image
      return this.api.put<ApiResponse<Server>>(`/servers/${id}`, serverData)
    }

    return this.api.put<ApiResponse<Server>>(`/servers/${id}`, data)
  }

  // 刪除伺服器
  async deleteServer(id: number): Promise<ApiResponse<null>> {
    return this.api.delete<ApiResponse<null>>(`/servers/${id}`)
  }

  // 更新伺服器狀態
  async updateServerStatus(
    id: number, 
    status: 'active' | 'inactive' | 'maintenance'
  ): Promise<ApiResponse<Server>> {
    return this.api.patch<ApiResponse<Server>>(`/servers/${id}/status`, { status })
  }

  // 測試伺服器連接
  async testServerConnection(id: number): Promise<ApiResponse<{ success: boolean; message: string }>> {
    return this.api.post<ApiResponse<{ success: boolean; message: string }>>(
      `/servers/${id}/test-connection`
    )
  }

  // 獲取伺服器統計
  async getServerStatistics(id: number): Promise<ApiResponse<{
    total_promotions: number
    active_promotions: number
    total_clicks: number
    total_conversions: number
    conversion_rate: number
    last_activity: string
  }>> {
    return this.api.get<ApiResponse<any>>(`/servers/${id}/statistics`)
  }

  // 獲取伺服器遊戲類型列表
  async getGameTypes(): Promise<ApiResponse<Array<{ value: string; label: string }>>> {
    return this.api.get<ApiResponse<any>>('/servers/game-types')
  }

  // 驗證伺服器代碼唯一性
  async validateServerCode(code: string, excludeId?: number): Promise<ApiResponse<{ available: boolean }>> {
    const params = new URLSearchParams({ code })
    if (excludeId) {
      params.append('exclude_id', String(excludeId))
    }
    
    return this.api.get<ApiResponse<{ available: boolean }>>(`/servers/validate-code?${params.toString()}`)
  }

  // 上傳伺服器圖片
  async uploadServerImage(
    file: File,
    onProgress?: (progress: { loaded: number; total: number; percentage: number }) => void
  ): Promise<ApiResponse<{ url: string; filename: string }>> {
    return this.api.upload<ApiResponse<{ url: string; filename: string }>>(
      '/upload/server-image',
      file,
      onProgress
    )
  }

  // 批量操作伺服器
  async batchUpdateServers(
    ids: number[],
    action: 'activate' | 'deactivate' | 'maintenance' | 'delete',
    data?: any
  ): Promise<ApiResponse<{ success: number[]; failed: Array<{ id: number; error: string }> }>> {
    return this.api.post<ApiResponse<any>>('/servers/batch', {
      ids,
      action,
      data,
    })
  }

  // 導出伺服器數據
  async exportServers(
    filters?: ServerFilter,
    format: 'csv' | 'excel' = 'csv'
  ): Promise<Blob> {
    const params = new URLSearchParams()
    params.append('format', format)
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          params.append(key, String(value))
        }
      })
    }

    const response = await this.api.get<Blob>(`/servers/export?${params.toString()}`, {
      headers: {
        'Accept': format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      },
    })

    return response
  }
}

// 創建服務實例
export const useServerService = () => {
  return new ServerService()
}