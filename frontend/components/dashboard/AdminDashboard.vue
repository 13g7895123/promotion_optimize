<template>
  <div class="admin-dashboard">
    <!-- Overview Cards -->
    <el-row :gutter="24" class="overview-cards">
      <el-col :xs="24" :sm="12" :md="6" v-for="card in overviewCards" :key="card.title">
        <el-card class="overview-card" :class="card.type">
          <div class="card-content">
            <div class="card-icon">
              <el-icon :size="32">
                <component :is="card.icon" />
              </el-icon>
            </div>
            <div class="card-info">
              <div class="card-value">{{ card.value }}</div>
              <div class="card-title">{{ card.title }}</div>
              <div class="card-trend" :class="{ 'positive': card.trend > 0, 'negative': card.trend < 0 }">
                <el-icon>
                  <component :is="card.trend > 0 ? 'ArrowUp' : 'ArrowDown'" />
                </el-icon>
                {{ Math.abs(card.trend) }}%
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Charts and Analytics -->
    <el-row :gutter="24" class="analytics-section">
      <el-col :xs="24" :lg="16">
        <el-card class="chart-card">
          <template #header>
            <div class="card-header">
              <span>用戶活躍度統計</span>
              <el-select v-model="chartPeriod" size="small">
                <el-option label="近7天" value="7d" />
                <el-option label="近30天" value="30d" />
                <el-option label="近3個月" value="3m" />
              </el-select>
            </div>
          </template>
          <div class="chart-container">
            <!-- Chart would be rendered here -->
            <div class="chart-placeholder">
              <el-icon size="48"><TrendCharts /></el-icon>
              <p>用戶活躍度圖表</p>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :xs="24" :lg="8">
        <el-card class="stats-card">
          <template #header>
            <span>系統狀態</span>
          </template>
          <div class="system-stats">
            <div class="stat-item" v-for="stat in systemStats" :key="stat.name">
              <div class="stat-label">{{ stat.name }}</div>
              <div class="stat-value" :class="stat.status">
                <el-icon>
                  <component :is="stat.icon" />
                </el-icon>
                {{ stat.value }}
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Recent Activities and Pending Actions -->
    <el-row :gutter="24" class="activity-section">
      <el-col :xs="24" :lg="12">
        <el-card class="activity-card">
          <template #header>
            <div class="card-header">
              <span>待處理事項</span>
              <el-badge :value="pendingItems.length" :hidden="pendingItems.length === 0">
                <el-button size="small" @click="refreshPendingItems">
                  <el-icon><Refresh /></el-icon>
                </el-button>
              </el-badge>
            </div>
          </template>
          <div class="pending-list">
            <div 
              v-for="item in pendingItems.slice(0, 5)" 
              :key="item.id"
              class="pending-item"
              @click="handlePendingItem(item)"
            >
              <div class="item-icon" :class="item.type">
                <el-icon>
                  <component :is="item.icon" />
                </el-icon>
              </div>
              <div class="item-content">
                <div class="item-title">{{ item.title }}</div>
                <div class="item-description">{{ item.description }}</div>
                <div class="item-time">{{ formatRelativeTime(item.created_at) }}</div>
              </div>
              <div class="item-action">
                <el-button size="small" type="primary">處理</el-button>
              </div>
            </div>
            <div v-if="pendingItems.length === 0" class="no-pending">
              <el-icon size="32"><CircleCheck /></el-icon>
              <p>太棒了！沒有待處理事項</p>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :xs="24" :lg="12">
        <el-card class="activity-card">
          <template #header>
            <span>最近活動</span>
          </template>
          <div class="recent-activities">
            <el-timeline>
              <el-timeline-item
                v-for="activity in recentActivities.slice(0, 6)"
                :key="activity.id"
                :timestamp="formatTime(activity.created_at)"
                :type="activity.type"
              >
                <div class="activity-content">
                  <div class="activity-title">{{ activity.title }}</div>
                  <div class="activity-description">{{ activity.description }}</div>
                </div>
              </el-timeline-item>
            </el-timeline>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Quick Access -->
    <el-row :gutter="24" class="quick-access-section">
      <el-col :span="24">
        <el-card>
          <template #header>
            <span>快速操作</span>
          </template>
          <div class="quick-access-grid">
            <div 
              v-for="action in quickActions" 
              :key="action.name"
              class="quick-action-item"
              @click="handleQuickAction(action)"
            >
              <div class="action-icon" :class="action.color">
                <el-icon size="24">
                  <component :is="action.icon" />
                </el-icon>
              </div>
              <div class="action-content">
                <div class="action-title">{{ action.title }}</div>
                <div class="action-description">{{ action.description }}</div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import {
  UserFilled,
  Monitor,
  Document,
  DataAnalysis,
  TrendCharts,
  Refresh,
  CircleCheck,
  ArrowUp,
  ArrowDown,
  Management,
  Setting,
  Files,
  Bell
} from '@element-plus/icons-vue'

