<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-header">
        <h1>建立新帳號</h1>
        <p>加入我們的遊戲推廣平台，開始您的推廣之旅</p>
      </div>

      <MultiStepForm
        v-model="registerForm"
        :steps="registrationSteps"
        :loading="authStore.isLoading"
        submit-text="完成註冊"
        @submit="handleSubmit"
        @step-change="handleStepChange"
        @validate="handleStepValidation"
      >
        <!-- Step 1: Basic Information -->
        <template #step-0="{ formData }">
          <div class="step-header">
            <h3>基本資訊</h3>
            <p>請填寫您的基本帳號資訊</p>
          </div>

          <el-form-item label="用戶名" prop="username">
            <el-input
              v-model="formData.username"
              placeholder="請輸入用戶名 (3-20個字符)"
              :prefix-icon="User"
              clearable
              @blur="checkUsernameAvailability"
            >
              <template #suffix>
                <el-icon v-if="usernameChecking" class="is-loading">
                  <Loading />
                </el-icon>
                <el-icon v-else-if="usernameAvailable === true" style="color: var(--el-color-success)">
                  <Check />
                </el-icon>
                <el-icon v-else-if="usernameAvailable === false" style="color: var(--el-color-danger)">
                  <Close />
                </el-icon>
              </template>
            </el-input>
            <div v-if="usernameMessage" class="field-message" :class="usernameAvailable ? 'success' : 'error'">
              {{ usernameMessage }}
            </div>
          </el-form-item>

          <el-form-item label="電子郵件" prop="email">
            <el-input
              v-model="formData.email"
              placeholder="請輸入電子郵件地址"
              :prefix-icon="Message"
              clearable
              @blur="checkEmailAvailability"
            >
              <template #suffix>
                <el-icon v-if="emailChecking" class="is-loading">
                  <Loading />
                </el-icon>
                <el-icon v-else-if="emailAvailable === true" style="color: var(--el-color-success)">
                  <Check />
                </el-icon>
                <el-icon v-else-if="emailAvailable === false" style="color: var(--el-color-danger)">
                  <Close />
                </el-icon>
              </template>
            </el-input>
            <div v-if="emailMessage" class="field-message" :class="emailAvailable ? 'success' : 'error'">
              {{ emailMessage }}
            </div>
          </el-form-item>

          <el-form-item label="暱稱" prop="nickname">
            <el-input
              v-model="formData.nickname"
              placeholder="請輸入暱稱 (可選)"
              :prefix-icon="Avatar"
              clearable
            />
          </el-form-item>
        </template>

        <!-- Step 2: Password Setup -->
        <template #step-1="{ formData }">
          <div class="step-header">
            <h3>密碼設定</h3>
            <p>請設定一個安全的密碼</p>
          </div>

          <el-form-item label="密碼" prop="password">
            <el-input
              v-model="formData.password"
              type="password"
              placeholder="請輸入密碼 (至少8個字符)"
              :prefix-icon="Lock"
              show-password
              clearable
            />
          </el-form-item>

          <!-- Password Strength Indicator -->
          <div v-if="formData.password" class="password-strength">
            <div class="strength-label">密碼強度：</div>
            <div class="strength-bar">
              <div 
                class="strength-fill" 
                :class="passwordStrengthClass"
                :style="{ width: passwordStrengthWidth }"
              />
            </div>
            <div class="strength-text" :class="passwordStrengthClass">
              {{ passwordStrengthText }}
            </div>
          </div>

          <el-form-item label="確認密碼" prop="password_confirmation">
            <el-input
              v-model="formData.password_confirmation"
              type="password"
              placeholder="請再次輸入密碼"
              :prefix-icon="Lock"
              show-password
              clearable
            />
          </el-form-item>

          <!-- Password Requirements -->
          <div class="password-requirements">
            <div class="requirement-title">密碼要求：</div>
            <ul class="requirements-list">
              <li :class="{ valid: passwordChecks.length }">
                <el-icon><Check v-if="passwordChecks.length" /><Close v-else /></el-icon>
                至少8個字符
              </li>
              <li :class="{ valid: passwordChecks.lowercase }">
                <el-icon><Check v-if="passwordChecks.lowercase" /><Close v-else /></el-icon>
                包含小寫字母
              </li>
              <li :class="{ valid: passwordChecks.uppercase }">
                <el-icon><Check v-if="passwordChecks.uppercase" /><Close v-else /></el-icon>
                包含大寫字母
              </li>
              <li :class="{ valid: passwordChecks.number }">
                <el-icon><Check v-if="passwordChecks.number" /><Close v-else /></el-icon>
                包含數字
              </li>
            </ul>
          </div>
        </template>

        <!-- Step 3: Additional Information -->
        <template #step-2="{ formData }">
          <div class="step-header">
            <h3>補充資訊</h3>
            <p>請填寫其他資訊 (可選)</p>
          </div>

          <el-form-item label="手機號碼" prop="phone">
            <el-input
              v-model="formData.phone"
              placeholder="請輸入手機號碼 (可選)"
              :prefix-icon="Phone"
              clearable
            />
          </el-form-item>

          <el-form-item label="推薦人" prop="referrer_code">
            <el-input
              v-model="formData.referrer_code"
              placeholder="請輸入推薦人代碼 (可選)"
              :prefix-icon="Share"
              clearable
            />
          </el-form-item>

          <el-form-item label="註冊原因">
            <el-select
              v-model="formData.registration_reason"
              placeholder="請選擇註冊原因"
              style="width: 100%"
            >
              <el-option label="推廣遊戲伺服器" value="promote_server" />
              <el-option label="參與推廣活動" value="join_promotion" />
              <el-option label="管理伺服器" value="manage_server" />
              <el-option label="其他" value="other" />
            </el-select>
          </el-form-item>
        </template>

        <!-- Step 4: Terms and Confirmation -->
        <template #step-3="{ formData }">
          <div class="step-header">
            <h3>條款確認</h3>
            <p>請閱讀並同意相關條款</p>
          </div>

          <!-- Registration Summary -->
          <div class="registration-summary">
            <h4>註冊資訊確認</h4>
            <el-descriptions :column="1" border>
              <el-descriptions-item label="用戶名">{{ formData.username }}</el-descriptions-item>
              <el-descriptions-item label="電子郵件">{{ formData.email }}</el-descriptions-item>
              <el-descriptions-item label="暱稱">{{ formData.nickname || '未設定' }}</el-descriptions-item>
              <el-descriptions-item label="手機號碼">{{ formData.phone || '未設定' }}</el-descriptions-item>
            </el-descriptions>
          </div>

          <el-form-item prop="terms_accepted">
            <el-checkbox v-model="formData.terms_accepted">
              我已閱讀並同意
              <el-link type="primary" @click="showTerms">服務條款</el-link>
              和
              <el-link type="primary" @click="showPrivacy">隱私政策</el-link>
            </el-checkbox>
          </el-form-item>

          <el-form-item prop="newsletter_subscribed">
            <el-checkbox v-model="formData.newsletter_subscribed">
              訂閱電子報以獲取最新活動資訊
            </el-checkbox>
          </el-form-item>
        </template>
      </MultiStepForm>

      <div class="register-footer">
        <el-divider>或</el-divider>
        <p>
          已有帳號？
          <el-link type="primary" @click="goToLogin">
            立即登入
          </el-link>
        </p>
      </div>
    </div>

    <!-- Terms Dialog -->
    <el-dialog v-model="showTermsDialog" title="服務條款" width="80%" max-width="600px">
      <div class="terms-content">
        <!-- Terms content here -->
        <p>服務條款內容...</p>
      </div>
    </el-dialog>

    <!-- Privacy Dialog -->
    <el-dialog v-model="showPrivacyDialog" title="隱私政策" width="80%" max-width="600px">
      <div class="privacy-content">
        <!-- Privacy content here -->
        <p>隱私政策內容...</p>
      </div>
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
  Share,
  Check,
  Close,
  Loading
} from '@element-plus/icons-vue'
import type { FormRules } from 'element-plus'
import type { RegisterData } from '~/types/auth'

