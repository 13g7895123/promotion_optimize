<template>
  <!-- 推廣頁面主要內容 -->
  <div class="promote-page-container">

    <!-- 頁面標題 -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title neon-text">
          <span class="title-icon">🚀</span>
          推廣發布系統
        </h1>
        <p class="page-description">創建和發布您的推廣內容，支援URL和圖片推廣</p>
      </div>
      <div class="header-decoration">
        <div class="cyber-hexagon"></div>
      </div>
    </div>

    <!-- 用戶狀態卡片 -->
    <div class="user-status-card cyber-card">
      <div class="card-content">
        <div class="user-info">
          <div class="user-avatar">
            <div class="avatar-ring">
              <span class="avatar-icon">👤</span>
            </div>
          </div>
          <div class="user-details">
            <h3 class="user-name cyber-text-primary">{{ gameAccount }}</h3>
            <p class="server-name cyber-text-secondary">{{ serverInfo?.name }}</p>
            <p class="reset-time cyber-text-muted">配額重置：{{ nextResetTime }}</p>
          </div>
        </div>
        
        <div class="quota-display">
          <div class="quota-circle">
            <svg class="quota-ring" width="100" height="100" viewBox="0 0 100 100">
              <circle 
                cx="50" cy="50" r="40" 
                stroke="var(--cyber-border-secondary)" 
                stroke-width="6" 
                fill="none"
              />
              <circle 
                cx="50" cy="50" r="40" 
                stroke="var(--cyber-primary)" 
                stroke-width="6" 
                fill="none"
                stroke-dasharray="251.2"
                :stroke-dashoffset="quotaProgress"
                class="progress-ring"
                stroke-linecap="round"
              />
            </svg>
            <div class="quota-text">
              <CountUp :end="quotaUsed" class="quota-number" />
              <span class="quota-separator">/</span>
              <span class="quota-total">{{ quotaLimit }}</span>
            </div>
          </div>
          <div class="quota-stats">
            <div class="stat-item">
              <CountUp :end="quotaRemaining" class="stat-value" />
              <span class="stat-label">剩餘配額</span>
            </div>
            <div class="stat-item">
              <CountUp :end="totalPosts" class="stat-value" />
              <span class="stat-label">本月發布</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 推廣創建區域 -->
    <div class="promotion-create-card cyber-card">
      <div class="card-header">
        <div class="header-left">
          <h2 class="card-title">
            <span class="header-icon">✨</span>
            創建推廣內容
          </h2>
          <p class="card-subtitle">每個推廣（URL或圖片）計為一個配額單位</p>
        </div>
        <div class="header-right">
          <div class="quota-badge">
            <span class="badge-label">剩餘配額</span>
            <span class="badge-value">{{ quotaRemaining }}</span>
          </div>
        </div>
      </div>

      <!-- 推廣列表 -->
      <div class="promotion-list">
        <TransitionGroup name="promotion-list" tag="div">
          <div 
            v-for="(post, index) in promotionPosts" 
            :key="post.id"
            class="promotion-item cyber-card"
          >
            <!-- 推廣項目頭部 -->
            <div class="promotion-header">
              <div class="promotion-info">
                <span class="promotion-number">#{{ index + 1 }}</span>
                <span class="promotion-status" :class="{ valid: isPostValid(post) }">
                  {{ isPostValid(post) ? '已完成' : '未完成' }}
                </span>
              </div>
              <button 
                @click="removePost(index)" 
                class="remove-btn cyber-btn-danger"
                :disabled="isSubmitting"
                title="移除此推廣"
              >
                <span class="remove-icon">✕</span>
              </button>
            </div>

            <!-- 推廣類型選擇器 -->
            <div class="promotion-type-selector">
              <button 
                @click="setPostType(index, 'url')"
                :class="['type-btn', { active: post.type === 'url' }]"
                :disabled="isSubmitting"
              >
                <span class="type-icon">🔗</span>
                <span class="type-label">URL推廣</span>
              </button>
              <button 
                @click="setPostType(index, 'image')"
                :class="['type-btn', { active: post.type === 'image' }]"
                :disabled="isSubmitting"
              >
                <span class="type-icon">🖼️</span>
                <span class="type-label">圖片推廣</span>
              </button>
            </div>

            <!-- 推廣內容表單 -->
            <div class="promotion-content">
              <!-- URL推廣內容 -->
              <div v-if="post.type === 'url'" class="content-form url-form">
                <div class="form-group">
                  <label class="form-label">
                    <span class="label-icon">🔗</span>
                    推廣URL
                    <span class="required">*</span>
                  </label>
                  <input 
                    v-model="post.url"
                    type="url" 
                    class="form-input cyber-input"
                    placeholder="https://example.com"
                    @input="generateUrlPreview(index)"
                    :disabled="isSubmitting"
                  />
                  <div class="input-validation" :class="{ valid: post.url && isValidUrl(post.url), invalid: post.url && !isValidUrl(post.url) }">
                    {{ post.url ? (isValidUrl(post.url) ? '✓ 有效的URL格式' : '✗ 請輸入有效的URL') : '請輸入完整的URL地址' }}
                  </div>
                </div>
              </div>

              <!-- 圖片推廣內容 -->
              <div v-else class="content-form image-form">
                <div class="form-group">
                  <label class="form-label">
                    <span class="label-icon">🖼️</span>
                    推廣圖片
                    <span class="required">*</span>
                  </label>
                  <div class="image-upload-area">
                    <input 
                      :ref="el => imageInputs[index] = el as HTMLInputElement | null"
                      type="file" 
                      accept="image/*"
                      @change="handleImageUpload(index, $event)"
                      class="image-input"
                      :disabled="isSubmitting"
                    />
                    
                    <div v-if="!post.image" class="upload-placeholder" @click="triggerImageUpload(index)">
                      <div class="upload-content">
                        <span class="upload-icon">📸</span>
                        <span class="upload-text">點擊選擇圖片</span>
                        <span class="upload-hint">支援 JPG, PNG, GIF 格式，最大 5MB</span>
                      </div>
                      <div class="upload-border"></div>
                    </div>
                    
                    <div v-else class="image-preview-container">
                      <div class="image-preview">
                        <img :src="post.image" alt="預覽圖片" />
                        <div class="image-overlay">
                          <button @click="triggerImageUpload(index)" class="change-image-btn" title="更換圖片">
                            <span>🔄</span>
                          </button>
                          <button @click="removeImage(index)" class="remove-image-btn" title="移除圖片">
                            <span>✕</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 預覽區域 -->
            <Transition name="preview-fade">
              <div v-if="isPostValid(post)" class="preview-area">
                <div class="preview-header">
                  <h4 class="preview-title">
                    <span class="preview-icon">👁️</span>
                    預覽效果
                  </h4>
                  <span class="preview-badge">即時預覽</span>
                </div>
                
                <div class="preview-card cyber-card">
                  <!-- URL預覽 -->
                  <div v-if="post.type === 'url'" class="url-preview">
                    <div class="preview-type-badge url-type">
                      <span class="type-icon">🔗</span>
                      <span class="type-text">URL推廣</span>
                    </div>
                    <div class="preview-url-container">
                      <span class="url-icon">🌐</span>
                      <a :href="post.url" target="_blank" class="preview-url">{{ post.url }}</a>
                    </div>
                    <div class="preview-footer">
                      <span class="preview-time">{{ formatDateTime(new Date()) }}</span>
                    </div>
                  </div>
                  
                  <!-- 圖片預覽 -->
                  <div v-else class="image-preview">
                    <div class="preview-type-badge image-type">
                      <span class="type-icon">🖼️</span>
                      <span class="type-text">圖片推廣</span>
                    </div>
                    <div class="preview-image-container">
                      <img :src="post.image" alt="推廣圖片" class="preview-image" />
                    </div>
                    <div class="preview-footer">
                      <span class="preview-time">{{ formatDateTime(new Date()) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </TransitionGroup>

        <!-- 空狀態 -->
        <Transition name="empty-state">
          <div v-if="promotionPosts.length === 0" class="empty-promotion-state">
            <div class="empty-content">
              <div class="empty-icon-container">
                <span class="empty-icon">📝</span>
                <div class="empty-glow"></div>
              </div>
              <h3 class="empty-title">尚未添加任何推廣內容</h3>
              <p class="empty-tip">點擊下方「添加推廣」按鈕開始創建您的第一個推廣</p>
            </div>
          </div>
        </Transition>
      </div>

      <!-- 操作按鈕區域 -->
      <div class="action-section">
        <div class="action-stats">
          <div class="stat-item">
            <span class="stat-label">已創建</span>
            <span class="stat-value">{{ promotionPosts.length }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">有效推廣</span>
            <span class="stat-value">{{ validPostsCount }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">可用配額</span>
            <span class="stat-value">{{ quotaRemaining }}</span>
          </div>
        </div>
        
        <div class="action-buttons">
          <button 
            @click="addPost"
            class="add-btn cyber-btn"
            :disabled="quotaRemaining <= 0 || isSubmitting"
            :title="quotaRemaining <= 0 ? '今日配額已用完' : '添加新的推廣內容'"
          >
            <span class="btn-icon">➕</span>
            <span class="btn-text">添加推廣</span>
            <span class="btn-counter">({{ promotionPosts.length }}/{{ quotaLimit }})</span>
          </button>
          
          <button 
            @click="submitAllPosts"
            class="submit-btn cyber-btn primary"
            :disabled="promotionPosts.length === 0 || !hasValidPosts || isSubmitting"
          >
            <span class="btn-icon">{{ isSubmitting ? '⏳' : '🚀' }}</span>
            <span class="btn-text">
              {{ isSubmitting ? '發布中...' : '批量發布' }}
            </span>
            <span v-if="!isSubmitting && validPostsCount > 0" class="btn-counter">({{ validPostsCount }}個)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 發布成功區域 -->
    <Transition name="success-slide">
      <div v-if="publishedPosts.length > 0" class="success-section">
        <div class="success-card cyber-card">
          <div class="success-header">
            <div class="success-icon-container">
              <span class="success-icon">🎉</span>
              <div class="success-glow"></div>
            </div>
            <div class="success-content">
              <h2 class="success-title">發布成功！</h2>
              <p class="success-subtitle">{{ publishedPosts.length }} 個推廣已成功發布</p>
            </div>
          </div>
          
          <div class="published-list">
            <TransitionGroup name="published-item" tag="div">
              <div 
                v-for="(post, index) in publishedPosts" 
                :key="post.id" 
                class="published-item"
                :style="{ '--delay': index * 0.1 + 's' }"
              >
                <div class="published-icon">
                  <span>{{ post.type === 'url' ? '🔗' : '🖼️' }}</span>
                </div>
                <div class="published-details">
                  <h4 class="published-title">{{ post.title }}</h4>
                  <p class="published-type">{{ post.type === 'url' ? 'URL推廣' : '圖片推廣' }}</p>
                </div>
                <div class="published-meta">
                  <span class="published-time">{{ formatDateTime(post.publishedAt) }}</span>
                  <span class="published-status">✅ 已發布</span>
                </div>
              </div>
            </TransitionGroup>
          </div>
          
          <div class="success-actions">
            <button @click="clearPublishedPosts" class="clear-btn cyber-btn">
              <span class="btn-icon">🗑️</span>
              <span class="btn-text">清除記錄</span>
            </button>
            <button @click="createNewPromotion" class="new-promotion-btn cyber-btn primary">
              <span class="btn-icon">➕</span>
              <span class="btn-text">創建新推廣</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// 組件導入
const CountUp = defineAsyncComponent(() => import('~/components/effects/CountUp.vue'))

// 路由和參數
const route = useRoute()
const router = useRouter()
const serverCode = route.params.server as string
const gameAccount = route.query.account as string

// 響應式數據
const serverInfo = ref<any>(null)
const isSubmitting = ref(false)
const publishedPosts = ref<any[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

// 配額管理
const quotaLimit = ref(10) // 每日推廣配額限制
const quotaUsed = ref(3) // 已使用配額
const totalPosts = ref(27) // 本月總發布數

// 推廣帖子列表
const promotionPosts = ref<PromotionPost[]>([])
const imageInputs = ref<(HTMLInputElement | null)[]>([])

// 計算屬性
const quotaRemaining = computed(() => Math.max(0, quotaLimit.value - quotaUsed.value - promotionPosts.value.length))

const quotaProgress = computed(() => {
  const percentage = quotaUsed.value / quotaLimit.value
  const circumference = 2 * Math.PI * 40 // 半徑為40的圓周長
  return circumference * (1 - percentage)
})

const nextResetTime = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  tomorrow.setHours(0, 0, 0, 0)
  return tomorrow.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
})

const hasValidPosts = computed(() => {
  return promotionPosts.value.some(post => isPostValid(post))
})

const validPostsCount = computed(() => {
  return promotionPosts.value.filter(post => isPostValid(post)).length
})

// 檢查是否有載入中的圖片（備用功能）
// const hasLoadingImages = computed(() => {
//   return promotionPosts.value.some(post => post.type === 'image' && post.image === 'loading')
// })

// 推廣帖子接口
interface PromotionPost {
  id: number
  type: 'url' | 'image'
  title: string
  description: string
  url?: string
  image?: string
  publishedAt?: Date
}

// 錯誤處理
interface ErrorState {
  message: string
  type: 'validation' | 'network' | 'system'
}

// 頁面元數據
definePageMeta({
  layout: 'server'
})

// 載入數據
const loadData = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    // 模擬API載入延遲
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // 載入伺服器信息
    serverInfo.value = {
      name: serverCode.toUpperCase() + ' 伺服器',
      code: serverCode,
      theme: 'cyber-tech'
    }

    // 模擬載入用戶配額信息
    quotaUsed.value = Math.floor(Math.random() * 5) + 2
    totalPosts.value = Math.floor(Math.random() * 50) + 20
    
    // 清空發布記錄（新會話開始）
    publishedPosts.value = []
    
  } catch (err) {
    console.error('載入數據失敗:', err)
    error.value = '載入數據失敗，請重新整理頁面'
  } finally {
    isLoading.value = false
  }
}

// 顯示錯誤訊息
const showError = (message: string, _type: ErrorState['type'] = 'system') => {
  error.value = message
  
  // 自動清除錯誤訊息
  setTimeout(() => {
    error.value = null
  }, 5000)
}

// 顯示成功訊息
const showSuccess = (message: string) => {
  // 可以在這裡添加成功提示邏輯
  console.log('Success:', message)
}

// 添加新的推廣帖子
const addPost = () => {
  if (quotaRemaining.value <= 0) {
    showError('今日推廣配額已用完，請明日再試！', 'validation')
    return
  }

  if (promotionPosts.value.length >= quotaLimit.value) {
    showError('已達到最大推廣數量限制', 'validation')
    return
  }

  const newPost: PromotionPost = {
    id: Date.now() + Math.random(), // 確保唯一性
    type: 'url',
    title: '',
    description: '',
    url: '',
    image: ''
  }

  promotionPosts.value.push(newPost)
  
  // 滾動到新添加的推廣項目
  nextTick(() => {
    const newElement = document.querySelector('.promotion-item:last-child')
    if (newElement) {
      newElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  })
}

// 移除推廣帖子
const removePost = (index: number) => {
  if (isSubmitting.value) return
  promotionPosts.value.splice(index, 1)
}

// 設置帖子類型
const setPostType = (index: number, type: 'url' | 'image') => {
  if (isSubmitting.value) return
  
  const post = promotionPosts.value[index]
  post.type = type
  
  // 清除與類型不符的數據
  if (type === 'url') {
    post.image = ''
  } else {
    post.url = ''
  }
}

// 處理圖片上傳
const handleImageUpload = async (index: number, event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return

  try {
    // 檢查文件大小 (5MB)
    if (file.size > 5 * 1024 * 1024) {
      showError('圖片大小不能超過 5MB', 'validation')
      target.value = '' // 清除選擇
      return
    }

    // 檢查文件類型
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
    if (!allowedTypes.includes(file.type)) {
      showError('請選擇 JPG、PNG、GIF 或 WebP 格式的圖片', 'validation')
      target.value = ''
      return
    }

    // 顯示載入狀態
    const post = promotionPosts.value[index]
    post.image = 'loading' // 臨時標記

    const reader = new FileReader()
    reader.onload = (e) => {
      post.image = e.target?.result as string
      showSuccess('圖片上傳成功！')
    }
    
    reader.onerror = () => {
      showError('圖片讀取失敗，請重新選擇', 'system')
      post.image = ''
      target.value = ''
    }
    
    reader.readAsDataURL(file)
    
  } catch (err) {
    console.error('圖片上傳錯誤:', err)
    showError('圖片處理失敗，請重試', 'system')
  }
}

// 觸發圖片上傳
const triggerImageUpload = (index: number) => {
  if (isSubmitting.value) return
  
  const input = imageInputs.value[index]
  if (input) {
    input.click()
  } else {
    showError('無法訪問文件選擇器，請重新整理頁面', 'system')
  }
}

// 移除圖片
const removeImage = (index: number) => {
  if (isSubmitting.value) return
  
  const post = promotionPosts.value[index]
  post.image = ''
  
  // 清除文件輸入
  const input = imageInputs.value[index]
  if (input) {
    input.value = ''
  }
  
  showSuccess('圖片已移除')
}

// 生成URL預覽
const generateUrlPreview = async (index: number) => {
  const post = promotionPosts.value[index]
  
  if (post.url && isValidUrl(post.url) && !post.title.trim()) {
    try {
      const url = new URL(post.url)
      
      // 自動生成標題建議
      let suggestedTitle = url.hostname.replace('www.', '')
      
      // 特殊網站處理
      const siteMap: Record<string, string> = {
        'youtube.com': 'YouTube 影片分享',
        'youtu.be': 'YouTube 影片分享',
        'facebook.com': 'Facebook 貼文分享',
        'instagram.com': 'Instagram 貼文分享',
        'twitter.com': 'Twitter 貼文分享',
        'x.com': 'X (Twitter) 貼文分享',
        'discord.gg': 'Discord 伺服器邀請',
        'github.com': 'GitHub 專案分享'
      }
      
      for (const [domain, title] of Object.entries(siteMap)) {
        if (url.hostname.includes(domain)) {
          suggestedTitle = title
          break
        }
      }
      
      post.title = suggestedTitle
      
    } catch (error) {
      // URL格式無效，靜默處理
      console.warn('URL預覽生成失敗:', error)
    }
  }
}

// 驗證帖子是否有效
const isPostValid = (post: PromotionPost): boolean => {
  if (post.type === 'url') {
    return !!post.url?.trim() && isValidUrl(post.url)
  } else {
    return !!post.image && post.image !== 'loading'
  }
}

// 驗證URL格式
const isValidUrl = (url: string): boolean => {
  if (!url?.trim()) return false
  
  try {
    const urlObj = new URL(url)
    // 只允許 http 和 https 協議
    return ['http:', 'https:'].includes(urlObj.protocol)
  } catch {
    return false
  }
}

// 批量提交所有帖子
const submitAllPosts = async () => {
  const validPosts = promotionPosts.value.filter(post => isPostValid(post))
  
  // 驗證檢查
  if (validPosts.length === 0) {
    showError('請至少完成一個有效的推廣帖子', 'validation')
    return
  }

  if (validPosts.length > quotaRemaining.value) {
    showError(`推廣帖子數量 (${validPosts.length}) 超過剩餘配額 (${quotaRemaining.value})`, 'validation')
    return
  }

  // 二次確認
  const confirmMessage = `確定要發布 ${validPosts.length} 個推廣嗎？這將消耗 ${validPosts.length} 個配額。`
  if (!confirm(confirmMessage)) {
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    // 模擬API調用進度
    for (let i = 0; i < validPosts.length; i++) {
      await new Promise(resolve => setTimeout(resolve, 800)) // 模擬每個推廣的處理時間
      
      // 這裡可以顯示進度（未來可擴展）
      console.log(`正在發布第 ${i + 1}/${validPosts.length} 個推廣...`)
    }

    // 模擬發布成功
    const published = validPosts.map(post => ({
      ...post,
      publishedAt: new Date()
    }))

    // 更新狀態
    publishedPosts.value = [...publishedPosts.value, ...published]
    quotaUsed.value += validPosts.length
    promotionPosts.value = [] // 清空編輯列表

    showSuccess(`成功發布 ${validPosts.length} 個推廣！`)

    // 自動滾動到成功區域
    await nextTick()
    setTimeout(() => {
      const successSection = document.querySelector('.success-section')
      if (successSection) {
        successSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }, 300)

  } catch (err) {
    console.error('發布失敗:', err)
    showError('發布失敗，請檢查網路連線後重試', 'network')
  } finally {
    isSubmitting.value = false
  }
}

// 清除發布記錄
const clearPublishedPosts = () => {
  if (confirm('確定要清除所有發布記錄嗎？')) {
    publishedPosts.value = []
    showSuccess('發布記錄已清除')
  }
}

// 創建新推廣
const createNewPromotion = () => {
  if (quotaRemaining.value > 0) {
    addPost()
    
    // 滾動到推廣創建區域
    setTimeout(() => {
      const createCard = document.querySelector('.promotion-create-card')
      if (createCard) {
        createCard.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }, 100)
  } else {
    showError('今日配額已用完，無法創建新推廣', 'validation')
  }
}

// 格式化日期時間
const formatDateTime = (date: Date | string) => {
  const dateObj = new Date(date)
  return dateObj.toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// 粒子動畫
const initParticleEffect = () => {
  if (!particleCanvas.value) return

  const canvas = particleCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  let animationId: number
  let particles: Particle[] = []

  // 設置 canvas 尺寸
  const resizeCanvas = () => {
    const dpr = window.devicePixelRatio || 1
    canvas.width = window.innerWidth * dpr
    canvas.height = window.innerHeight * dpr
    canvas.style.width = window.innerWidth + 'px'
    canvas.style.height = window.innerHeight + 'px'
    ctx.scale(dpr, dpr)
    
    // 重新初始化粒子
    initParticles()
  }
  
  resizeCanvas()
  window.addEventListener('resize', resizeCanvas)

  // 粒子類
  class Particle {
    x: number
    y: number
    size: number
    speedX: number
    speedY: number
    opacity: number
    hue: number
    life: number
    maxLife: number
    pulse: number

    constructor() {
      this.x = Math.random() * window.innerWidth
      this.y = Math.random() * window.innerHeight
      this.size = Math.random() * 3 + 0.5
      this.speedX = Math.random() * 0.8 - 0.4
      this.speedY = Math.random() * 0.8 - 0.4
      this.opacity = Math.random() * 0.6 + 0.2
      this.hue = Math.random() * 60 + 180 // 青色到藍色範圍
      this.life = 0
      this.maxLife = Math.random() * 300 + 200
      this.pulse = Math.random() * Math.PI * 2
    }

    update() {
      this.x += this.speedX
      this.y += this.speedY
      this.life++
      this.pulse += 0.02

      // 生命週期透明度變化
      const lifeFactor = 1 - (this.life / this.maxLife)
      const pulseFactor = (Math.sin(this.pulse) + 1) * 0.5
      this.opacity = Math.max(0, lifeFactor * 0.8 * (0.5 + pulseFactor * 0.5))

      // 邊界處理
      if (this.x > window.innerWidth + 100) this.x = -100
      if (this.x < -100) this.x = window.innerWidth + 100
      if (this.y > window.innerHeight + 100) this.y = -100
      if (this.y < -100) this.y = window.innerHeight + 100

      // 重生條件
      if (this.life >= this.maxLife) {
        this.x = Math.random() * window.innerWidth
        this.y = Math.random() * window.innerHeight
        this.life = 0
        this.maxLife = Math.random() * 300 + 200
        this.hue = Math.random() * 60 + 180
        this.pulse = Math.random() * Math.PI * 2
      }
    }

    draw() {
      if (this.opacity <= 0.01) return
      
      ctx!.save()
      ctx!.globalAlpha = this.opacity
      
      // 创建漸變
      const gradient = ctx!.createRadialGradient(
        this.x, this.y, 0,
        this.x, this.y, this.size * 3
      )
      gradient.addColorStop(0, `hsla(${this.hue}, 100%, 70%, 1)`)
      gradient.addColorStop(0.7, `hsla(${this.hue}, 100%, 60%, 0.3)`)
      gradient.addColorStop(1, `hsla(${this.hue}, 100%, 50%, 0)`)
      
      ctx!.fillStyle = gradient
      ctx!.shadowBlur = 20
      ctx!.shadowColor = `hsla(${this.hue}, 100%, 70%, 0.6)`
      ctx!.beginPath()
      ctx!.arc(this.x, this.y, this.size, 0, Math.PI * 2)
      ctx!.fill()
      ctx!.restore()
    }
  }

  // 初始化粒子
  const initParticles = () => {
    particles = []
    // 根據螢幕大小調整數量，確保性能
    const area = window.innerWidth * window.innerHeight
    const density = Math.max(20, Math.min(80, area / 20000))
    
    for (let i = 0; i < density; i++) {
      particles.push(new Particle())
    }
  }

  // 連線效果
  const drawConnections = () => {
    const maxDistance = 120
    const maxConnections = 3
    
    for (let i = 0; i < particles.length; i++) {
      let connections = 0
      
      for (let j = i + 1; j < particles.length && connections < maxConnections; j++) {
        const dx = particles[i].x - particles[j].x
        const dy = particles[i].y - particles[j].y
        const distance = Math.sqrt(dx * dx + dy * dy)
        
        if (distance < maxDistance) {
          const opacity = ((maxDistance - distance) / maxDistance) * 0.15
          ctx!.save()
          ctx!.globalAlpha = opacity * Math.min(particles[i].opacity, particles[j].opacity)
          ctx!.strokeStyle = '#00d4ff'
          ctx!.lineWidth = 1
          ctx!.beginPath()
          ctx!.moveTo(particles[i].x, particles[i].y)
          ctx!.lineTo(particles[j].x, particles[j].y)
          ctx!.stroke()
          ctx!.restore()
          connections++
        }
      }
    }
  }

  // 動畫循環
  let lastTime = 0
  const animate = (currentTime: number) => {
    const deltaTime = currentTime - lastTime
    lastTime = currentTime
    
    // 幀率控制（目標 60 FPS）
    if (deltaTime < 16.67) {
      animationId = requestAnimationFrame(animate)
      return
    }

    ctx.clearRect(0, 0, window.innerWidth, window.innerHeight)
    
    // 繪製連線
    drawConnections()
    
    // 更新和繪製粒子
    particles.forEach(particle => {
      particle.update()
      particle.draw()
    })

    animationId = requestAnimationFrame(animate)
  }

  // 初始化粒子和開始動畫
  initParticles()
  animationId = requestAnimationFrame(animate)

  // 清理函數
  return () => {
    cancelAnimationFrame(animationId)
    window.removeEventListener('resize', resizeCanvas)
  }
}

// 粒子效果清理函數
let particleCleanup: (() => void) | null = null

// 生命週期
onMounted(async () => {
  // 檢查必要參數
  if (!gameAccount) {
    showError('請先輸入遊戲帳號', 'validation')
    await new Promise(resolve => setTimeout(resolve, 2000))
    router.push(`/${serverCode}`)
    return
  }
  
  if (!serverCode) {
    showError('伺服器代碼無效', 'validation')
    await new Promise(resolve => setTimeout(resolve, 2000))
    router.push('/')
    return
  }
  
  // 載入數據
  await loadData()
})

</script>

<style scoped>
/* 導入共用伺服器頁面樣式 */
@import '@/assets/css/server-pages.css';

/* 推廣頁面容器 */
.promote-page-container {
  min-height: 100vh;
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  position: relative;
  z-index: 3;
}

/* CSS 自定義屬性 */
:root {
  --font-size-sm: 0.875rem;
  --font-size-md: 1rem;
  --font-size-lg: 1.125rem;
  --spacing-md: 1rem;
  --cyber-text-primary: #00d4ff;
  --cyber-text-secondary: rgba(255, 255, 255, 0.8);
  --cyber-primary: #00d4ff;
  --cyber-border-secondary: rgba(255, 255, 255, 0.3);
}

/* 全屏背景容器 */
.fullscreen-background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: linear-gradient(135deg, #0a0e1a 0%, #1a1f35 30%, #2d1b69 60%, #1a0f4a 100%);
  overflow-x: hidden;
  overflow-y: auto;
  z-index: 0;
  /* GPU 加速和性能優化 */
  transform: translateZ(0);
  will-change: scroll-position;
  backface-visibility: hidden;
}

/* 漸變背景層 */
.gradient-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(ellipse at 20% 80%, rgba(0, 212, 255, 0.15) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(45, 27, 105, 0.3) 0%, transparent 50%),
    radial-gradient(ellipse at 40% 40%, rgba(0, 128, 255, 0.1) 0%, transparent 50%);
  animation: gradient-shift 20s ease-in-out infinite;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: transform, opacity;
  backface-visibility: hidden;
}

/* 原有的 gradient-shift 動畫已經被移到優化的動畫區塊 */

/* 頁面主容器 */
.promote-page {
  position: relative;
  min-height: 100vh;
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  z-index: 1;
}

.particle-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 1;
  pointer-events: none;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: transform;
}

.particle-canvas {
  width: 100vw;
  height: 100vh;
  display: block;
  /* 硬件加速 */
  transform: translateZ(0);
  will-change: transform;
}

/* 頁面標題區域 */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 20px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
  position: relative;
  z-index: 10;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.1), transparent);
  animation: cyber-scan 3s ease-in-out infinite;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: transform;
}

