<template>
  <div class="login-page">
    <div class="login-form-container">
      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="loginRules"
        size="large"
        @submit.prevent="handleLogin"
      >
        <div class="form-header">
          <h2>歡迎回來</h2>
          <p>請登入您的帳號以繼續使用服務</p>
        </div>

        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            placeholder="用戶名 或 電子郵件"
            :prefix-icon="User"
            clearable
            @keyup.enter="handleLogin"
          />
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            placeholder="密碼"
            :prefix-icon="Lock"
            show-password
            clearable
            @keyup.enter="handleLogin"
          />
        </el-form-item>

        <el-form-item>
          <div class="form-options">
            <el-checkbox v-model="loginForm.remember">
              記住我
            </el-checkbox>
            <el-link type="primary" @click="showForgotPassword">
              忘記密碼？
            </el-link>
          </div>
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="authStore.isLoading"
            :disabled="!isFormValid"
            class="login-button"
            @click="handleLogin"
          >
            <template v-if="!authStore.isLoading">
              <el-icon><Right /></el-icon>
              登入
            </template>
            <template v-else>
              登入中...
            </template>
          </el-button>
        </el-form-item>

        <div class="form-footer">
          <el-divider>或</el-divider>
          <p>
            還沒有帳號？
            <el-link type="primary" @click="goToRegister">
              立即註冊
            </el-link>
          </p>
        </div>
      </el-form>
    </div>

    <!-- Forgot Password Dialog -->
    <el-dialog
      v-model="showForgotDialog"
      title="重設密碼"
      width="400px"
      center
    >
      <el-form
        ref="forgotFormRef"
        :model="forgotForm"
        :rules="forgotRules"
        size="large"
      >
        <p class="forgot-description">
          請輸入您的電子郵件地址，我們將發送重設密碼的連結給您。
        </p>
        
        <el-form-item prop="email">
          <el-input
            v-model="forgotForm.email"
            placeholder="電子郵件地址"
            :prefix-icon="Message"
            clearable
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showForgotDialog = false">
            取消
          </el-button>
          <el-button
            type="primary"
            :loading="forgotLoading"
            @click="handleForgotPassword"
          >
            發送重設連結
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { User, Lock, Right, Message } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import type { LoginCredentials } from '~/types/auth'

// Page meta
definePageMeta({
  layout: 'auth',
  middleware: 'guest'
})

// Auth store
const authStore = useAuthStore()

// Form refs
const loginFormRef = ref<FormInstance>()
const forgotFormRef = ref<FormInstance>()

// Form data
const loginForm = reactive<LoginCredentials>({
  username: '',
  password: '',
  remember: false
})

const forgotForm = reactive({
  email: ''
})

// Loading states
const forgotLoading = ref(false)
const showForgotDialog = ref(false)

// Form validation rules
const loginRules: FormRules = {
  username: [
    { required: true, message: '請輸入用戶名或電子郵件', trigger: 'blur' },
    { min: 3, message: '用戶名至少需要3個字符', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '請輸入密碼', trigger: 'blur' },
    { min: 6, message: '密碼至少需要6個字符', trigger: 'blur' }
  ]
}

const forgotRules: FormRules = {
  email: [
    { required: true, message: '請輸入電子郵件地址', trigger: 'blur' },
    { type: 'email', message: '請輸入有效的電子郵件地址', trigger: 'blur' }
  ]
}

// Computed
const isFormValid = computed(() => {
  return loginForm.username.length >= 3 && loginForm.password.length >= 6
})

// Methods
const handleLogin = async () => {
  if (!loginFormRef.value) return
  
  try {
    await loginFormRef.value.validate()
    await authStore.login(loginForm)
    
    ElMessage.success('登入成功！')
  } catch (error) {
    if (error instanceof Error) {
      ElMessage.error(error.message)
    } else {
      ElMessage.error('登入失敗，請稍後再試')
    }
  }
}

const goToRegister = async () => {
  await navigateTo('/register')
}

const showForgotPassword = () => {
  showForgotDialog.value = true
  forgotForm.email = ''
}

const handleForgotPassword = async () => {
  if (!forgotFormRef.value) return
  
  try {
    await forgotFormRef.value.validate()
    forgotLoading.value = true
    
    // TODO: Implement forgot password API call
    await new Promise(resolve => setTimeout(resolve, 2000)) // Mock delay
    
    ElMessage.success('重設密碼連結已發送到您的郵箱')
    showForgotDialog.value = false
  } catch (error) {
    ElMessage.error('發送失敗，請稍後再試')
  } finally {
    forgotLoading.value = false
  }
}

// Auto-focus on mount
onMounted(() => {
  nextTick(() => {
    if (loginFormRef.value) {
      const firstInput = loginFormRef.value.$el.querySelector('input')
      firstInput?.focus()
    }
  })
})
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-form-container {
  width: 100%;
  max-width: 400px;
}

.form-header {
  text-align: center;
  margin-bottom: 32px;
}

.form-header h2 {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.form-header p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 14px;
  line-height: 1.5;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.login-button {
  width: 100%;
  height: 48px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.form-footer {
  text-align: center;
  margin-top: 24px;
}

.form-footer p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.forgot-description {
  color: var(--el-text-color-secondary);
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 24px;
}

.dialog-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Form styling improvements */
:deep(.el-form-item) {
  margin-bottom: 24px;
}

:deep(.el-input) {
  border-radius: 8px;
}

:deep(.el-input__wrapper) {
  padding: 12px 16px;
  border-radius: 8px;
}

:deep(.el-button) {
  border-radius: 8px;
}

/* Mobile responsiveness */
@media (max-width: 480px) {
  .login-form-container {
    padding: 0 16px;
  }
  
  .form-header h2 {
    font-size: 24px;
  }
  
  .form-options {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}
</style>