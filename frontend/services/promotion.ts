// 推廣相關 API 服務

import type {
  Promotion,
  PromotionForm,
  PromotionFilter,
  PromotionStatistics,
  PromotionAnalytics,
  PromotionMaterial,
  ApiResponse,
  PaginatedResponse,
  PaginationParams,
} from '~/types'
import { useApi } from './api'

export class PromotionService {
  private api = useApi()

  // 獲取推廣列表
  async getPromotions(
    filters?: PromotionFilter,
    pagination?: PaginationParams
  ): Promise<PaginatedResponse<Promotion>> {
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
    const url = `/promotions${queryString ? `?${queryString}` : ''}`
    
    return this.api.get<PaginatedResponse<Promotion>>(url)
  }

  // 獲取單個推廣詳情
  async getPromotion(id: number): Promise<ApiResponse<Promotion>> {
    return this.api.get<ApiResponse<Promotion>>(`/promotions/${id}`)
  }

  // 創建推廣
  async createPromotion(data: PromotionForm): Promise<ApiResponse<Promotion>> {
    return this.api.post<ApiResponse<Promotion>>('/promotions', data)
  }

  // 更新推廣
  async updatePromotion(id: number, data: Partial<PromotionForm>): Promise<ApiResponse<Promotion>> {
    return this.api.put<ApiResponse<Promotion>>(`/promotions/${id}`, data)
  }

  // 刪除推廣
  async deletePromotion(id: number): Promise<ApiResponse<null>> {
    return this.api.delete<ApiResponse<null>>(`/promotions/${id}`)
  }

  // 暫停/恢復推廣
  async togglePromotionStatus(id: number, status: 'active' | 'paused'): Promise<ApiResponse<Promotion>> {
    return this.api.patch<ApiResponse<Promotion>>(`/promotions/${id}/status`, { status })
  }

  // 獲取推廣統計
  async getPromotionStatistics(id: number): Promise<ApiResponse<PromotionStatistics>> {
    return this.api.get<ApiResponse<PromotionStatistics>>(`/promotions/${id}/statistics`)
  }

  // 獲取推廣分析數據
  async getPromotionAnalytics(
    id: number,
    dateRange?: { start: string; end: string }
  ): Promise<ApiResponse<PromotionAnalytics>> {
    const params = new URLSearchParams()
    if (dateRange) {
      params.append('start_date', dateRange.start)
      params.append('end_date', dateRange.end)
    }

    const queryString = params.toString()
    const url = `/promotions/${id}/analytics${queryString ? `?${queryString}` : ''}`
    
    return this.api.get<ApiResponse<PromotionAnalytics>>(url)
  }

  // 生成推廣素材
  async generatePromotionMaterials(id: number): Promise<ApiResponse<PromotionMaterial>> {
    return this.api.post<ApiResponse<PromotionMaterial>>(`/promotions/${id}/materials`)
  }

  // 重新生成推廣連結
  async regeneratePromotionLink(id: number): Promise<ApiResponse<{ link: string }>> {
    return this.api.post<ApiResponse<{ link: string }>>(`/promotions/${id}/regenerate-link`)
  }

  // 批量操作推廣
  async batchUpdatePromotions(
    ids: number[],
    action: 'activate' | 'pause' | 'delete',
    data?: any
  ): Promise<ApiResponse<{ success: number[]; failed: Array<{ id: number; error: string }> }>> {
    return this.api.post<ApiResponse<any>>('/promotions/batch', {
      ids,
      action,
      data,
    })
  }

  // 複製推廣
  async duplicatePromotion(id: number): Promise<ApiResponse<Promotion>> {
    return this.api.post<ApiResponse<Promotion>>(`/promotions/${id}/duplicate`)
  }

  // 導出推廣數據
  async exportPromotions(
    filters?: PromotionFilter,
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

    const response = await this.api.get<Blob>(`/promotions/export?${params.toString()}`, {
      headers: {
        'Accept': format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      },
    })

    return response
  }

  // 上傳推廣圖片
  async uploadPromotionImage(
    file: File,
    onProgress?: (progress: { loaded: number; total: number; percentage: number }) => void
  ): Promise<ApiResponse<{ url: string; filename: string }>> {
    return this.api.upload<ApiResponse<{ url: string; filename: string }>>(
      '/upload/promotion-image',
      file,
      onProgress
    )
  }
}

// 創建服務實例
export const usePromotionService = () => {
  return new PromotionService()
}