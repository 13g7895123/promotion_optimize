<template>
  <div class="register-page">
    <div class="register-form-container">
      <el-form
        ref="registerFormRef"
        :model="registerForm"
        :rules="registerRules"
        size="large"
        @submit.prevent="handleRegister"
      >
        <div class="form-header">
          <h2>建立新帳號</h2>
          <p>加入我們的遊戲推廣平台，開始您的推廣之旅</p>
        </div>

        <el-form-item prop="username">
          <el-input
            v-model="registerForm.username"
            placeholder="用戶名 (3-20個字符)"
            :prefix-icon="User"
            clearable
            @blur="checkUsernameAvailability"
          />
        </el-form-item>

        <el-form-item prop="email">
          <el-input
            v-model="registerForm.email"
            placeholder="電子郵件地址"
            :prefix-icon="Message"
            clearable
            @blur="checkEmailAvailability"
          />
        </el-form-item>

        <el-form-item prop="nickname">
          <el-input
            v-model="registerForm.nickname"
            placeholder="暱稱 (可選)"
            :prefix-icon="Avatar"
            clearable
          />
        </el-form-item>

        <el-form-item prop="phone">
          <el-input
            v-model="registerForm.phone"
            placeholder="手機號碼 (可選)"
            :prefix-icon="Phone"
            clearable
          />
        </el-form-item>

        <el-form-item prop="password">
          <el-input
            v-model="registerForm.password"
            type="password"
            placeholder="密碼 (至少8個字符)"
            :prefix-icon="Lock"
            show-password
            clearable
          />
        </el-form-item>

        <el-form-item prop="password_confirmation">
          <el-input
            v-model="registerForm.password_confirmation"
            type="password"
            placeholder="確認密碼"
            :prefix-icon="Lock"
            show-password
            clearable
          />
        </el-form-item>

        <!-- Password Strength Indicator -->
        <div v-if="registerForm.password" class="password-strength">
          <div class="strength-label">密碼強度：</div>
          <div class="strength-bar">
            <div 
              class="strength-fill" 
              :class="passwordStrengthClass"
              :style="{ width: passwordStrengthWidth }"
            ></div>
          </div>
          <div class="strength-text" :class="passwordStrengthClass">
            {{ passwordStrengthText }}
          </div>
        </div>

        <el-form-item prop="terms_accepted">
          <el-checkbox v-model="registerForm.terms_accepted">
            我已閱讀並同意
            <el-link type="primary" @click="showTerms">
              服務條款
            </el-link>
            和
            <el-link type="primary" @click="showPrivacy">
              隱私政策
            </el-link>
          </el-checkbox>
        </el-form-item>

        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="authStore.isLoading"
            :disabled="!isFormValid"
            class="register-button"
            @click="handleRegister"
          >
            <template v-if="!authStore.isLoading">
              <el-icon><CirclePlus /></el-icon>
              註冊帳號
            </template>
            <template v-else>
              註冊中...
            </template>
          </el-button>
        </el-form-item>

        <div class="form-footer">
          <el-divider>或</el-divider>
          <p>
            已有帳號？
            <el-link type="primary" @click="goToLogin">
              立即登入
            </el-link>
          </p>
        </div>
      </el-form>
    </div>

    <!-- Terms of Service Dialog -->
    <el-dialog
      v-model="showTermsDialog"
      title="服務條款"
      width="80%"
      max-width="600px"
      center
    >
      <div class="terms-content">
        <h3>1. 服務內容</h3>
        <p>本平台提供遊戲伺服器推廣管理服務，包括但不限於推廣連結生成、獎勵管理、活動系統等功能。</p>
        
        <h3>2. 用戶責任</h3>
        <p>用戶應遵守平台規則，不得進行虛假推廣、作弊行為或任何違法活動。</p>
        
        <h3>3. 服務條款修改</h3>
        <p>平台保留修改服務條款的權利，修改後將在平台公告。</p>
        
        <h3>4. 免責聲明</h3>
        <p>平台對因使用服務而產生的任何損失不承擔責任。</p>
      </div>
      
      <template #footer>
        <el-button @click="showTermsDialog = false">
          關閉
        </el-button>
      </template>
    </el-dialog>

    <!-- Privacy Policy Dialog -->
    <el-dialog
      v-model="showPrivacyDialog"
      title="隱私政策"
      width="80%"
      max-width="600px"
      center
    >
      <div class="privacy-content">
        <h3>1. 資訊收集</h3>
        <p>我們收集您提供的基本資訊，包括用戶名、電子郵件地址等，用於帳號管理和服務提供。</p>
        
        <h3>2. 資訊使用</h3>
        <p>收集的資訊僅用於平台功能運作，不會用於其他商業用途。</p>
        
        <h3>3. 資訊保護</h3>
        <p>我們採用適當的技術和管理措施保護您的個人資訊安全。</p>
        
        <h3>4. 資訊共享</h3>
        <p>除法律要求外，我們不會與第三方共享您的個人資訊。</p>
      </div>
      
      <template #footer>
        <el-button @click="showPrivacyDialog = false">
          關閉
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { 
  User, 
  Message, 
  Avatar, 
  Phone, 
  Lock, 
  CirclePlus 
} from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import type { RegisterData } from '~/types/auth'