// Page meta
definePageMeta({
  layout: 'auth',
  middleware: 'guest'
})

const authStore = useAuthStore()

// Form data
const registerForm = ref<RegisterData & {
  referrer_code?: string
  registration_reason?: string
  newsletter_subscribed?: boolean
}>({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  nickname: '',
  phone: '',
  terms_accepted: false,
  referrer_code: '',
  registration_reason: '',
  newsletter_subscribed: false
})

// Dialog states
const showTermsDialog = ref(false)
const showPrivacyDialog = ref(false)

// Availability checking states
const usernameChecking = ref(false)
const emailChecking = ref(false)
const usernameAvailable = ref<boolean | null>(null)
const emailAvailable = ref<boolean | null>(null)
const usernameMessage = ref('')
const emailMessage = ref('')

// Registration steps configuration
const registrationSteps = computed(() => [
  {
    title: '基本資訊',
    description: '帳號基本資料',
    icon: 'User',
    rules: {
      username: [
        { required: true, message: '請輸入用戶名', trigger: 'blur' },
        { min: 3, max: 20, message: '用戶名長度需在3-20個字符之間', trigger: 'blur' },
        { 
          pattern: /^[a-zA-Z0-9_]+$/, 
          message: '用戶名只能包含字母、數字和下劃線', 
          trigger: 'blur' 
        }
      ],
      email: [
        { required: true, message: '請輸入電子郵件地址', trigger: 'blur' },
        { type: 'email', message: '請輸入有效的電子郵件地址', trigger: 'blur' }
      ]
    } as FormRules,
    validate: async () => {
      return usernameAvailable.value === true && emailAvailable.value === true
    }
  },
  {
    title: '密碼設定',
    description: '安全密碼',
    icon: 'Lock',
    rules: {
      password: [
        { required: true, message: '請輸入密碼', trigger: 'blur' },
        { min: 8, message: '密碼至少需要8個字符', trigger: 'blur' },
        { 
          validator: validatePassword, 
          trigger: 'blur' 
        }
      ],
      password_confirmation: [
        { required: true, message: '請確認密碼', trigger: 'blur' },
        { 
          validator: validatePasswordConfirmation, 
          trigger: 'blur' 
        }
      ]
    } as FormRules,
    validate: () => {
      return passwordStrength.value >= 3 && 
             registerForm.value.password === registerForm.value.password_confirmation
    }
  },
  {
    title: '補充資訊',
    description: '其他資料',
    icon: 'Info'
  },
  {
    title: '確認註冊',
    description: '條款同意',
    icon: 'Check',
    rules: {
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
    } as FormRules
  }
])

