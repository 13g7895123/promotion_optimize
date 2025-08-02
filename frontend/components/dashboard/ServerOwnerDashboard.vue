<template>
  <div class="server-owner-dashboard">
    <!-- Server Overview -->
    <el-row :gutter="24" class="server-overview">
      <el-col :xs="24" :sm="12" :md="8" v-for="stat in serverStats" :key="stat.title">
        <el-card class="stat-card" :class="stat.type">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon :size="28">
                <component :is="stat.icon" />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stat.value }}</div>
              <div class="stat-title">{{ stat.title }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Main Content Grid -->
    <el-row :gutter="24" class="main-content">
      <!-- Server Management -->
      <el-col :xs="24" :lg="12">
        <el-card class="server-management-card">
          <template #header>
            <div class="card-header">
              <span>我的伺服器</span>
              <el-button size="small" type="primary" @click="addNewServer">
                <el-icon><Plus /></el-icon>
                新增伺服器
              </el-button>
            </div>
          </template>
          <div class="server-list">
            <div 
              v-for="server in myServers" 
              :key="server.id"
              class="server-item"
              @click="goToServer(server.id)"
            >
              <div class="server-avatar">
                <img v-if="server.logo" :src="server.logo" :alt="server.name" />
                <el-icon v-else size="24"><Monitor /></el-icon>
              </div>
              <div class="server-info">
                <div class="server-name">{{ server.name }}</div>
                <div class="server-details">
                  <el-tag :type="getStatusType(server.status)" size="small">
                    {{ getStatusText(server.status) }}
                  </el-tag>
                  <span class="server-players">{{ server.online_players }}/{{ server.max_players }} 玩家</span>
                </div>
              </div>
              <div class="server-actions">
                <el-dropdown @command="(cmd) => handleServerAction(cmd, server)">
                  <el-button size="small" circle>
                    <el-icon><MoreFilled /></el-icon>
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="edit">編輯</el-dropdown-item>
                      <el-dropdown-item command="settings">設定</el-dropdown-item>
                      <el-dropdown-item command="promotion">推廣</el-dropdown-item>
                      <el-dropdown-item divided command="disable" v-if="server.status === 'active'">
                        停用
                      </el-dropdown-item>
                      <el-dropdown-item command="enable" v-else>啟用</el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>
            </div>
            
            <div v-if="myServers.length === 0" class="no-servers">
              <el-icon size="48"><Monitor /></el-icon>
              <p>還沒有註冊任何伺服器</p>
              <el-button type="primary" @click="addNewServer">
                註冊第一個伺服器
              </el-button>
            </div>
          </div>
        </el-card>
      </el-col>

      <!-- Promotion Statistics -->
      <el-col :xs="24" :lg="12">
        <el-card class="promotion-stats-card">
          <template #header>
            <div class="card-header">
              <span>推廣統計</span>
              <el-select v-model="statsPeriod" size="small">
                <el-option label="本週" value="week" />
                <el-option label="本月" value="month" />
                <el-option label="近3個月" value="quarter" />
              </el-select>
            </div>
          </template>
          <div class="promotion-stats">
            <div class="stats-summary">
              <div class="summary-item" v-for="item in promotionSummary" :key="item.name">
                <div class="summary-value" :class="item.type">{{ item.value }}</div>
                <div class="summary-label">{{ item.label }}</div>
              </div>
            </div>
            
            <el-divider />
            
            <div class="recent-promotions">
              <h4>最近推廣活動</h4>
              <div class="promotion-list">
                <div 
                  v-for="promotion in recentPromotions" 
                  :key="promotion.id"
                  class="promotion-item"
                >
                  <div class="promotion-info">
                    <div class="promotion-title">{{ promotion.title }}</div>
                    <div class="promotion-details">
                      <el-tag :type="getPromotionStatusType(promotion.status)" size="small">
                        {{ getPromotionStatusText(promotion.status) }}
                      </el-tag>
                      <span class="promotion-clicks">{{ promotion.clicks }} 次點擊</span>
                    </div>
                  </div>
                  <div class="promotion-reward">
                    +{{ promotion.reward_points }} 積分
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Quick Actions -->
    <el-row :gutter="24" class="quick-actions-section">
      <el-col :span="24">
        <el-card>
          <template #header>
            <span>快速操作</span>
          </template>
          <div class="quick-actions-grid">
            <div 
              v-for="action in quickActions" 
              :key="action.name"
              class="quick-action-item"
              @click="handleQuickAction(action)"
            >
              <div class="action-icon" :class="action.color">
                <el-icon size="20">
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
  Monitor,
  Share,
  TrophyBase,
  Plus,
  MoreFilled,
  Setting,
  DataAnalysis,
  Promotion,
  Calendar
} from '@element-plus/icons-vue'

