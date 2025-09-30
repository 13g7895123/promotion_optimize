<template>
  <div class="container mx-auto p-6">
    <div class="max-w-4xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
        CORS 連接測試
      </h1>

      <!-- Environment Info -->
      <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
          當前環境配置
        </h2>
        <div class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
          <p><strong>API Base URL:</strong> {{ apiBase }}</p>
          <p><strong>環境模式:</strong> {{ process.client ? 'Client' : 'Server' }}</p>
          <p><strong>當前時間:</strong> {{ currentTime }}</p>
        </div>
      </div>

      <!-- Test Controls -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <button
          @click="testCorsGet"
          :disabled="loading"
          class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
        >
          測試 GET 請求
        </button>

        <button
          @click="testCorsPost"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
        >
          測試 POST 請求
        </button>

        <button
          @click="testAuthLogin"
          :disabled="loading"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
        >
          測試登入 API
        </button>
      </div>

      <!-- Loading Indicator -->
      <div v-if="loading" class="mb-6 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-2"></div>
        <p class="text-gray-600 dark:text-gray-300">測試中...</p>
      </div>

      <!-- Test Results -->
      <div v-if="testResults.length > 0" class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
          測試結果
        </h2>

        <div
          v-for="result in testResults"
          :key="result.id"
          class="p-4 border rounded-lg"
          :class="{
            'border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800': result.success,
            'border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800': !result.success
          }"
        >
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold" :class="{
              'text-green-800 dark:text-green-200': result.success,
              'text-red-800 dark:text-red-200': !result.success
            }">
              {{ result.title }}
            </h3>
            <span class="px-2 py-1 text-xs font-semibold rounded" :class="{
              'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200': result.success,
              'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200': !result.success
            }">
              {{ result.success ? '成功' : '失敗' }}
            </span>
          </div>

          <div class="text-sm space-y-2">
            <p><strong>URL:</strong> {{ result.url }}</p>
            <p><strong>狀態碼:</strong> {{ result.status }}</p>
            <p><strong>響應時間:</strong> {{ result.responseTime }}ms</p>
            <p><strong>時間:</strong> {{ result.timestamp }}</p>

            <div v-if="result.error" class="mt-2">
              <p class="font-medium text-red-800 dark:text-red-200">錯誤信息:</p>
              <pre class="mt-1 p-2 bg-red-100 dark:bg-red-900/30 rounded text-xs overflow-auto">{{ result.error }}</pre>
            </div>

            <div v-if="result.data" class="mt-2">
              <p class="font-medium">響應數據:</p>
              <pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto">{{ JSON.stringify(result.data, null, 2) }}</pre>
            </div>

            <div v-if="result.headers" class="mt-2">
              <p class="font-medium">響應標頭:</p>
              <pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto">{{ JSON.stringify(result.headers, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>

      <!-- Clear Results -->
      <div v-if="testResults.length > 0" class="mt-6 text-center">
        <button
          @click="clearResults"
          class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
        >
          清除結果
        </button>
      </div>

      <!-- Instructions -->
      <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
          使用說明
        </h2>
        <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
          <p>1. <strong>測試 GET 請求:</strong> 測試基本的 CORS GET 請求</p>
          <p>2. <strong>測試 POST 請求:</strong> 測試 CORS POST 請求與 JSON 數據傳輸</p>
          <p>3. <strong>測試登入 API:</strong> 測試實際的登入 API 端點（會失敗，但可以檢查 CORS）</p>
          <p>4. 如果所有測試都成功，表示 CORS 配置正確</p>
          <p>5. 如果出現 CORS 錯誤，請檢查後端 CORS 配置和服務狀態</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Define page meta
definePageMeta({
  title: 'CORS 測試',
  description: 'CORS 連接測試頁面'
})

// Reactive data
const loading = ref(false)
const testResults = ref([])
const currentTime = ref('')

// Get runtime config
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

// Update current time every second
const updateTime = () => {
  currentTime.value = new Date().toLocaleString('zh-TW')
}

// Test functions
const testCorsGet = async () => {
  const startTime = Date.now()
  loading.value = true

  try {
    const response = await $fetch('/cors-test', {
      baseURL: apiBase,
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    })

    const endTime = Date.now()

    testResults.value.unshift({
      id: Date.now(),
      title: 'CORS GET 測試',
      url: `${apiBase}/cors-test`,
      status: 200,
      success: true,
      data: response,
      responseTime: endTime - startTime,
      timestamp: new Date().toLocaleString('zh-TW')
    })

  } catch (error) {
    const endTime = Date.now()

    testResults.value.unshift({
      id: Date.now(),
      title: 'CORS GET 測試',
      url: `${apiBase}/cors-test`,
      status: error.status || 0,
      success: false,
      error: error.message || error.toString(),
      responseTime: endTime - startTime,
      timestamp: new Date().toLocaleString('zh-TW')
    })
  } finally {
    loading.value = false
  }
}

const testCorsPost = async () => {
  const startTime = Date.now()
  loading.value = true

  try {
    const testData = {
      message: 'CORS POST 測試',
      timestamp: new Date().toISOString(),
      userAgent: process.client ? navigator.userAgent : 'SSR'
    }

    const response = await $fetch('/cors-test', {
      baseURL: apiBase,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: testData
    })

    const endTime = Date.now()

    testResults.value.unshift({
      id: Date.now(),
      title: 'CORS POST 測試',
      url: `${apiBase}/cors-test`,
      status: 200,
      success: true,
      data: response,
      responseTime: endTime - startTime,
      timestamp: new Date().toLocaleString('zh-TW')
    })

  } catch (error) {
    const endTime = Date.now()

    testResults.value.unshift({
      id: Date.now(),
      title: 'CORS POST 測試',
      url: `${apiBase}/cors-test`,
      status: error.status || 0,
      success: false,
      error: error.message || error.toString(),
      responseTime: endTime - startTime,
      timestamp: new Date().toLocaleString('zh-TW')
    })
  } finally {
    loading.value = false
  }
}

const testAuthLogin = async () => {
  const startTime = Date.now()
  loading.value = true

  try {
    const loginData = {
      email: 'test@example.com',
      password: 'password123'
    }

    const response = await $fetch('/auth/login', {
      baseURL: apiBase,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: loginData
    })

    const endTime = Date.now()

    testResults.value.unshift({
      id: Date.now(),
      title: '登入 API 測試',
      url: `${apiBase}/auth/login`,
      status: 200,
      success: true,
      data: response,
      responseTime: endTime - startTime,
      timestamp: new Date().toLocaleString('zh-TW')
    })

  } catch (error) {
    const endTime = Date.now()

    // Even if login fails, if we get a proper response (not CORS error), it's a success
    const isAuthError = error.status === 401 || error.status === 422 || error.status === 400
    const success = isAuthError // Auth errors mean CORS is working

    testResults.value.unshift({
      id: Date.now(),
      title: '登入 API 測試',
      url: `${apiBase}/auth/login`,
      status: error.status || 0,
      success: success,
      error: error.message || error.toString(),
      data: error.data || null,
      responseTime: endTime - startTime,
      timestamp: new Date().toLocaleString('zh-TW')
    })
  } finally {
    loading.value = false
  }
}

const clearResults = () => {
  testResults.value = []
}

// Initialize
onMounted(() => {
  updateTime()
  setInterval(updateTime, 1000)
})
</script>

<style scoped>
pre {
  white-space: pre-wrap;
  word-break: break-word;
}
</style>