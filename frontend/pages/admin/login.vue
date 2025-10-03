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
        <form
          ref="loginFormRef"
          @submit.prevent="handleLogin"
          novalidate
        >
          <div class="form-header">
            <div class="logo-section">
              <div class="logo-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 3H4C2.9 3 2 3.9 2 5V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V5C22 3.9 21.1 3 20 3ZM20 19H4V5H20V19Z" fill="currentColor"/>
                  <path d="M6 7H18V9H6V7ZM6 11H18V13H6V11ZM6 15H14V17H6V15Z" fill="currentColor"/>
                </svg>
              </div>
              <h2>管理員後台</h2>
              <p>推廣平台管理系統</p>
            </div>
            
            <div class="demo-credentials">
              <div class="credentials-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM13 17H11V11H13V17ZM13 9H11V7H13V9Z" fill="currentColor"/>
                </svg>
                <span>測試帳號資訊</span>
              </div>
              <div class="credentials-content">
                <div class="credential-item">
                  <span class="label">用戶名：</span>
                  <span class="value">admin</span>
                  <button type="button" class="quick-fill-btn" @click="fillCredentials">快速填入</button>
                </div>
                <div class="credential-item">
                  <span class="label">密碼：</span>
                  <span class="value">admin123</span>
                </div>
              </div>
            </div>
          </div>

          <div class="form-field">
            <div class="input-wrapper">
              <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
              </svg>
              <input
                v-model="loginForm.username"
                type="text"
                placeholder="用戶名 或 電子郵件"
                class="form-input"
                :class="{ 'error': validationErrors.username }"
                @keyup.enter="handleLogin"
                @blur="validateField('username')"
              />
              <button 
                v-if="loginForm.username"
                type="button" 
                class="clear-btn"
                @click="loginForm.username = ''"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                </svg>
              </button>
            </div>
            <div v-if="validationErrors.username" class="error-message">{{ validationErrors.username }}</div>
          </div>

          <div class="form-field">
            <div class="input-wrapper">
              <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 8H17V6C17 3.24 14.76 1 12 1S7 3.24 7 6V8H6C4.9 8 4 8.9 4 10V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V10C20 8.9 19.1 8 18 8ZM12 17C10.9 17 10 16.1 10 15S10.9 13 12 13 14 13.9 14 15 13.1 17 12 17ZM15.1 8H8.9V6C8.9 4.29 10.29 2.9 12 2.9S15.1 4.29 15.1 6V8Z" fill="currentColor"/>
              </svg>
              <input
                v-model="loginForm.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="密碼"
                class="form-input"
                :class="{ 'error': validationErrors.password }"
                @keyup.enter="handleLogin"
                @blur="validateField('password')"
              />
              <button 
                type="button" 
                class="password-toggle-btn"
                @click="showPassword = !showPassword"
              >
                <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 7C13.66 7 15 8.34 15 10C15 11.66 13.66 13 12 13C10.34 13 9 11.66 9 10C9 8.34 10.34 7 12 7ZM12 9C11.45 9 11 9.45 11 10C11 10.55 11.45 11 12 11C12.55 11 13 10.55 13 10C13 9.45 12.55 9 12 9ZM12 17C7.05 17 2.73 13.37 1 10C2.73 6.63 7.05 3 12 3C16.95 3 21.27 6.63 23 10C21.27 13.37 16.95 17 12 17Z" fill="currentColor"/>
                </svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 7C13.66 7 15 8.34 15 10C15 10.13 14.99 10.26 14.97 10.38L17.82 7.54C16.17 6.03 14.16 5 12 5C9.84 5 7.83 6.03 6.18 7.54L12 13.36C12.74 13.13 13.37 12.65 13.82 12.03L17.5 8.35C18.88 9.48 19.93 10.96 20.49 12.64C20.79 13.39 20.42 14.22 19.67 14.52C18.92 14.82 18.09 14.45 17.79 13.7C17.31 12.42 16.24 11.35 14.96 10.87L12 13.83C10.34 13.83 9 12.49 9 10.83C9 9.17 10.34 7.83 12 7.83C12.32 7.83 12.62 7.9 12.9 8.02L15.46 5.46C14.38 4.55 13.22 4 12 4C7.05 4 2.73 7.63 1 11C1.5 12.12 2.18 13.16 3 14.08L1.39 15.69C1 16.08 1 16.71 1.39 17.1C1.78 17.49 2.41 17.49 2.8 17.1L22.1 -2.2C22.49 -2.59 22.49 -3.22 22.1 -3.61C21.71 -4 21.08 -4 20.69 -3.61L3.51 13.57C2.74 12.81 2.08 11.95 1.51 11C2.73 7.63 7.05 4 12 4Z" fill="currentColor"/>
                </svg>
              </button>
              <button 
                v-if="loginForm.password"
                type="button" 
                class="clear-btn password-clear"
                @click="loginForm.password = ''"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                </svg>
              </button>
            </div>
            <div v-if="validationErrors.password" class="error-message">{{ validationErrors.password }}</div>
          </div>

          <div class="form-field">
            <div class="form-options">
              <label class="checkbox-wrapper">
                <input 
                  v-model="loginForm.remember" 
                  type="checkbox" 
                  class="checkbox-input"
                />
                <span class="checkbox-custom"></span>
                <span class="checkbox-label">記住我</span>
              </label>
              <button type="button" class="forgot-link" @click="showForgotPassword">
                忘記密碼？
              </button>
            </div>
          </div>

          <div class="form-field">
            <button
              type="submit"
              :disabled="!isFormValid || authStore.isLoading"
              class="login-button"
            >
              <span v-if="!authStore.isLoading" class="button-content">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8.59 16.59L13.17 12L8.59 7.41L10 6L16 12L10 18L8.59 16.59Z" fill="currentColor"/>
                </svg>
                登入
              </span>
              <span v-else class="button-content">
                <span class="loading-spinner"></span>
                登入中...
              </span>
            </button>
          </div>

          <div class="form-footer">
            <div class="back-to-frontend">
              <button type="button" @click="goToFrontend" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M15.41 16.59L10.83 12L15.41 7.41L14 6L8 12L14 18L15.41 16.59Z" fill="currentColor"/>
                </svg>
                返回前台
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Forgot Password Modal -->
    <div v-if="showForgotDialog" class="modal-overlay" @click.self="showForgotDialog = false">
      <div class="modal-dialog">
        <div class="modal-header">
          <h3>重設密碼</h3>
          <button type="button" class="modal-close" @click="showForgotDialog = false">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
            </svg>
          </button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleForgotPassword" novalidate>
            <p class="forgot-description">
              請輸入您的電子郵件地址，我們將發送重設密碼的連結給您。
            </p>
            
            <div class="form-field">
              <div class="input-wrapper">
                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z" fill="currentColor"/>
                </svg>
                <input
                  v-model="forgotForm.email"
                  type="email"
                  placeholder="電子郵件地址"
                  class="form-input"
                  :class="{ 'error': validationErrors.email }"
                  @blur="validateField('email', forgotForm.email)"
                />
                <button 
                  v-if="forgotForm.email"
                  type="button" 
                  class="clear-btn"
                  @click="forgotForm.email = ''"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                  </svg>
                </button>
              </div>
              <div v-if="validationErrors.email" class="error-message">{{ validationErrors.email }}</div>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="showForgotDialog = false">
            取消
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="forgotLoading || !forgotForm.email"
            @click="handleForgotPassword"
          >
            <span v-if="!forgotLoading">發送重設連結</span>
            <span v-else class="button-content">
              <span class="loading-spinner"></span>
              發送中...
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { LoginCredentials } from '~/types/auth'

