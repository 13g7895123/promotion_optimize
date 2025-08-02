// 全局錯誤處理 composable

import { ApiError } from '~/services/api'
import type { NotificationOptions } from '~/types'

interface ErrorHandlerOptions {
  showMessage?: boolean
  showNotification?: boolean
  logError?: boolean
  fallbackMessage?: string
  onError?: (error: Error) => void
}

export const useErrorHandler = () => {
  // 處理 API 錯誤
  const handleApiError = (error: unknown, options: ErrorHandlerOptions = {}) => {
    const {
      showMessage = true,
      showNotification = false,
      logError = true,
      fallbackMessage = '操作失敗，請稍後再試',
      onError,
    } = options

    let errorMessage = fallbackMessage
    let statusCode = 500

    if (error instanceof ApiError) {
      errorMessage = error.message
      statusCode = error.status
    } else if (error instanceof Error) {
      errorMessage = error.message
    } else if (typeof error === 'string') {
      errorMessage = error
    }

    // 記錄錯誤
    if (logError) {
      console.error('Error handled:', {
        message: errorMessage,
        status: statusCode,
        timestamp: new Date().toISOString(),
        error,
      })
    }

    // 顯示錯誤消息
    if (showMessage) {
      const messageType = getMessageType(statusCode)
      ElMessage({
        type: messageType,
        message: errorMessage,
        duration: messageType === 'error' ? 5000 : 3000,
        showClose: true,
      })
    }

    // 顯示通知
    if (showNotification) {
      const notificationType = getNotificationType(statusCode)
      ElNotification({
        title: '錯誤',
        message: errorMessage,
        type: notificationType,
        duration: 5000,
      })
    }

    // 執行自訂錯誤處理
    if (onError) {
      onError(error instanceof Error ? error : new Error(errorMessage))
    }

    // 特殊狀態碼處理
    handleSpecialStatusCodes(statusCode)

    return {
      message: errorMessage,
      status: statusCode,
    }
  }

  // 處理表單驗證錯誤
  const handleValidationError = (errors: Record<string, string[]>, formRef?: any) => {
    const firstError = Object.values(errors)[0]?.[0]
    if (firstError) {
      ElMessage.error(firstError)
    }

    // 如果有表單引用，標記字段錯誤
    if (formRef && formRef.value) {
      Object.keys(errors).forEach(field => {
        formRef.value.setFieldError(field, errors[field])
      })
    }

    return errors
  }

  // 處理網路錯誤
  const handleNetworkError = (error: unknown) => {
    let message = '網路連接失敗，請檢查網路設置'

    if (error instanceof Error) {
      if (error.message.includes('timeout')) {
        message = '請求超時，請檢查網路連接'
      } else if (error.message.includes('NetworkError')) {
        message = '網路錯誤，請稍後再試'
      }
    }

    ElMessage.error(message)
    return message
  }

  // 創建錯誤重試機制
  const createRetryHandler = <T>(
    asyncFn: () => Promise<T>,
    maxRetries: number = 3,
    delay: number = 1000
  ) => {
    return async (): Promise<T> => {
      let lastError: unknown

      for (let i = 0; i < maxRetries; i++) {
        try {
          return await asyncFn()
        } catch (error) {
          lastError = error
          
          if (i < maxRetries - 1) {
            // 指數退避
            const waitTime = delay * Math.pow(2, i)
            await new Promise(resolve => setTimeout(resolve, waitTime))
          }
        }
      }

      throw lastError
    }
  }

  // 包裝異步操作
  const wrapAsync = <T>(
    asyncFn: () => Promise<T>,
    options: ErrorHandlerOptions = {}
  ) => {
    return async (): Promise<T | null> => {
      try {
        return await asyncFn()
      } catch (error) {
        handleApiError(error, options)
        return null
      }
    }
  }

  // 工具函數：獲取消息類型
  const getMessageType = (statusCode: number): 'success' | 'warning' | 'info' | 'error' => {
    if (statusCode >= 200 && statusCode < 300) return 'success'
    if (statusCode >= 400 && statusCode < 500) return 'warning'
    return 'error'
  }

  // 工具函數：獲取通知類型
  const getNotificationType = (statusCode: number): 'success' | 'warning' | 'info' | 'error' => {
    return getMessageType(statusCode)
  }

  // 特殊狀態碼處理
  const handleSpecialStatusCodes = (statusCode: number) => {
    switch (statusCode) {
      case 401:
        // 未授權，可能需要重新登入
        ElMessageBox.confirm(
          '登入已過期，請重新登入',
          '授權失效',
          {
            confirmButtonText: '重新登入',
            cancelButtonText: '取消',
            type: 'warning',
          }
        ).then(() => {
          // 清除 token 並導航到登入頁
          const authStore = useAuthStore()
          authStore.logout()
          navigateTo('/login')
        }).catch(() => {
          // 用戶取消，不做任何操作
        })
        break

      case 403:
        // 權限不足
        ElMessage.error('權限不足，無法執行此操作')
        break

      case 404:
        // 資源不存在
        ElMessage.warning('請求的資源不存在')
        break

      case 429:
        // 請求過於頻繁
        ElMessage.warning('請求過於頻繁，請稍後再試')
        break

      case 500:
      case 502:
      case 503:
        // 伺服器錯誤
        ElMessage.error('伺服器暫時無法響應，請稍後再試')
        break
    }
  }

  return {
    handleApiError,
    handleValidationError,
    handleNetworkError,
    createRetryHandler,
    wrapAsync,
  }
}

// 全局錯誤處理插件
export const useGlobalErrorHandler = () => {
  const { handleApiError, handleNetworkError } = useErrorHandler()

  // 設置全局錯誤處理
  const setupGlobalErrorHandler = () => {
    // Vue 錯誤處理
    const app = getCurrentInstance()?.appContext.app
    if (app) {
      app.config.errorHandler = (error: unknown, vm, info) => {
        console.error('Vue Error:', error, vm, info)
        handleApiError(error, {
          showMessage: true,
          fallbackMessage: '應用程式發生錯誤',
        })
      }
    }

    // 全局未捕獲的 Promise 錯誤
    if (process.client) {
      window.addEventListener('unhandledrejection', (event) => {
        console.error('Unhandled Promise Rejection:', event.reason)
        handleApiError(event.reason, {
          showMessage: true,
          fallbackMessage: '異步操作失敗',
        })
        event.preventDefault()
      })

      // 全局錯誤事件
      window.addEventListener('error', (event) => {
        console.error('Global Error:', event.error)
        handleApiError(event.error, {
          showMessage: false, // 避免過於頻繁的錯誤提示
          logError: true,
        })
      })
    }
  }

  return {
    setupGlobalErrorHandler,
  }
}

// 錯誤邊界 composable
export const useErrorBoundary = () => {
  const error = ref<Error | null>(null)
  const hasError = computed(() => error.value !== null)

  const captureError = (err: unknown) => {
    if (err instanceof Error) {
      error.value = err
    } else {
      error.value = new Error(String(err))
    }
  }

  const clearError = () => {
    error.value = null
  }

  const retry = (fn: () => void | Promise<void>) => {
    clearError()
    try {
      const result = fn()
      if (result instanceof Promise) {
        result.catch(captureError)
      }
    } catch (err) {
      captureError(err)
    }
  }

  return {
    error: readonly(error),
    hasError,
    captureError,
    clearError,
    retry,
  }
}