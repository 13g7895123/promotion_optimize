<template>
  <div class="admin-portal">
    <!-- 登入檢查 -->
    <div v-if="!isAuthenticated" class="admin-login">
      <div class="login-container">
        <div class="login-card">
          <div class="login-header">
            <h1 class="login-title">管理後台</h1>
            <p class="login-subtitle">私人遊戲伺服器推廣平台</p>
          </div>
          
          <el-form @submit.prevent="handleLogin" class="login-form">
            <el-form-item>
              <el-input
                v-model="loginForm.username"
                placeholder="管理員帳號"
                size="large"
                prefix-icon="User"
              />
            </el-form-item>
            <el-form-item>
              <el-input
                v-model="loginForm.password"
                type="password"
                placeholder="密碼"
                size="large"
                prefix-icon="Lock"
                show-password
                @keyup.enter="handleLogin"
              />
            </el-form-item>
            <el-form-item>
              <el-button 
                type="primary" 
                size="large" 
                style="width: 100%"
                :loading="isLogging"
                @click="handleLogin"
              >
                登入管理後台
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </div>
    </div>

    <!-- 管理後台首頁 -->
    <div v-else class="admin-dashboard">
      <!-- 歡迎標題 -->
      <div class="dashboard-header">
        <h1 class="dashboard-title">
          <el-icon><Odometer /></el-icon>
          管理儀表板
        </h1>
        <p class="dashboard-subtitle">{{ currentTime }}</p>
      </div>

      <!-- 統計卡片 -->
      <div class="stats-grid">
        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon servers">
              <el-icon><Monitor /></el-icon>
            </div>
            <div class="stat-info">
              <CountUp :end="dashboardStats.total_servers" class="stat-number" />
              <p class="stat-label">註冊伺服器</p>
            </div>
          </div>
        </el-card>

        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon users">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-info">
              <CountUp :end="dashboardStats.total_users" class="stat-number" />
              <p class="stat-label">註冊用戶</p>
            </div>
          </div>
        </el-card>

        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon promotions">
              <el-icon><Promotion /></el-icon>
            </div>
            <div class="stat-info">
              <CountUp :end="dashboardStats.active_promotions" class="stat-number" />
              <p class="stat-label">活躍推廣</p>
            </div>
          </div>
        </el-card>

        <el-card class="stat-card" shadow="hover">
          <div class="stat-content">
            <div class="stat-icon revenue">
              <el-icon><Money /></el-icon>
            </div>
            <div class="stat-info">
              <CountUp :end="dashboardStats.total_revenue" :prefix="'$'" class="stat-number" />
              <p class="stat-label">總收益</p>
            </div>
          </div>
        </el-card>
      </div>

      <!-- 快速操作 -->
      <div class="quick-actions">
        <h2>快速操作</h2>
        <div class="actions-grid">
          <el-card class="action-card" shadow="hover" @click="navigateTo('/admin/servers')">
            <div class="action-content">
              <el-icon class="action-icon"><Monitor /></el-icon>
              <h3>伺服器管理</h3>
              <p>管理註冊的遊戲伺服器</p>
            </div>
          </el-card>

          <el-card class="action-card" shadow="hover" @click="navigateTo('/admin/users')">
            <div class="action-content">
              <el-icon class="action-icon"><User /></el-icon>
              <h3>用戶管理</h3>
              <p>管理註冊用戶和權限</p>
            </div>
          </el-card>

          <el-card class="action-card" shadow="hover" @click="navigateTo('/admin/promotions')">
            <div class="action-content">
              <el-icon class="action-icon"><Promotion /></el-icon>
              <h3>推廣管理</h3>
              <p>管理推廣活動和設定</p>
            </div>
          </el-card>

          <el-card class="action-card" shadow="hover" @click="navigateTo('/admin/reports')">
            <div class="action-content">
              <el-icon class="action-icon"><TrendCharts /></el-icon>
              <h3>統計報表</h3>
              <p>查看詳細統計數據</p>
            </div>
          </el-card>
        </div>
      </div>

      <!-- 最近活動 -->
      <div class="recent-activity">
        <h2>最近活動</h2>
        <el-card shadow="hover">
          <el-timeline>
            <el-timeline-item
              v-for="activity in recentActivities"
              :key="activity.id"
              :timestamp="activity.timestamp"
              :type="activity.type"
            >
              <div class="activity-content">
                <h4>{{ activity.title }}</h4>
                <p>{{ activity.description }}</p>
              </div>
            </el-timeline-item>
          </el-timeline>
        </el-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { 
  Odometer, Monitor, User, Promotion, Money, TrendCharts, 
  Lock
} from '@element-plus/icons-vue'