// Page meta
definePageMeta({
  layout: 'auth',
  middleware: 'guest'
})

// Auth store
const authStore = useAuthStore()

// Form refs
const registerFormRef = ref<FormInstance>()

// Form data
const registerForm = reactive<RegisterData>({
  username: '',
  email: '',
  nickname: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms_accepted: false
})

// Dialog states
const showTermsDialog = ref(false)
const showPrivacyDialog = ref(false)

// Password validation
const validatePassword = (rule: any, value: string, callback: any) => {
  if (!value) {
    callback(new Error('請輸入密碼'))
  } else if (value.length < 8) {
    callback(new Error('密碼至少需要8個字符'))
  } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
    callback(new Error('密碼需包含大小寫字母和數字'))
  } else {
    callback()
  }
}

const validatePasswordConfirmation = (rule: any, value: string, callback: any) => {
  if (!value) {
    callback(new Error('請確認密碼'))
  } else if (value !== registerForm.password) {
    callback(new Error('兩次輸入的密碼不一致'))
  } else {
    callback()
  }
}

const validateUsername = async (rule: any, value: string, callback: any) => {
  if (!value) {
    callback(new Error('請輸入用戶名'))
  } else if (value.length < 3 || value.length > 20) {
    callback(new Error('用戶名長度需在3-20個字符之間'))
  } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
    callback(new Error('用戶名只能包含字母、數字和下劃線'))
  } else {
    callback()
  }
}

// Form validation rules
const registerRules: FormRules = {
  username: [
    { validator: validateUsername, trigger: 'blur' }
  ],
  email: [
    { required: true, message: '請輸入電子郵件地址', trigger: 'blur' },
    { type: 'email', message: '請輸入有效的電子郵件地址', trigger: 'blur' }
  ],
  password: [
    { validator: validatePassword, trigger: 'blur' }
  ],
  password_confirmation: [
    { validator: validatePasswordConfirmation, trigger: 'blur' }
  ],
  terms_accepted: [
    { 
      validator: (rule: any, value: boolean, callback: any) => {
        if (!value) {
          callback(new Error('請同意服務條款和隱私政策'))
        } else {
          callback()
        }
      }, 
      trigger: 'change' 
    }
  ]
}

// Password strength calculation
const passwordStrength = computed(() => {
  const password = registerForm.password
  if (!password) return 0
  
  let score = 0
  
  // Length
  if (password.length >= 8) score += 1
  if (password.length >= 12) score += 1
  
  // Character types
  if (/[a-z]/.test(password)) score += 1
  if (/[A-Z]/.test(password)) score += 1
  if (/\d/.test(password)) score += 1
  if (/[^a-zA-Z\d]/.test(password)) score += 1
  
  return Math.min(score, 4)
})

