<template>
  <div class="promote-page">
    <!-- 頁面標題 -->
    <div class="page-header">
      <h1 class="page-title">
        <el-icon><Promotion /></el-icon>
        推廣工具
      </h1>
      <p class="page-description">生成您的專屬推廣連結和素材，邀請好友加入遊戲</p>
    </div>

    <!-- 用戶信息 -->
    <el-card class="user-info-card" shadow="hover">
      <div class="user-info">
        <div class="user-details">
          <h3>推廣帳號：{{ gameAccount }}</h3>
          <p>伺服器：{{ serverInfo?.name }}</p>
        </div>
        <div class="user-stats">
          <div class="stat-item">
            <CountUp :end="promotionStats.total_invites" class="stat-number" />
            <span class="stat-label">累計邀請</span>
          </div>
          <div class="stat-item">
            <CountUp :end="promotionStats.successful_invites" class="stat-number" />
            <span class="stat-label">成功邀請</span>
          </div>
          <div class="stat-item">
            <CountUp :end="promotionStats.total_rewards" class="stat-number" />
            <span class="stat-label">獲得獎勵</span>
          </div>
        </div>
      </div>
    </el-card>

    <!-- 推廣連結生成 -->
    <el-card class="promotion-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <el-icon><Link /></el-icon>
          <span>推廣連結</span>
        </div>
      </template>
      
      <div class="promotion-link-section">
        <div class="link-generator">
          <el-form @submit.prevent="generatePromotionLink" class="generator-form">
            <el-form-item label="連結類型">
              <el-select v-model="linkType" placeholder="請選擇連結類型" style="width: 100%">
                <el-option label="一般推廣" value="general" />
                <el-option label="新手推廣" value="newbie" />
                <el-option label="回歸推廣" value="return" />
              </el-select>
            </el-form-item>
            <el-form-item label="有效期限">
              <el-select v-model="expireDays" placeholder="選擇有效期" style="width: 100%">
                <el-option label="7天" :value="7" />
                <el-option label="30天" :value="30" />
                <el-option label="永久" :value="0" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <GlowButton @click="generatePromotionLink" :loading="isGenerating" style="width: 100%">
                <el-icon><Magic /></el-icon>
                生成推廣連結
              </GlowButton>
            </el-form-item>
          </el-form>
        </div>

        <!-- 生成的連結 -->
        <div v-if="promotionLink" class="generated-link">
          <h4>您的推廣連結：</h4>
          <div class="link-container">
            <el-input 
              v-model="promotionLink" 
              readonly 
              class="link-input"
            >
              <template #append>
                <el-button @click="copyToClipboard(promotionLink)" type="primary">
                  <el-icon><CopyDocument /></el-icon>
                  複製
                </el-button>
              </template>
            </el-input>
          </div>
          <div class="link-actions">
            <el-button @click="generateQRCode" type="success">
              <el-icon><Qrcode /></el-icon>
              生成 QR Code
            </el-button>
            <el-button @click="shareToSocial" type="info">
              <el-icon><Share /></el-icon>
              分享到社群
            </el-button>
          </div>
        </div>
      </div>
    </el-card>

    <!-- 推廣素材 -->
    <el-card class="materials-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <el-icon><Picture /></el-icon>
          <span>推廣素材</span>
        </div>
      </template>

      <div class="materials-section">
        <div class="material-generator">
          <h4>自定義推廣圖片</h4>
          <el-form class="material-form">
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="背景模板">
                  <el-select v-model="selectedTemplate" placeholder="選擇模板" style="width: 100%">
                    <el-option 
                      v-for="template in backgroundTemplates" 
                      :key="template.id"
                      :label="template.name" 
                      :value="template.id" 
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="文字內容">
                  <el-input 
                    v-model="customText" 
                    placeholder="輸入推廣文字"
                    maxlength="50"
                    show-word-limit
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-form-item>
              <GlowButton @click="generatePromotionImage" :loading="isGeneratingImage" style="width: 100%">
                <el-icon><Camera /></el-icon>
                生成推廣圖片
              </GlowButton>
            </el-form-item>
          </el-form>
        </div>

        <!-- 預設素材 -->
        <div class="preset-materials">
          <h4>預設推廣素材</h4>
          <div class="materials-grid">
            <div 
              v-for="material in promotionMaterials" 
              :key="material.id"
              class="material-item"
              @click="downloadMaterial(material)"
            >
              <img :src="material.thumbnail" :alt="material.name" class="material-preview" />
              <div class="material-info">
                <p class="material-name">{{ material.name }}</p>
                <p class="material-size">{{ material.size }}</p>
              </div>
              <div class="material-overlay">
                <el-icon class="download-icon"><Download /></el-icon>
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-card>

    <!-- QR Code 彈窗 -->
    <el-dialog v-model="qrCodeVisible" title="推廣 QR Code" width="400px" center>
      <div class="qr-code-container">
        <div ref="qrCodeRef" class="qr-code"></div>
        <p class="qr-code-tip">掃描此 QR Code 即可直接訪問您的推廣連結</p>
        <el-button @click="downloadQRCode" type="primary" style="width: 100%">
          <el-icon><Download /></el-icon>
          下載 QR Code
        </el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import QRCode from 'qrcode'
