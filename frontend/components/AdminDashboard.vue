<template>
  <div class="admin-dashboard">
    <!-- 頁面標題 -->
    <div class="dashboard-header">
      <div class="header-content">
        <h1 class="page-title">管理儀表板</h1>
        <p class="page-subtitle">歡迎回來，掌控您的推廣平台</p>
      </div>
      <div class="header-actions">
        <button class="admin-btn admin-btn-secondary">
          <UIcon name="i-heroicons-arrow-down-tray" />
          匯出報告
        </button>
        <button class="admin-btn admin-btn-primary">
          <UIcon name="i-heroicons-plus" />
          新增伺服器
        </button>
      </div>
    </div>

    <!-- 統計卡片網格 -->
    <div class="stats-grid">
      <div class="stats-card" v-for="stat in statistics" :key="stat.id">
        <div class="stats-icon" :style="{ backgroundColor: stat.color + '20', color: stat.color }">
          <UIcon :name="stat.icon" />
        </div>
        <div class="stats-content">
          <div class="stats-value">{{ formatNumber(stat.value) }}</div>
          <div class="stats-label">{{ stat.label }}</div>
          <div class="stats-change" :class="stat.change >= 0 ? 'positive' : 'negative'">
            <UIcon :name="stat.change >= 0 ? 'i-heroicons-arrow-trending-up' : 'i-heroicons-arrow-trending-down'" />
            {{ Math.abs(stat.change) }}%
            <span class="change-period">較上月</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 主要內容區域 -->
    <div class="dashboard-content">
      <!-- 圖表區域 -->
      <div class="dashboard-grid">
        <!-- 推廣趨勢圖表 -->
        <div class="admin-card chart-container">
          <div class="card-header">
            <h3 class="card-title">推廣趨勢</h3>
            <div class="card-actions">
              <div class="admin-tabs chart-tabs">
                <button 
                  v-for="period in chartPeriods" 
                  :key="period.value"
                  class="admin-tab"
                  :class="{ active: selectedPeriod === period.value }"
                  @click="selectedPeriod = period.value"
                >
                  {{ period.label }}
                </button>
              </div>
            </div>
          </div>
          <div class="chart-area">
            <!-- 這裡放置圖表組件 -->
            <div class="chart-placeholder">
              <UIcon name="i-heroicons-chart-bar" class="chart-icon" />
              <p>推廣數據圖表</p>
            </div>
          </div>
        </div>

        <!-- 活動統計 -->
        <div class="admin-card">
          <div class="card-header">
            <h3 class="card-title">活動統計</h3>
            <button class="admin-btn admin-btn-secondary btn-sm">
              <UIcon name="i-heroicons-eye" />
              查看全部
            </button>
          </div>
          <div class="activity-list">
            <div 
              v-for="activity in recentActivities" 
              :key="activity.id"
              class="activity-item"
            >
              <div class="activity-icon" :style="{ backgroundColor: activity.color + '20', color: activity.color }">
                <UIcon :name="activity.icon" />
              </div>
              <div class="activity-content">
                <div class="activity-title">{{ activity.title }}</div>
                <div class="activity-description">{{ activity.description }}</div>
                <div class="activity-time">{{ activity.time }}</div>
              </div>
              <div class="activity-status">
                <span class="status-indicator" :class="activity.status">
                  <span class="status-dot"></span>
                  {{ getStatusText(activity.status) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 快速操作和最新數據 -->
      <div class="dashboard-bottom">
        <!-- 快速操作 -->
        <div class="admin-card quick-actions">
          <div class="card-header">
            <h3 class="card-title">快速操作</h3>
          </div>
          <div class="actions-grid">
            <button 
              v-for="action in quickActions" 
              :key="action.id"
              class="action-button"
              @click="handleQuickAction(action.id)"
            >
              <div class="action-icon" :style="{ backgroundColor: action.color + '20', color: action.color }">
                <UIcon :name="action.icon" />
              </div>
              <div class="action-content">
                <div class="action-title">{{ action.title }}</div>
                <div class="action-description">{{ action.description }}</div>
              </div>
            </button>
          </div>
        </div>

        <!-- 最新伺服器 -->
        <div class="admin-card latest-servers">
          <div class="card-header">
            <h3 class="card-title">最新伺服器</h3>
            <button class="admin-btn admin-btn-secondary btn-sm">
              <UIcon name="i-heroicons-plus" />
              管理伺服器
            </button>
          </div>
          <div class="servers-list">
            <div 
              v-for="server in latestServers" 
              :key="server.id"
              class="server-item"
            >
              <div class="server-avatar">
                <img v-if="server.logo" :src="server.logo" :alt="server.name" />
                <div v-else class="avatar-placeholder">
                  <UIcon name="i-heroicons-server" />
                </div>
              </div>
              <div class="server-info">
                <div class="server-name">{{ server.name }}</div>
                <div class="server-type">{{ server.gameType }}</div>
              </div>
              <div class="server-stats">
                <div class="stat-item">
                  <span class="stat-value">{{ server.players }}</span>
                  <span class="stat-label">玩家</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value">{{ server.promotions }}</span>
                  <span class="stat-label">推廣</span>
                </div>
              </div>
              <div class="server-status">
                <span class="status-indicator" :class="server.status">
                  <span class="status-dot"></span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// 響應式數據
const selectedPeriod = ref('7d')

// 統計數據
const statistics = ref([
  {
    id: 1,
    label: '總註冊伺服器',
    value: 156,
    change: 12,
    icon: 'i-heroicons-server',
    color: '#6366f1'
  },
  {
    id: 2,
    label: '活躍用戶',
    value: 23480,
    change: 8,
    icon: 'i-heroicons-users',
    color: '#10b981'
  },
  {
    id: 3,
    label: '本月推廣',
    value: 8920,
    change: -3,
    icon: 'i-heroicons-megaphone',
    color: '#f59e0b'
  },
  {
    id: 4,
    label: '總收益',
    value: 145280,
    change: 25,
    icon: 'i-heroicons-currency-dollar',
    color: '#ef4444'
  }
])

// 圖表時間週期
const chartPeriods = ref([
  { label: '7天', value: '7d' },
  { label: '30天', value: '30d' },
  { label: '90天', value: '90d' },
  { label: '1年', value: '1y' }
])

// 最近活動
const recentActivities = ref([
  {
    id: 1,
    title: '新伺服器註冊',
    description: 'DragonCraft 伺服器已成功註冊',
    time: '2分鐘前',
    icon: 'i-heroicons-server',
    color: '#6366f1',
    status: 'success'
  },
  {
    id: 2,
    title: '推廣活動完成',
    description: '春季推廣活動已結束，共獲得 1,250 名新玩家',
    time: '1小時前',
    icon: 'i-heroicons-trophy',
    color: '#10b981',
    status: 'success'
  },
  {
    id: 3,
    title: '系統維護通知',
    description: '系統將於今晚 23:00 進行例行維護',
    time: '3小時前',
    icon: 'i-heroicons-wrench-screwdriver',
    color: '#f59e0b',
    status: 'warning'
  },
  {
    id: 4,
    title: '安全警告',
    description: '檢測到異常登入嘗試，已自動封鎖',
    time: '5小時前',
    icon: 'i-heroicons-shield-exclamation',
    color: '#ef4444',
    status: 'danger'
  }
])

// 快速操作
const quickActions = ref([
  {
    id: 'add-server',
    title: '新增伺服器',
    description: '註冊新的遊戲伺服器',
    icon: 'i-heroicons-plus-circle',
    color: '#6366f1'
  },
  {
    id: 'create-promotion',
    title: '建立推廣',
    description: '開始新的推廣活動',
    icon: 'i-heroicons-megaphone',
    color: '#10b981'
  },
  {
    id: 'view-analytics',
    title: '查看分析',
    description: '檢視詳細數據報告',
    icon: 'i-heroicons-chart-bar',
    color: '#f59e0b'
  },
  {
    id: 'manage-users',
    title: '用戶管理',
    description: '管理平台用戶',
    icon: 'i-heroicons-user-group',
    color: '#ef4444'
  }
])

// 最新伺服器
const latestServers = ref([
  {
    id: 1,
    name: 'DragonCraft',
    gameType: 'Minecraft',
    logo: null,
    players: 156,
    promotions: 12,
    status: 'success'
  },
  {
    id: 2,
    name: 'SkyBlock Pro',
    gameType: 'Minecraft',
    logo: null,
    players: 89,
    promotions: 8,
    status: 'success'
  },
  {
    id: 3,
    name: 'PvP Arena',
    gameType: 'Minecraft',
    logo: null,
    players: 234,
    promotions: 15,
    status: 'warning'
  }
])

// 方法
const formatNumber = (value) => {
  return new Intl.NumberFormat('zh-TW').format(value)
}

const getStatusText = (status) => {
  const statusMap = {
    success: '正常',
    warning: '警告',
    danger: '錯誤',
    info: '資訊'
  }
  return statusMap[status] || '未知'
}

const handleQuickAction = (actionId) => {
  console.log('Quick action:', actionId)
  // 這裡添加快速操作的邏輯
}
</script>

<style scoped>
.admin-dashboard {
  padding: var(--content-padding);
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.header-content p {
  color: var(--text-secondary);
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stats-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px;
}

.stats-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}

.stats-content {
  flex: 1;
}

.change-period {
  color: var(--text-muted);
  font-size: 11px;
}

.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

.chart-container {
  padding: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary);
}

.chart-tabs {
  margin: 0;
  border: none;
}

.chart-tabs .admin-tab {
  padding: 6px 12px;
  font-size: 12px;
}

.chart-area {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
}

.chart-placeholder {
  text-align: center;
  color: var(--text-muted);
}

.chart-icon {
  font-size: 48px;
  margin-bottom: 8px;
  opacity: 0.5;
}

.activity-list {
  padding: 16px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-secondary);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 4px;
}