// Password strength calculation
const passwordStrength = computed(() => {
  const password = registerForm.value.password
  if (!password) return 0
  
  let score = 0
  
  if (password.length >= 8) score += 1
  if (password.length >= 12) score += 1
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

const passwordChecks = computed(() => ({
  length: registerForm.value.password.length >= 8,
  lowercase: /[a-z]/.test(registerForm.value.password),
  uppercase: /[A-Z]/.test(registerForm.value.password),
  number: /\d/.test(registerForm.value.password)
}))

// Validation functions
function validatePassword(rule: any, value: string, callback: any) {
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

function validatePasswordConfirmation(rule: any, value: string, callback: any) {
  if (!value) {
    callback(new Error('請確認密碼'))
  } else if (value !== registerForm.value.password) {
    callback(new Error('兩次輸入的密碼不一致'))
  } else {
    callback()
  }
}

// Methods
const checkUsernameAvailability = async () => {
  const username = registerForm.value.username
  if (username.length < 3) return
  
  usernameChecking.value = true
  usernameAvailable.value = null
  usernameMessage.value = ''
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Mock availability check
    const unavailableUsernames = ['admin', 'test', 'user']
    const isAvailable = !unavailableUsernames.includes(username.toLowerCase())
    
    usernameAvailable.value = isAvailable
    usernameMessage.value = isAvailable ? '用戶名可以使用' : '用戶名已被使用'
  } catch (error) {
    usernameMessage.value = '檢查用戶名時發生錯誤'
  } finally {
    usernameChecking.value = false
  }
}

const checkEmailAvailability = async () => {
  const email = registerForm.value.email
  if (!email.includes('@')) return
  
  emailChecking.value = true
  emailAvailable.value = null
  emailMessage.value = ''
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Mock availability check
    const unavailableEmails = ['admin@test.com', 'test@test.com']
    const isAvailable = !unavailableEmails.includes(email.toLowerCase())
    
    emailAvailable.value = isAvailable
    emailMessage.value = isAvailable ? '電子郵件可以使用' : '電子郵件已被註冊'
  } catch (error) {
    emailMessage.value = '檢查電子郵件時發生錯誤'
  } finally {
    emailChecking.value = false
  }
}

const handleSubmit = async (formData: any) => {
  try {
    await authStore.register(formData)
    ElMessage.success('註冊成功！歡迎加入我們的平台')
  } catch (error) {
    if (error instanceof Error) {
      ElMessage.error(error.message)
    } else {
      ElMessage.error('註冊失敗，請稍後再試')
    }
  }
}

const handleStepChange = (step: number) => {
  console.log('Step changed to:', step)
}

const handleStepValidation = (step: number, isValid: boolean) => {
  console.log(`Step ${step} validation:`, isValid)
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
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, var(--el-color-primary-light-9), var(--el-bg-color-page));
}

