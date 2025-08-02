<template>
  <div class="error-page">
    <!-- 背景動畫 -->
    <div class="background-animation">
      <div class="floating-elements">
        <div class="element element-1"></div>
        <div class="element element-2"></div>
        <div class="element element-3"></div>
        <div class="element element-4"></div>
        <div class="element element-5"></div>
      </div>
    </div>

    <!-- 主要內容 -->
    <div class="error-container">
      <div class="error-content">
        <!-- 404 數字 -->
        <div class="error-number">
          <span class="number-4">4</span>
          <span class="number-0">0</span>
          <span class="number-4-alt">4</span>
        </div>

        <!-- 錯誤信息 -->
        <div class="error-message">
          <h1 class="error-title">頁面走失了</h1>
          <p class="error-description">
            您訪問的頁面可能已經搬家或者不存在了
          </p>
        </div>

        <!-- 導航按鈕 -->
        <div class="error-actions">
          <button @click="goToServerSelect" class="action-btn primary">
            <span class="btn-icon">🏠</span>
            選擇伺服器
          </button>
          <button @click="goToAdmin" class="action-btn secondary">
            <span class="btn-icon">⚙️</span>
            管理後台
          </button>
        </div>

        <!-- 建議區域 -->
        <div class="suggestions">
          <h3 class="suggestions-title">您可以嘗試：</h3>
          <ul class="suggestions-list">
            <li @click="goToServerExample('sv001')">
              <span class="list-icon">➤</span>
              前往 龍之谷伺服器
            </li>
            <li @click="goToServerExample('sv002')">
              <span class="list-icon">➤</span>
              前往 新楓之谷伺服器
            </li>
            <li @click="goToServerExample('sv003')">
              <span class="list-icon">➤</span>
              前往 天堂伺服器
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

// 路由
const router = useRouter()

// 頁面元數據
definePageMeta({
  layout: 'default'
})

// 導航方法
const goToServerSelect = () => {
  // 可以導航到一個伺服器選擇頁面，或者默認伺服器
  router.push('/sv001')
}

const goToAdmin = () => {
  router.push('/admin/login')
}

const goToServerExample = (serverCode: string) => {
  router.push(`/${serverCode}`)
}
</script>

<style scoped>
.error-page {
  min-height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 背景動畫 */
.background-animation {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.floating-elements {
  position: relative;
  width: 100%;
  height: 100%;
}

.element {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.element-1 {
  width: 200px;
  height: 200px;
  top: 10%;
  left: 10%;
  animation: float1 8s ease-in-out infinite;
}

.element-2 {
  width: 150px;
  height: 150px;
  top: 60%;
  right: 15%;
  animation: float2 10s ease-in-out infinite;
}

.element-3 {
  width: 100px;
  height: 100px;
  top: 30%;
  right: 30%;
  animation: float3 6s ease-in-out infinite;
}

.element-4 {
  width: 80px;
  height: 80px;
  bottom: 20%;
  left: 20%;
  animation: float4 12s ease-in-out infinite;
}

.element-5 {
  width: 120px;
  height: 120px;
  top: 15%;
  left: 70%;
  animation: float5 9s ease-in-out infinite;
}

@keyframes float1 {
  0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
  50% { transform: translateY(-30px) translateX(20px) rotate(180deg); }
}

@keyframes float2 {
  0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
  50% { transform: translateY(40px) translateX(-25px) rotate(-180deg); }
}

@keyframes float3 {
  0%, 100% { transform: translateY(0px) translateX(0px) scale(1); }
  50% { transform: translateY(-20px) translateX(15px) scale(1.1); }
}

@keyframes float4 {
  0%, 100% { transform: translateY(0px) translateX(0px) rotate(0deg); }
  50% { transform: translateY(-35px) translateX(-20px) rotate(360deg); }
}

@keyframes float5 {
  0%, 100% { transform: translateY(0px) translateX(0px); }
  50% { transform: translateY(25px) translateX(-30px); }
}

/* 主要內容 */
.error-container {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 600px;
  padding: 2rem;
}

.error-content {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 30px;
  padding: 3rem 2rem;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
}

/* 404 數字動畫 */
.error-number {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.number-4,
.number-0,
.number-4-alt {
  font-size: 8rem;
  font-weight: 900;
  color: rgba(255, 255, 255, 0.9);
  text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
  animation: numberPulse 3s ease-in-out infinite;
}

.number-0 {
  animation-delay: 0.5s;
  color: #ffd700;
  text-shadow: 0 0 30px rgba(255, 215, 0, 0.8);
}

.number-4-alt {
  animation-delay: 1s;
}

@keyframes numberPulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.9;
  }
  50% {
    transform: scale(1.1);
    opacity: 1;
  }
}

/* 錯誤信息 */
.error-message {
  margin-bottom: 3rem;
}

.error-title {
  font-size: 2.5rem;
  color: white;
  margin-bottom: 1rem;
  font-weight: 700;
  text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
}

.error-description {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin-bottom: 0;
}

/* 操作按鈕 */
.error-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-bottom: 3rem;
  flex-wrap: wrap;
}

.action-btn {
  border-radius: 25px;
  padding: 12px 24px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.action-btn.primary {
  background: #409eff;
  color: white;
  border-color: #409eff;
}

.action-btn.secondary {
  background: #909399;
  color: white;
  border-color: #909399;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
  opacity: 0.9;
}

.btn-icon {
  font-size: 1.1rem;
}

/* 建議區域 */
.suggestions {
  text-align: left;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 2rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.suggestions-title {
  color: white;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  font-weight: 600;
  text-align: center;
}

.suggestions-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.suggestions-list li {
  color: rgba(255, 255, 255, 0.9);
  padding: 0.8rem 1rem;
  margin-bottom: 0.5rem;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.list-icon {
  color: #00d4ff;
  font-weight: bold;
}

.suggestions-list li:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateX(10px);
  color: white;
}

.suggestions-list li:last-child {
  margin-bottom: 0;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .error-container {
    padding: 1rem;
  }

  .error-content {
    padding: 2rem 1.5rem;
  }

  .error-number {
    gap: 0.5rem;
  }

  .number-4,
  .number-0,
  .number-4-alt {
    font-size: 5rem;
  }

  .error-title {
    font-size: 2rem;
  }

  .error-description {
    font-size: 1rem;
  }

  .error-actions {
    flex-direction: column;
    align-items: center;
  }

  .action-btn {
    width: 200px;
  }

  .floating-elements .element {
    display: none;
  }
}

@media (max-width: 480px) {
  .error-content {
    padding: 1.5rem 1rem;
  }

  .number-4,
  .number-0,
  .number-4-alt {
    font-size: 4rem;
  }

  .error-title {
    font-size: 1.8rem;
  }

  .suggestions {
    padding: 1.5rem;
  }
}
</style>