/* 原有的 cyber-scan 動畫已經被移到優化的動畫區塊 */

.header-content {
  flex: 1;
  text-align: center;
}

.page-title {
  font-size: clamp(2rem, 4vw, 3rem);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  font-weight: 700;
  letter-spacing: 1px;
}

.neon-text {
  color: #00d4ff;
  text-shadow: 
    0 0 5px #00d4ff,
    0 0 10px #00d4ff,
    0 0 15px #00d4ff,
    0 0 20px #00d4ff;
  animation: neon-flicker 2s infinite alternate;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: text-shadow;
}

@keyframes neon-flicker {
  0%, 18%, 22%, 25%, 53%, 57%, 100% {
    text-shadow: 
      0 0 5px #00d4ff,
      0 0 10px #00d4ff,
      0 0 15px #00d4ff,
      0 0 20px #00d4ff;
    transform: translateZ(0);
  }
  20%, 24%, 55% {
    text-shadow: none;
    transform: translateZ(0);
  }
}

.title-icon {
  font-size: 1.2em;
  filter: drop-shadow(0 0 10px #00d4ff);
}

.page-description {
  font-size: 1.125rem;
  color: #ffffff;
  margin: 0;
}

.header-decoration {
  display: none;
}

/* 用戶狀態卡片 */
.user-status-card {
  margin-bottom: 3rem;
  padding: 2rem;
  position: relative;
  z-index: 10;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 20px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.card-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
}

.user-avatar {
  position: relative;
}

.avatar-ring {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  animation: neon-pulse 2s ease-in-out infinite alternate;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: box-shadow;
}

/* 原有的 neon-pulse 動畫已經被移到優化的動畫區塊 */

.avatar-icon {
  font-size: 2rem;
  color: #0a0e1a;
}

.user-details {
  flex: 1;
}

.user-name {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  color: #00d4ff;
}

.server-name {
  font-size: 1rem;
  margin: 0 0 0.5rem 0;
  color: #ffffff;
}

.reset-time {
  font-size: 0.875rem;
  margin: 0;
  color: rgba(255, 255, 255, 0.7);
}

/* 配額顯示 */
.quota-display {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.quota-circle {
  position: relative;
  width: 100px;
  height: 100px;
}

.quota-ring {
  transform: rotate(-90deg);
}

.progress-ring {
  transition: stroke-dashoffset 0.6s ease;
  filter: drop-shadow(0 0 8px #00d4ff);
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: stroke-dashoffset;
}

.quota-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.quota-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #00d4ff;
  line-height: 1;
}

.quota-separator {
  color: rgba(255, 255, 255, 0.5);
  margin: 0 2px;
}

.quota-total {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1rem;
}

.quota-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #00d4ff;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 0.5rem;
}

/* 推廣創建卡片 */
.promotion-create-card {
  margin-bottom: 3rem;
  padding: 2rem;
  position: relative;
  z-index: 10;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 20px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.header-left {
  flex: 1;
}

.card-title {
  font-size: 1.25rem;
  color: #00d4ff;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-subtitle {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
}

.header-icon {
  font-size: 1.2em;
  filter: drop-shadow(0 0 5px currentColor);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.quota-badge {
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  color: #0a0e1a;
  padding: 0.75rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
}

.badge-label {
  font-size: 0.875rem;
}

.badge-value {
  font-size: 1rem;
  font-weight: 700;
}

/* 推廣列表 */
.promotion-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

/* 推廣項目 */
.promotion-item {
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 16px;
  transition: all 0.3s ease;
  position: relative;
  z-index: 10;
  backdrop-filter: blur(12px);
}

.promotion-item:hover {
  border-color: rgba(0, 212, 255, 0.5);
  box-shadow: 0 8px 25px rgba(0, 212, 255, 0.2);
  transform: translateY(-2px);
}

.promotion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.promotion-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.promotion-number {
  font-size: 0.875rem;
  font-weight: 600;
  color: #00d4ff;
  background: rgba(0, 212, 255, 0.1);
  padding: 4px 8px;
  border-radius: 8px;
  border: 1px solid rgba(0, 212, 255, 0.3);
}

.promotion-status {
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: 12px;
  background: rgba(255, 128, 0, 0.1);
  color: #ff8000;
  border: 1px solid rgba(255, 128, 0, 0.3);
  transition: all 0.15s ease;
}

.promotion-status.valid {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
  border-color: rgba(34, 197, 94, 0.3);
}

.remove-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  font-size: 0.875rem;
}

.remove-btn:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
  border-color: #ef4444;
  transform: scale(1.1);
}

.remove-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.remove-icon {
  font-weight: 600;
}

/* 推廣類型選擇器 */
.promotion-type-selector {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding: 0.75rem;
  background: rgba(0, 212, 255, 0.05);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.type-btn {
  flex: 1;
  padding: 1rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: rgba(255, 255, 255, 0.8);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
}

.type-btn:hover:not(:disabled) {
  border-color: rgba(0, 212, 255, 0.3);
  color: #ffffff;
  background: rgba(0, 212, 255, 0.05);
}

.type-btn.active {
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  border-color: #00d4ff;
  color: #0a0e1a;
  box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
}

.type-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.type-icon {
  font-size: 1.1em;
}

.type-label {
  font-size: var(--font-size-sm);
  font-weight: 600;
}

/* 表單內容 */
.promotion-content {
  margin-bottom: 1.5rem;
}

.content-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.label-icon {
  font-size: 1em;
}

.required {
  color: #ef4444;
  margin-left: 4px;
}

.form-input,
.form-textarea {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(0, 212, 255, 0.3);
  color: #ffffff;
  padding: 1rem;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  resize: vertical;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #00d4ff;
  box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
  background: rgba(255, 255, 255, 0.15);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.form-input:disabled,
.form-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.input-hint {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  text-align: right;
}

.input-validation {
  font-size: 0.75rem;
  padding: 0.5rem;
  border-radius: 4px;
  transition: all 0.15s ease;
}

.input-validation.valid {
  color: #22c55e;
  background: rgba(34, 197, 94, 0.1);
}

.input-validation.invalid {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

/* 圖片上傳區域 */
.image-upload-area {
  position: relative;
}

.image-input {
  display: none;
}

.upload-placeholder {
  border: 2px dashed rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: rgba(0, 212, 255, 0.02);
  position: relative;
  overflow: hidden;
}

.upload-placeholder:hover {
  border-color: rgba(0, 212, 255, 0.5);
  background: rgba(0, 212, 255, 0.05);
  transform: translateY(-2px);
}

.upload-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  position: relative;
  z-index: 2;
}

.upload-icon {
  font-size: 3rem;
  margin-bottom: 0.75rem;
  filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.3));
}

.upload-text {
  font-size: 1.125rem;
  font-weight: 600;
  color: #ffffff;
}

.upload-hint {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.7);
}