.register-container {
  width: 100%;
  max-width: 900px;
  background: var(--el-bg-color);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.register-header {
  text-align: center;
  padding: 40px 40px 20px;
  background: linear-gradient(135deg, var(--el-color-primary), var(--el-color-primary-light-3));
  color: white;
}

.register-header h1 {
  margin: 0 0 12px;
  font-size: 32px;
  font-weight: 700;
}

.register-header p {
  margin: 0;
  font-size: 16px;
  opacity: 0.9;
}

.step-header {
  text-align: center;
  margin-bottom: 30px;
}

.step-header h3 {
  margin: 0 0 8px;
  font-size: 24px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.step-header p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

.field-message {
  font-size: 12px;
  margin-top: 4px;
  padding-left: 4px;
}

.field-message.success {
  color: var(--el-color-success);
}

.field-message.error {
  color: var(--el-color-danger);
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

.strength-fill.weak { background: var(--el-color-danger); }
.strength-fill.fair { background: var(--el-color-warning); }
.strength-fill.good { background: var(--el-color-primary); }
.strength-fill.strong { background: var(--el-color-success); }

.strength-text {
  white-space: nowrap;
  font-weight: 500;
}

.strength-text.weak { color: var(--el-color-danger); }
.strength-text.fair { color: var(--el-color-warning); }
.strength-text.good { color: var(--el-color-primary); }
.strength-text.strong { color: var(--el-color-success); }

.password-requirements {
  margin-top: 16px;
  padding: 16px;
  background: var(--el-bg-color-page);
  border-radius: 8px;
}

.requirement-title {
  font-size: 14px;
  font-weight: 500;
  color: var(--el-text-color-primary);
  margin-bottom: 8px;
}

.requirements-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.requirements-list li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
  margin: 4px 0;
}

.requirements-list li.valid {
  color: var(--el-color-success);
}

.requirements-list li .el-icon {
  font-size: 14px;
}

.registration-summary {
  margin-bottom: 24px;
}

.registration-summary h4 {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.register-footer {
  text-align: center;
  padding: 20px 40px 40px;
}

.register-footer p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 14px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .register-page {
    padding: 20px 16px;
  }
  
  .register-container {
    border-radius: 8px;
  }
  
  .register-header {
    padding: 30px 20px 20px;
  }
  
  .register-header h1 {
    font-size: 28px;
  }
  
  .register-footer {
    padding: 20px;
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

/* Loading animation */
.is-loading {
  animation: loading 1s infinite linear;
}

@keyframes loading {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>