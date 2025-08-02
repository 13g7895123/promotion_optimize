<template>
  <section class="hero-section">
    <!-- 粒子背景效果 -->
    <ParticleBackground />
    
    <!-- 主要內容 -->
    <div class="hero-content">
      <div class="container">
        <div class="hero-inner">
          <!-- 標題區域 -->
          <div class="hero-title-section">
            <div class="title-decoration">
              <div class="cyber-hexagon"></div>
            </div>
            
            <TypewriterText 
              :text="mainTitle"
              :speed="80"
              :delay="1000"
              container-class="hero-title-container"
              text-class="hero-title"
            />
            
            <div class="subtitle-container">
              <h2 class="hero-subtitle cyber-text-neon">
                {{ subtitle }}
              </h2>
              <div class="cyber-divider"></div>
            </div>
          </div>

          <!-- 功能特色 -->
          <div class="features-grid">
            <div 
              v-for="(feature, index) in features" 
              :key="feature.id"
              class="feature-card"
              :style="{ animationDelay: `${2000 + index * 200}ms` }"
            >
              <div class="feature-icon">
                <el-icon>
                  <component :is="feature.icon" />
                </el-icon>
              </div>
              <h3 class="feature-title">{{ feature.title }}</h3>
              <p class="feature-description">{{ feature.description }}</p>
              <div class="feature-glow"></div>
            </div>
          </div>

          <!-- 操作按鈕區域 -->
          <div class="action-section">
            <div class="action-buttons">
              <glow-button 
                variant="primary" 
                size="large"
                :pulse="true"
                @click="handleGetStarted"
              >
                <el-icon><IconRocket /></el-icon>
                開始推廣
              </glow-button>
              
              <glow-button 
                variant="secondary" 
                size="large"
                @click="handleViewServers"
              >
                <el-icon><IconServer /></el-icon>
                瀏覽伺服器
              </glow-button>
            </div>
            
            <!-- 伺服器選擇器 -->
            <div class="server-selector-section">
              <p class="selector-label cyber-text-secondary">
                選擇您的遊戲伺服器開始體驗
              </p>
              <ServerSwitcher 
                v-model="selectedServer"
                :servers="availableServers"
                @server-change="handleServerChange"
                @refresh="handleRefreshServers"
              />
            </div>
          </div>

          <!-- 統計數據 */
          <div class="stats-section">
            <div class="stats-grid">
              <div 
                v-for="(stat, index) in stats" 
                :key="stat.id"
                class="stat-item"
                :style="{ animationDelay: `${3000 + index * 150}ms` }"
              >
                <div class="stat-value cyber-text-neon">
                  <CountUp 
                    :end-val="stat.value" 
                    :duration="2000"
                    :delay="3000 + index * 150"
                  />
                  {{ stat.unit }}
                </div>
                <div class="stat-label">{{ stat.label }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 裝飾性元素 */
    <div class="hero-decorations">
      <div class="decoration decoration-1"></div>
      <div class="decoration decoration-2"></div>
      <div class="decoration decoration-3"></div>
      <div class="floating-elements">
        <div v-for="i in 6" :key="i" class="floating-element" :class="`element-${i}`"></div>
      </div>
    </div>

    <!-- 向下滾動指示器 -->
    <div class="scroll-indicator">
      <div class="scroll-arrow">
        <el-icon><IconArrowDown /></el-icon>
      </div>
      <span class="scroll-text">探索更多</span>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import ParticleBackground from '~/components/effects/ParticleBackground.vue'
import TypewriterText from '~/components/effects/TypewriterText.vue'
import GlowButton from '~/components/effects/GlowButton.vue'
import ServerSwitcher from '~/components/common/ServerSwitcher.vue'
import CountUp from '~/components/effects/CountUp.vue'

// Icons
import {
  Rocket as IconRocket,
  Monitor as IconServer,
  ArrowDown as IconArrowDown,
  Trophy as IconTrophy,
  Users as IconUsers,
  Star as IconStar,
  Gift as IconGift
} from '@element-plus/icons-vue'

interface Feature {
  id: string
  title: string
  description: string
  icon: any
}

interface Stat {
  id: string
  label: string
  value: number
  unit: string
}

interface Server {
  id: string
  name: string
  code: string
  gameType: string
  logo?: string
  status: 'online' | 'offline' | 'maintenance'
  onlinePlayers: number
  maxPlayers: number
  ping: number
}

const emit = defineEmits<{
  'get-started': []
  'view-servers': []
  'server-change': [server: Server]
  'refresh-servers': []
}>()

const selectedServer = ref<Server | null>(null)

const mainTitle = ref('遊戲伺服器推廣平台')
const subtitle = ref('連接玩家與伺服器的最佳選擇')

const features = ref<Feature[]>([
  {
    id: '1',
    title: '智能推廣',
    description: '自動化推廣系統，輕鬆獲得新玩家',
    icon: IconTrophy
  },
  {
    id: '2',
    title: '豐富獎勵',
    description: '多元化獎勵機制，提升玩家參與度',
    icon: IconGift
  },
  {
    id: '3',
    title: '即時統計',
    description: '詳細數據分析，掌握推廣效果',
    icon: IconStar
  },
  {
    id: '4',
    title: '社群支持',
    description: '活躍的玩家社群，共同成長',
    icon: IconUsers
  }
])

const stats = ref<Stat[]>([
  {
    id: '1',
    label: '註冊伺服器',
    value: 150,
    unit: '+'
  },
  {
    id: '2',
    label: '活躍玩家',
    value: 25000,
    unit: '+'
  },
  {
    id: '3',
    label: '推廣成功率',
    value: 85,
    unit: '%'
  },
  {
    id: '4',
    label: '平均回報',
    value: 300,
    unit: '%'
  }
])

const availableServers = ref<Server[]>([
  {
    id: '1',
    name: 'DragonCraft',
    code: 'dragon',
    gameType: 'Minecraft',
    status: 'online',
    onlinePlayers: 156,
    maxPlayers: 300,
    ping: 25
  },
  {
    id: '2',
    name: 'SkyBlock Pro',
    code: 'skyblock',
    gameType: 'Minecraft',
    status: 'online',
    onlinePlayers: 89,
    maxPlayers: 200,
    ping: 18
  },
  {
    id: '3',
    name: 'PvP Arena',
    code: 'pvp',
    gameType: 'Minecraft',
    status: 'maintenance',
    onlinePlayers: 0,
    maxPlayers: 150,
    ping: 32
  }
])

const handleGetStarted = () => {
  emit('get-started')
}

const handleViewServers = () => {
  emit('view-servers')
}

const handleServerChange = (server: Server) => {
  selectedServer.value = server
  emit('server-change', server)
}

const handleRefreshServers = () => {
  emit('refresh-servers')
}

onMounted(() => {
  // 添加進場動畫類
  setTimeout(() => {
    document.querySelectorAll('.feature-card').forEach((el, index) => {
      setTimeout(() => {
        el.classList.add('animate-in')
      }, index * 200)
    })
  }, 2000)
})
</script>

<style scoped>
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  background: var(--cyber-bg-primary);
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: var(--z-normal);
  width: 100%;
  padding: 40px 0;
}