import { 
  Promotion, Link, Magic, CopyDocument, Qrcode, Share, 
  Picture, Camera, Download 
} from '@element-plus/icons-vue'

// 組件導入
const GlowButton = defineAsyncComponent(() => import('~/components/common/GlowButton.vue'))
const CountUp = defineAsyncComponent(() => import('~/components/effects/CountUp.vue'))

// 路由和參數
const route = useRoute()
const router = useRouter()
const serverCode = route.params.server as string
const gameAccount = route.query.account as string

// 響應式數據
const serverInfo = ref<any>(null)
const promotionStats = ref({
  total_invites: 0,
  successful_invites: 0,
  total_rewards: 0
})

// 連結生成相關
const linkType = ref('general')
const expireDays = ref(30)
const promotionLink = ref('')
const isGenerating = ref(false)

// QR Code 相關
const qrCodeVisible = ref(false)
const qrCodeRef = ref<HTMLElement>()

// 素材生成相關
const selectedTemplate = ref('')
const customText = ref('')
const isGeneratingImage = ref(false)

// 背景模板
const backgroundTemplates = [
  { id: 'template1', name: '科技風格' },
  { id: 'template2', name: '夢幻風格' },
  { id: 'template3', name: '簡約風格' },
  { id: 'template4', name: '遊戲風格' }
]

// 預設推廣素材
const promotionMaterials = [
  {
    id: 1,
    name: '橫幅廣告',
    size: '728x90',
    thumbnail: '/images/materials/banner1.jpg',
    downloadUrl: '/downloads/banner1.png'
  },
  {
    id: 2,
    name: '方形圖片',
    size: '400x400',
    thumbnail: '/images/materials/square1.jpg',
    downloadUrl: '/downloads/square1.png'
  },
  {
    id: 3,
    name: '直式海報',
    size: '600x800',
    thumbnail: '/images/materials/poster1.jpg',
    downloadUrl: '/downloads/poster1.png'
  }
]

// 頁面元數據
definePageMeta({
  layout: 'default'
})

// 載入數據
const loadData = async () => {
  try {
    // 載入伺服器信息
    serverInfo.value = {
      name: serverCode.toUpperCase() + ' 伺服器'
    }

    // 載入推廣統計
    promotionStats.value = {
      total_invites: Math.floor(Math.random() * 100),
      successful_invites: Math.floor(Math.random() * 50),
      total_rewards: Math.floor(Math.random() * 10000)
    }
  } catch (error) {
    console.error('載入數據失敗:', error)
  }
}

// 生成推廣連結
const generatePromotionLink = async () => {
  isGenerating.value = true
  
  try {
    // 模擬API調用
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const baseUrl = window.location.origin
    const linkId = Math.random().toString(36).substr(2, 9)
    promotionLink.value = `${baseUrl}/${serverCode}?ref=${gameAccount}&type=${linkType.value}&id=${linkId}`
    
    ElMessage.success('推廣連結生成成功！')
  } catch (error) {
    console.error('生成連結失敗:', error)
    ElMessage.error('生成連結失敗')
  } finally {
    isGenerating.value = false
  }
}

// 複製到剪貼板
const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    ElMessage.success('已複製到剪貼板！')
  } catch (error) {
    console.error('複製失敗:', error)
    ElMessage.error('複製失敗')
  }
}