// Reactive data
const statsPeriod = ref('month')

// Mock data
const serverStats = ref([
  {
    title: '活躍伺服器',
    value: '3',
    icon: 'Monitor',
    type: 'primary'
  },
  {
    title: '總推廣次數',
    value: '1,247',
    icon: 'Share',
    type: 'success'
  },
  {
    title: '獲得積分',
    value: '15,680',
    icon: 'TrophyBase',
    type: 'warning'
  }
])

const myServers = ref([
  {
    id: 1,
    name: 'DragonCraft RPG',
    logo: null,
    status: 'active',
    online_players: 127,
    max_players: 200,
    version: '1.20.1'
  },
  {
    id: 2,
    name: 'SkyBlock Paradise',
    logo: null,
    status: 'maintenance',
    online_players: 0,
    max_players: 150,
    version: '1.19.4'
  },
  {
    id: 3,
    name: 'PvP Arena',
    logo: null,
    status: 'active',
    online_players: 45,
    max_players: 100,
    version: '1.20.1'
  }
])

const promotionSummary = ref([
  {
    name: 'total_clicks',
    label: '總點擊數',
    value: '2,847',
    type: 'primary'
  },
  {
    name: 'new_players',
    label: '新加入玩家',
    value: '156',
    type: 'success'
  },
  {
    name: 'reward_points',
    label: '獲得積分',
    value: '4,230',
    type: 'warning'
  }
])

const recentPromotions = ref([
  {
    id: 1,
    title: 'DragonCraft 新手推廣',
    status: 'active',
    clicks: 234,
    reward_points: 1170
  },
  {
    id: 2,
    title: 'SkyBlock 特殊活動',
    status: 'pending',
    clicks: 89,
    reward_points: 445
  },
  {
    id: 3,
    title: 'PvP 週末活動',
    status: 'completed',
    clicks: 156,
    reward_points: 780
  }
])

const quickActions = ref([
  {
    name: 'create-promotion',
    title: '建立推廣',
    description: '為伺服器建立新的推廣活動',
    icon: 'Promotion',
    color: 'primary'
  },
  {
    name: 'server-settings',
    title: '伺服器設定',
    description: '配置伺服器連接和獎勵',
    icon: 'Setting',
    color: 'success'
  },
  {
    name: 'view-analytics',
    title: '查看分析',
    description: '檢視詳細的推廣數據分析',
    icon: 'DataAnalysis',
    color: 'info'
  },
  {
    name: 'manage-events',
    title: '活動管理',
    description: '管理伺服器活動和獎勵',
    icon: 'Calendar',
    color: 'warning'
  }
])

// Methods
const getStatusType = (status: string): string => {
  const statusMap: Record<string, string> = {
    'active': 'success',
    'maintenance': 'warning',
    'inactive': 'danger',
    'pending': 'info'
  }
  return statusMap[status] || 'info'
}

const getStatusText = (status: string): string => {
  const statusMap: Record<string, string> = {
    'active': '運行中',
    'maintenance': '維護中',
    'inactive': '已停用',
    'pending': '待審核'
  }
  return statusMap[status] || status
}

const getPromotionStatusType = (status: string): string => {
  const statusMap: Record<string, string> = {
    'active': 'success',
    'pending': 'warning',
    'completed': 'info',
    'rejected': 'danger'
  }
  return statusMap[status] || 'info'
}

