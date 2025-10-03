<template>
  <div class="admin-layout">
    <!-- 側邊欄 -->
    <aside class="admin-sidebar" :class="{ collapsed: isSidebarCollapsed }">
      <!-- Logo 區域 -->
      <div class="sidebar-header">
        <div class="logo">
          <el-icon size="28"><Monitor /></el-icon>
          <span v-if="!isSidebarCollapsed" class="logo-text">推廣平台</span>
        </div>
      </div>

      <!-- 導航選單 -->
      <nav class="sidebar-nav">
        <div
          v-for="item in menuItems"
          :key="item.path"
          class="nav-item"
          :class="{ active: isActive(item.path) }"
          @click="navigateTo(item.path)"
        >
          <el-icon size="20">
            <component :is="item.icon" />
          </el-icon>
          <span v-if="!isSidebarCollapsed" class="nav-text">{{ item.title }}</span>
        </div>
      </nav>

      <!-- 側邊欄底部 -->
      <div class="sidebar-footer">
        <div class="nav-item" @click="goToFrontend">
          <el-icon size="20"><View /></el-icon>
          <span v-if="!isSidebarCollapsed" class="nav-text">前台預覽</span>
        </div>
      </div>
    </aside>

    <!-- 主要內容區域 -->
    <div class="admin-main-wrapper">
      <!-- 頂部欄 -->
      <header class="admin-topbar">
        <div class="topbar-left">
          <el-button text @click="toggleSidebar">
            <el-icon size="20"><Fold v-if="!isSidebarCollapsed" /><Expand v-else /></el-icon>
          </el-button>
          <span class="page-title">{{ currentPageTitle }}</span>
        </div>
        <div class="topbar-right">
          <el-dropdown @command="handleUserCommand">
            <el-button type="primary" text>
              <el-icon><User /></el-icon>
              <span style="margin-left: 8px;">管理員</span>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">個人設定</el-dropdown-item>
                <el-dropdown-item command="logout" divided>登出</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>

      <!-- 內容區域 -->
      <main class="admin-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Monitor,
  View,
  User,
  Fold,
  Expand,
  HomeFilled,
  DataAnalysis,
  Document,
  Setting
} from '@element-plus/icons-vue'
import { useRouter, useRoute } from 'vue-router'

// 載入後台專用樣式
import '~/assets/css/admin.css'

const router = useRouter()
const route = useRoute()

// 側邊欄狀態
const isSidebarCollapsed = ref(false)

// 選單項目
const menuItems = ref([
  {
    path: '/admin/dashboard',
    title: '儀表板',
    icon: 'HomeFilled'
  },
  {
    path: '/admin/promotions',
    title: '推廣管理',
    icon: 'DataAnalysis'
  },
  {
    path: '/admin/tools',
    title: '工具箱',
    icon: 'Document'
  },
  {
    path: '/admin/index',
    title: '系統設定',
    icon: 'Setting'
  }
])

// 當前頁面標題
const currentPageTitle = computed(() => {
  const currentItem = menuItems.value.find(item => route.path === item.path)
  return currentItem?.title || '管理後台'
})

// 切換側邊欄
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

// 檢查是否為當前路由
const isActive = (path: string) => {
  return route.path === path
}

// 導航
const navigateTo = (path: string) => {
  router.push(path)
}

// 導航到前台
const goToFrontend = () => {
  router.push('/')
}

// 用戶操作
const handleUserCommand = async (command: string) => {
  switch (command) {
    case 'profile':
      // 跳轉到個人設定頁面
      break
    case 'logout':
      // 登出邏輯
      await router.push('/admin/login')
      break
  }
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #f5f7fa;
}

/* 側邊欄 */
.admin-sidebar {
  width: 250px;
  background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
  color: white;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  z-index: 1000;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
}

.admin-sidebar.collapsed {
  width: 70px;
}

/* Logo 區域 */
.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
  color: white;
  font-size: 20px;
  font-weight: 700;
}

.logo-text {
  white-space: nowrap;
  overflow: hidden;
}

/* 導航區域 */
.sidebar-nav {
  flex: 1;
  padding: 20px 0;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: rgba(255, 255, 255, 0.8);
  margin: 4px 12px;
  border-radius: 8px;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.nav-item.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.nav-text {
  white-space: nowrap;
  overflow: hidden;
  font-size: 14px;
  font-weight: 500;
}

.collapsed .nav-text {
  display: none;
}

/* 側邊欄底部 */
.sidebar-footer {
  padding: 20px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* 主要內容區域 */
.admin-main-wrapper {
  flex: 1;
  margin-left: 250px;
  transition: margin-left 0.3s ease;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.collapsed + .admin-main-wrapper {
  margin-left: 70px;
}

/* 頂部欄 */
.admin-topbar {
  background: white;
  border-bottom: 1px solid #e4e7ed;
  padding: 0 24px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  position: sticky;
  top: 0;
  z-index: 999;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.page-title {
  font-size: 18px;
  font-weight: 600;
  color: #2c3e50;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

/* 內容區域 */
.admin-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .admin-sidebar {
    transform: translateX(-100%);
  }

  .admin-sidebar:not(.collapsed) {
    transform: translateX(0);
  }

  .admin-main-wrapper {
    margin-left: 0;
  }

  .admin-content {
    padding: 16px;
  }

  .page-title {
    font-size: 16px;
  }
}
</style>