// Page meta
definePageMeta({
  layout: false,
  middleware: 'guest'
})

// Auth store
const authStore = useAuthStore()

// Form refs
const loginFormRef = ref<HTMLFormElement>()

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
const showPassword = ref(false)

// Validation errors
const validationErrors = reactive<Record<string, string>>({
  username: '',
  password: '',
  email: ''
})

// Validation rules
const validateField = (field: string, value?: string) => {
  const val = value || loginForm[field as keyof LoginCredentials] || forgotForm[field as keyof typeof forgotForm]
  
  switch (field) {
    case 'username':
      if (!val || val.length === 0) {
        validationErrors.username = '請輸入用戶名或電子郵件'
      } else if (val.length < 3) {
        validationErrors.username = '用戶名至少需要3個字符'
      } else {
        validationErrors.username = ''
      }
      break
    case 'password':
      if (!val || val.length === 0) {
        validationErrors.password = '請輸入密碼'
      } else if (val.length < 6) {
        validationErrors.password = '密碼至少需要6個字符'
      } else {
        validationErrors.password = ''
      }
      break
    case 'email':
      if (!val || val.length === 0) {
        validationErrors.email = '請輸入電子郵件地址'
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        validationErrors.email = '請輸入有效的電子郵件地址'
      } else {
        validationErrors.email = ''
      }
      break
  }
}

