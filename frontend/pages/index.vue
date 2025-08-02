<template>
  <div class="welcome-page">
    <!-- 粒子背景效果 -->
    <div class="particle-background">
      <canvas ref="particleCanvas" class="particle-canvas"></canvas>
    </div>

    <div class="welcome-container">
      <div class="welcome-content">
        <!-- 主標題 -->
        <h1 class="welcome-title">
          <TypewriterText 
            :texts="['遊戲伺服器推廣平台', '邀請好友，獲得獎勵！', '立即開始您的推廣之旅！']"
            :speed="100"
            :pause="3000"
            class="title-typewriter"
          />
        </h1>
        
        <p class="welcome-description">
          歡迎使用私人遊戲伺服器推廣平台，選擇您的伺服器開始推廣之旅
        </p>

        <!-- 伺服器選擇區域 -->
        <div class="server-selection">
          <h2 class="selection-title">選擇您的遊戲伺服器</h2>
          <div class="servers-grid">
            <div 
              v-for="server in availableServers" 
              :key="server.code"
              class="server-card"
              @click="selectServer(server)"
            >
              <div class="server-image">
                <img :src="server.image" :alt="server.name" />
                <div class="server-status" :class="server.status">
                  <span class="status-dot"></span>
                  {{ server.status === 'online' ? '在線' : '離線' }}
                </div>
              </div>
              <div class="server-info">
                <h3 class="server-name">{{ server.name }}</h3>
                <p class="server-description">{{ server.description }}</p>
                <div class="server-stats">
                  <span class="online-count">
                    <el-icon><User /></el-icon>
                    {{ server.online_players }} 在線
                  </span>
                  <span class="promotion-count">
                    <el-icon><Promotion /></el-icon>
                    {{ server.active_promotions }} 推廣中
                  </span>
                </div>
              </div>
              <div class="server-overlay">
                <GlowButton size="large">
                  <el-icon><Right /></el-icon>
                  進入推廣
                </GlowButton>
              </div>
            </div>
          </div>
        </div>

        <!-- 功能介紹 -->
        <div class="features-section">
          <h2 class="features-title">平台特色</h2>
          <div class="features-grid">
            <div class="feature-item">
              <div class="feature-icon">
                <el-icon><Link /></el-icon>
              </div>
              <h3>專屬推廣連結</h3>
              <p>生成專屬推廣連結，追蹤推廣效果</p>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <el-icon><Trophy /></el-icon>
              </div>
              <h3>豐富獎勵系統</h3>
              <p>推廣成功即可獲得豐厚遊戲獎勵</p>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <el-icon><TrendCharts /></el-icon>
              </div>
              <h3>即時數據統計</h3>
              <p>即時查看推廣數據和獎勵記錄</p>
            </div>
          </div>
        </div>

        <!-- 管理後台入口 -->
        <div class="admin-portal">
          <el-button type="info" plain @click="goToAdmin">
            <el-icon><Setting /></el-icon>
            管理後台
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  User, Promotion, Link, Trophy, TrendCharts, 
  Setting, Right 
} from '@element-plus/icons-vue'

// 組件導入
const TypewriterText = defineAsyncComponent(() => import('~/components/effects/TypewriterText.vue'))
const GlowButton = defineAsyncComponent(() => import('~/components/common/GlowButton.vue'))

// 路由
const router = useRouter()

// 響應式數據
const particleCanvas = ref<HTMLCanvasElement>()
const availableServers = ref([
  {
    code: 'sv001',
    name: '龍之谷伺服器',
    description: '高倍率經驗，友善環境',
    image: '/images/servers/server1.jpg',
    status: 'online',
    online_players: 1247,
    active_promotions: 23
  },
  {
    code: 'sv002', 
    name: '新楓之谷伺服器',
    description: '懷舊版本，穩定運營',
    image: '/images/servers/server2.jpg',
    status: 'online',
    online_players: 892,
    active_promotions: 15
  },
  {
    code: 'sv003',
    name: '天堂伺服器',
    description: '經典重現，熱血PK',
    image: '/images/servers/server3.jpg',
    status: 'online',
    online_players: 654,
    active_promotions: 8
  }
])

// 頁面元數據
definePageMeta({
  layout: 'default'
})

// 伺服器選擇
const selectServer = (server: any) => {
  router.push(`/${server.code}`)
}

// 導航到管理後台
const goToAdmin = () => {
  router.push('/admin')
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
      ctx!.fillStyle = '#667eea'
      ctx!.shadowBlur = 10
      ctx!.shadowColor = '#667eea'
      ctx!.beginPath()
      ctx!.arc(this.x, this.y, this.size, 0, Math.PI * 2)
      ctx!.fill()
      ctx!.restore()
    }
  }

  // 創建粒子
  const particles: Particle[] = []
  for (let i = 0; i < 30; i++) {
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
  nextTick(() => {
    initParticleEffect()
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', () => {})
})
</script>

<style scoped>
.welcome-page {
  min-height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow-x: hidden;
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

.welcome-container {
  position: relative;
  z-index: 2;
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.welcome-content {
  text-align: center;
  padding: 3rem 2rem;
}

.welcome-title {
  font-size: 3rem;
  color: white;
  margin-bottom: 1rem;
  font-weight: 700;
  min-height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.title-typewriter {
  text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
}

.welcome-description {
  font-size: 1.3rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 3rem;
  line-height: 1.6;
}

.server-selection {
  margin-bottom: 4rem;
}

.selection-title {
  font-size: 2rem;
  color: white;
  margin-bottom: 2rem;
  font-weight: 600;
}

.servers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.server-card {
  position: relative;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
  min-height: 350px;
}

.server-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  border-color: rgba(255, 255, 255, 0.4);
}

.server-card:hover .server-overlay {
  opacity: 1;
}

.server-image {
  position: relative;
  height: 180px;
  background: linear-gradient(45deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.server-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.server-status {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #2ecc71;
}

.server-status.offline .status-dot {
  background: #e74c3c;
}

.server-info {
  padding: 1.5rem;
  color: white;
}

.server-name {
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.server-description {
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 1rem;
  line-height: 1.5;
}

.server-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.online-count,
.promotion-count {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  color: rgba(255, 255, 255, 0.9);
}

.server-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(102, 126, 234, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.features-section {
  margin-bottom: 4rem;
}

.features-title {
  font-size: 2rem;
  color: white;
  margin-bottom: 2rem;
  font-weight: 600;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.feature-item {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 15px;
  padding: 2rem;
  text-align: center;
  color: white;
  transition: transform 0.3s ease;
}

.feature-item:hover {
  transform: translateY(-5px);
}

.feature-icon {
  font-size: 3rem;
  color: #764ba2;
  margin-bottom: 1rem;
}

.feature-item h3 {
  font-size: 1.3rem;
  margin-bottom: 1rem;
  font-weight: 600;
}

.feature-item p {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.5;
}

.admin-portal {
  margin-top: 3rem;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .welcome-content {
    padding: 2rem 1rem;
  }
  
  .welcome-title {
    font-size: 2rem;
  }
  
  .servers-grid {
    grid-template-columns: 1fr;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .server-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .welcome-container {
    padding: 1rem;
  }
  
  .server-card {
    min-height: 280px;
  }
  
  .server-image {
    height: 120px;
  }
}
</style>