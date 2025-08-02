// 全局錯誤處理插件

import { useGlobalErrorHandler } from '~/composables/useErrorHandler'

export default defineNuxtPlugin(() => {
  const { setupGlobalErrorHandler } = useGlobalErrorHandler()
  
  // 設置全局錯誤處理
  setupGlobalErrorHandler()
  
  // 設置 fetch 錯誤處理
  if (process.client) {
    // 監聽 fetch 錯誤
    const originalFetch = window.fetch
    window.fetch = async (...args) => {
      try {
        const response = await originalFetch(...args)
        
        // 檢查 HTTP 狀態
        if (!response.ok) {
          const error = new Error(`HTTP ${response.status}: ${response.statusText}`)
          ;(error as any).status = response.status
          throw error
        }
        
        return response
      } catch (error) {
        // 錯誤會由全局錯誤處理器處理
        throw error
      }
    }
  }
})