.upload-border {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 12px;
  background: linear-gradient(45deg, transparent, rgba(0, 212, 255, 0.1), transparent);
  background-size: 200% 200%;
  opacity: 0;
  transition: opacity 0.3s ease;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: opacity, background-position;
}

.upload-placeholder:hover .upload-border {
  opacity: 1;
  animation: border-glow 2s ease-in-out infinite;
}

@keyframes border-glow {
  0%, 100% { 
    background-position: 0% 50%;
    transform: translateZ(0);
  }
  50% { 
    background-position: 100% 50%;
    transform: translateZ(0);
  }
}

/* 圖片預覽 */
.image-preview-container {
  border-radius: 12px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.image-preview {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  background: rgba(255, 255, 255, 0.05);
}

.image-preview img {
  max-width: 100%;
  max-height: 300px;
  object-fit: contain;
  border-radius: 8px;
}

.image-overlay {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-preview:hover .image-overlay {
  opacity: 1;
}

.change-image-btn,
.remove-image-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  transition: all 0.15s ease;
  backdrop-filter: blur(10px);
}

.change-image-btn {
  background: rgba(0, 212, 255, 0.8);
  color: #0a0e1a;
}

.remove-image-btn {
  background: rgba(239, 68, 68, 0.8);
  color: white;
}

.change-image-btn:hover,
.remove-image-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

/* 預覽區域 */
.preview-area {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: rgba(0, 212, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.preview-title {
  font-size: 1rem;
  color: #ffffff;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.preview-icon {
  font-size: 1.1em;
}

.preview-badge {
  font-size: 0.75rem;
  padding: 4px 8px;
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  color: #0a0e1a;
  border-radius: 12px;
  font-weight: 600;
}

.preview-card {
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  position: relative;
}

.preview-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.preview-type-badge.url-type {
  background: rgba(0, 128, 255, 0.2);
  color: #0080ff;
  border: 1px solid rgba(0, 128, 255, 0.3);
}

.preview-type-badge.image-type {
  background: rgba(168, 85, 247, 0.2);
  color: #a855f7;
  border: 1px solid rgba(168, 85, 247, 0.3);
}

.preview-post-title {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--cyber-text-primary);
  margin: 0 0 var(--spacing-md) 0;
  line-height: 1.4;
}

.preview-url-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: rgba(0, 212, 255, 0.05);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.url-icon {
  font-size: 0.875rem;
  color: #0080ff;
}

.preview-url {
  color: #0080ff;
  text-decoration: none;
  font-size: 0.875rem;
  word-break: break-all;
  transition: color 0.15s ease;
}

.preview-url:hover {
  color: #00d4ff;
  text-decoration: underline;
}

.preview-image-container {
  margin-bottom: 1rem;
  border-radius: 8px;
  overflow: hidden;
  background: #0a0e1a;
  display: flex;
  justify-content: center;
  align-items: center;
  max-height: 300px;
}

.preview-image {
  max-width: 100%;
  height: auto;
  display: block;
}

.preview-description {
  color: var(--cyber-text-secondary);
  font-size: var(--font-size-md);
  line-height: 1.6;
  margin: 0 0 var(--spacing-md) 0;
  white-space: pre-wrap;
}

.preview-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.preview-time {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
}

/* 空狀態 */
.empty-promotion-state {
  text-align: center;
  padding: 3rem;
  background: rgba(0, 212, 255, 0.02);
  border: 2px dashed rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  margin: 2rem 0;
}

.empty-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.empty-icon-container {
  position: relative;
  display: inline-block;
}

.empty-icon {
  font-size: 4rem;
  color: rgba(255, 255, 255, 0.5);
  filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.2));
}