.hero-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 60px;
}

.hero-title-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.title-decoration {
  opacity: 0;
  animation: fadeInUp 1s ease 0.5s forwards;
}

.hero-title-container {
  opacity: 0;
  animation: fadeInUp 1s ease 1.5s forwards;
}

.hero-title {
  font-size: 4rem;
  font-weight: 700;
  background: var(--cyber-gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: none;
  margin-bottom: 0;
}

.subtitle-container {
  opacity: 0;
  animation: fadeInUp 1s ease 2s forwards;
}

.hero-subtitle {
  font-size: 1.5rem;
  font-weight: 400;
  margin-bottom: 20px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
  max-width: 1000px;
  width: 100%;
}

.feature-card {
  position: relative;
  padding: 30px 20px;
  background: var(--cyber-bg-card);
  border: 1px solid var(--cyber-border-secondary);
  border-radius: 16px;
  backdrop-filter: blur(20px);
  transition: all var(--transition-normal);
  opacity: 0;
  transform: translateY(30px);
}

.feature-card.animate-in {
  opacity: 1;
  transform: translateY(0);
  animation: featureSlideIn 0.6s ease forwards;
}

.feature-card:hover {
  transform: translateY(-5px);
  border-color: var(--cyber-primary);
  box-shadow: var(--cyber-shadow-glow);
}

.feature-card:hover .feature-glow {
  opacity: 1;
}

.feature-icon {
  width: 60px;
  height: 60px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--cyber-gradient-card);
  border: 2px solid var(--cyber-border-primary);
  border-radius: 12px;
  color: var(--cyber-primary);
  font-size: 24px;
}

