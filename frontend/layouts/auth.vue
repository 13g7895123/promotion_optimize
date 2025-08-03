<template>
  <div class="auth-layout">
    <div class="auth-container">
      <!-- Background Pattern -->
      <div class="auth-background">
        <div class="pattern-overlay"></div>
      </div>
      
      <!-- Auth Content -->
      <div class="auth-content">
        <div class="auth-card">
          <!-- Logo -->
          <div class="auth-logo">
            <el-icon size="48" color="var(--el-color-primary)">
              <Platform />
            </el-icon>
            <h1>遊戲推廣平台</h1>
            <p>專業的私人遊戲伺服器推廣管理平台</p>
          </div>
          
          <!-- Main Content Slot -->
          <div class="auth-form">
            <slot />
          </div>
          
          <!-- Footer -->
          <div class="auth-footer">
            <p>&copy; 2024 遊戲推廣平台. 版權所有.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Platform } from '@element-plus/icons-vue'
</script>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--el-color-primary-light-7) 0%, var(--el-color-primary-light-5) 100%);
  position: relative;
  overflow: hidden;
}

.auth-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 400px;
  padding: 20px;
}

.auth-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
  background-size: 300px 300px;
  /* animation: float 6s ease-in-out infinite; */
}

.pattern-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
}

.auth-content {
  position: relative;
  z-index: 3;
}

.auth-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 40px;
  box-shadow: 
    0 20px 40px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.auth-logo {
  text-align: center;
  margin-bottom: 32px;
}

.auth-logo h1 {
  margin: 16px 0 8px;
  font-size: 28px;
  font-weight: 700;
  color: var(--el-text-color-primary);
  background: linear-gradient(135deg, var(--el-color-primary), var(--el-color-primary-light-3));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.auth-logo p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 14px;
  line-height: 1.5;
}

.auth-form {
  margin-bottom: 24px;
}

.auth-footer {
  text-align: center;
  padding-top: 24px;
  border-top: 1px solid var(--el-border-color-lighter);
}

.auth-footer p {
  margin: 0;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

/* Animations */
@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}

/* Mobile responsiveness */
@media (max-width: 480px) {
  .auth-container {
    max-width: 100%;
    padding: 16px;
  }
  
  .auth-card {
    padding: 24px;
    border-radius: 12px;
  }
  
  .auth-logo h1 {
    font-size: 24px;
  }
  
  .auth-logo {
    margin-bottom: 24px;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .auth-card {
    background: rgba(30, 30, 30, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
  }
}
</style>