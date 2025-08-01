/**
 * Global error handling plugin
 * Provides centralized error handling, reporting, and user notifications
 */
export default defineNuxtPlugin(() => {
  const { $api } = useNuxtApp()
  
  // Error reporting configuration
  const ERROR_REPORTING_ENABLED = process.env.NODE_ENV === 'production'
  const MAX_ERROR_REPORTS_PER_SESSION = 10
  let errorReportCount = 0

  /**
   * Report error to monitoring service
   */
  const reportError = async (error: Error, context?: any) => {
    if (!ERROR_REPORTING_ENABLED || errorReportCount >= MAX_ERROR_REPORTS_PER_SESSION) {
      return
    }

    try {
      errorReportCount++
      
      const errorReport = {
        message: error.message,
        stack: error.stack,
        name: error.name,
        url: window.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
        context,
        sessionId: getSessionId()
      }

      // Send to error reporting service
      // Replace with your actual error reporting endpoint
      console.warn('Error reported:', errorReport)
      
      // Example: Send to monitoring service
      // await $fetch('/api/errors', {
      //   method: 'POST',
      //   body: errorReport
      // })
      
    } catch (reportingError) {
      console.error('Failed to report error:', reportingError)
    }
  }

  /**
   * Get or create session ID for error tracking
   */
  const getSessionId = (): string => {
    let sessionId = sessionStorage.getItem('error-session-id')
    if (!sessionId) {
      sessionId = Math.random().toString(36).substr(2, 16)
      sessionStorage.setItem('error-session-id', sessionId)
    }
    return sessionId
  }

  /**
   * Show user-friendly error notification
   */
  const showErrorNotification = (error: Error, severity: 'error' | 'warning' | 'info' = 'error') => {
    const message = getUserFriendlyMessage(error)
    
    switch (severity) {
      case 'error':
        ElMessage.error({
          message,
          duration: 5000,
          showClose: true
        })
        break
      case 'warning':
        ElMessage.warning({
          message,
          duration: 4000,
          showClose: true
        })
        break
      case 'info':
        ElMessage.info({
          message,
          duration: 3000,
          showClose: true
        })
        break
    }
  }

  /**
   * Convert technical error messages to user-friendly ones
   */
  const getUserFriendlyMessage = (error: Error): string => {
    const message = error.message.toLowerCase()
    
    // Network errors
    if (message.includes('network') || message.includes('fetch')) {
      return '網路連線異常，請檢查網路狀態後重試'
    }
    
    // Authentication errors
    if (message.includes('unauthorized') || message.includes('401')) {
      return '登入已過期，請重新登入'
    }
    
    // Permission errors
    if (message.includes('forbidden') || message.includes('403')) {
      return '您沒有權限執行此操作'
    }
    
    // Not found errors
    if (message.includes('not found') || message.includes('404')) {
      return '找不到請求的資源'
    }
    
    // Server errors
    if (message.includes('500') || message.includes('internal server')) {
      return '伺服器暫時無法處理請求，請稍後再試'
    }
    
    // Validation errors
    if (message.includes('validation') || message.includes('invalid')) {
      return '資料格式不正確，請檢查後重新提交'
    }
    
    // Timeout errors
    if (message.includes('timeout')) {
      return '請求超時，請稍後再試'
    }
    
    // Default message
    return error.message || '發生未知錯誤，請聯繫技術支援'
  }

  /**
   * Handle different types of errors with appropriate actions
   */
  const handleError = async (error: Error, context?: any) => {
    console.error('Global error handler:', error, context)
    
    // Report error
    await reportError(error, context)
    
    // Determine error severity
    let severity: 'error' | 'warning' | 'info' = 'error'
    
    if (error.name === 'ValidationError') {
      severity = 'warning'
    } else if (error.name === 'InfoError') {
      severity = 'info'
    }
    
    // Show notification
    showErrorNotification(error, severity)
    
    // Handle specific error types
    if (error.message.includes('401') || error.message.includes('unauthorized')) {
      // Clear auth and redirect to login
      const authStore = useAuthStore()
      authStore.clearAuth()
      await navigateTo('/login')
    }
  }

  // Set up global error handlers
  if (process.client) {
    // Capture unhandled JavaScript errors
    window.addEventListener('error', (event) => {
      const error = event.error || new Error(event.message)
      handleError(error, {
        type: 'javascript',
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      })
    })

    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      const error = event.reason instanceof Error 
        ? event.reason 
        : new Error(String(event.reason))
      
      handleError(error, {
        type: 'promise_rejection',
        promise: event.promise
      })
      
      // Prevent default browser handling
      event.preventDefault()
    })

    // Capture resource loading errors
    window.addEventListener('error', (event) => {
      if (event.target && event.target !== window) {
        const target = event.target as HTMLElement
        if (target.tagName) {
          handleError(new Error(`Failed to load resource: ${target.tagName}`), {
            type: 'resource',
            element: target.tagName.toLowerCase(),
            src: (target as any).src || (target as any).href
          })
        }
      }
    }, true)
  }

  // Provide error handling utilities
  return {
    provide: {
      errorHandler: {
        handleError,
        reportError,
        showErrorNotification,
        getUserFriendlyMessage
      }
    }
  }
})