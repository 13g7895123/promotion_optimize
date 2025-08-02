<template>
  <div class="server-home">
    <!-- 粒子背景效果 -->
    <div class="particle-background">
      <canvas ref="particleCanvas" class="particle-canvas"></canvas>
    </div>

    <!-- 主要內容 -->
    <div class="hero-section">
      <div class="hero-container">
        <!-- 伺服器標題 -->
        <div class="server-info">
          <h1 class="server-title">
            <span class="neon-text">{{ serverInfo?.name || '伺服器' }}</span>
          </h1>
          <div class="server-status">
            <span class="status-badge" :class="serverInfo?.status === 'online' ? 'status-online' : 'status-offline'">
              {{ serverInfo?.status === 'online' ? '在線' : '離線' }}
            </span>
            <span class="player-count">{{ serverInfo?.online_players || 0 }} 人在線</span>
          </div>
        </div>

        <!-- 歡迎訊息 -->
        <div class="welcome-message">
          <TypewriterText 
            :texts="welcomeTexts" 
            :speed="80" 
            :pause="2000"
            class="typewriter-welcome"
          />
        </div>

        <!-- 帳號輸入區域 -->
        <div class="account-input-section">
          <div class="input-container">
            <h3 class="input-title">請輸入您的遊戲帳號</h3>
            <form @submit.prevent="handleAccountSubmit" class="account-form">
              <div class="form-item">
                <div class="input-wrapper">
                  <span class="input-icon">👤</span>
                  <input
                    v-model="gameAccount"
                    placeholder="請輸入遊戲帳號"
                    class="account-input"
                    :disabled="isValidating"
                    @keyup.enter="handleAccountSubmit"
                  />
                </div>
              </div>
              <div class="form-item">
                <GlowButton 
                  @click="handleAccountSubmit"
                  :loading="isValidating"
                  class="submit-btn"
                >
                  <span class="btn-icon">🚀</span>
                  開始推廣
                </GlowButton>
              </div>
            </form>
          </div>
        </div>

        <!-- 快速功能 -->
        <div class="quick-actions">
          <div class="action-card" @click="goToEvents">
            <span class="action-icon">📅</span>
            <span class="action-text">活動中心</span>
          </div>
          <div class="action-card" @click="goToRankings">
            <span class="action-icon">🏆</span>
            <span class="action-text">推廣排行</span>
          </div>
          <div class="action-card" @click="goToProfile">
            <span class="action-icon">👤</span>
            <span class="action-text">個人記錄</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// 組件導入
const TypewriterText = defineAsyncComponent(() => import('~/components/effects/TypewriterText.vue'))
const GlowButton = defineAsyncComponent(() => import('~/components/common/GlowButton.vue'))

// 路由和狀態
const route = useRoute()
const router = useRouter()
const serverCode = route.params.server as string

// 響應式數據
const gameAccount = ref('')
const isValidating = ref(false)
const serverInfo = ref<any>(null)
const particleCanvas = ref<HTMLCanvasElement>()

// 歡迎文字
const welcomeTexts = [
  `歡迎來到 ${serverInfo.value?.name || '遊戲伺服器'}`,
  '立即開始推廣，獲得豐厚獎勵！',
  '邀請好友，共同征戰遊戲世界！'
]

// 頁面元數據
definePageMeta({
  layout: 'default'
})

// 載入伺服器信息
const loadServerInfo = async () => {
  try {
    // 這裡應該從API載入伺服器信息
    // const response = await $fetch(`/api/servers/${serverCode}`)
    // serverInfo.value = response.data
    
    // 臨時模擬數據
    serverInfo.value = {
      name: serverCode.toUpperCase() + ' 伺服器',
      status: 'online',
      online_players: Math.floor(Math.random() * 1000) + 100,
      description: '高倍率、友善環境、穩定運營'
    }
  } catch (error) {
    console.error('載入伺服器信息失敗:', error)
    alert('載入伺服器信息失敗')
    // 跳轉到主頁或 404 頁面
    router.push('/')
  }
}

// 帳號驗證和提交
const handleAccountSubmit = async () => {
  if (!gameAccount.value.trim()) {
    alert('請輸入遊戲帳號')
    return
  }

  isValidating.value = true
  
  try {
    // 這裡應該驗證帳號是否存在
    // const response = await $fetch('/api/validate-account', {
    //   method: 'POST',
    //   body: {
    //     server: serverCode,
    //     account: gameAccount.value
    //   }
    // })
    
    // 模擬驗證延遲
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    // 驗證成功，跳轉到推廣頁面
    router.push(`/${serverCode}/promote?account=${encodeURIComponent(gameAccount.value)}`)
    
  } catch (error) {
    console.error('帳號驗證失敗:', error)
    alert('帳號驗證失敗，請確認帳號是否正確')
  } finally {
    isValidating.value = false
  }
}

// 快速功能導航
const goToEvents = () => {
  router.push(`/${serverCode}/events`)
}

const goToRankings = () => {
  router.push(`/${serverCode}/rankings`)
}

