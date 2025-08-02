<template>
  <div class="promote-page">
    <!-- 頁面標題 -->
    <div class="page-header">
      <h1 class="page-title">
        <span class="title-icon">📊</span>
        推廣記錄
      </h1>
      <p class="page-description">查看您的推廣活動記錄和統計數據</p>
    </div>

    <!-- 用戶信息 -->
    <div class="user-info-card">
      <div class="user-info">
        <div class="user-details">
          <h3>推廣帳號：{{ gameAccount }}</h3>
          <p>伺服器：{{ serverInfo?.name }}</p>
          <p class="join-date">加入時間：{{ formatDate(new Date()) }}</p>
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
    </div>

    <!-- 推廣記錄表 -->
    <div class="promotion-records-card">
      <div class="card-header">
        <h2>
          <span class="header-icon">📋</span>
          推廣記錄
        </h2>
        <button @click="recordPromotion" class="record-btn" :disabled="isRecording">
          <span class="btn-icon">➕</span>
          {{ isRecording ? '記錄中...' : '記錄推廣' }}
        </button>
      </div>
      
      <div class="records-table">
        <div class="table-header">
          <div class="th">時間</div>
          <div class="th">類型</div>
          <div class="th">狀態</div>
          <div class="th">獎勵</div>
        </div>
        
        <div class="table-body">
          <div 
            v-for="record in promotionRecords" 
            :key="record.id"
            class="table-row"
          >
            <div class="td">{{ formatDateTime(record.createdAt) }}</div>
            <div class="td">
              <span class="record-type" :class="record.type">
                {{ getTypeLabel(record.type) }}
              </span>
            </div>
            <div class="td">
              <span class="record-status" :class="record.status">
                {{ getStatusLabel(record.status) }}
              </span>
            </div>
            <div class="td">
              <span class="reward-amount">{{ record.reward || 0 }}</span>
            </div>
          </div>
          
          <div v-if="promotionRecords.length === 0" class="empty-state">
            <span class="empty-icon">📝</span>
            <p>尚無推廣記錄</p>
            <p class="empty-tip">點擊上方「記錄推廣」按鈕開始記錄您的推廣活動</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 統計圖表 -->
    <div class="statistics-card">
      <div class="card-header">
        <h2>
          <span class="header-icon">📈</span>
          推廣統計
        </h2>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-content">
            <div class="stat-value">{{ todayStats.count }}</div>
            <div class="stat-label">今日推廣</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <div class="stat-value">{{ weekStats.count }}</div>
            <div class="stat-label">本週推廣</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">💰</div>
          <div class="stat-content">
            <div class="stat-value">{{ totalRewards }}</div>
            <div class="stat-label">總獎勵</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">🎯</div>
          <div class="stat-content">
            <div class="stat-value">{{ successRate }}%</div>
            <div class="stat-label">成功率</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

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

// 推廣記錄相關
const isRecording = ref(false)
const promotionRecords = ref<any[]>([])

// 統計數據
const todayStats = ref({ count: 0 })
const weekStats = ref({ count: 0 })

const totalRewards = computed(() => {
  return promotionRecords.value.reduce((sum, record) => sum + (record.reward || 0), 0)
})

const successRate = computed(() => {
  if (promotionRecords.value.length === 0) return 0
  const successCount = promotionRecords.value.filter(record => record.status === 'success').length
  return Math.round((successCount / promotionRecords.value.length) * 100)
})

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

    // 載入推廣記錄
    await loadPromotionRecords()
    
    // 載入統計數據
    await loadStatistics()
  } catch (error) {
    console.error('載入數據失敗:', error)
  }
}

// 載入推廣記錄
const loadPromotionRecords = async () => {
  try {
    // 模擬API調用 - 實際使用時應該從後端API載入
    const mockRecords = [
      {
        id: 1,
        type: 'invitation',
        status: 'success',
        reward: 100,
        createdAt: new Date(Date.now() - 24 * 60 * 60 * 1000)
      },
      {
        id: 2,
        type: 'share',
        status: 'pending',
        reward: 0,
        createdAt: new Date(Date.now() - 2 * 60 * 60 * 1000)
      },
      {
        id: 3,
        type: 'referral',
        status: 'success',
        reward: 200,
        createdAt: new Date(Date.now() - 60 * 60 * 1000)
      }
    ]
    
    promotionRecords.value = mockRecords
  } catch (error) {
    console.error('載入推廣記錄失敗:', error)
  }
}