.activity-description {
  color: var(--text-secondary);
  font-size: 12px;
  margin-bottom: 4px;
}

.activity-time {
  color: var(--text-muted);
  font-size: 11px;
}

.dashboard-bottom {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.quick-actions {
  padding: 24px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-primary);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  text-align: left;
}

.action-button:hover {
  background: var(--bg-primary);
  border-color: var(--primary-500);
  transform: translateY(-1px);
}

.action-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.action-title {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 2px;
}

.action-description {
  color: var(--text-secondary);
  font-size: 12px;
}

.latest-servers {
  padding: 24px;
}

.servers-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.server-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.server-item:hover {
  background: var(--bg-primary);
  box-shadow: var(--shadow-sm);
}

.server-avatar {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--border-primary);
  flex-shrink: 0;
}

.server-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-tertiary);
  color: var(--text-muted);
  font-size: 20px;
}

.server-info {
  flex: 1;
}

.server-name {
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 2px;
}

.server-type {
  color: var(--text-secondary);
  font-size: 12px;
}

.server-stats {
  display: flex;
  gap: 16px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
  line-height: 1;
}

.stat-label {
  color: var(--text-muted);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.server-status {
  flex-shrink: 0;
}

/* 響應式設計 */
@media (max-width: 1200px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  
  .dashboard-bottom {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .header-actions {
    flex-direction: column;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .server-stats {
    flex-direction: column;
    gap: 8px;
  }
}

/* 深色模式適配 */
.dark .chart-area {
  background: var(--dark-bg-secondary);
}

.dark .action-button {
  background: var(--dark-bg-secondary);
  border-color: var(--dark-border-primary);
}

.dark .action-button:hover {
  background: var(--dark-bg-card);
}

.dark .server-item {
  background: var(--dark-bg-secondary);
}

.dark .server-item:hover {
  background: var(--dark-bg-card);
}

.dark .avatar-placeholder {
  background: var(--dark-bg-tertiary);
}
</style>