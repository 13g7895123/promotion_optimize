<template>
  <div class="promotion-tools-page">
    <!-- 頁面標題 -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">推廣工具</h1>
        <p class="page-description">
          建立推廣活動，生成推廣連結和素材，開始您的推廣之旅
        </p>
      </div>
    </div>

    <!-- 步驟導航 -->
    <div class="steps-container">
      <el-steps 
        :active="currentStep" 
        :process-status="stepStatus"
        align-center
        finish-status="success"
      >
        <el-step 
          title="選擇伺服器" 
          description="選擇要推廣的遊戲伺服器"
          :icon="Monitor"
        />
        <el-step 
          title="設定內容" 
          description="配置推廣活動的詳細資訊"
          :icon="Edit"
        />
        <el-step 
          title="生成素材" 
          description="生成推廣連結、QR碼和宣傳素材"
          :icon="Picture"
        />
        <el-step 
          title="發布推廣" 
          description="完成設定並開始推廣活動"
          :icon="Share"
        />
      </el-steps>
    </div>

    <!-- 主要內容區域 -->
    <div class="main-content">
      <!-- 步驟 1: 選擇伺服器 -->
      <div v-if="currentStep === 0" class="step-content">
        <div class="step-header">
          <h2>選擇要推廣的伺服器</h2>
          <p>請選擇您要建立推廣活動的遊戲伺服器</p>
        </div>
        
        <ServerSelector
          v-model="promotionForm.server_id"
          mode="card"
          @change="handleServerChange"
          @create-server="handleCreateServer"
        />

        <div v-if="selectedServer" class="server-preview">
          <h3>已選擇伺服器</h3>
          <div class="selected-server-info">
            <div class="server-avatar">
              <el-image
                :src="selectedServer.image_url || '/images/default-server.png'"
                :alt="selectedServer.name"
                fit="cover"
              >
                <template #error>
                  <div class="image-error">
                    <el-icon><Picture /></el-icon>
                  </div>
                </template>
              </el-image>
            </div>
            <div class="server-details">
              <h4>{{ selectedServer.name }}</h4>
              <p>{{ selectedServer.code }}</p>
              <el-tag :type="getGameTypeColor(selectedServer.game_type)">
                {{ selectedServer.game_type }}
              </el-tag>
            </div>
          </div>
        </div>
      </div>

      <!-- 步驟 2: 設定內容 -->
      <div v-if="currentStep === 1" class="step-content">
        <div class="step-header">
          <h2>設定推廣內容</h2>
          <p>配置您的推廣活動詳細資訊</p>
        </div>

        <el-form
          ref="promotionFormRef"
          :model="promotionForm"
          :rules="promotionFormRules"
          label-position="top"
          class="promotion-form"
        >
          <el-row :gutter="24">
            <el-col :span="24">
              <el-form-item label="推廣標題" prop="title">
                <el-input
                  v-model="promotionForm.title"
                  placeholder="請輸入吸引人的推廣標題"
                  maxlength="100"
                  show-word-limit
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="24">
            <el-col :span="24">
              <el-form-item label="推廣描述" prop="description">
                <el-input
                  v-model="promotionForm.description"
                  type="textarea"
                  :rows="4"
                  placeholder="請描述推廣活動的詳細內容，讓玩家了解活動亮點"
                  maxlength="500"
                  show-word-limit
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="24">
            <el-col :span="12">
              <el-form-item label="獎勵類型" prop="reward_type">
                <el-select v-model="promotionForm.reward_type" placeholder="請選擇獎勵類型">
                  <el-option label="積分" value="points">
                    <div class="reward-option">
                      <el-icon><Star /></el-icon>
                      <span>積分</span>
                    </div>
                  </el-option>
                  <el-option label="道具" value="items">
                    <div class="reward-option">
                      <el-icon><Present /></el-icon>
                      <span>道具</span>
                    </div>
                  </el-option>
                  <el-option label="經驗" value="experience">
                    <div class="reward-option">
                      <el-icon><Trophy /></el-icon>
                      <span>經驗</span>
                    </div>
                  </el-option>
                  <el-option label="貨幣" value="currency">
                    <div class="reward-option">
                      <el-icon><Coin /></el-icon>
                      <span>貨幣</span>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="獎勵數量" prop="reward_value">
                <el-input-number
                  v-model="promotionForm.reward_value"
                  :min="1"
                  :max="999999"
                  placeholder="請輸入獎勵數量"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="24">
            <el-col :span="24">
              <el-form-item label="獎勵說明" prop="reward_description">
                <el-input
                  v-model="promotionForm.reward_description"
                  placeholder="請詳細說明獎勵內容，如道具名稱、使用方式等"
                  maxlength="200"
                  show-word-limit
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="24">
            <el-col :span="12">
              <el-form-item label="最大使用次數" prop="max_uses">
                <el-input-number
                  v-model="promotionForm.max_uses"
                  :min="1"
                  :max="99999"
                  placeholder="限制推廣次數"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="活動期間" prop="date_range">
                <el-date-picker
                  v-model="dateRange"
                  type="datetimerange"
                  range-separator="至"
                  start-placeholder="開始時間"
                  end-placeholder="結束時間"
                  format="YYYY-MM-DD HH:mm"
                  value-format="YYYY-MM-DD HH:mm:ss"
                  style="width: 100%"
                  @change="handleDateRangeChange"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <!-- 自訂圖片上傳 -->
          <el-row :gutter="24">
            <el-col :span="24">
              <el-form-item label="自訂推廣圖片 (可選)">
                <el-upload
                  ref="uploadRef"
                  :show-file-list="false"
                  :before-upload="handleImageUpload"
                  accept="image/*"
                  :disabled="imageUploading"
                  class="image-uploader"
                >
                  <div v-if="promotionForm.custom_image" class="uploaded-image">
                    <img :src="imagePreviewUrl" alt="推廣圖片預覽" />
                    <div class="image-overlay">
                      <el-button 
                        type="danger" 
                        :icon="Delete"
                        circle
                        size="small"
                        @click.stop="removeImage"
                      />
                    </div>
                  </div>
                  <div v-else class="upload-placeholder" :class="{ 'is-uploading': imageUploading }">
                    <el-icon v-if="!imageUploading" class="upload-icon"><Plus /></el-icon>
                    <el-icon v-else class="upload-icon"><Loading /></el-icon>
                    <div class="upload-text">
                      {{ imageUploading ? '上傳中...' : '點擊上傳推廣圖片' }}
                    </div>
                    <div class="upload-tip">支援 JPG、PNG 格式，建議尺寸 800x400px</div>
                  </div>
                </el-upload>
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>

        <!-- 預覽區域 -->
        <div class="content-preview">
          <h3>推廣內容預覽</h3>
          <div class="preview-card">
            <div class="preview-header">
              <h4>{{ promotionForm.title || '推廣標題' }}</h4>
              <el-tag v-if="promotionForm.reward_type" :type="getRewardTypeColor(promotionForm.reward_type)">
                {{ getRewardTypeText(promotionForm.reward_type) }}
              </el-tag>
            </div>
            <p class="preview-description">
              {{ promotionForm.description || '推廣描述內容會顯示在這裡...' }}
            </p>
            <div class="preview-reward">
              <el-icon><component :is="getRewardIcon(promotionForm.reward_type)" /></el-icon>
              <span>獎勵：{{ formatRewardText() }}</span>
            </div>
            <div v-if="promotionForm.reward_description" class="preview-reward-desc">
              {{ promotionForm.reward_description }}
            </div>
            <div class="preview-meta">
              <div class="meta-item">
                <el-icon><User /></el-icon>
                <span>限額：{{ promotionForm.max_uses || 0 }} 名</span>
              </div>
              <div v-if="dateRange && dateRange.length === 2" class="meta-item">
                <el-icon><Calendar /></el-icon>
                <span>{{ formatDateRange() }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 步驟 3: 生成素材 -->
      <div v-if="currentStep === 2" class="step-content">
        <div class="step-header">
          <h2>生成推廣素材</h2>
          <p>生成推廣連結、QR碼和宣傳圖片</p>
        </div>

        <div class="materials-container">
          <!-- 推廣連結 -->
          <div class="material-section">
            <h3>推廣連結</h3>
            <div class="link-generator">
              <el-input 
                :value="generatedLink" 
                readonly 
                placeholder="推廣連結將在此顯示"
              >
                <template #append>
                  <el-button 
                    @click="copyLink"
                    :loading="copyLinkLoading"
                  >
                    複製
                  </el-button>
                </template>
              </el-input>
            </div>
          </div>

          <!-- QR Code 生成器 -->
          <div class="material-section">
            <h3>QR Code</h3>
            <QRCodeGenerator 
              :url="generatedLink || 'https://example.com'"
              @generated="handleQRGenerated"
              @error="handleQRError"
            />
          </div>

          <!-- 宣傳文案 -->
          <div class="material-section">
            <h3>宣傳文案</h3>
            <div class="promotion-text">
              <el-input
                v-model="generatedText"
                type="textarea"
                :rows="6"
                placeholder="宣傳文案將自動生成"
                readonly
              >
                <template #append>
                  <el-button @click="copyText" :loading="copyTextLoading">
                    複製文案
                  </el-button>
                </template>
              </el-input>
            </div>
          </div>

          <!-- 社群分享按鈕 -->
          <div class="material-section">
            <h3>快速分享</h3>
            <div class="share-buttons">
              <el-button 
                type="primary" 
                :icon="Share"
                @click="shareToSocial('facebook')"
              >
                分享到 Facebook
              </el-button>
              <el-button 
                type="success" 
                :icon="Share"
                @click="shareToSocial('line')"
              >
                分享到 LINE
              </el-button>
              <el-button 
                type="info" 
                :icon="Share"
                @click="shareToSocial('discord')"
              >
                分享到 Discord
              </el-button>
            </div>
          </div>
        </div>
      </div>

      <!-- 步驟 4: 發布推廣 -->
      <div v-if="currentStep === 3" class="step-content">
        <div class="step-header">
          <h2>發布推廣活動</h2>
          <p>確認所有設定並發布您的推廣活動</p>
        </div>

        <div class="publish-summary">
          <el-card class="summary-card">
            <template #header>
              <div class="card-header">
                <span>推廣活動摘要</span>
                <el-button type="text" size="small" @click="currentStep = 1">
                  編輯
                </el-button>
              </div>
            </template>
            
            <div class="summary-content">
              <div class="summary-row">
                <span class="summary-label">伺服器：</span>
                <span class="summary-value">{{ selectedServer?.name }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">標題：</span>
                <span class="summary-value">{{ promotionForm.title }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">獎勵：</span>
                <span class="summary-value">{{ formatRewardText() }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">限額：</span>
                <span class="summary-value">{{ promotionForm.max_uses }} 名</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">期間：</span>
                <span class="summary-value">{{ formatDateRange() }}</span>
              </div>
            </div>
          </el-card>

          <div class="publish-actions">
            <el-button 
              type="primary" 
              size="large"
              :loading="publishing"
              @click="publishPromotion"
            >
              <el-icon><Upload /></el-icon>
              發布推廣活動
            </el-button>
            <el-button size="large" @click="saveDraft">
              <el-icon><Document /></el-icon>
              儲存為草稿
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部操作按鈕 -->
    <div class="step-actions">
      <el-button 
        v-if="currentStep > 0" 
        @click="previousStep"
        :disabled="publishing"
      >
        上一步
      </el-button>
      <el-button 
        v-if="currentStep < 3" 
        type="primary" 
        @click="nextStep"
        :disabled="!canProceedToNext"
        :loading="loading"
      >
        下一步
      </el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Monitor,
  Edit,
  Picture,
  Share,
  Star,
  Present,
  Trophy,
  Coin,
  Plus,
  Loading,
  Delete,
  User,
  Calendar,
  Upload,
  Document
} from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import type { Server, PromotionForm } from '~/types'

// 頁面元數據
definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

// 使用 stores
const serverStore = useServerStore()
const promotionStore = usePromotionStore()

// 響應式數據
const currentStep = ref(0)
const stepStatus = ref<'wait' | 'process' | 'finish' | 'error' | 'success'>('process')
const loading = ref(false)
const publishing = ref(false)

// 表單相關
const promotionFormRef = ref<FormInstance>()
const promotionForm = ref<PromotionForm>({
  server_id: 0,
  title: '',
  description: '',
  reward_type: 'points',
  reward_value: 100,
  reward_description: '',
  max_uses: 100,
  start_date: '',
  end_date: '',
})

const dateRange = ref<[string, string] | null>(null)

// 圖片上傳相關
const imageUploading = ref(false)
const imagePreviewUrl = ref('')

// 素材生成相關
const generatedLink = ref('')
const generatedQRCode = ref('')
const generatedText = ref('')
const copyLinkLoading = ref(false)
const copyTextLoading = ref(false)

// 計算屬性
const selectedServer = computed(() => {
  return serverStore.myServers.find(s => s.id === promotionForm.value.server_id) || null
})

const canProceedToNext = computed(() => {
  switch (currentStep.value) {
    case 0:
      return promotionForm.value.server_id > 0
    case 1:
      return promotionForm.value.title && 
             promotionForm.value.description && 
             promotionForm.value.reward_value > 0 &&
             promotionForm.value.max_uses > 0 &&
             dateRange.value && dateRange.value.length === 2
    case 2:
      return generatedLink.value !== ''
    default:
      return false
  }
})

// 表單驗證規則
const promotionFormRules: FormRules = {
  title: [
    { required: true, message: '請輸入推廣標題', trigger: 'blur' },
    { min: 5, max: 100, message: '標題長度在 5 到 100 個字符', trigger: 'blur' }
  ],
  description: [
    { required: true, message: '請輸入推廣描述', trigger: 'blur' },
    { min: 10, max: 500, message: '描述長度在 10 到 500 個字符', trigger: 'blur' }
  ],
  reward_type: [
    { required: true, message: '請選擇獎勵類型', trigger: 'change' }
  ],
  reward_value: [
    { required: true, message: '請輸入獎勵數量', trigger: 'blur' },
    { type: 'number', min: 1, message: '獎勵數量必須大於 0', trigger: 'blur' }
  ],
  max_uses: [
    { required: true, message: '請設定最大使用次數', trigger: 'blur' },
    { type: 'number', min: 1, message: '使用次數必須大於 0', trigger: 'blur' }
  ],
  date_range: [
    { required: true, message: '請選擇活動期間', trigger: 'change' }
  ]
}

// 方法
const handleServerChange = (server: Server | null) => {
  if (server) {
    promotionForm.value.server_id = server.id
  }
}

const handleCreateServer = () => {
  // 導航到伺服器建立頁面
  navigateTo('/servers/create')
}

const handleDateRangeChange = (dates: [string, string] | null) => {
  if (dates) {
    promotionForm.value.start_date = dates[0]
    promotionForm.value.end_date = dates[1]
  }
}

const handleImageUpload = (file: File): boolean => {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上傳圖片文件')
    return false
  }

  const isLt5M = file.size / 1024 / 1024 < 5
  if (!isLt5M) {
    ElMessage.error('圖片大小不能超過 5MB')
    return false
  }

  imageUploading.value = true
  
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreviewUrl.value = e.target?.result as string
    promotionForm.value.custom_image = file
    imageUploading.value = false
  }
  reader.onerror = () => {
    ElMessage.error('圖片讀取失敗')
    imageUploading.value = false
  }
  reader.readAsDataURL(file)

  return false
}

const removeImage = () => {
  promotionForm.value.custom_image = undefined
  imagePreviewUrl.value = ''
}

const nextStep = async () => {
  if (currentStep.value === 1) {
    // 驗證表單
    if (!promotionFormRef.value) return
    
    try {
      await promotionFormRef.value.validate()
    } catch (error) {
      ElMessage.error('請完善表單資訊')
      return
    }
  }

  if (currentStep.value === 2) {
    // 生成素材
    await generateMaterials()
  }

  if (currentStep.value < 3) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const generateMaterials = async () => {
  loading.value = true
  
  try {
    // 生成推廣連結 (模擬)
    const baseUrl = window.location.origin
    const promotionId = `temp_${Date.now()}`
    generatedLink.value = `${baseUrl}/promo/${promotionId}`
    
    // 生成宣傳文案
    generatedText.value = generatePromotionText()
    
    ElMessage.success('推廣素材生成成功')
  } catch (error: any) {
    ElMessage.error('素材生成失敗：' + error.message)
  } finally {
    loading.value = false
  }
}

const generatePromotionText = (): string => {
  const server = selectedServer.value
  const form = promotionForm.value
  
  return `🎉 ${server?.name} 推廣活動開始啦！

📋 活動標題：${form.title}
🎁 豐厚獎勵：${formatRewardText()}
👥 限額：僅限 ${form.max_uses} 名
⏰ 活動期間：${formatDateRange()}

${form.description}

${form.reward_description ? `💎 獎勵說明：${form.reward_description}` : ''}

🔗 立即參與：${generatedLink.value}

#${server?.game_type} #遊戲推廣 #${server?.name.replace(/\s+/g, '')}`
}

const copyLink = async () => {
  if (!generatedLink.value) return
  
  copyLinkLoading.value = true
  try {
    await navigator.clipboard.writeText(generatedLink.value)
    ElMessage.success('推廣連結已複製')
  } catch (error) {
    ElMessage.error('複製失敗')
  } finally {
    copyLinkLoading.value = false
  }
}

const copyText = async () => {
  if (!generatedText.value) return
  
  copyTextLoading.value = true
  try {
    await navigator.clipboard.writeText(generatedText.value)
    ElMessage.success('宣傳文案已複製')
  } catch (error) {
    ElMessage.error('複製失敗')
  } finally {
    copyTextLoading.value = false
  }
}

const shareToSocial = (platform: string) => {
  const url = encodeURIComponent(generatedLink.value)
  const text = encodeURIComponent(promotionForm.value.title)
  
  let shareUrl = ''
  
  switch (platform) {
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`
      break
    case 'line':
      shareUrl = `https://social-plugins.line.me/lineit/share?url=${url}&text=${text}`
      break
    case 'discord':
      // Discord 沒有直接分享 URL，複製內容到剪貼簿
      navigator.clipboard.writeText(`${promotionForm.value.title}\n${generatedLink.value}`)
      ElMessage.success('內容已複製，請貼到 Discord 頻道中')
      return
  }
  
  if (shareUrl) {
    window.open(shareUrl, '_blank', 'width=550,height=420')
  }
}

const publishPromotion = async () => {
  publishing.value = true
  
  try {
    await promotionStore.createPromotion(promotionForm.value)
    
    ElMessage.success('推廣活動發布成功！')
    
    // 導航到推廣列表頁面
    await navigateTo('/promotion/records')
  } catch (error: any) {
    ElMessage.error('發布失敗：' + error.message)
  } finally {
    publishing.value = false
  }
}

const saveDraft = () => {
  // 儲存草稿到本地存儲
  localStorage.setItem('promotion_draft', JSON.stringify({
    ...promotionForm.value,
    dateRange: dateRange.value,
    imagePreviewUrl: imagePreviewUrl.value,
  }))
  
  ElMessage.success('草稿已儲存')
}

const loadDraft = () => {
  const draft = localStorage.getItem('promotion_draft')
  if (draft) {
    try {
      const data = JSON.parse(draft)
      Object.assign(promotionForm.value, data)
      dateRange.value = data.dateRange
      imagePreviewUrl.value = data.imagePreviewUrl || ''
    } catch (error) {
      console.error('載入草稿失敗:', error)
    }
  }
}

const handleQRGenerated = (qrUrl: string) => {
  generatedQRCode.value = qrUrl
}

const handleQRError = (error: string) => {
  ElMessage.error('QR Code 生成失敗：' + error)
}

// 工具方法
const getGameTypeColor = (gameType: string) => {
  const colorMap: Record<string, string> = {
    'minecraft': 'success',
    'terraria': 'warning',
    'csgo': 'danger',
    'gmod': 'info',
    'other': 'default',
  }
  return colorMap[gameType.toLowerCase()] || 'default'
}

const getRewardTypeColor = (type: string) => {
  const colorMap: Record<string, string> = {
    'points': 'primary',
    'items': 'success',
    'experience': 'warning',
    'currency': 'danger',
  }
  return colorMap[type] || 'default'
}

const getRewardTypeText = (type: string) => {
  const textMap: Record<string, string> = {
    'points': '積分',
    'items': '道具',
    'experience': '經驗',
    'currency': '貨幣',
  }
  return textMap[type] || type
}

const getRewardIcon = (type: string) => {
  const iconMap: Record<string, any> = {
    'points': Star,
    'items': Present,
    'experience': Trophy,
    'currency': Coin,
  }
  return iconMap[type] || Present
}

const formatRewardText = () => {
  const { reward_type, reward_value } = promotionForm.value
  if (!reward_type || !reward_value) return '未設定'
  
  switch (reward_type) {
    case 'points':
      return `${reward_value} 積分`
    case 'experience':
      return `${reward_value} 經驗值`
    case 'currency':
      return `$${reward_value}`
    case 'items':
      return `${reward_value} 個道具`
    default:
      return `${reward_value}`
  }
}

const formatDateRange = () => {
  if (!dateRange.value || dateRange.value.length !== 2) return '未設定'
  
  const start = new Date(dateRange.value[0]).toLocaleDateString('zh-TW')
  const end = new Date(dateRange.value[1]).toLocaleDateString('zh-TW')
  return `${start} ~ ${end}`
}

// 生命週期
onMounted(async () => {
  // 載入用戶伺服器列表
  if (serverStore.myServers.length === 0) {
    await serverStore.fetchMyServers()
  }
  
  // 載入草稿
  loadDraft()
})

// 監聽路由離開
onBeforeRouteLeave((to, from, next) => {
  if (promotionForm.value.title || promotionForm.value.description) {
    ElMessageBox.confirm(
      '您有未儲存的內容，確定要離開嗎？',
      '確認離開',
      {
        confirmButtonText: '儲存並離開',
        cancelButtonText: '直接離開',
        distinguishCancelAndClose: true,
        type: 'warning',
      }
    ).then(() => {
      saveDraft()
      next()
    }).catch((action) => {
      if (action === 'cancel') {
        next()
      } else {
        next(false)
      }
    })
  } else {
    next()
  }
})
</script>

<style scoped lang="scss">
.promotion-tools-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;

  .page-header {
    text-align: center;
    margin-bottom: 32px;

    .page-title {
      font-size: 32px;
      font-weight: 700;
      color: var(--el-text-color-primary);
      margin: 0 0 8px 0;
    }

    .page-description {
      font-size: 16px;
      color: var(--el-text-color-secondary);
      margin: 0;
    }
  }

  .steps-container {
    margin-bottom: 40px;

    :deep(.el-steps) {
      .el-step__title {
        font-size: 14px;
        font-weight: 600;
      }

      .el-step__description {
        font-size: 12px;
      }
    }
  }

  .main-content {
    background: var(--el-bg-color);
    border-radius: 12px;
    padding: 32px;
    margin-bottom: 24px;
    border: 1px solid var(--el-border-color-lighter);

    .step-content {
      .step-header {
        text-align: center;
        margin-bottom: 32px;

        h2 {
          font-size: 24px;
          font-weight: 600;
          color: var(--el-text-color-primary);
          margin: 0 0 8px 0;
        }

        p {
          font-size: 14px;
          color: var(--el-text-color-secondary);
          margin: 0;
        }
      }

      .server-preview {
        margin-top: 24px;
        padding: 20px;
        background: var(--el-fill-color-light);
        border-radius: 8px;

        h3 {
          font-size: 16px;
          margin: 0 0 16px 0;
          color: var(--el-text-color-primary);
        }

        .selected-server-info {
          display: flex;
          align-items: center;
          gap: 16px;

          .server-avatar {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;

            :deep(.el-image) {
              width: 100%;
              height: 100%;
            }

            .image-error {
              display: flex;
              align-items: center;
              justify-content: center;
              width: 100%;
              height: 100%;
              background: var(--el-fill-color);
              color: var(--el-text-color-placeholder);
            }
          }

          .server-details {
            flex: 1;

            h4 {
              font-size: 18px;
              font-weight: 600;
              margin: 0 0 4px 0;
              color: var(--el-text-color-primary);
            }

            p {
              font-size: 12px;
              color: var(--el-text-color-secondary);
              margin: 0 0 8px 0;
              font-family: monospace;
            }
          }
        }
      }

      .promotion-form {
        margin-bottom: 32px;

        .reward-option {
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .image-uploader {
          :deep(.el-upload) {
            border: 2px dashed var(--el-border-color);
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: var(--el-transition-duration);

            &:hover {
              border-color: var(--el-color-primary);
            }
          }

          .uploaded-image {
            position: relative;
            width: 200px;
            height: 120px;

            img {
              width: 100%;
              height: 100%;
              object-fit: cover;
            }

            .image-overlay {
              position: absolute;
              top: 0;
              left: 0;
              right: 0;
              bottom: 0;
              background: rgba(0, 0, 0, 0.5);
              display: flex;
              align-items: center;
              justify-content: center;
              opacity: 0;
              transition: opacity 0.3s;

              &:hover {
                opacity: 1;
              }
            }
          }

          .upload-placeholder {
            width: 200px;
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--el-text-color-secondary);

            &.is-uploading {
              color: var(--el-color-primary);
            }

            .upload-icon {
              font-size: 28px;
              margin-bottom: 8px;
            }

            .upload-text {
              font-size: 14px;
              margin-bottom: 4px;
            }

            .upload-tip {
              font-size: 12px;
              color: var(--el-text-color-placeholder);
            }
          }
        }
      }

      .content-preview {
        .preview-card {
          background: var(--el-fill-color-light);
          border-radius: 8px;
          padding: 20px;
          border: 1px solid var(--el-border-color-lighter);

          .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;

            h4 {
              font-size: 18px;
              font-weight: 600;
              margin: 0;
              color: var(--el-text-color-primary);
              flex: 1;
            }
          }

          .preview-description {
            color: var(--el-text-color-regular);
            line-height: 1.6;
            margin-bottom: 16px;
          }

          .preview-reward {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--el-color-primary);
            font-weight: 600;
            margin-bottom: 8px;
          }

          .preview-reward-desc {
            font-size: 14px;
            color: var(--el-text-color-secondary);
            margin-bottom: 16px;
          }

          .preview-meta {
            display: flex;
            gap: 24px;
            font-size: 14px;

            .meta-item {
              display: flex;
              align-items: center;
              gap: 6px;
              color: var(--el-text-color-secondary);
            }
          }
        }
      }

      .materials-container {
        .material-section {
          margin-bottom: 32px;

          h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: var(--el-text-color-primary);
          }

          .link-generator {
            .el-input {
              --el-input-font-family: monospace;
            }
          }

          .promotion-text {
            .el-textarea {
              :deep(.el-textarea__inner) {
                font-family: inherit;
                line-height: 1.6;
              }
            }
          }

          .share-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
          }
        }
      }

      .publish-summary {
        .summary-card {
          margin-bottom: 24px;

          .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
          }

          .summary-content {
            .summary-row {
              display: flex;
              margin-bottom: 12px;

              .summary-label {
                flex: 0 0 80px;
                color: var(--el-text-color-secondary);
              }

              .summary-value {
                flex: 1;
                color: var(--el-text-color-primary);
                font-weight: 500;
              }
            }
          }
        }

        .publish-actions {
          display: flex;
          justify-content: center;
          gap: 16px;
        }
      }
    }
  }

  .step-actions {
    display: flex;
    justify-content: center;
    gap: 16px;
  }
}

// 響應式設計
@media (max-width: 768px) {
  .promotion-tools-page {
    padding: 16px;

    .main-content {
      padding: 20px;
    }

    .steps-container {
      :deep(.el-steps) {
        .el-step__title {
          font-size: 12px;
        }

        .el-step__description {
          display: none;
        }
      }
    }

    .step-content {
      .server-preview .selected-server-info {
        flex-direction: column;
        text-align: center;
      }

      .materials-container .material-section .share-buttons {
        flex-direction: column;

        .el-button {
          width: 100%;
        }
      }

      .publish-summary .publish-actions {
        flex-direction: column;

        .el-button {
          width: 100%;
        }
      }
    }

    .step-actions {
      flex-direction: column;

      .el-button {
        width: 100%;
      }
    }
  }
}
</style>