.empty-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 80px;
  height: 80px;
  background: radial-gradient(circle, rgba(0, 212, 255, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  animation: empty-pulse 2s ease-in-out infinite;
  /* GPU 加速優化 */
  will-change: opacity, transform;
}

/* 原有的 empty-pulse 動畫已經被移到優化的動畫區塊 */

.empty-title {
  font-size: 1.125rem;
  color: #ffffff;
  margin: 0;
  font-weight: 600;
}

.empty-tip {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
  max-width: 400px;
}

/* 操作區域 */
.action-section {
  padding: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 16px;
  margin-top: 1.5rem;
  position: relative;
  z-index: 10;
  backdrop-filter: blur(12px);
}

.action-stats {
  display: flex;
  justify-content: space-around;
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  background: rgba(0, 212, 255, 0.05);
  border-radius: 12px;
}

.action-stats .stat-item {
  text-align: center;
}

.action-stats .stat-label {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
  display: block;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.action-stats .stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #00d4ff;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.cyber-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  background: transparent;
  border: 2px solid rgba(0, 212, 255, 0.3);
  color: #00d4ff;
  border-radius: 8px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.3s ease;
  overflow: hidden;
  min-width: 140px;
}

.cyber-btn:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.2), transparent);
  transition: left 0.5s;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: left;
}

