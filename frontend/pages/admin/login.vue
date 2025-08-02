<template>
  <div class="login-page">
    <!-- Background Elements -->
    <div class="bg-decoration">
      <div class="bg-circle bg-circle-1"></div>
      <div class="bg-circle bg-circle-2"></div>
      <div class="bg-circle bg-circle-3"></div>
    </div>
    
    <!-- Login Card -->
    <div class="login-card">
      <div class="login-form-container">
      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="loginRules"
        size="large"
        @submit.prevent="handleLogin"
      >
        <div class="form-header">
          <div class="logo-section">
            <div class="logo-icon">
              <el-icon size="40"><Monitor /></el-icon>
            </div>
            <h2>管理員後台</h2>
            <p>推廣平台管理系統</p>
          </div>
          
          <div class="demo-credentials">
            <div class="credentials-header">
              <el-icon><InfoFilled /></el-icon>
              <span>測試帳號資訊</span>
            </div>
            <div class="credentials-content">
              <div class="credential-item">
                <span class="label">用戶名：</span>
                <span class="value">admin</span>
                <el-button link size="small" @click="fillCredentials">快速填入</el-button>
              </div>
              <div class="credential-item">
                <span class="label">密碼：</span>
                <span class="value">admin123</span>
              </div>
            </div>
          </div>
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
          <div class="back-to-frontend">
            <el-link @click="goToFrontend" class="back-link">
              <el-icon><ArrowLeft /></el-icon>
              返回前台
            </el-link>
          </div>
        </div>
      </el-form>
      </div>
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
import { User, Lock, Right, Message, Monitor, InfoFilled, ArrowLeft } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import type { LoginCredentials } from '~/types/auth'

// Page meta
definePageMeta({
  layout: false,
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
  await navigateTo('/admin/register')
}

const goToFrontend = async () => {
  await navigateTo('/')
}

const fillCredentials = () => {
  loginForm.username = 'admin'
  loginForm.password = 'admin123'
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

/* Background decoration */
.bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.bg-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.bg-circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  right: -150px;
  animation: float 6s ease-in-out infinite;
}

.bg-circle-2 {
  width: 200px;
  height: 200px;
  bottom: -100px;
  left: -100px;
  animation: float 8s ease-in-out infinite reverse;
}

.bg-circle-3 {
  width: 150px;
  height: 150px;
  top: 20%;
  left: 10%;
  animation: float 10s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

/* Login card */
.login-card {
  position: relative;
  z-index: 2;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 
    0 25px 50px rgba(0, 0, 0, 0.15),
    0 0 0 1px rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  overflow: hidden;
}

.login-form-container {
  width: 100%;
  max-width: 450px;
  padding: 40px;
}

/* Form header */
.form-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo-section {
  margin-bottom: 30px;
}

.logo-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  margin: 0 auto 20px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.logo-section h2 {
  margin: 0 0 8px;
  font-size: 32px;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.logo-section p {
  margin: 0;
  color: #64748b;
  font-size: 16px;
  font-weight: 500;
}

/* Demo credentials */
.demo-credentials {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  margin-top: 20px;
  text-align: left;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.credentials-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  color: #475569;
  font-weight: 600;
  font-size: 14px;
}

.credentials-content {
  space-y: 12px;
}

.credential-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 14px;
}

.credential-item .label {
  color: #64748b;
  font-weight: 500;
  min-width: 60px;
}

.credential-item .value {
  color: #1e293b;
  font-weight: 600;
  font-family: 'Monaco', 'Consolas', monospace;
  background: #f1f5f9;
  padding: 4px 8px;
  border-radius: 6px;
  flex: 1;
}

/* Form styling */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.login-button {
  width: 100%;
  height: 54px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
}

.login-button:active {
  transform: translateY(0);
}

/* Form footer */
.form-footer {
  text-align: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.back-to-frontend {
  display: flex;
  justify-content: center;
}

.back-link {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  text-decoration: none;
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.back-link:hover {
  color: #475569;
  background: #f1f5f9;
}

.forgot-description {
  color: #64748b;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 24px;
}

.dialog-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Enhanced form styling */
:deep(.el-form-item) {
  margin-bottom: 24px;
}

:deep(.el-input) {
  border-radius: 12px;
}

:deep(.el-input__wrapper) {
  padding: 16px 20px;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  background: #fafafa;
  transition: all 0.3s ease;
  box-shadow: none;
}

:deep(.el-input__wrapper:hover) {
  border-color: #667eea;
  background: #ffffff;
}

:deep(.el-input__wrapper.is-focus) {
  border-color: #667eea;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

:deep(.el-input__inner) {
  font-size: 16px;
  color: #1e293b;
}

:deep(.el-input__inner::placeholder) {
  color: #94a3b8;
}

:deep(.el-checkbox__label) {
  font-weight: 500;
  color: #475569;
}

:deep(.el-link) {
  font-weight: 600;
}

:deep(.el-button) {
  border-radius: 12px;
  font-weight: 600;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .login-form-container {
    padding: 30px 24px;
    max-width: 90vw;
  }
  
  .logo-section h2 {
    font-size: 28px;
  }
  
  .logo-icon {
    width: 70px;
    height: 70px;
  }
  
  .form-options {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }
  
  .bg-circle-1,
  .bg-circle-2,
  .bg-circle-3 {
    display: none;
  }
}

@media (max-width: 480px) {
  .login-form-container {
    padding: 24px 20px;
  }
  
  .demo-credentials {
    padding: 16px;
  }
  
  .credential-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .credential-item .value {
    width: 100%;
  }
}
</style>