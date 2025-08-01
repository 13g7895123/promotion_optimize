<template>
  <div class="error-boundary">
    <slot v-if="!hasError" />
    
    <!-- Error Display -->
    <div v-else class="error-display">
      <el-result
        :icon="errorIcon"
        :title="errorTitle"
        :sub-title="errorMessage"
      >
        <template #extra>
          <div class="error-actions">
            <el-button @click="retry" type="primary">
              <el-icon><Refresh /></el-icon>
              重試
            </el-button>
            <el-button @click="goHome">
              <el-icon><House /></el-icon>
              回到首頁
            </el-button>
            <el-button v-if="showDetails" @click="toggleDetails" text>
              {{ showErrorDetails ? '隱藏' : '顯示' }}詳細資訊
            </el-button>
          </div>
          
          <!-- Error Details -->
          <el-collapse v-if="showDetails && showErrorDetails" class="error-details">
            <el-collapse-item title="錯誤詳細資訊" name="details">
              <div class="error-info">
                <p><strong>錯誤類型:</strong> {{ error?.name }}</p>
                <p><strong>錯誤訊息:</strong> {{ error?.message }}</p>
                <p><strong>發生時間:</strong> {{ errorTime }}</p>
                <p><strong>頁面路徑:</strong> {{ route.path }}</p>
                <p><strong>用戶代理:</strong> {{ userAgent }}</p>
              </div>
              
              <div v-if="error?.stack" class="error-stack">
                <h4>堆疊追蹤:</h4>
                <pre>{{ error.stack }}</pre>
              </div>
            </el-collapse-item>
          </el-collapse>
        </template>
      </el-result>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Refresh, House } from '@element-plus/icons-vue'

interface Props {
  fallbackComponent?: any
  onError?: (error: Error, errorInfo: any) => void
  showDetails?: boolean
  level?: 'page' | 'component' | 'global'
}

const props = withDefaults(defineProps<Props>(), {
  showDetails: true,
  level: 'component'
})

const route = useRoute()
const hasError = ref(false)
const error = ref<Error | null>(null)
const errorTime = ref('')
const showErrorDetails = ref(false)
const userAgent = process.client ? navigator.userAgent : ''

// Error display properties
const errorIcon = computed(() => {
  if (!error.value) return 'warning'
  
  if (error.value.name === 'NetworkError') return 'warning'
  if (error.value.name === 'ValidationError') return 'info'
  return 'error'
})

const errorTitle = computed(() => {
  if (!error.value) return '發生錯誤'
  
  const errorTitleMap: Record<string, string> = {
    'NetworkError': '網路連線錯誤',
    'ValidationError': '資料驗證錯誤',
    'PermissionError': '權限不足',
    'NotFoundError': '資源不存在',
    'TypeError': '類型錯誤',
    'ReferenceError': '參考錯誤'
  }
  
  return errorTitleMap[error.value.name] || '未知錯誤'
})

const errorMessage = computed(() => {
  if (!error.value) return '頁面載入時發生錯誤'
  
  // Provide user-friendly error messages
  const friendlyMessages: Record<string, string> = {
    'Network Error': '無法連接到伺服器，請檢查網路連線',
    'Failed to fetch': '資料載入失敗，請稍後再試',
    'Unauthorized': '您沒有權限執行此操作',
    'Forbidden': '禁止訪問該資源',
    'Not Found': '找不到請求的資源'
  }
  
  const message = error.value.message
  for (const [key, friendlyMessage] of Object.entries(friendlyMessages)) {
    if (message.includes(key)) {
      return friendlyMessage
    }
  }
  
  return message || '發生未知錯誤，請聯繫技術支援'
})

// Error handling methods
const captureError = (err: Error, errorInfo?: any) => {
  hasError.value = true
  error.value = err
  errorTime.value = new Date().toLocaleString()
  
  // Log error
  console.error('Error captured by ErrorBoundary:', err, errorInfo)
  
  // Report error to monitoring service
  reportError(err, errorInfo)
  
  // Call custom error handler
  if (props.onError) {
    props.onError(err, errorInfo)
  }
}

const reportError = (err: Error, errorInfo?: any) => {
  // Here you would typically send error to monitoring service
  // like Sentry, LogRocket, or custom error reporting endpoint
  
  const errorReport = {
    message: err.message,
    stack: err.stack,
    name: err.name,
    url: window.location.href,
    userAgent: navigator.userAgent,
    timestamp: new Date().toISOString(),
    route: route.path,
    level: props.level,
    errorInfo
  }
  
  // Mock error reporting - replace with actual service
  console.warn('Error reported:', errorReport)
  
  // Example: Send to monitoring service
  // await $fetch('/api/errors', {
  //   method: 'POST',
  //   body: errorReport
  // })
}

const retry = () => {
  hasError.value = false
  error.value = null
  errorTime.value = ''
  showErrorDetails.value = false
  
  // Refresh current route
  if (process.client) {
    window.location.reload()
  }
}

const goHome = async () => {
  hasError.value = false
  error.value = null
  await navigateTo('/')
}

const toggleDetails = () => {
  showErrorDetails.value = !showErrorDetails.value
}

// Set up global error handlers
onMounted(() => {
  if (props.level === 'global') {
    // Capture unhandled JavaScript errors
    window.addEventListener('error', (event) => {
      captureError(event.error || new Error(event.message))
    })
    
    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      captureError(new Error(event.reason))
    })
    
    // Vue error handler
    const app = getCurrentInstance()?.appContext.app
    if (app) {
      app.config.errorHandler = (err, instance, info) => {
        captureError(err instanceof Error ? err : new Error(String(err)), info)
      }
    }
  }
})

// Expose error capture method for manual error reporting
defineExpose({
  captureError,
  retry,
  hasError: readonly(hasError)
})
</script>

<style scoped>
.error-boundary {
  width: 100%;
  height: 100%;
}

.error-display {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 40px 20px;
}

.error-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.error-details {
  max-width: 600px;
  margin-top: 20px;
  text-align: left;
}

.error-info p {
  margin: 8px 0;
  font-size: 14px;
  color: var(--el-text-color-regular);
}

.error-info strong {
  color: var(--el-text-color-primary);
}

.error-stack {
  margin-top: 16px;
}

.error-stack h4 {
  margin: 0 0 8px;
  font-size: 14px;
  color: var(--el-text-color-primary);
}

.error-stack pre {
  background: var(--el-bg-color-page);
  padding: 12px;
  border-radius: 4px;
  font-size: 12px;
  color: var(--el-text-color-regular);
  overflow-x: auto;
  max-height: 200px;
  overflow-y: auto;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .error-display {
    min-height: 300px;
    padding: 20px 16px;
  }
  
  .error-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .error-actions .el-button {
    width: 200px;
  }
  
  .error-details {
    max-width: 100%;
  }
  
  .error-stack pre {
    font-size: 11px;
  }
}

/* Global error styles */
:deep(.el-result) {
  background: transparent;
}

:deep(.el-result__icon svg) {
  width: 64px;
  height: 64px;
}

:deep(.el-result__title) {
  font-size: 20px;
  margin: 16px 0 8px;
}

:deep(.el-result__subtitle) {
  font-size: 14px;
  line-height: 1.5;
  color: var(--el-text-color-regular);
}
</style>