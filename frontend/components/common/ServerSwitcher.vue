<template>
  <div class="server-switcher">
    <!-- 當前選中的伺服器顯示 -->
    <div 
      class="current-server"
      @click="toggleDropdown"
      :class="{ 'active': isDropdownOpen }"
    >
      <div class="server-info">
        <div class="server-avatar">
          <img 
            v-if="currentServer?.logo" 
            :src="currentServer.logo" 
            :alt="currentServer.name"
            class="avatar-image"
          />
          <div v-else class="avatar-placeholder">
            <el-icon class="server-icon">
              <IconServer />
            </el-icon>
          </div>
        </div>
        <div class="server-details">
          <h3 class="server-name">{{ currentServer?.name || '選擇伺服器' }}</h3>
          <p class="server-type">{{ currentServer?.gameType || '遊戲類型' }}</p>
        </div>
      </div>
      <div class="dropdown-indicator">
        <el-icon class="chevron-icon" :class="{ 'rotated': isDropdownOpen }">
          <IconChevronDown />
        </el-icon>
      </div>
      <div class="glow-border"></div>
    </div>

    <!-- 伺服器選擇下拉選單 -->
    <transition name="dropdown" appear>
      <div v-if="isDropdownOpen" class="server-dropdown">
        <div class="dropdown-content">
          <div class="dropdown-header">
            <h4 class="cyber-text-neon">選擇遊戲伺服器</h4>
            <div class="cyber-divider"></div>
          </div>
          
          <div class="server-grid">
            <div 
              v-for="server in availableServers" 
              :key="server.id"
              class="server-option"
              :class="{ 
                'selected': currentServer?.id === server.id,
                'online': server.status === 'online',
                'offline': server.status === 'offline'
              }"
              @click="selectServer(server)"
            >
              <div class="server-card">
                <div class="server-status-indicator">
                  <div class="status-dot" :class="server.status"></div>
                </div>
                
                <div class="server-logo">
                  <img 
                    v-if="server.logo" 
                    :src="server.logo" 
                    :alt="server.name"
                    class="logo-image"
                  />
                  <div v-else class="logo-placeholder">
                    <el-icon class="placeholder-icon">
                      <IconServer />
                    </el-icon>
                  </div>
                </div>
                
                <div class="server-info-card">
                  <h5 class="server-name-card">{{ server.name }}</h5>
                  <p class="server-game-type">{{ server.gameType }}</p>
                  <div class="server-stats">
                    <span class="stat-item">
                      <el-icon><IconUser /></el-icon>
                      {{ server.onlinePlayers }}/{{ server.maxPlayers }}
                    </span>
                    <span class="stat-item">
                      <el-icon><IconClock /></el-icon>
                      {{ server.ping }}ms
                    </span>
                  </div>
                </div>
                
                <div class="selection-indicator">
                  <el-icon v-if="currentServer?.id === server.id" class="check-icon">
                    <IconCheck />
                  </el-icon>
                </div>
              </div>
            </div>
          </div>
          
          <div class="dropdown-footer">
            <glow-button 
              variant="secondary" 
              size="small"
              @click="refreshServers"
              :loading="isRefreshing"
            >
              <el-icon><IconRefresh /></el-icon>
              重新整理
            </glow-button>
          </div>
        </div>
      </div>
    </transition>

    <!-- 背景遮罩 -->
    <div 
      v-if="isDropdownOpen" 
      class="dropdown-backdrop"
      @click="closeDropdown"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import GlowButton from '~/components/effects/GlowButton.vue'

// Icons (假設使用 Element Plus Icons)
import {
  ArrowDown as IconChevronDown,
  Check as IconCheck,
  Refresh as IconRefresh,
  User as IconUser,
  Clock as IconClock,
  Monitor as IconServer
} from '@element-plus/icons-vue'

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
  description?: string
}

interface Props {
  modelValue?: Server | null
  servers?: Server[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  servers: () => [],
  loading: false
})

const emit = defineEmits<{
  'update:modelValue': [server: Server | null]
  'server-change': [server: Server]
  'refresh': []
}>()

const isDropdownOpen = ref(false)
const isRefreshing = ref(false)

const currentServer = computed(() => props.modelValue)
const availableServers = computed(() => props.servers)

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value
}

const closeDropdown = () => {
  isDropdownOpen.value = false
}

const selectServer = (server: Server) => {
  emit('update:modelValue', server)
  emit('server-change', server)
  closeDropdown()
}

const refreshServers = async () => {
  isRefreshing.value = true
  emit('refresh')
  
  // 模擬刷新延遲
  setTimeout(() => {
    isRefreshing.value = false
  }, 1000)
}

// 點擊外部關閉下拉選單
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.server-switcher')) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.server-switcher {
  position: relative;
  min-width: 300px;
}