// 生成 QR Code
const generateQRCode = async () => {
  if (!promotionLink.value) {
    ElMessage.warning('請先生成推廣連結')
    return
  }

  qrCodeVisible.value = true
  
  nextTick(async () => {
    if (qrCodeRef.value) {
      try {
        await QRCode.toCanvas(qrCodeRef.value, promotionLink.value, {
          width: 300,
          margin: 2,
          color: {
            dark: '#000000',
            light: '#FFFFFF'
          }
        })
      } catch (error) {
        console.error('生成 QR Code 失敗:', error)
        ElMessage.error('生成 QR Code 失敗')
      }
    }
  })
}

// 下載 QR Code
const downloadQRCode = async () => {
  if (!qrCodeRef.value) return
  
  try {
    const canvas = qrCodeRef.value.querySelector('canvas')
    if (canvas) {
      const link = document.createElement('a')
      link.download = `promotion-qrcode-${gameAccount}.png`
      link.href = canvas.toDataURL()
      link.click()
      ElMessage.success('QR Code 下載成功！')
    }
  } catch (error) {
    console.error('下載失敗:', error)
    ElMessage.error('下載失敗')
  }
}

// 分享到社群
const shareToSocial = () => {
  const shareText = `快來加入 ${serverInfo.value?.name}！使用我的推廣連結獲得額外獎勵：${promotionLink.value}`
  
  if (navigator.share) {
    navigator.share({
      title: '遊戲推廣邀請',
      text: shareText,
      url: promotionLink.value
    })
  } else {
    copyToClipboard(shareText)
    ElMessage.info('分享內容已複製到剪貼板')
  }
}

// 生成推廣圖片
const generatePromotionImage = async () => {
  isGeneratingImage.value = true
  
  try {
    // 模擬圖片生成
    await new Promise(resolve => setTimeout(resolve, 2000))
    ElMessage.success('推廣圖片生成成功！')
  } catch (error) {
    console.error('生成圖片失敗:', error)
    ElMessage.error('生成圖片失敗')
  } finally {
    isGeneratingImage.value = false
  }
}

// 下載推廣素材
const downloadMaterial = (material: any) => {
  const link = document.createElement('a')
  link.href = material.downloadUrl
  link.download = material.name
  link.click()
  ElMessage.success(`${material.name} 下載成功！`)
}

// 生命週期
onMounted(() => {
  if (!gameAccount) {
    ElMessage.warning('請先輸入遊戲帳號')
    // 跳轉回伺服器首頁
    router.push(`/${serverCode}`)
    return
  }
  loadData()
})
</script>

<style scoped>
.promote-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-title {
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.page-description {
  font-size: 1.2rem;
  color: #7f8c8d;
}

.user-info-card {
  margin-bottom: 2rem;
}

.user-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
}

.user-details h3 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.user-details p {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.user-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #3498db;
}

.stat-label {
  font-size: 0.9rem;
  color: #7f8c8d;
}

.promotion-card,
.materials-card {
  margin-bottom: 2rem;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.2rem;
  font-weight: 600;
}

.promotion-link-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.generator-form .el-form-item {
  margin-bottom: 1.5rem;
}

.generated-link {
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 10px;
  border: 2px dashed #3498db;
}

.generated-link h4 {
  margin-bottom: 1rem;
  color: #2c3e50;
}

.link-container {
  margin-bottom: 1rem;
}

.link-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.materials-section {
  space-y: 2rem;
}

.material-generator {
  margin-bottom: 3rem;
}

.material-generator h4,
.preset-materials h4 {
  margin-bottom: 1rem;
  color: #2c3e50;
}

.materials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.material-item {
  position: relative;
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.material-item:hover {
  transform: translateY(-5px);
}

.material-preview {
  width: 100%;
  height: 150px;
  object-fit: cover;
}

.material-info {
  padding: 1rem;
  background: white;
}

.material-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.material-size {
  font-size: 0.9rem;
  color: #7f8c8d;
}

.material-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(52, 152, 219, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.material-item:hover .material-overlay {
  opacity: 1;
}

.download-icon {
  font-size: 2rem;
  color: white;
}

.qr-code-container {
  text-align: center;
}

.qr-code {
  margin-bottom: 1rem;
}

.qr-code-tip {
  color: #7f8c8d;
  margin-bottom: 1rem;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .promote-page {
    padding: 1rem;
  }
  
  .promotion-link-section {
    grid-template-columns: 1fr;
  }
  
  .user-info {
    flex-direction: column;
    text-align: center;
  }
  
  .user-stats {
    justify-content: center;
  }
  
  .link-actions {
    flex-direction: column;
  }
}
</style>