.cyber-btn:hover:not(:disabled):before {
  left: 100%;
}

.cyber-btn:hover:not(:disabled) {
  background: #00d4ff;
  color: #0a0e1a;
  box-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
  transform: translateY(-2px);
}

.cyber-btn.primary {
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  border-color: #00d4ff;
  color: #0a0e1a;
  box-shadow: 0 4px 15px rgba(0, 212, 255, 0.3);
}

.cyber-btn.primary:hover:not(:disabled) {
  box-shadow: 0 0 20px rgba(0, 212, 255, 0.6);
  transform: translateY(-3px);
}

.cyber-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
}

.cyber-btn:disabled:hover {
  background: transparent;
  color: #00d4ff;
  box-shadow: none;
}

.cyber-btn.primary:disabled:hover {
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  color: #0a0e1a;
}

.btn-icon {
  font-size: 1.1em;
}

.btn-text {
  font-size: 0.875rem;
}

.btn-counter {
  font-size: 0.75rem;
  opacity: 0.8;
}

/* 成功區域 */
.success-section {
  margin-top: 3rem;
  position: relative;
  z-index: 10;
}

.success-card {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(0, 212, 255, 0.05) 100%);
  border: 1px solid rgba(34, 197, 94, 0.3);
  border-radius: 20px;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.success-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(34, 197, 94, 0.2);
}

