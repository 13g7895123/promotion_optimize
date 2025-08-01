<template>
  <div class="layout-default">
    <el-container class="layout-container">
      <!-- Side Navigation - Hidden on Mobile -->
      <el-aside 
        v-if="!isMobile"
        :width="isCollapsed ? '64px' : '240px'" 
        class="layout-aside"
        :class="{ 'collapsed': isCollapsed }"
      >
        <div class="sidebar-header">
          <div v-if="!isCollapsed" class="logo">
            <h2>遊戲推廣平台</h2>
          </div>
          <div v-else class="logo-collapsed">
            <el-icon size="24"><Platform /></el-icon>
          </div>
        </div>
        
        <AppNavigation :collapsed="isCollapsed" />
      </el-aside>

      <!-- Mobile Navigation Component -->
      <AppNavigation v-if="isMobile" :collapsed="false" />

      <!-- Main Content Area -->
      <el-container class="main-container">
        <!-- Top Header -->
        <el-header class="layout-header">
          <div class="header-left">
            <!-- Desktop Sidebar Toggle -->
            <el-button 
              v-if="!isMobile"
              @click="toggleSidebar" 
              :icon="isCollapsed ? Expand : Fold"
              circle
              size="small"
            />
            
            <!-- Mobile Logo -->
            <div v-if="isMobile" class="mobile-logo">
              <h3>遊戲推廣平台</h3>
            </div>
            
            <!-- Breadcrumb Navigation -->
            <el-breadcrumb v-if="!isMobile" separator="/">
              <el-breadcrumb-item 
                v-for="breadcrumb in breadcrumbs" 
                :key="breadcrumb.path"
                :to="breadcrumb.path"
              >
                {{ breadcrumb.title }}
              </el-breadcrumb-item>
            </el-breadcrumb>
          </div>
          
          <div class="header-right">
            <el-space>
              <!-- Notifications -->
              <el-badge :value="notificationCount" :hidden="notificationCount === 0">
                <el-button :icon="Bell" circle size="small" />
              </el-badge>
              
              <!-- User Menu -->
              <el-dropdown @command="handleUserCommand">
                <el-button circle size="small">
                  <el-avatar :size="32" :src="currentUser?.avatar">
                    {{ currentUser?.nickname?.[0] || currentUser?.username?.[0] }}
                  </el-avatar>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="profile">
                      <el-icon><User /></el-icon>
                      個人資料
                    </el-dropdown-item>
                    <el-dropdown-item command="settings">
                      <el-icon><Setting /></el-icon>
                      設定
                    </el-dropdown-item>
                    <el-dropdown-item divided command="logout">
                      <el-icon><SwitchButton /></el-icon>
                      登出
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </el-space>
          </div>
        </el-header>

        <!-- Main Content -->
        <el-main class="layout-main">
          <div class="main-content">
            <slot />
          </div>
        </el-main>
      </el-container>
    </el-container>

    <!-- User Guide System -->
    <UserGuide
      v-if="currentGuide"
      v-model="showGuide"
      :steps="currentGuide.steps"
      @finish="finishGuide"
      @skip="skipGuide"
    />
  </div>
</template>

<script setup lang="ts">
import { 
  Platform, 
  Expand, 
  Fold, 
  Bell, 
  User, 
  Setting, 
  SwitchButton 
} from '@element-plus/icons-vue'

// Sidebar state
const isCollapsed = ref(false)

// Auth store
const authStore = useAuthStore()
const currentUser = computed(() => authStore.currentUser)

// Notification count (mock data for now)
const notificationCount = ref(3)

// Breadcrumbs
const { breadcrumbs } = useBreadcrumb()

// User Guide System
const { showGuide, currentGuide, finishGuide, skipGuide } = useUserGuide()

// Methods
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

const handleUserCommand = async (command: string) => {
  switch (command) {
    case 'profile':
      await navigateTo('/profile')
      break
    case 'settings':
      await navigateTo('/settings')
      break
    case 'logout':
      await authStore.logout()
      break
  }
}

// Responsive handling
const { width } = useWindowSize()
const isMobile = computed(() => width.value < 768)

watchEffect(() => {
  if (isMobile.value && !isCollapsed.value) {
    isCollapsed.value = true
  }
})
</script>

<style scoped>
.layout-default {
  height: 100vh;
  overflow: hidden;
}

.layout-container {
  height: 100%;
}

.layout-aside {
  background: var(--el-bg-color);
  border-right: 1px solid var(--el-border-color);
  transition: width 0.3s ease;
  overflow: hidden;
}

.layout-aside.collapsed {
  width: 64px !important;
}

.sidebar-header {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-bottom: 1px solid var(--el-border-color);
  padding: 0 16px;
}

.logo h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--el-color-primary);
}

.logo-collapsed {
  color: var(--el-color-primary);
}

.main-container {
  flex: 1;
  overflow: hidden;
}

.layout-header {
  background: var(--el-bg-color);
  border-bottom: 1px solid var(--el-border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  height: 60px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-right {
  display: flex;
  align-items: center;
}

.layout-main {
  background: var(--el-bg-color-page);
  overflow: auto;
  padding: 24px;
}

.main-content {
  max-width: 1200px;
  margin: 0 auto;
}

.mobile-logo h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--el-color-primary);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .layout-container {
    flex-direction: column;
  }
  
  .main-container {
    width: 100%;
  }
  
  .layout-header {
    padding: 0 16px;
    height: 56px;
  }
  
  .layout-main {
    padding: 16px;
  }
  
  .header-left {
    gap: 8px;
    flex: 1;
  }
  
  .header-right .el-space {
    flex-wrap: wrap;
    gap: 8px;
  }
  
  .header-right .el-dropdown {
    margin-left: 0;
  }
}

/* Tablet adjustments */
@media (max-width: 1024px) and (min-width: 769px) {
  .layout-main {
    padding: 20px;
  }
  
  .main-content {
    max-width: 100%;
  }
}

/* Small mobile adjustments */
@media (max-width: 480px) {
  .layout-header {
    padding: 0 12px;
    height: 52px;
  }
  
  .layout-main {
    padding: 12px;
  }
  
  .mobile-logo h3 {
    font-size: 16px;
  }
  
  .header-right .el-space {
    gap: 4px;
  }
}
</style>