const goToProfile = () => {
  if (!gameAccount.value) {
    alert('請先輸入遊戲帳號')
    return
  }
  router.push(`/${serverCode}/profile?account=${encodeURIComponent(gameAccount.value)}`)
}

// 粒子動畫
const initParticleEffect = () => {
  if (!particleCanvas.value) return

  const canvas = particleCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // 設置 canvas 尺寸
  const resizeCanvas = () => {
    canvas.width = window.innerWidth
    canvas.height = window.innerHeight
  }
  
  resizeCanvas()
  window.addEventListener('resize', resizeCanvas)

  // 粒子類
  class Particle {
    x: number
    y: number
    size: number
    speedX: number
    speedY: number
    opacity: number

    constructor() {
      this.x = Math.random() * canvas.width
      this.y = Math.random() * canvas.height
      this.size = Math.random() * 2 + 0.5
      this.speedX = Math.random() * 0.5 - 0.25
      this.speedY = Math.random() * 0.5 - 0.25
      this.opacity = Math.random() * 0.5 + 0.2
    }

    update() {
      this.x += this.speedX
      this.y += this.speedY

      if (this.x > canvas.width) this.x = 0
      if (this.x < 0) this.x = canvas.width
      if (this.y > canvas.height) this.y = 0
      if (this.y < 0) this.y = canvas.height
    }

    draw() {
      ctx!.save()
      ctx!.globalAlpha = this.opacity
      ctx!.fillStyle = '#00d4ff'
      ctx!.shadowBlur = 10
      ctx!.shadowColor = '#00d4ff'
      ctx!.beginPath()
      ctx!.arc(this.x, this.y, this.size, 0, Math.PI * 2)
      ctx!.fill()
      ctx!.restore()
    }
  }

  // 創建粒子
  const particles: Particle[] = []
  for (let i = 0; i < 50; i++) {
    particles.push(new Particle())
  }

  // 動畫循環
  const animate = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    
    particles.forEach(particle => {
      particle.update()
      particle.draw()
    })

    requestAnimationFrame(animate)
  }

  animate()
}

// 生命週期
onMounted(() => {
  loadServerInfo()
  nextTick(() => {
    initParticleEffect()
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', () => {})
})
</script>

<style scoped>
.server-home {
  min-height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #0a0e1a 0%, #1a1f35 50%, #2d1b69 100%);
  overflow: hidden;
}

.particle-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.particle-canvas {
  width: 100%;
  height: 100%;
}

.hero-section {
  position: relative;
  z-index: 2;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.hero-container {
  max-width: 600px;
  text-align: center;
  width: 100%;
}

.server-info {
  margin-bottom: 2rem;
}

.server-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.neon-text {
  color: #00d4ff;
  text-shadow: 
    0 0 5px #00d4ff,
    0 0 10px #00d4ff,
    0 0 15px #00d4ff,
    0 0 20px #00d4ff;
  animation: neon-flicker 2s infinite alternate;
}

@keyframes neon-flicker {
  0%, 18%, 22%, 25%, 53%, 57%, 100% {
    text-shadow: 
      0 0 5px #00d4ff,
      0 0 10px #00d4ff,
      0 0 15px #00d4ff,
      0 0 20px #00d4ff;
  }
  20%, 24%, 55% {
    text-shadow: none;
  }
}

.server-status {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.status-badge {
  padding: 0.3rem 0.8rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-online {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-offline {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.player-count {
  color: #ffffff;
  font-size: 1.1rem;
}

.welcome-message {
  margin-bottom: 3rem;
}

.typewriter-welcome {
  font-size: 1.3rem;
  color: #ffffff;
  min-height: 60px;
}

.account-input-section {
  margin-bottom: 3rem;
}

.input-container {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.input-title {
  color: #ffffff;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.account-form .form-item {
  margin-bottom: 1.5rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 15px;
  font-size: 1.2rem;
  z-index: 2;
  color: rgba(0, 212, 255, 0.8);
}

.account-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(0, 212, 255, 0.3);
  color: #000000;
  font-size: 1.1rem;
  padding: 0 15px 0 45px;
  height: 50px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.account-input:focus {
  outline: none;
  border-color: #00d4ff;
  box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
}

.account-input::placeholder {
  color: rgba(0, 0, 0, 0.6);
}

.account-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.submit-btn {
  width: 100%;
  height: 50px;
  font-size: 1.1rem;
  font-weight: 600;
}

.btn-icon {
  margin-right: 0.5rem;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-top: 2rem;
}

.action-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 15px;
  padding: 1.5rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #ffffff;
}

.action-card:hover {
  background: rgba(0, 212, 255, 0.1);
  border-color: rgba(0, 212, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 212, 255, 0.2);
}

.action-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  display: block;
}

.action-text {
  display: block;
  font-size: 1rem;
  font-weight: 500;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .hero-section {
    padding: 1rem;
  }
  
  .server-title {
    font-size: 2rem;
  }
  
  .input-container {
    padding: 1.5rem;
  }
  
  .quick-actions {
    grid-template-columns: 1fr;
  }
}
</style>