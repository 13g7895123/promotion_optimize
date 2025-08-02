// 推廣相關狀態管理

import { defineStore } from 'pinia'
import type {
  Promotion,
  PromotionForm,
  PromotionFilter,
  PromotionStatistics,
  PromotionAnalytics,
  PaginatedResponse,
  LoadingState,
} from '~/types'
import { usePromotionService } from '~/services'

interface PromotionState {
  // 推廣列表
  promotions: Promotion[]
  totalPromotions: number
  currentPage: number
  lastPage: number
  perPage: number
  
  // 當前選中的推廣
  currentPromotion: Promotion | null
  
  // 推廣統計
  statistics: Record<number, PromotionStatistics>
  
  // 推廣分析數據
  analytics: Record<number, PromotionAnalytics>
  
  // 篩選條件
  filters: PromotionFilter
  
  // 載入狀態
  loadingStates: {
    list: LoadingState
    current: LoadingState
    statistics: LoadingState
    analytics: LoadingState
    create: LoadingState
    update: LoadingState
    delete: LoadingState
  }
}

export const usePromotionStore = defineStore('promotion', {
  state: (): PromotionState => ({
    promotions: [],
    totalPromotions: 0,
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    
    currentPromotion: null,
    
    statistics: {},
    analytics: {},
    
    filters: {},
    
    loadingStates: {
      list: { loading: false, error: null },
      current: { loading: false, error: null },
      statistics: { loading: false, error: null },
      analytics: { loading: false, error: null },
      create: { loading: false, error: null },
      update: { loading: false, error: null },
      delete: { loading: false, error: null },
    },
  }),

  getters: {
    // 獲取指定伺服器的推廣
    getPromotionsByServer: (state) => (serverId: number) => {
      return state.promotions.filter(p => p.server_id === serverId)
    },

    // 獲取活躍推廣
    activePromotions: (state) => {
      return state.promotions.filter(p => p.status === 'active')
    },

    // 獲取已過期推廣
    expiredPromotions: (state) => {
      const now = new Date()
      return state.promotions.filter(p => new Date(p.end_date) < now)
    },

    // 獲取即將過期的推廣 (7天內)
    expiringPromotions: (state) => {
      const now = new Date()
      const sevenDaysLater = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
      return state.promotions.filter(p => {
        const endDate = new Date(p.end_date)
        return endDate > now && endDate <= sevenDaysLater
      })
    },

    // 載入狀態檢查器
    isLoading: (state) => (type: keyof PromotionState['loadingStates']) => {
      return state.loadingStates[type].loading
    },

    hasError: (state) => (type: keyof PromotionState['loadingStates']) => {
      return state.loadingStates[type].error !== null
    },
  },

  actions: {
    // 設置載入狀態
    setLoadingState(type: keyof PromotionState['loadingStates'], loading: boolean, error: string | null = null) {
      this.loadingStates[type] = { loading, error }
    },

    // 獲取推廣列表
    async fetchPromotions(page: number = 1, filters?: PromotionFilter, perPage: number = 20) {
      this.setLoadingState('list', true)
      
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.getPromotions(
          { ...this.filters, ...filters },
          { page, per_page: perPage }
        )

        this.promotions = response.data
        this.totalPromotions = response.total
        this.currentPage = response.current_page
        this.lastPage = response.last_page
        this.perPage = response.per_page

        if (filters) {
          this.filters = { ...this.filters, ...filters }
        }

        this.setLoadingState('list', false)
        return response
      } catch (error: any) {
        this.setLoadingState('list', false, error.message)
        throw error
      }
    },

    // 獲取單個推廣
    async fetchPromotion(id: number) {
      this.setLoadingState('current', true)
      
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.getPromotion(id)
        
        this.currentPromotion = response.data
        
        // 同時更新列表中的數據
        const index = this.promotions.findIndex(p => p.id === id)
        if (index !== -1) {
          this.promotions[index] = response.data
        }

        this.setLoadingState('current', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('current', false, error.message)
        throw error
      }
    },

    // 創建推廣
    async createPromotion(data: PromotionForm) {
      this.setLoadingState('create', true)
      
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.createPromotion(data)
        
        // 添加到列表開頭
        this.promotions.unshift(response.data)
        this.totalPromotions++

        this.setLoadingState('create', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('create', false, error.message)
        throw error
      }
    },

    // 更新推廣
    async updatePromotion(id: number, data: Partial<PromotionForm>) {
      this.setLoadingState('update', true)
      
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.updatePromotion(id, data)
        
        // 更新列表中的數據
        const index = this.promotions.findIndex(p => p.id === id)
        if (index !== -1) {
          this.promotions[index] = response.data
        }

        // 更新當前推廣
        if (this.currentPromotion?.id === id) {
          this.currentPromotion = response.data
        }

        this.setLoadingState('update', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('update', false, error.message)
        throw error
      }
    },

    // 刪除推廣
    async deletePromotion(id: number) {
      this.setLoadingState('delete', true)
      
      try {
        const promotionService = usePromotionService()
        await promotionService.deletePromotion(id)
        
        // 從列表中移除
        this.promotions = this.promotions.filter(p => p.id !== id)
        this.totalPromotions--

        // 清空當前推廣如果是被刪除的
        if (this.currentPromotion?.id === id) {
          this.currentPromotion = null
        }

        // 清理相關數據
        delete this.statistics[id]
        delete this.analytics[id]

        this.setLoadingState('delete', false)
      } catch (error: any) {
        this.setLoadingState('delete', false, error.message)
        throw error
      }
    },

    // 切換推廣狀態
    async togglePromotionStatus(id: number, status: 'active' | 'paused') {
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.togglePromotionStatus(id, status)
        
        // 更新列表中的數據
        const index = this.promotions.findIndex(p => p.id === id)
        if (index !== -1) {
          this.promotions[index] = response.data
        }

        // 更新當前推廣
        if (this.currentPromotion?.id === id) {
          this.currentPromotion = response.data
        }

        return response.data
      } catch (error: any) {
        throw error
      }
    },

    // 獲取推廣統計
    async fetchPromotionStatistics(id: number) {
      this.setLoadingState('statistics', true)
      
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.getPromotionStatistics(id)
        
        this.statistics[id] = response.data
        
        this.setLoadingState('statistics', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('statistics', false, error.message)
        throw error
      }
    },

    // 獲取推廣分析數據
    async fetchPromotionAnalytics(id: number, dateRange?: { start: string; end: string }) {
      this.setLoadingState('analytics', true)
      
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.getPromotionAnalytics(id, dateRange)
        
        this.analytics[id] = response.data
        
        this.setLoadingState('analytics', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('analytics', false, error.message)
        throw error
      }
    },

    // 批量操作
    async batchOperation(ids: number[], action: 'activate' | 'pause' | 'delete') {
      try {
        const promotionService = usePromotionService()
        const response = await promotionService.batchUpdatePromotions(ids, action)
        
        // 刷新列表
        await this.fetchPromotions(this.currentPage, this.filters, this.perPage)
        
        return response.data
      } catch (error: any) {
        throw error
      }
    },

    // 重置篩選條件
    resetFilters() {
      this.filters = {}
    },

    // 清空當前推廣
    clearCurrentPromotion() {
      this.currentPromotion = null
    },

    // 清空所有數據
    clearAll() {
      this.promotions = []
      this.totalPromotions = 0
      this.currentPage = 1
      this.lastPage = 1
      this.currentPromotion = null
      this.statistics = {}
      this.analytics = {}
      this.filters = {}
      
      // 重置載入狀態
      Object.keys(this.loadingStates).forEach(key => {
        this.loadingStates[key as keyof PromotionState['loadingStates']] = { loading: false, error: null }
      })
    },
  },
})