.current-server {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--cyber-bg-card);
  border: 2px solid var(--cyber-border-secondary);
  border-radius: 12px;
  cursor: pointer;
  transition: all var(--transition-normal);
  backdrop-filter: blur(20px);
  overflow: hidden;
}

.current-server:hover {
  border-color: var(--cyber-border-primary);
  box-shadow: var(--cyber-shadow-md);
}

.current-server.active {
  border-color: var(--cyber-primary);
  box-shadow: var(--cyber-shadow-glow);
}

.server-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.server-avatar {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid var(--cyber-border-primary);
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--cyber-gradient-card);
  color: var(--cyber-primary);
}

.server-icon {
  font-size: 20px;
}

.server-details {
  flex: 1;
}

.server-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--cyber-text-primary);
  margin: 0 0 4px 0;
}

.server-type {
  font-size: 12px;
  color: var(--cyber-text-secondary);
  margin: 0;
}

.dropdown-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.chevron-icon {
  font-size: 16px;
  color: var(--cyber-primary);
  transition: transform var(--transition-normal);
}

.chevron-icon.rotated {
  transform: rotate(180deg);
}

.glow-border {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 12px;
  padding: 2px;
  background: linear-gradient(45deg, var(--cyber-primary), var(--cyber-secondary));
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask-composite: exclude;
  opacity: 0;
  transition: opacity var(--transition-normal);
}

.current-server.active .glow-border {
  opacity: 0.6;
}

.server-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  z-index: var(--z-dropdown);
  background: var(--cyber-bg-card);
  border: 2px solid var(--cyber-border-primary);
  border-radius: 16px;
  backdrop-filter: blur(20px);
  box-shadow: var(--cyber-shadow-lg);
  max-height: 400px;
  overflow-y: auto;
}

.dropdown-content {
  padding: 20px;
}

.dropdown-header h4 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  text-align: center;
}

.server-grid {
  display: grid;
  gap: 12px;
  margin-bottom: 20px;
}

.server-option {
  cursor: pointer;
  transition: all var(--transition-normal);
}

.server-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: var(--cyber-bg-glass);
  border: 1px solid var(--cyber-border-secondary);
  border-radius: 12px;
  transition: all var(--transition-normal);
}

.server-option:hover .server-card {
  border-color: var(--cyber-border-primary);
  box-shadow: var(--cyber-shadow-sm);
  transform: translateY(-1px);
}

.server-option.selected .server-card {
  border-color: var(--cyber-primary);
  background: var(--cyber-gradient-card);
  box-shadow: var(--cyber-shadow-md);
}

.server-status-indicator {
  position: absolute;
  top: 8px;
  right: 8px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  position: relative;
}

.status-dot.online {
  background: var(--cyber-success);
  box-shadow: 0 0 10px var(--cyber-success);
}

.status-dot.offline {
  background: var(--cyber-text-muted);
}

.status-dot.maintenance {
  background: var(--cyber-warning);
  box-shadow: 0 0 10px var(--cyber-warning);
}

.status-dot.online::after {
  content: '';
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  border-radius: 50%;
  border: 1px solid var(--cyber-success);
  animation: ping 2s infinite;
}

@keyframes ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

.server-logo {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid var(--cyber-border-secondary);
  flex-shrink: 0;
}

.logo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.logo-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--cyber-bg-tertiary);
  color: var(--cyber-text-secondary);
}

.placeholder-icon {
  font-size: 16px;
}

.server-info-card {
  flex: 1;
}

.server-name-card {
  font-size: 14px;
  font-weight: 600;
  color: var(--cyber-text-primary);
  margin: 0 0 4px 0;
}

.server-game-type {
  font-size: 12px;
  color: var(--cyber-text-secondary);
  margin: 0 0 8px 0;
}

.server-stats {
  display: flex;
  gap: 12px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--cyber-text-muted);
}

.selection-indicator {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.check-icon {
  color: var(--cyber-primary);
  font-size: 16px;
}

.dropdown-footer {
  display: flex;
  justify-content: center;
  padding-top: 16px;
  border-top: 1px solid var(--cyber-border-secondary);
}

.dropdown-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: calc(var(--z-dropdown) - 1);
  background: rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(2px);
}

/* 下拉動畫 */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-5px) scale(0.98);
}

/* 響應式設計 */
@media (max-width: 768px) {
  .server-switcher {
    min-width: 250px;
  }
  
  .current-server {
    padding: 12px 16px;
  }
  
  .server-avatar {
    width: 40px;
    height: 40px;
  }
  
  .server-name {
    font-size: 14px;
  }
  
  .server-type {
    font-size: 11px;
  }
  
  .dropdown-content {
    padding: 16px;
  }
  
  .server-card {
    padding: 12px;
  }
}
</style>