// Reactive data
const chartPeriod = ref('7d')

// Mock data - would be replaced with API calls
const overviewCards = ref([
  {
    title: '總用戶數',
    value: '2,847',
    trend: 12.5,
    icon: 'UserFilled',
    type: 'primary'
  },
  {
    title: '活躍伺服器',
    value: '156',
    trend: -2.3,
    icon: 'Monitor',
    type: 'success'
  },
  {
    title: '待審核項目',
    value: '23',
    trend: 8.7,
    icon: 'Document',
    type: 'warning'
  },
  {
    title: '系統負載',
    value: '67%',
    trend: -5.2,
    icon: 'DataAnalysis',
    type: 'info'
  }
])

const systemStats = ref([
  {
    name: 'API 響應時間',
    value: '125ms',
    status: 'good',
    icon: 'TrendCharts'
  },
  {
    name: '資料庫連接',
    value: '正常',
    status: 'good',
    icon: 'CircleCheck'
  },
  {
    name: 'Redis 狀態',
    value: '正常',
    status: 'good',
    icon: 'CircleCheck'
  },
  {
    name: '磁碟使用率',
    value: '78%',
    status: 'warning',
    icon: 'DataAnalysis'
  }
])

const pendingItems = ref([
  {
    id: 1,
    title: '伺服器註冊申請',
    description: 'DragonCraft 伺服器申請註冊',
    type: 'server',
    icon: 'Monitor',
    created_at: new Date(Date.now() - 2 * 60 * 60 * 1000)
  },
  {
    id: 2,
    title: '推廣內容審核',
    description: '用戶 PlayerX 的推廣內容需要審核',
    type: 'promotion',
    icon: 'Document',
    created_at: new Date(Date.now() - 5 * 60 * 60 * 1000)
  }
])

const recentActivities = ref([
  {
    id: 1,
    title: '新用戶註冊',
    description: 'GamerPro 加入了平台',
    type: 'success',
    created_at: new Date(Date.now() - 30 * 60 * 1000)
  },
  {
    id: 2,
    title: '伺服器狀態更新',
    description: 'SkyBlock 伺服器狀態更新為活躍',
    type: 'info',
    created_at: new Date(Date.now() - 1 * 60 * 60 * 1000)
  }
])

const quickActions = ref([
  {
    name: 'user-management',
    title: '用戶管理',
    description: '管理系統用戶和權限',
    icon: 'UserFilled',
    color: 'primary'
  },
  {
    name: 'server-management',
    title: '伺服器管理',
    description: '審核和管理伺服器',
    icon: 'Monitor',
    color: 'success'
  },
  {
    name: 'system-settings',
    title: '系統設定',
    description: '配置系統參數',
    icon: 'Setting',
    color: 'warning'
  },
  {
    name: 'system-logs',
    title: '系統日誌',
    description: '查看系統運行日誌',
    icon: 'Files',
    color: 'info'
  }
])

// Methods
const formatRelativeTime = (date: Date): string => {
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const minutes = Math.floor(diff / (1000 * 60))
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (minutes < 60) return `${minutes} 分鐘前`
  if (hours < 24) return `${hours} 小時前`
  return `${days} 天前`
}

