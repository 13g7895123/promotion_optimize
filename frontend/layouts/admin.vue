<template>
  <div class="admin-layout">
    <!-- 後台導航欄 -->
    <div class="admin-header">
      <div class="admin-header-left">
        <h2 class="admin-title">
          <el-icon><Monitor /></el-icon>
          推廣平台管理後台
        </h2>
      </div>
      <div class="admin-header-right">
        <el-button type="primary" text @click="goToFrontend">
          <el-icon><View /></el-icon>
          前台預覽
        </el-button>
        <el-dropdown @command="handleUserCommand">
          <el-button type="primary" text>
            <el-icon><User /></el-icon>
            管理員
          </el-button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="profile">個人設定</el-dropdown-item>
              <el-dropdown-item command="logout">登出</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <!-- 主要內容區域 -->
    <div class="admin-main">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Monitor, View, User } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'

const router = useRouter()

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
  min-height: 100vh;
  background: #f5f7fa;
}

.admin-header {
  background: white;
  border-bottom: 1px solid #e4e7ed;
  padding: 0 24px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}

.admin-header-left {
  display: flex;
  align-items: center;
}

.admin-title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 8px;
}

.admin-header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.admin-main {
  padding: 24px;
  min-height: calc(100vh - 64px);
}

/* 響應式設計 */
@media (max-width: 768px) {
  .admin-header {
    padding: 0 16px;
    height: 56px;
  }
  
  .admin-title {
    font-size: 18px;
  }
  
  .admin-main {
    padding: 16px;
  }
  
  .admin-header-right {
    gap: 8px;
  }
}
</style>