const validateForm = () => {
  validateField('username')
  validateField('password')
  return !validationErrors.username && !validationErrors.password
}

// Computed
const isFormValid = computed(() => {
  return loginForm.username.length >= 3 && 
         loginForm.password.length >= 6 && 
         !validationErrors.username && 
         !validationErrors.password
})

// Custom notification system
const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  // Create notification element
  const notification = document.createElement('div')
  notification.className = `notification notification-${type}`
  notification.innerHTML = `
    <div class="notification-content">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        ${type === 'success' 
          ? '<path d="M9 16.17L4.83 12L3.41 13.41L9 19L21 7L19.59 5.59L9 16.17Z" fill="currentColor"/>'
          : '<path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>'
        }
      </svg>
      <span>${message}</span>
    </div>
  `
  
  // Add to DOM
  document.body.appendChild(notification)
  
  // Show animation
  setTimeout(() => notification.classList.add('show'), 10)
  
  // Auto remove
  setTimeout(() => {
    notification.classList.remove('show')
    setTimeout(() => document.body.removeChild(notification), 300)
  }, 3000)
}

// Methods
const handleLogin = async () => {
  if (!validateForm()) return
  
  try {
    await authStore.login(loginForm)
    showNotification('登入成功！', 'success')
  } catch (error) {
    if (error instanceof Error) {
      showNotification(error.message, 'error')
    } else {
      showNotification('登入失敗，請稍後再試', 'error')
    }
  }
}

const goToFrontend = async () => {
  await navigateTo('/')
}

const fillCredentials = () => {
  loginForm.username = 'admin'
  loginForm.password = 'admin123'
  // Clear validation errors
  validationErrors.username = ''
  validationErrors.password = ''
}

const showForgotPassword = () => {
  showForgotDialog.value = true
  forgotForm.email = ''
  validationErrors.email = ''
}

const handleForgotPassword = async () => {
  validateField('email', forgotForm.email)
  if (validationErrors.email) return
  
  try {
    forgotLoading.value = true
    
    // TODO: Implement forgot password API call
    await new Promise(resolve => setTimeout(resolve, 2000)) // Mock delay
    
    showNotification('重設密碼連結已發送到您的郵箱', 'success')
    showForgotDialog.value = false
  } catch (error) {
    showNotification('發送失敗，請稍後再試', 'error')
  } finally {
    forgotLoading.value = false
  }
}