.success-icon-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-icon {
  font-size: 3rem;
  color: #22c55e;
  filter: drop-shadow(0 0 10px #22c55e);
  animation: success-bounce 2s ease-in-out infinite;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: transform;
}

/* 原有的 success-bounce 動畫已經被移到優化的動畫區塊 */

.success-glow {
  position: absolute;
  width: 60px;
  height: 60px;
  background: radial-gradient(circle, #22c55e 0%, transparent 70%);
  border-radius: 50%;
  opacity: 0.3;
  animation: success-glow-pulse 1.5s ease-in-out infinite;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: transform, opacity;
}

/* 原有的 success-glow-pulse 動畫已經被移到優化的動畫區塊 */

.success-content {
  flex: 1;
}

.success-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #22c55e;
  margin: 0 0 0.5rem 0;
}

.success-subtitle {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

.published-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.published-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(34, 197, 94, 0.05);
  border: 1px solid rgba(34, 197, 94, 0.2);
  border-radius: 8px;
  transition: all 0.3s ease;
  animation: published-item-enter 0.6s ease-out var(--delay, 0s) both;
}

@keyframes published-item-enter {
  from {
    opacity: 0;
    transform: translateX(-30px) translateZ(0);
  }
  to {
    opacity: 1;
    transform: translateX(0) translateZ(0);
  }
}

.published-item:hover {
  background: rgba(34, 197, 94, 0.08);
  border-color: rgba(34, 197, 94, 0.4);
  transform: translateX(5px);
}

.published-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(45deg, #00d4ff, #0080ff);
  border-radius: 50%;
  font-size: 1.125rem;
}

.published-details {
  flex: 1;
}

.published-title {
  font-size: 1rem;
  font-weight: 600;
  color: #ffffff;
  margin: 0 0 0.5rem 0;
  line-height: 1.2;
}

.published-type {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
}

.published-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.published-time {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.7);
}

