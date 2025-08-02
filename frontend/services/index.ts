// 統一導出所有服務

export * from './api'
export * from './promotion'
export * from './server'

// 便捷的服務 composables
export const useServices = () => {
  return {
    promotion: usePromotionService(),
    server: useServerService(),
  }
}