.feature-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--cyber-text-primary);
  margin-bottom: 12px;
}

.feature-description {
  color: var(--cyber-text-secondary);
  line-height: 1.6;
  margin: 0;
}

.feature-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at center, rgba(0, 255, 255, 0.1) 0%, transparent 70%);
  border-radius: 16px;
  opacity: 0;
  transition: opacity var(--transition-normal);
  pointer-events: none;
}

.action-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 40px;
  max-width: 600px;
  width: 100%;
}

.action-buttons {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  justify-content: center;
}

.server-selector-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  width: 100%;
}

.selector-label {
  font-size: 14px;
  text-align: center;
}

.stats-section {
  width: 100%;
  max-width: 800px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 30px;
}

.stat-item {
  text-align: center;
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.6s ease forwards;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 8px;
}

.stat-label {
  color: var(--cyber-text-secondary);
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.hero-decorations {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  z-index: var(--z-background);
}

.decoration {
  position: absolute;
  border: 1px solid var(--cyber-border-secondary);
  border-radius: 50%;
}

.decoration-1 {
  top: 10%;
  left: 10%;
  width: 100px;
  height: 100px;
  animation: float 6s ease-in-out infinite;
}

.decoration-2 {
  top: 20%;
  right: 15%;
  width: 150px;
  height: 150px;
  animation: float 8s ease-in-out infinite reverse;
}

.decoration-3 {
  bottom: 15%;
  left: 20%;
  width: 80px;
  height: 80px;
  animation: float 10s ease-in-out infinite;
}

.floating-elements {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.floating-element {
  position: absolute;
  width: 4px;
  height: 4px;
  background: var(--cyber-primary);
  border-radius: 50%;
  box-shadow: 0 0 6px var(--cyber-primary);
}

.element-1 { top: 20%; left: 25%; animation: twinkle 3s ease-in-out infinite; }
.element-2 { top: 30%; right: 30%; animation: twinkle 4s ease-in-out infinite 1s; }
.element-3 { top: 60%; left: 15%; animation: twinkle 5s ease-in-out infinite 2s; }
.element-4 { top: 70%; right: 20%; animation: twinkle 3.5s ease-in-out infinite 0.5s; }
.element-5 { top: 40%; left: 70%; animation: twinkle 4.5s ease-in-out infinite 1.5s; }
.element-6 { bottom: 30%; right: 50%; animation: twinkle 6s ease-in-out infinite 2.5s; }

.scroll-indicator {
  position: absolute;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: var(--cyber-text-secondary);
  animation: bounce 2s infinite;
}

.scroll-arrow {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--cyber-border-primary);
  border-radius: 50%;
  font-size: 16px;
}

.scroll-text {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* 動畫定義 */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes featureSlideIn {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes float {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  25% { transform: translateY(-10px) rotate(90deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
  75% { transform: translateY(-10px) rotate(270deg); }
}

@keyframes twinkle {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.2); }
}

/* 響應式設計 */
@media (max-width: 1200px) {
  .hero-title {
    font-size: 3.5rem;
  }
}

@media (max-width: 768px) {
  .hero-inner {
    gap: 40px;
  }
  
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-subtitle {
    font-size: 1.25rem;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .action-buttons {
    flex-direction: column;
    width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
  
  .stat-value {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .feature-card {
    padding: 20px 16px;
  }
  
  .feature-icon {
    width: 50px;
    height: 50px;
    font-size: 20px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>