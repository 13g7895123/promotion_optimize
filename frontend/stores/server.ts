// 伺服器相關狀態管理

import { defineStore } from 'pinia'
import type {
  Server,
  ApiResponse,
  PaginatedResponse,
  LoadingState,
} from '~/types'
import { useServerService, type ServerFilter, type ServerForm } from '~/services'

interface ServerState {
  // 伺服器列表
  servers: Server[]
  totalServers: number
  currentPage: number
  lastPage: number
  perPage: number
  
  // 我的伺服器 (用戶有權限的)
  myServers: Server[]
  
  // 當前選中的伺服器
  currentServer: Server | null
  
  // 遊戲類型列表
  gameTypes: Array<{ value: string; label: string }>
  
  // 篩選條件
  filters: ServerFilter
  
  // 載入狀態
  loadingStates: {
    list: LoadingState
    myServers: LoadingState
    current: LoadingState
    gameTypes: LoadingState
    create: LoadingState
    update: LoadingState
    delete: LoadingState
    testConnection: LoadingState
  }
}

export const useServerStore = defineStore('server', {
  state: (): ServerState => ({
    servers: [],
    totalServers: 0,
    currentPage: 1,
    lastPage: 1,
    perPage: 20,
    
    myServers: [],
    
    currentServer: null,
    
    gameTypes: [],
    
    filters: {},
    
    loadingStates: {
      list: { loading: false, error: null },
      myServers: { loading: false, error: null },
      current: { loading: false, error: null },
      gameTypes: { loading: false, error: null },
      create: { loading: false, error: null },
      update: { loading: false, error: null },
      delete: { loading: false, error: null },
      testConnection: { loading: false, error: null },
    },
  }),

  getters: {
    // 獲取活躍伺服器
    activeServers: (state) => {
      return state.servers.filter(s => s.status === 'active')
    },

    // 獲取指定遊戲類型的伺服器
    getServersByGameType: (state) => (gameType: string) => {
      return state.servers.filter(s => s.game_type === gameType)
    },

    // 檢查用戶是否有指定伺服器的權限
    hasServerPermission: (state) => (serverId: number) => {
      return state.myServers.some(s => s.id === serverId)
    },

    // 載入狀態檢查器
    isLoading: (state) => (type: keyof ServerState['loadingStates']) => {
      return state.loadingStates[type].loading
    },

    hasError: (state) => (type: keyof ServerState['loadingStates']) => {
      return state.loadingStates[type].error !== null
    },

    // 獲取伺服器選項 (用於下拉選單)
    serverOptions: (state) => {
      return state.myServers.map(server => ({
        label: server.name,
        value: server.id,
        disabled: server.status !== 'active',
      }))
    },
  },

  actions: {
    // 設置載入狀態
    setLoadingState(type: keyof ServerState['loadingStates'], loading: boolean, error: string | null = null) {
      this.loadingStates[type] = { loading, error }
    },

    // 獲取伺服器列表
    async fetchServers(page: number = 1, filters?: ServerFilter, perPage: number = 20) {
      this.setLoadingState('list', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.getServers(
          { ...this.filters, ...filters },
          { page, per_page: perPage }
        )

        this.servers = response.data
        this.totalServers = response.total
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

    // 獲取我的伺服器列表
    async fetchMyServers() {
      this.setLoadingState('myServers', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.getMyServers()
        
        this.myServers = response.data
        
        this.setLoadingState('myServers', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('myServers', false, error.message)
        throw error
      }
    },

    // 獲取單個伺服器
    async fetchServer(id: number) {
      this.setLoadingState('current', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.getServer(id)
        
        this.currentServer = response.data
        
        // 同時更新列表中的數據
        const index = this.servers.findIndex(s => s.id === id)
        if (index !== -1) {
          this.servers[index] = response.data
        }

        // 更新我的伺服器列表
        const myIndex = this.myServers.findIndex(s => s.id === id)
        if (myIndex !== -1) {
          this.myServers[myIndex] = response.data
        }

        this.setLoadingState('current', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('current', false, error.message)
        throw error
      }
    },

    // 創建伺服器
    async createServer(data: ServerForm) {
      this.setLoadingState('create', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.createServer(data)
        
        // 添加到列表開頭
        this.servers.unshift(response.data)
        this.myServers.unshift(response.data)
        this.totalServers++

        this.setLoadingState('create', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('create', false, error.message)
        throw error
      }
    },

    // 更新伺服器
    async updateServer(id: number, data: Partial<ServerForm>) {
      this.setLoadingState('update', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.updateServer(id, data)
        
        // 更新列表中的數據
        const index = this.servers.findIndex(s => s.id === id)
        if (index !== -1) {
          this.servers[index] = response.data
        }

        // 更新我的伺服器列表
        const myIndex = this.myServers.findIndex(s => s.id === id)
        if (myIndex !== -1) {
          this.myServers[myIndex] = response.data
        }

        // 更新當前伺服器
        if (this.currentServer?.id === id) {
          this.currentServer = response.data
        }

        this.setLoadingState('update', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('update', false, error.message)
        throw error
      }
    },

    // 刪除伺服器
    async deleteServer(id: number) {
      this.setLoadingState('delete', true)
      
      try {
        const serverService = useServerService()
        await serverService.deleteServer(id)
        
        // 從列表中移除
        this.servers = this.servers.filter(s => s.id !== id)
        this.myServers = this.myServers.filter(s => s.id !== id)
        this.totalServers--

        // 清空當前伺服器如果是被刪除的
        if (this.currentServer?.id === id) {
          this.currentServer = null
        }

        this.setLoadingState('delete', false)
      } catch (error: any) {
        this.setLoadingState('delete', false, error.message)
        throw error
      }
    },

    // 更新伺服器狀態
    async updateServerStatus(id: number, status: 'active' | 'inactive' | 'maintenance') {
      try {
        const serverService = useServerService()
        const response = await serverService.updateServerStatus(id, status)
        
        // 更新列表中的數據
        const index = this.servers.findIndex(s => s.id === id)
        if (index !== -1) {
          this.servers[index] = response.data
        }

        // 更新我的伺服器列表
        const myIndex = this.myServers.findIndex(s => s.id === id)
        if (myIndex !== -1) {
          this.myServers[myIndex] = response.data
        }

        // 更新當前伺服器
        if (this.currentServer?.id === id) {
          this.currentServer = response.data
        }

        return response.data
      } catch (error: any) {
        throw error
      }
    },

    // 測試伺服器連接
    async testServerConnection(id: number) {
      this.setLoadingState('testConnection', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.testServerConnection(id)
        
        this.setLoadingState('testConnection', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('testConnection', false, error.message)
        throw error
      }
    },

    // 獲取遊戲類型列表
    async fetchGameTypes() {
      this.setLoadingState('gameTypes', true)
      
      try {
        const serverService = useServerService()
        const response = await serverService.getGameTypes()
        
        this.gameTypes = response.data
        
        this.setLoadingState('gameTypes', false)
        return response.data
      } catch (error: any) {
        this.setLoadingState('gameTypes', false, error.message)
        throw error
      }
    },

    // 驗證伺服器代碼
    async validateServerCode(code: string, excludeId?: number) {
      try {
        const serverService = useServerService()
        const response = await serverService.validateServerCode(code, excludeId)
        
        return response.data.available
      } catch (error: any) {
        throw error
      }
    },

    // 批量操作
    async batchOperation(ids: number[], action: 'activate' | 'deactivate' | 'maintenance' | 'delete') {
      try {
        const serverService = useServerService()
        const response = await serverService.batchUpdateServers(ids, action)
        
        // 刷新列表
        await this.fetchServers(this.currentPage, this.filters, this.perPage)
        await this.fetchMyServers()
        
        return response.data
      } catch (error: any) {
        throw error
      }
    },

    // 設置當前伺服器
    setCurrentServer(server: Server | null) {
      this.currentServer = server
    },

    // 重置篩選條件
    resetFilters() {
      this.filters = {}
    },

    // 清空當前伺服器
    clearCurrentServer() {
      this.currentServer = null
    },

    // 清空所有數據
    clearAll() {
      this.servers = []
      this.myServers = []
      this.totalServers = 0
      this.currentPage = 1
      this.lastPage = 1
      this.currentServer = null
      this.gameTypes = []
      this.filters = {}
      
      // 重置載入狀態
      Object.keys(this.loadingStates).forEach(key => {
        this.loadingStates[key as keyof ServerState['loadingStates']] = { loading: false, error: null }
      })
    },
  },
})