const getPromotionStatusText = (status: string): string => {
  const statusMap: Record<string, string> = {
    'active': '進行中',
    'pending': '審核中',
    'completed': '已完成',
    'rejected': '已拒絕'
  }
  return statusMap[status] || status
}

const addNewServer = () => {
  navigateTo('/server/register')
}

const goToServer = (serverId: number) => {
  navigateTo(`/server/manage/${serverId}`)
}

const handleServerAction = (command: string, server: any) => {
  switch (command) {
    case 'edit':
      navigateTo(`/server/edit/${server.id}`)
      break
    case 'settings':
      navigateTo(`/server/settings/${server.id}`)
      break
    case 'promotion':
      navigateTo(`/promotion/create?server=${server.id}`)
      break
    case 'disable':
      // TODO: Implement server disable
      ElMessage.success('伺服器已停用')
      break
    case 'enable':
      // TODO: Implement server enable
      ElMessage.success('伺服器已啟用')
      break
  }
}

const handleQuickAction = (action: any) => {
  switch (action.name) {
    case 'create-promotion':
      navigateTo('/promotion/tools')
      break
    case 'server-settings':
      navigateTo('/server/settings')
      break
    case 'view-analytics':
      navigateTo('/reports/promotions')
      break
    case 'manage-events':
      navigateTo('/events/list')
      break
  }
}
</script>

<style scoped>
.server-owner-dashboard {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.stat-card {
  transition: transform 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-card.primary .stat-icon {
  background: var(--el-color-primary-light-8);
  color: var(--el-color-primary);
}

.stat-card.success .stat-icon {
  background: var(--el-color-success-light-8);
  color: var(--el-color-success);
}

.stat-card.warning .stat-icon {
  background: var(--el-color-warning-light-8);
  color: var(--el-color-warning);
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--el-text-color-primary);
  margin-bottom: 4px;
}

.stat-title {
  font-size: 14px;
  color: var(--el-text-color-secondary);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.server-list {
  max-height: 400px;
  overflow-y: auto;
}

.server-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
  border: 1px solid transparent;
}

.server-item:hover {
  background: var(--el-bg-color-page);
  border-color: var(--el-border-color);
}

.server-avatar {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  background: var(--el-color-info-light-8);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--el-color-info);
  overflow: hidden;
}

.server-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.server-info {
  flex: 1;
}

.server-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  margin-bottom: 8px;
}

.server-details {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.server-players {
  color: var(--el-text-color-regular);
}

.no-servers {
  text-align: center;
  padding: 60px 20px;
  color: var(--el-text-color-secondary);
}

.no-servers p {
  margin: 16px 0 24px;
  font-size: 16px;
}

.promotion-stats {
  height: 400px;
  overflow-y: auto;
}

.stats-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.summary-item {
  text-align: center;
  padding: 16px;
  border-radius: 8px;
  background: var(--el-bg-color-page);
}

.summary-value {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 4px;
}

.summary-value.primary {
  color: var(--el-color-primary);
}

.summary-value.success {
  color: var(--el-color-success);
}

.summary-value.warning {
  color: var(--el-color-warning);
}

.summary-label {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.recent-promotions h4 {
  margin: 0 0 16px;
  font-size: 14px;
  color: var(--el-text-color-primary);
}

.promotion-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.promotion-item:last-child {
  border-bottom: none;
}

.promotion-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  margin-bottom: 8px;
}

.promotion-details {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}

.promotion-clicks {
  color: var(--el-text-color-secondary);
}

.promotion-reward {
  font-size: 14px;
  font-weight: 600;
  color: var(--el-color-warning);
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
  width: 40px;
  height: 40px;
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

.action-icon.info {
  background: var(--el-color-info-light-8);
  color: var(--el-color-info);
}

.action-icon.warning {
  background: var(--el-color-warning-light-8);
  color: var(--el-color-warning);
}

.action-title {
  font-size: 14px;
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
  .stats-summary {
    grid-template-columns: 1fr;
  }
  
  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
  
  .server-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .server-actions {
    align-self: stretch;
    display: flex;
    justify-content: flex-end;
  }
}
</style>