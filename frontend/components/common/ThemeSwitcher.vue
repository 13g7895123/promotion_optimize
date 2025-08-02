<template>
  <div class="theme-switcher">
    <button 
      class="theme-toggle-btn"
      @click="toggleTheme"
      :title="currentTheme === 'frontend' ? '切換到管理後台模式' : '切換到前台模式'"
    >
      <div class="toggle-track">
        <div 
          class="toggle-thumb"
          :class="{ 'active': currentTheme === 'backend' }"
        >
          <div class="thumb-icon">
            <el-icon v-if="currentTheme === 'frontend'">
              <IconGamepad />
            </el-icon>
            <el-icon v-else>
              <IconSettings />
            </el-icon>
          </div>
        </div>
      </div>
      <div class="toggle-labels">
        <span 
          class="toggle-label"
          :class="{ 'active': currentTheme === 'frontend' }"
        >
          前台模式
        </span>
        <span 
          class="toggle-label"
          :class="{ 'active': currentTheme === 'backend' }"
        >
          後台模式
        </span>
      </div>
    </button>
    
    <!-- 深色模式切換（僅在後台模式顯示） -->
    <div v-if="currentTheme === 'backend'" class="dark-mode-toggle">
      <button 
        class="dark-toggle-btn unified-btn unified-btn-secondary"
        @click="toggleDarkMode"
        :title="isDarkMode ? '切換到淺色模式' : '切換到深色模式'"
      >
        <el-icon>
          <IconSun v-if="isDarkMode" />
          <IconMoon v-else />
        </el-icon>
        {{ isDarkMode ? '淺色' : '深色' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { 
  Setting as IconSettings,
  Sunny as IconSun,
  Moon as IconMoon
} from '@element-plus/icons-vue'

// 自定義遊戲手把圖標組件
const IconGamepad = {
  name: 'IconGamepad',
  render() {
    return h('svg', {
      xmlns: 'http://www.w3.org/2000/svg',
      viewBox: '0 0 24 24',
      fill: 'none',
      stroke: 'currentColor',
      'stroke-width': '2',
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round'
    }, [
      h('line', { x1: '6', y1: '11', x2: '10', y2: '11' }),
      h('line', { x1: '8', y1: '9', x2: '8', y2: '13' }),
      h('line', { x1: '15', y1: '12', x2: '15.01', y2: '12' }),
      h('line', { x1: '18', y1: '10', x2: '18.01', y2: '10' }),
      h('path', { d: 'M17.32 5H6.68a4 4 0 0 0-4.12 4.42l1.54 9A4 4 0 0 0 8.18 22h7.64a4 4 0 0 0 4.08-3.58l1.54-9A4 4 0 0 0 17.32 5Z' })
    ])
  }
}

interface Props {
  modelValue?: 'frontend' | 'backend'
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: 'frontend'
})

const emit = defineEmits<{
  'update:modelValue': [theme: 'frontend' | 'backend']
  'theme-change': [theme: 'frontend' | 'backend', darkMode: boolean]
}>()

const currentTheme = ref<'frontend' | 'backend'>(props.modelValue)
const isDarkMode = ref(false)

// 監聽 props 變化
watch(() => props.modelValue, (newValue) => {
  currentTheme.value = newValue
  applyTheme()
})

// 監聽主題變化
watch(currentTheme, () => {
  emit('update:modelValue', currentTheme.value)
  applyTheme()
  saveThemePreference()
})

// 監聽深色模式變化
watch(isDarkMode, () => {
  applyTheme()
  saveThemePreference()
})

const toggleTheme = () => {
  currentTheme.value = currentTheme.value === 'frontend' ? 'backend' : 'frontend'
  
  // 切換到前台時自動關閉深色模式
  if (currentTheme.value === 'frontend') {
    isDarkMode.value = false
  }
}

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
}

const applyTheme = () => {
  const htmlElement = document.documentElement
  
  // 移除所有主題類
  htmlElement.classList.remove('theme-frontend', 'theme-backend', 'dark')
  
  // 應用新主題
  if (currentTheme.value === 'frontend') {
    htmlElement.classList.add('theme-frontend')
  } else {
    htmlElement.classList.add('theme-backend')
    if (isDarkMode.value) {
      htmlElement.classList.add('dark')
    }
  }
  
  // 觸發主題變化事件
  emit('theme-change', currentTheme.value, isDarkMode.value)
  
  // 更新 CSS 變量
  updateCSSVariables()
}

const updateCSSVariables = () => {
  const root = document.documentElement
  
  if (currentTheme.value === 'frontend') {
    // 前台科技風變量
    root.style.setProperty('--current-primary', 'var(--cyber-primary)')
    root.style.setProperty('--current-accent', 'var(--cyber-accent)')
    root.style.setProperty('--current-bg', 'var(--cyber-bg-primary)')
  } else {
    // 後台現代化變量
    if (isDarkMode.value) {
      root.style.setProperty('--current-primary', 'var(--primary-500)')
      root.style.setProperty('--current-accent', 'var(--primary-400)')
      root.style.setProperty('--current-bg', 'var(--dark-bg-primary)')
    } else {
      root.style.setProperty('--current-primary', 'var(--primary-500)')
      root.style.setProperty('--current-accent', 'var(--primary-600)')
      root.style.setProperty('--current-bg', 'var(--bg-primary)')
    }
  }
}