// Auto-focus on mount
onMounted(() => {
  nextTick(() => {
    if (loginFormRef.value) {
      const firstInput = loginFormRef.value.querySelector('input')
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

.quick-fill-btn {
  background: transparent;
  border: none;
  color: #667eea;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.quick-fill-btn:hover {
  background: rgba(102, 126, 234, 0.1);
}

/* Form field styling */
.form-field {
  margin-bottom: 24px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.form-input {
  width: 100%;
  padding: 16px 20px 16px 50px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 16px;
  color: #1e293b;
  background: #fafafa;
  transition: all 0.3s ease;
  outline: none;
}

.form-input:hover {
  border-color: #667eea;
  background: #ffffff;
}

.form-input:focus {
  border-color: #667eea;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-input.error {
  border-color: #ef4444;
  background: #fef2f2;
}

.form-input::placeholder {
  color: #94a3b8;
}

.input-icon {
  position: absolute;
  left: 16px;
  color: #94a3b8;
  pointer-events: none;
  z-index: 2;
}

.clear-btn, .password-toggle-btn {
  position: absolute;
  right: 16px;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.clear-btn:hover, .password-toggle-btn:hover {
  color: #64748b;
  background: rgba(100, 116, 139, 0.1);
}

.password-clear {
  right: 50px;
}

.error-message {
  color: #ef4444;
  font-size: 14px;
  margin-top: 6px;
  font-weight: 500;
}

/* Form options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

/* Custom checkbox */
.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}

.checkbox-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-radius: 4px;
  background: #ffffff;
  position: relative;
  transition: all 0.2s ease;
}

.checkbox-input:checked + .checkbox-custom {
  background: #667eea;
  border-color: #667eea;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '';
  position: absolute;
  left: 3px;
  top: 1px;
  width: 6px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-label {
  font-weight: 500;
  color: #475569;
  font-size: 14px;
}

.forgot-link {
  background: transparent;
  border: none;
  color: #667eea;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

.forgot-link:hover {
  color: #5b6fd8;
  text-decoration: underline;
}

/* Login button */
.login-button {
  width: 100%;
  height: 54px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
}

.login-button:active:not(:disabled) {
  transform: translateY(0);
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.button-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Loading spinner */
.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
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
  background: transparent;
  border: none;
  font-weight: 500;
  font-size: 14px;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.back-link:hover {
  color: #475569;
  background: #f1f5f9;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-dialog {
  background: white;
  border-radius: 16px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  max-width: 400px;
  width: 90vw;
  max-height: 90vh;
  overflow: hidden;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 24px 0;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}

.modal-close {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.modal-close:hover {
  color: #475569;
  background: #f1f5f9;
}

.modal-body {
  padding: 0 24px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 24px;
  border-top: 1px solid #e2e8f0;
  margin-top: 24px;
}

.forgot-description {
  color: #64748b;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 24px;
}

/* Button variants for modal */
.btn {
  padding: 12px 20px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-secondary {
  background: #f1f5f9;
  color: #64748b;
}

.btn-secondary:hover {
  background: #e2e8f0;
  color: #475569;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none !important;
}

/* Notification styles */
:global(.notification) {
  position: fixed;
  top: 20px;
  right: 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  padding: 16px 20px;
  z-index: 10000;
  min-width: 300px;
  opacity: 0;
  transform: translateX(100%);
  transition: all 0.3s ease;
}

:global(.notification.show) {
  opacity: 1;
  transform: translateX(0);
}

:global(.notification-success) {
  border-left: 4px solid #10b981;
}

:global(.notification-error) {
  border-left: 4px solid #ef4444;
}

:global(.notification-content) {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #1e293b;
  font-weight: 500;
}

:global(.notification-success .notification-content svg) {
  color: #10b981;
}

:global(.notification-error .notification-content svg) {
  color: #ef4444;
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

  .modal-dialog {
    width: 95vw;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 20px;
    padding-right: 20px;
  }
  
  :global(.notification) {
    right: 10px;
    left: 10px;
    min-width: auto;
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
  
  .form-input {
    font-size: 16px; /* Prevent zoom on iOS */
  }
}
</style>