.published-status {
  font-size: 0.75rem;
  color: #22c55e;
  font-weight: 600;
}

.success-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(34, 197, 94, 0.2);
}

/* 動畫效果 */
.promotion-list-enter-active,
.promotion-list-leave-active {
  transition: all 0.5s ease;
}

.promotion-list-enter-from {
  opacity: 0;
  transform: translateY(-30px);
}

.promotion-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.preview-fade-enter-active,
.preview-fade-leave-active {
  transition: all 0.3s ease;
}

.preview-fade-enter-from,
.preview-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.empty-state-enter-active,
.empty-state-leave-active {
  transition: all 0.4s ease;
}

.empty-state-enter-from,
.empty-state-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

.success-slide-enter-active {
  transition: all 0.6s ease;
}

.success-slide-enter-from {
  opacity: 0;
  transform: translateY(50px);
}

.published-item-enter-active,
.published-item-leave-active {
  transition: all 0.4s ease;
}

.published-item-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.published-item-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* 響應式設計 */

/* 平板和小型桌面 */
@media (max-width: 1024px) {
  .fullscreen-background {
    /* 確保全屏背景在平板上也正常工作 */
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
  }
  
  .page-header {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }
  
  .header-decoration {
    display: block;
    order: -1;
  }
  
  .card-content {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .quota-display {
    justify-content: center;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .cyber-btn {
    width: 100%;
    min-width: auto;
  }
}

/* 手機橫屏和小平板 */
@media (max-width: 768px) {
  .fullscreen-background {
    /* 手機上確保背景完全覆蓋 */
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    min-height: -webkit-fill-available; /* iOS Safari 支援 */
  }
  
  .promote-page {
    padding: 1rem;
    min-height: 100vh;
    min-height: -webkit-fill-available; /* iOS Safari 支援 */
  }
  
  .page-header {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .user-status-card,
  .promotion-create-card {
    padding: 1.5rem;
  }
  
  .card-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .header-right {
    justify-content: center;
  }
  
  .user-info {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .quota-display {
    flex-direction: column;
    align-items: center;
  }
  
  .quota-stats {
    flex-direction: row;
    gap: 1.5rem;
  }
  
  .promotion-type-selector {
    flex-direction: column;
  }
  
  .type-btn {
    justify-content: flex-start;
    padding: 1.5rem;
  }
  
  .action-stats {
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
  }
  
  .published-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .published-meta {
    align-items: flex-start;
    width: 100%;
  }
  
  .success-actions {
    flex-direction: column;
  }
}

/* 手機直屏 */
@media (max-width: 480px) {
  .fullscreen-background {
    /* 手機直屏模式優化 */
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    min-height: -webkit-fill-available;
  }
  
  .promote-page {
    padding: 0.75rem;
    min-height: 100vh;
    min-height: -webkit-fill-available;
  }
  
  .page-header {
    padding: 1rem;
  }
  
  .page-title {
    font-size: 1.75rem;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .user-status-card,
  .promotion-create-card,
  .success-card {
    padding: 1rem;
  }
  
  .promotion-item {
    padding: 1rem;
  }
  
  .user-avatar .avatar-ring {
    width: 60px;
    height: 60px;
  }
  
  .avatar-icon {
    font-size: 1.5rem;
  }
  
  .quota-circle {
    width: 80px;
    height: 80px;
  }
  
  .quota-stats {
    gap: 1rem;
  }
  
  .promotion-header {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  
  .promotion-info {
    justify-content: space-between;
  }
  
  .remove-btn {
    align-self: center;
  }
  
  .form-input,
  .form-textarea {
    font-size: 16px; /* 防止iOS縮放 */
  }
  
  .upload-placeholder {
    padding: 1.5rem;
  }
  
  .upload-icon {
    font-size: 2rem;
  }
  
  .empty-icon {
    font-size: 3rem;
  }
  
  .success-header {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .action-stats {
    padding: 1rem;
  }
  
  .action-stats .stat-value {
    font-size: 1.125rem;
  }
}

/* 超小屏幕 */
@media (max-width: 360px) {
  .fullscreen-background {
    /* 超小屏幕優化 */
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
  }
  
  .quota-stats {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .type-btn .type-label {
    display: none;
  }
  
  .btn-text {
    display: none;
  }
  
  .cyber-btn {
    min-width: 60px;
    padding: 1rem;
  }
  
  .published-item {
    padding: 0.75rem;
  }
}

/* 載入狀態 */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(10, 10, 15, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  backdrop-filter: blur(5px);
}

.loading-content {
  text-align: center;
  color: #ffffff;
}

.loading-spinner {
  width: 60px;
  height: 60px;
  border: 3px solid rgba(255, 255, 255, 0.2);
  border-top: 3px solid #00d4ff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
  /* GPU 加速優化 */
  transform: translateZ(0);
  will-change: transform;
}

@keyframes spin {
  from { transform: rotate(0deg) translateZ(0); }
  to { transform: rotate(360deg) translateZ(0); }
}

.loading-text {
  font-size: 1.125rem;
  font-weight: 600;
}

/* 錯誤提示 */
.error-toast {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  border: 1px solid #ef4444;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
  z-index: 1080;
  animation: toast-slide-in 0.3s ease-out;
  max-width: 400px;
  backdrop-filter: blur(10px);
}

@keyframes toast-slide-in {
  from {
    transform: translateX(100%) translateZ(0);
    opacity: 0;
  }
  to {
    transform: translateX(0) translateZ(0);
    opacity: 1;
  }
}

/* 高性能GPU加速動畫 */
@keyframes gradient-shift {
  0%, 100% {
    transform: scale(1) rotate(0deg) translateZ(0);
    opacity: 1;
  }
  33% {
    transform: scale(1.1) rotate(2deg) translateZ(0);
    opacity: 0.8;
  }
  66% {
    transform: scale(0.9) rotate(-1deg) translateZ(0);
    opacity: 0.9;
  }
}

@keyframes cyber-scan {
  0% { 
    left: -100%; 
    transform: translateZ(0);
  }
  100% { 
    left: 100%; 
    transform: translateZ(0);
  }
}

@keyframes neon-pulse {
  0% {
    box-shadow: 0 0 5px #00d4ff, 0 0 10px #00d4ff, 0 0 15px #00d4ff;
    transform: translateZ(0);
  }
  100% {
    box-shadow: 0 0 10px #00d4ff, 0 0 20px #00d4ff, 0 0 30px #00d4ff;
    transform: translateZ(0);
  }
}

@keyframes success-bounce {
  0%, 100% { 
    transform: translateY(0) translateZ(0); 
  }
  50% { 
    transform: translateY(-10px) translateZ(0); 
  }
}

@keyframes empty-pulse {
  0%, 100% { 
    opacity: 0.3; 
    transform: translate(-50%, -50%) scale(1) translateZ(0); 
  }
  50% { 
    opacity: 0.6; 
    transform: translate(-50%, -50%) scale(1.1) translateZ(0); 
  }
}

@keyframes success-glow-pulse {
  0%, 100% { 
    transform: scale(1) translateZ(0); 
    opacity: 0.3; 
  }
  50% { 
    transform: scale(1.2) translateZ(0); 
    opacity: 0.1; 
  }
}

/* 高對比度支援 */
@media (prefers-contrast: high) {
  .promotion-item,
  .user-status-card,
  .promotion-create-card {
    border-width: 2px;
  }
  
  .cyber-btn {
    border-width: 3px;
  }
  
  .form-input:focus,
  .form-textarea:focus {
    box-shadow: 0 0 0 3px #00d4ff;
  }
}

/* 性能優化和減少動畫選項 */
@media (prefers-reduced-motion: reduce) {
  .gradient-background,
  .page-header::before,
  .avatar-ring,
  .neon-text,
  .success-icon,
  .empty-glow,
  .success-glow,
  .cyber-btn:before,
  .upload-border {
    animation: none !important;
  }
  
  .progress-ring,
  .cyber-btn,
  .promotion-item {
    transition: none !important;
  }
}

/* 低功率設備動畫優化 */
@media (max-width: 768px) {
  .gradient-background {
    animation-duration: 30s; /* 降低動畫頻率 */
  }
  
  .page-header::before,
  .cyber-btn:before {
    animation-duration: 4s; /* 降低動畫頻率 */
  }
  
  .neon-text {
    animation-duration: 3s; /* 降低動畫頻率 */
  }
}

/* 触摸優化 */
.cyber-btn,
.type-btn,
.remove-btn {
  touch-action: manipulation;
  -webkit-tap-highlight-color: transparent;
}

/* 跨瀏覽器兼容性和供應商前綴 */
.fullscreen-background,
.gradient-background,
.particle-background {
  /* 標準屬性 */
  transform: translateZ(0);
  backface-visibility: hidden;
  
  /* Webkit 前綴 */
  -webkit-transform: translateZ(0);
  -webkit-backface-visibility: hidden;
  
  /* Firefox */
  -moz-backface-visibility: hidden;
  
  /* IE/Edge */
  -ms-backface-visibility: hidden;
}

.cyber-btn,
.promotion-item,
.user-status-card,
.promotion-create-card {
  /* 標準屬性 */
  backdrop-filter: blur(15px);
  
  /* Webkit 前綴 */
  -webkit-backdrop-filter: blur(15px);
}

/* iOS Safari 支援 */
@supports (-webkit-appearance: none) {
  .fullscreen-background {
    height: -webkit-fill-available;
  }
  
  .promote-page {
    min-height: -webkit-fill-available;
  }
}

/* Firefox 支援 */
@-moz-document url-prefix() {
  .fullscreen-background {
    /* Firefox 特定優化 */
    background-attachment: fixed;
  }
}

/* Edge/IE 支援 */
@supports (-ms-ime-align: auto) {
  .gradient-background {
    /* Edge 特定優化 */
    filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
  }
}

/* 打印樣式 */
@media print {
  .fullscreen-background {
    background: white;
    color: black;
    position: static;
    width: auto;
    height: auto;
  }
  
  .promote-page {
    background: white;
    color: black;
  }
  
  .cyber-card {
    border: 1px solid #ccc;
    box-shadow: none;
  }
  
  .particle-background,
  .gradient-background,
  .action-buttons,
  .remove-btn,
  .image-overlay {
    display: none;
  }
}
</style>