// 載入統計數據
const loadStatistics = async () => {
  try {
    const today = new Date()
    const todayRecords = promotionRecords.value.filter(record => {
      const recordDate = new Date(record.createdAt)
      return recordDate.toDateString() === today.toDateString()
    })
    
    const oneWeekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
    const weekRecords = promotionRecords.value.filter(record => {
      return new Date(record.createdAt) >= oneWeekAgo
    })
    
    todayStats.value = { count: todayRecords.length }
    weekStats.value = { count: weekRecords.length }
  } catch (error) {
    console.error('載入統計數據失敗:', error)
  }
}

// 記錄推廣活動
const recordPromotion = async () => {
  isRecording.value = true
  
  try {
    // 模擬API調用
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const newRecord = {
      id: Date.now(),
      type: 'manual',
      status: 'success',
      reward: Math.floor(Math.random() * 100) + 50,
      createdAt: new Date()
    }
    
    promotionRecords.value.unshift(newRecord)
    
    // 更新統計
    await loadStatistics()
    
    alert('推廣記錄已成功添加！')
  } catch (error) {
    console.error('記錄推廣失敗:', error)
    alert('記錄推廣失敗')
  } finally {
    isRecording.value = false
  }
}

// 格式化日期
const formatDate = (date: Date) => {
  return date.toLocaleDateString('zh-TW')
}

// 格式化日期時間
const formatDateTime = (date: Date) => {
  return new Date(date).toLocaleString('zh-TW')
}

// 獲取類型標籤
const getTypeLabel = (type: string) => {
  const typeMap: { [key: string]: string } = {
    invitation: '邀請',
    share: '分享',
    referral: '推薦',
    manual: '手動記錄'
  }
  return typeMap[type] || type
}

// 獲取狀態標籤
const getStatusLabel = (status: string) => {
  const statusMap: { [key: string]: string } = {
    success: '成功',
    pending: '處理中',
    failed: '失敗'
  }
  return statusMap[status] || status
}

// 生命週期
onMounted(() => {
  if (!gameAccount) {
    alert('請先輸入遊戲帳號')
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-title {
  font-size: 2.5rem;
  color: white;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.title-icon {
  font-size: 2.5rem;
}

.page-description {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.9);
}

.user-info-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.user-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
}

.user-details h3 {
  color: white;
  margin-bottom: 0.5rem;
  font-size: 1.5rem;
}

.user-details p {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1.1rem;
  margin: 0.3rem 0;
}

.join-date {
  font-size: 0.9rem !important;
  color: rgba(255, 255, 255, 0.6) !important;
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
  color: #00d4ff;
}

.stat-label {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.7);
}

.promotion-records-card,
.statistics-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card-header h2 {
  color: white;
  font-size: 1.5rem;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.header-icon {
  font-size: 1.5rem;
}

.record-btn {
  background: linear-gradient(45deg, #00d4ff, #0099cc);
  color: white;
  border: none;
  padding: 0.8rem 1.5rem;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.record-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 212, 255, 0.3);
}

.record-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-icon {
  font-size: 1.1rem;
}

.records-table {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.table-header {
  background: rgba(255, 255, 255, 0.1);
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem;
  font-weight: 600;
  color: white;
}

.table-body {
  min-height: 200px;
}

.table-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
  transition: background 0.3s ease;
}

.table-row:hover {
  background: rgba(255, 255, 255, 0.05);
}

.table-row:last-child {
  border-bottom: none;
}

.th, .td {
  display: flex;
  align-items: center;
}

.record-type {
  padding: 0.3rem 0.8rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
}

.record-type.invitation {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.record-type.share {
  background: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
}

.record-type.referral {
  background: rgba(168, 85, 247, 0.2);
  color: #a855f7;
}

.record-type.manual {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
}

.record-status {
  padding: 0.3rem 0.8rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
}

.record-status.success {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.record-status.pending {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
}

.record-status.failed {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.reward-amount {
  font-weight: 600;
  color: #00d4ff;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: rgba(255, 255, 255, 0.6);
}

.empty-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
}

.empty-tip {
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 15px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
}

.stat-card:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #00d4ff;
  margin-bottom: 0.3rem;
}

.stat-label {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.7);
}

/* 響應式設計 */
@media (max-width: 768px) {
  .promote-page {
    padding: 1rem;
  }
  
  .user-info {
    flex-direction: column;
    text-align: center;
  }
  
  .user-stats {
    justify-content: center;
  }
  
  .table-header,
  .table-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  
  .th, .td {
    justify-content: space-between;
    padding: 0.5rem 0;
  }
  
  .th::before, .td::before {
    content: attr(data-label);
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>