const passwordStrengthClass = computed(() => {
  const strength = passwordStrength.value
  if (strength <= 1) return 'weak'
  if (strength <= 2) return 'fair'
  if (strength <= 3) return 'good'
  return 'strong'
})

const passwordStrengthWidth = computed(() => {
  return `${(passwordStrength.value / 4) * 100}%`
})

const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value
  if (strength <= 1) return '弱'
  if (strength <= 2) return '一般'
  if (strength <= 3) return '良好'
  return '強'
})

// Computed
const isFormValid = computed(() => {
  return registerForm.username.length >= 3 &&
         registerForm.email.includes('@') &&
         registerForm.password.length >= 8 &&
         registerForm.password === registerForm.password_confirmation &&
         registerForm.terms_accepted
})

// Methods
const handleRegister = async () => {
  if (!registerFormRef.value) return
  
  try {
    await registerFormRef.value.validate()
    await authStore.register(registerForm)
    
    ElMessage.success('註冊成功！歡迎加入我們的平台')
  } catch (error) {
    if (error instanceof Error) {
      ElMessage.error(error.message)
    } else {
      ElMessage.error('註冊失敗，請稍後再試')
    }
  }
}

const goToLogin = async () => {
  await navigateTo('/login')
}

const showTerms = () => {
  showTermsDialog.value = true
}

const showPrivacy = () => {
  showPrivacyDialog.value = true
}

const checkUsernameAvailability = async () => {
  const username = registerForm.username
  if (username.length < 3) return
  
  // TODO: Implement username availability check
  // This would be an API call to check if username is available
}

const checkEmailAvailability = async () => {
  const email = registerForm.email
  if (!email.includes('@')) return
  
  // TODO: Implement email availability check
  // This would be an API call to check if email is available
}

// Auto-focus on mount
onMounted(() => {
  nextTick(() => {
    if (registerFormRef.value) {
      const firstInput = registerFormRef.value.$el.querySelector('input')
      firstInput?.focus()
    }
  })
})
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 0;
}

.register-form-container {
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

.password-strength {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  font-size: 12px;
}

.strength-label {
  color: var(--el-text-color-secondary);
  white-space: nowrap;
}

.strength-bar {
  flex: 1;
  height: 4px;
  background: var(--el-border-color-lighter);
  border-radius: 2px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 2px;
}

.strength-fill.weak {
  background: var(--el-color-danger);
}

.strength-fill.fair {
  background: var(--el-color-warning);
}

.strength-fill.good {
  background: var(--el-color-primary);
}

.strength-fill.strong {
  background: var(--el-color-success);
}

.strength-text {
  white-space: nowrap;
  font-weight: 500;
}

.strength-text.weak {
  color: var(--el-color-danger);
}

.strength-text.fair {
  color: var(--el-color-warning);
}

.strength-text.good {
  color: var(--el-color-primary);
}

.strength-text.strong {
  color: var(--el-color-success);
}

.register-button {
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

.terms-content,
.privacy-content {
  max-height: 400px;
  overflow-y: auto;
  padding-right: 8px;
}

.terms-content h3,
.privacy-content h3 {
  color: var(--el-text-color-primary);
  font-size: 16px;
  margin: 16px 0 8px;
}

.terms-content p,
.privacy-content p {
  color: var(--el-text-color-secondary);
  line-height: 1.6;
  margin: 0 0 12px;
}

/* Form styling improvements */
:deep(.el-form-item) {
  margin-bottom: 20px;
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

:deep(.el-checkbox) {
  line-height: 1.5;
}

:deep(.el-checkbox__label) {
  font-size: 14px;
  line-height: 1.5;
}

/* Mobile responsiveness */
@media (max-width: 480px) {
  .register-form-container {
    padding: 0 16px;
  }
  
  .form-header h2 {
    font-size: 24px;
  }
  
  .password-strength {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .strength-bar {
    width: 100%;
  }
}
</style>