const formatTime = (date: Date): string => {
  return new Intl.DateTimeFormat('zh-TW', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const refreshPendingItems = () => {
  // TODO: Implement API call to refresh pending items
  ElMessage.success('刷新成功')
}

const handlePendingItem = (item: any) => {
  // Navigate to appropriate page based on item type
  if (item.type === 'server') {
    navigateTo('/admin/servers')
  } else if (item.type === 'promotion') {
    navigateTo('/reviewer/promotions')
  }
}

const handleQuickAction = (action: any) => {
  switch (action.name) {
    case 'user-management':
      navigateTo('/admin/users')
      break
    case 'server-management':
      navigateTo('/admin/servers')
      break
    case 'system-settings':
      navigateTo('/admin/settings')
      break
    case 'system-logs':
      navigateTo('/admin/logs')
      break
  }
}
</script>

<style scoped>
.admin-dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.overview-cards {
  margin-bottom: 0;
}

.overview-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.overview-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.card-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.card-icon {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.overview-card.primary .card-icon {
  background: var(--el-color-primary-light-8);
  color: var(--el-color-primary);
}

.overview-card.success .card-icon {
  background: var(--el-color-success-light-8);
  color: var(--el-color-success);
}

.overview-card.warning .card-icon {
  background: var(--el-color-warning-light-8);
  color: var(--el-color-warning);
}

.overview-card.info .card-icon {
  background: var(--el-color-info-light-8);
  color: var(--el-color-info);
}

.card-info {
  flex: 1;
}

.card-value {
  font-size: 28px;
  font-weight: 700;
  color: var(--el-text-color-primary);
  margin-bottom: 4px;
}

.card-title {
  font-size: 14px;
  color: var(--el-text-color-secondary);
  margin-bottom: 8px;
}

.card-trend {
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.card-trend.positive {
  color: var(--el-color-success);
}

.card-trend.negative {
  color: var(--el-color-danger);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chart-container {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chart-placeholder {
  text-align: center;
  color: var(--el-text-color-secondary);
}

.chart-placeholder p {
  margin: 16px 0 0;
  font-size: 16px;
}

.system-stats {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-label {
  font-size: 14px;
  color: var(--el-text-color-secondary);
}

.stat-value {
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
}

.stat-value.good {
  color: var(--el-color-success);
}

.stat-value.warning {
  color: var(--el-color-warning);
}

.stat-value.danger {
  color: var(--el-color-danger);
}

.pending-list {
  max-height: 400px;
  overflow-y: auto;
}

.pending-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.pending-item:hover {
  background: var(--el-bg-color-page);
}

.item-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-icon.server {
  background: var(--el-color-success-light-8);
  color: var(--el-color-success);
}

.item-icon.promotion {
  background: var(--el-color-warning-light-8);
  color: var(--el-color-warning);
}

.item-content {
  flex: 1;
}

.item-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  margin-bottom: 4px;
}

.item-description {
  font-size: 12px;
  color: var(--el-text-color-secondary);
  margin-bottom: 4px;
}

.item-time {
  font-size: 11px;
  color: var(--el-text-color-placeholder);
}

.no-pending {
  text-align: center;
  padding: 40px 20px;
  color: var(--el-text-color-secondary);
}

.no-pending p {
  margin: 16px 0 0;
  font-size: 14px;
}

.recent-activities {
  max-height: 400px;
  overflow-y: auto;
}

.activity-content {
  padding-left: 8px;
}

.activity-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  margin-bottom: 4px;
}

.activity-description {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.quick-access-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}

.quick-action-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 1px solid var(--el-border-color-light);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-action-item:hover {
  border-color: var(--el-color-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-icon.primary {
  background: var(--el-color-primary-light-8);
  color: var(--el-color-primary);
}

.action-icon.success {
  background: var(--el-color-success-light-8);
  color: var(--el-color-success);
}

.action-icon.warning {
  background: var(--el-color-warning-light-8);
  color: var(--el-color-warning);
}

.action-icon.info {
  background: var(--el-color-info-light-8);
  color: var(--el-color-info);
}

.action-content {
  flex: 1;
}

.action-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  margin-bottom: 4px;
}

.action-description {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .card-content {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .quick-access-grid {
    grid-template-columns: 1fr;
  }
  
  .pending-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .item-action {
    align-self: stretch;
  }
}
</style>