<template>
  <div class="api-test-page">
    <div class="test-container">
      <h1>API 連接測試</h1>
      
      <div class="test-section">
        <h2>基本連接測試</h2>
        <el-button @click="testBasicConnection" :loading="isTestingBasic" type="primary">
          測試基本連接
        </el-button>
        <div v-if="basicTestResult" class="test-result" :class="basicTestResult.success ? 'success' : 'error'">
          {{ basicTestResult.message }}
          <pre v-if="basicTestResult.data">{{ JSON.stringify(basicTestResult.data, null, 2) }}</pre>
        </div>
      </div>

      <div class="test-section">
        <h2>健康檢查</h2>
        <el-button @click="testHealth" :loading="isTestingHealth" type="success">
          健康檢查
        </el-button>
        <div v-if="healthTestResult" class="test-result" :class="healthTestResult.success ? 'success' : 'error'">
          {{ healthTestResult.message }}
          <pre v-if="healthTestResult.data">{{ JSON.stringify(healthTestResult.data, null, 2) }}</pre>
        </div>
      </div>

      <div class="test-section">
        <h2>登入測試</h2>
        <el-form @submit.prevent="testLogin" class="login-test-form">
          <el-form-item label="用戶名">
            <el-input v-model="loginData.username" placeholder="admin" />
          </el-form-item>
          <el-form-item label="密碼">
            <el-input v-model="loginData.password" type="password" placeholder="admin123" />
          </el-form-item>
          <el-form-item>
            <el-button @click="testLogin" :loading="isTestingLogin" type="warning">
              測試登入
            </el-button>
          </el-form-item>
        </el-form>
        <div v-if="loginTestResult" class="test-result" :class="loginTestResult.success ? 'success' : 'error'">
          {{ loginTestResult.message }}
          <pre v-if="loginTestResult.data">{{ JSON.stringify(loginTestResult.data, null, 2) }}</pre>
        </div>
      </div>

      <div class="debug-section">
        <h2>調試信息</h2>
        <div class="debug-info">
          <p><strong>API Base URL:</strong> {{ apiBaseUrl }}</p>
          <p><strong>Environment:</strong> {{ process.env.NODE_ENV }}</p>
          <p><strong>Current URL:</strong> {{ currentUrl }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Page meta
definePageMeta({
  layout: false
})

// Reactive data
const isTestingBasic = ref(false)
const isTestingHealth = ref(false)
const isTestingLogin = ref(false)

const basicTestResult = ref<any>(null)
const healthTestResult = ref<any>(null)
const loginTestResult = ref<any>(null)

const loginData = reactive({
  username: 'admin',
  password: 'admin123'
})

// Get API base URL from config
const config = useRuntimeConfig()
const apiBaseUrl = config.public.apiBase
const currentUrl = ref('')

onMounted(() => {
  currentUrl.value = window.location.href
})

// Test basic connection
const testBasicConnection = async () => {
  isTestingBasic.value = true
  basicTestResult.value = null
  
  try {
    const response = await fetch(`${apiBaseUrl}/test`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      }
    })
    
    const data = await response.json()
    
    basicTestResult.value = {
      success: response.ok,
      message: response.ok ? '基本連接成功' : '基本連接失敗',
      data: data
    }
  } catch (error: any) {
    basicTestResult.value = {
      success: false,
      message: `連接錯誤: ${error.message}`,
      data: null
    }
  } finally {
    isTestingBasic.value = false
  }
}

// Test health check
const testHealth = async () => {
  isTestingHealth.value = true
  healthTestResult.value = null
  
  try {
    const response = await fetch(`${apiBaseUrl}/health`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      }
    })
    
    const data = await response.json()
    
    healthTestResult.value = {
      success: response.ok,
      message: response.ok ? '健康檢查通過' : '健康檢查失敗',
      data: data
    }
  } catch (error: any) {
    healthTestResult.value = {
      success: false,
      message: `健康檢查錯誤: ${error.message}`,
      data: null
    }
  } finally {
    isTestingHealth.value = false
  }
}

// Test login
const testLogin = async () => {
  isTestingLogin.value = true
  loginTestResult.value = null
  
  try {
    const response = await fetch(`${apiBaseUrl}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(loginData)
    })
    
    const data = await response.json()
    
    loginTestResult.value = {
      success: response.ok,
      message: response.ok ? '登入測試成功' : '登入測試失敗',
      data: data
    }
  } catch (error: any) {
    loginTestResult.value = {
      success: false,
      message: `登入測試錯誤: ${error.message}`,
      data: null
    }
  } finally {
    isTestingLogin.value = false
  }
}
</script>

<style scoped>
.api-test-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 2rem;
}

.test-container {
  max-width: 800px;
  margin: 0 auto;
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.test-section {
  margin-bottom: 2rem;
  padding: 1rem;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
}

.test-section h2 {
  margin-top: 0;
  color: #303133;
}

.test-result {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 4px;
  border-left: 4px solid;
}

.test-result.success {
  background: #f0f9ff;
  border-color: #10b981;
  color: #065f46;
}

.test-result.error {
  background: #fef2f2;
  border-color: #ef4444;
  color: #991b1b;
}

.test-result pre {
  background: #f8f9fa;
  padding: 0.5rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.login-test-form {
  max-width: 400px;
}

.debug-section {
  margin-top: 2rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 4px;
}

.debug-info p {
  margin: 0.5rem 0;
  font-family: monospace;
  font-size: 0.875rem;
}
</style>