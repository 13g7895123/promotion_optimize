<template>
  <div class="dashboard-page">
    <!-- Welcome Header -->
    <div class="dashboard-header">
      <div class="welcome-section">
        <h1>歡迎回來，{{ currentUser?.nickname || currentUser?.username }}！</h1>
        <p class="welcome-subtitle">
          今天是 {{ formatDate(new Date()) }}，
          <span class="role-badge" :class="roleColorClass">
            {{ roleDisplayName }}
          </span>
        </p>
      </div>
      <div class="quick-actions">
        <el-button 
          v-if="canCreatePromotion" 
          type="primary" 
          :icon="Share"
          @click="goToPromotionTools"
        >
          建立推廣
        </el-button>
        <el-button 
          v-if="canManageServers" 
          type="success" 
          :icon="Monitor"
          @click="goToServerManagement"
        >
          管理伺服器
        </el-button>
      </div>
    </div>

    <!-- Dashboard Content Based on Role -->
    <div class="dashboard-content">
      <!-- Super Admin / Admin Dashboard -->
      <AdminDashboard v-if="isAdmin" />
      
      <!-- Server Owner Dashboard -->
      <ServerOwnerDashboard v-else-if="isServerOwner" />
      
      <!-- Reviewer Dashboard -->
      <ReviewerDashboard v-else-if="isReviewer" />
      
      <!-- Regular User Dashboard -->
      <UserDashboard v-else />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Share, Monitor } from '@element-plus/icons-vue'

// Page meta
definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

// Auth and permissions
const authStore = useAuthStore()
const { 
  isAdmin, 
  isServerOwner, 
  isReviewer, 
  getRoleDisplayName, 
  getRoleColor,
  canPerformAction 
} = usePermissions()

// Computed
const currentUser = computed(() => authStore.currentUser)
const roleDisplayName = computed(() => {
  const primaryRole = authStore.roles[0] || '用戶'
  return getRoleDisplayName(primaryRole)
})
const roleColorClass = computed(() => {
  const primaryRole = authStore.roles[0] || '用戶'
  return `role-${getRoleColor(primaryRole)}`
})

const canCreatePromotion = computed(() => 
  canPerformAction('create', 'promotion')
)
const canManageServers = computed(() => 
  canPerformAction('read', 'server')
)

// Methods
const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long'
  }).format(date)
}

const goToPromotionTools = async () => {
  await navigateTo('/promotion/tools')
}

const goToServerManagement = async () => {
  await navigateTo('/server/list')
}

// Initialize dashboard data on mount
onMounted(async () => {
  // Initialize auth state if needed
  if (!authStore.isAuthenticated) {
    await authStore.initializeAuth()
  }
})
</script>

<style scoped>
.dashboard-page {
  padding: 0;
}

.dashboard-header {
  background: linear-gradient(135deg, var(--el-color-primary-light-7) 0%, var(--el-color-primary-light-8) 100%);
  padding: 32px;
  border-radius: 12px;
  margin-bottom: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.welcome-section h1 {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.welcome-subtitle {
  margin: 0;
  font-size: 16px;
  color: var(--el-text-color-secondary);
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.role-badge {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  color: white;
}

.role-badge.role-danger {
  background: var(--el-color-danger);
}

.role-badge.role-warning {
  background: var(--el-color-warning);
}

.role-badge.role-primary {
  background: var(--el-color-primary);
}

.role-badge.role-success {
  background: var(--el-color-success);
}

.role-badge.role-info {
  background: var(--el-color-info);
}

.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.dashboard-content {
  min-height: 400px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .dashboard-header {
    padding: 24px;
    flex-direction: column;
    align-items: flex-start;
  }
  
  .welcome-section h1 {
    font-size: 24px;
  }
  
  .welcome-subtitle {
    font-size: 14px;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .quick-actions {
    width: 100%;
    justify-content: center;
  }
}
</style>