// 組件導入
const CountUp = defineAsyncComponent(() => import('~/components/effects/CountUp.vue'))

// 路由
const router = useRouter()

// 認證狀態
const isAuthenticated = ref(false)
const isLogging = ref(false)

// 登入表單
const loginForm = ref({
  username: '',
  password: ''
})

// 儀表板數據
const dashboardStats = ref({
  total_servers: 0,
  total_users: 0,
  active_promotions: 0,
  total_revenue: 0
})

// 最近活動
const recentActivities = ref([
  {
    id: 1,
    title: '新伺服器註冊',
    description: '龍之谷伺服器已成功註冊並開始推廣活動',
    timestamp: '2025-01-15 14:30',
    type: 'success'
  },
  {
    id: 2,
    title: '推廣獎勵發放',
    description: '已向 100 位玩家發放推廣獎勵',
    timestamp: '2025-01-15 12:15',
    type: 'primary'
  },
  {
    id: 3,
    title: '系統維護',
    description: '後台系統維護完成，所有功能正常運行',
    timestamp: '2025-01-15 10:00',
    type: 'info'
  }
])

// 當前時間
const currentTime = computed(() => {
  return new Date().toLocaleString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

// 頁面元數據
definePageMeta({
  layout: 'admin'
})

// 登入處理
const handleLogin = async () => {
  if (!loginForm.value.username || !loginForm.value.password) {
    ElMessage.warning('請輸入帳號和密碼')
    return
  }

  isLogging.value = true

  try {
    // 模擬登入驗證
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // 簡單驗證 (實際應該調用 API)
    if (loginForm.value.username === 'admin' && loginForm.value.password === 'admin123') {
      isAuthenticated.value = true
      ElMessage.success('登入成功！')
      loadDashboardData()
    } else {
      ElMessage.error('帳號或密碼錯誤')
    }
  } catch (error) {
    console.error('登入失敗:', error)
    ElMessage.error('登入失敗，請稍後再試')
  } finally {
    isLogging.value = false
  }
}

// 載入儀表板數據
const loadDashboardData = async () => {
  try {
    // 模擬API調用
    await new Promise(resolve => setTimeout(resolve, 500))
    
    dashboardStats.value = {
      total_servers: Math.floor(Math.random() * 50) + 10,
      total_users: Math.floor(Math.random() * 1000) + 500,
      active_promotions: Math.floor(Math.random() * 100) + 50,
      total_revenue: Math.floor(Math.random() * 50000) + 10000
    }
  } catch (error) {
    console.error('載入儀表板數據失敗:', error)
  }
}

// 導航到指定頁面
const navigateTo = (path: string) => {
  router.push(path)
}

// 生命週期
onMounted(() => {
  // 檢查是否已登入
  const token = localStorage.getItem('admin_token')
  if (token) {
    isAuthenticated.value = true
    loadDashboardData()
  }
})
</script>

<style scoped>
.admin-portal {
  min-height: 100vh;
}

/* 登入頁面樣式 */
.admin-login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-container {
  max-width: 400px;
  width: 100%;
  padding: 2rem;
}

.login-card {
  background: white;
  padding: 3rem 2rem;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.login-title {
  font-size: 2rem;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-weight: 700;
}

.login-subtitle {
  color: #7f8c8d;
  font-size: 1rem;
}

.login-form .el-form-item {
  margin-bottom: 1.5rem;
}

/* 儀表板樣式 */
.admin-dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  text-align: center;
  margin-bottom: 3rem;
}

.dashboard-title {
  font-size: 2.5rem;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.dashboard-subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  border-radius: 15px;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.stat-icon.servers {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.stat-icon.users {
  background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.stat-icon.promotions {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.stat-icon.revenue {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-top: 0.25rem;
}

.quick-actions,
.recent-activity {
  margin-bottom: 3rem;
}

.quick-actions h2,
.recent-activity h2 {
  color: #2c3e50;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.action-card {
  border-radius: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.action-card:hover {
  transform: translateY(-5px);
  border-color: #3498db;
  box-shadow: 0 10px 30px rgba(52, 152, 219, 0.2);
}

.action-content {
  text-align: center;
  padding: 1rem;
}

.action-icon {
  font-size: 2.5rem;
  color: #3498db;
  margin-bottom: 1rem;
}

.action-content h3 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-size: 1.2rem;
}

.action-content p {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.activity-content h4 {
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.activity-content p {
  color: #7f8c8d;
  margin: 0;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .admin-dashboard {
    padding: 1rem;
  }
  
  .dashboard-title {
    font-size: 2rem;
  }
  
  .stats-grid,
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .stat-content {
    flex-direction: column;
    text-align: center;
  }
}
</style>