const saveThemePreference = () => {
  const preferences = {
    theme: currentTheme.value,
    darkMode: isDarkMode.value
  }
  localStorage.setItem('theme-preferences', JSON.stringify(preferences))
}

const loadThemePreference = () => {
  try {
    const saved = localStorage.getItem('theme-preferences')
    if (saved) {
      const preferences = JSON.parse(saved)
      currentTheme.value = preferences.theme || 'frontend'
      isDarkMode.value = preferences.darkMode || false
    }
  } catch (error) {
    console.warn('無法載入主題偏好設定:', error)
  }
}

// 系統深色模式偵測
const detectSystemDarkMode = () => {
  if (typeof window !== 'undefined') {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    
    // 只在後台模式且沒有手動設定時使用系統偏好
    if (currentTheme.value === 'backend' && !localStorage.getItem('theme-preferences')) {
      isDarkMode.value = mediaQuery.matches
    }
    
    // 監聽系統主題變化
    mediaQuery.addEventListener('change', (e) => {
      if (currentTheme.value === 'backend' && !localStorage.getItem('manual-dark-mode')) {
        isDarkMode.value = e.matches
      }
    })
  }
}

onMounted(() => {
  loadThemePreference()
  detectSystemDarkMode()
  applyTheme()
})

// 暴露方法
defineExpose({
  toggleTheme,
  toggleDarkMode,
  applyTheme
})
</script>

<style scoped>
.theme-switcher {
  display: flex;
  align-items: center;
  gap: 16px;
}

.theme-toggle-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px;
  background: transparent;
  border: 1px solid;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: all var(--transition-normal);
  font-family: var(--font-sans);
}

.theme-frontend .theme-toggle-btn {
  border-color: rgba(0, 255, 255, 0.3);
  color: var(--cyber-text-primary);
}

.theme-frontend .theme-toggle-btn:hover {
  border-color: rgba(0, 255, 255, 0.6);
  box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
}

.theme-backend .theme-toggle-btn {
  border-color: var(--border-primary);
  color: var(--text-primary);
}

.theme-backend .theme-toggle-btn:hover {
  border-color: var(--primary-500);
  box-shadow: var(--shadow-md);
}

.theme-backend.dark .theme-toggle-btn {
  border-color: var(--dark-border-primary);
  color: var(--dark-text-primary);
}

.toggle-track {
  position: relative;
  width: 48px;
  height: 24px;
  background: var(--current-bg-secondary, var(--gray-200));
  border-radius: var(--radius-full);
  transition: all var(--transition-normal);
}

.theme-frontend .toggle-track {
  background: rgba(0, 255, 255, 0.1);
  border: 1px solid rgba(0, 255, 255, 0.3);
}

.toggle-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  background: var(--current-accent, var(--primary-500));
  border-radius: var(--radius-full);
  transition: all var(--transition-normal);
  display: flex;
  align-items: center;
  justify-content: center;
  transform: translateX(0);
}

.toggle-thumb.active {
  transform: translateX(24px);
}

.theme-frontend .toggle-thumb {
  background: var(--cyber-primary);
  box-shadow: 0 0 10px var(--cyber-primary);
}

.thumb-icon {
  font-size: 12px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toggle-labels {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.toggle-label {
  font-size: 11px;
  font-weight: 500;
  opacity: 0.6;
  transition: all var(--transition-fast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.toggle-label.active {
  opacity: 1;
  font-weight: 600;
}

.theme-frontend .toggle-label.active {
  color: var(--cyber-primary);
  text-shadow: 0 0 8px var(--cyber-primary);
}

.theme-backend .toggle-label.active {
  color: var(--primary-500);
}

.dark-mode-toggle {
  display: flex;
  align-items: center;
}

.dark-toggle-btn {
  font-size: 12px;
  padding: 6px 12px;
  min-width: auto;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .theme-switcher {
    flex-direction: column;
    gap: 12px;
  }
  
  .toggle-labels {
    display: none;
  }
  
  .theme-toggle-btn {
    padding: 6px 12px;
  }
}

@media (max-width: 480px) {
  .theme-switcher {
    align-items: stretch;
  }
  
  .theme-toggle-btn,
  .dark-toggle-btn {
    width: 100%;
    justify-content: center;
  }
}

/* 高對比度支援 */
@media (prefers-contrast: high) {
  .toggle-track {
    border: 2px solid currentColor;
  }
  
  .toggle-thumb {
    border: 2px solid white;
  }
}

/* 減少動畫支援 */
@media (prefers-reduced-motion: reduce) {
  .toggle-thumb {
    transition: none;
  }
  
  .toggle-label {
    transition: none;
  }
}
</style>