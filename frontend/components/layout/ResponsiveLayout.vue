<template>
  <div 
    class="responsive-layout"
    :class="[
      `layout-${layoutType}`,
      { 'sidebar-collapsed': sidebarCollapsed }
    ]"
  >
    <!-- 頂部導航 -->
    <header v-if="showHeader" class="layout-header">
      <div class="responsive-container">
        <nav class="responsive-nav">
          <div class="nav-brand">
            <slot name="brand">
              <h1 class="brand-title unified-heading unified-heading-3">
                {{ title }}
              </h1>
            </slot>
          </div>
          
          <div class="nav-actions">
            <slot name="nav-actions">
              <!-- 預設導航動作 -->
              <ThemeSwitcher v-if="showThemeSwitcher" />
            </slot>
            
            <!-- 移動端選單按鈕 -->
            <button 
              v-if="showMobileMenu"
              class="mobile-menu-btn unified-btn unified-btn-secondary sm:responsive-hide"
              @click="toggleMobileMenu"
              :aria-label="mobileMenuOpen ? '關閉選單' : '開啟選單'"
            >
              <el-icon>
                <IconClose v-if="mobileMenuOpen" />
                <IconMenu v-else />
              </el-icon>
            </button>
          </div>
        </nav>
      </div>
    </header>

    <!-- 主要佈局容器 -->
    <div class="layout-container">
      <!-- 側邊欄 -->
      <aside 
        v-if="showSidebar" 
        class="layout-sidebar"
        :class="{ 
          'sidebar-open': mobileMenuOpen,
          'sidebar-collapsed': sidebarCollapsed 
        }"
      >
        <div class="sidebar-content">
          <slot name="sidebar">
            <!-- 預設側邊欄內容 -->
            <nav class="sidebar-nav">
              <div class="nav-section">
                <h3 class="nav-section-title">主要功能</h3>
                <ul class="nav-list">
                  <li><a href="#" class="nav-link">儀表板</a></li>
                  <li><a href="#" class="nav-link">推廣管理</a></li>
                  <li><a href="#" class="nav-link">活動中心</a></li>
                  <li><a href="#" class="nav-link">統計分析</a></li>
                </ul>
              </div>
            </nav>
          </slot>
        </div>
        
        <!-- 側邊欄切換按鈕 -->
        <button 
          v-if="allowSidebarCollapse"
          class="sidebar-toggle-btn"
          @click="toggleSidebar"
          :aria-label="sidebarCollapsed ? '展開側邊欄' : '收合側邊欄'"
        >
          <el-icon>
            <IconArrowRight v-if="sidebarCollapsed" />
            <IconArrowLeft v-else />
          </el-icon>
        </button>
      </aside>

      <!-- 移動端側邊欄遮罩 -->
      <div 
        v-if="showSidebar && mobileMenuOpen" 
        class="sidebar-overlay sm:responsive-hide"
        @click="closeMobileMenu"
      ></div>

      <!-- 主要內容區域 -->
      <main class="layout-main">
        <div 
          class="main-content"
          :class="[
            contentPadding ? 'responsive-container' : 'responsive-container-fluid'
          ]"
        >
          <!-- 麵包屑導航 -->
          <nav v-if="showBreadcrumb" class="breadcrumb-nav">
            <slot name="breadcrumb">
              <ol class="breadcrumb-list">
                <li class="breadcrumb-item">
                  <a href="#" class="breadcrumb-link">首頁</a>
                </li>
                <li class="breadcrumb-item current">
                  <span>當前頁面</span>
                </li>
              </ol>
            </slot>
          </nav>

          <!-- 頁面標題區域 -->
          <div v-if="showPageHeader" class="page-header">
            <slot name="page-header">
              <div class="page-title-section">
                <h1 class="page-title unified-heading unified-heading-2">
                  {{ pageTitle }}
                </h1>
                <p v-if="pageDescription" class="page-description">
                  {{ pageDescription }}
                </p>
              </div>
              <div class="page-actions">
                <slot name="page-actions"></slot>
              </div>
            </slot>
          </div>

          <!-- 主要內容插槽 -->
          <div class="content-area">
            <slot></slot>
          </div>
        </div>
      </main>
    </div>

    <!-- 底部 -->
    <footer v-if="showFooter" class="layout-footer">
      <div class="responsive-container">
        <slot name="footer">
          <div class="footer-content">
            <p class="footer-text">
              © 2024 遊戲伺服器推廣平台. All rights reserved.
            </p>
          </div>
        </slot>
      </div>
    </footer>

    <!-- 返回頂部按鈕 -->
    <button 
      v-if="showBackToTop && showBackToTopBtn"
      class="back-to-top-btn unified-btn unified-btn-primary"
      @click="scrollToTop"
      :aria-label="'返回頂部'"
    >
      <el-icon>
        <IconArrowUp />
      </el-icon>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import ThemeSwitcher from '~/components/common/ThemeSwitcher.vue'
import {
  Menu as IconMenu,
  Close as IconClose,
  ArrowLeft as IconArrowLeft,
  ArrowRight as IconArrowRight,
  ArrowUp as IconArrowUp
} from '@element-plus/icons-vue'

interface Props {
  layoutType?: 'default' | 'sidebar' | 'centered' | 'wide'
  title?: string
  pageTitle?: string
  pageDescription?: string
  showHeader?: boolean
  showSidebar?: boolean
  showFooter?: boolean
  showBreadcrumb?: boolean
  showPageHeader?: boolean
  showMobileMenu?: boolean
  showThemeSwitcher?: boolean
  showBackToTop?: boolean
  allowSidebarCollapse?: boolean
  contentPadding?: boolean
  sidebarDefaultCollapsed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  layoutType: 'default',
  title: '推廣平台',
  pageTitle: '',
  pageDescription: '',
  showHeader: true,
  showSidebar: false,
  showFooter: true,
  showBreadcrumb: false,
  showPageHeader: false,
  showMobileMenu: true,
  showThemeSwitcher: true,
  showBackToTop: true,
  allowSidebarCollapse: true,
  contentPadding: true,
  sidebarDefaultCollapsed: false
})

const emit = defineEmits<{
  'sidebar-toggle': [collapsed: boolean]
  'mobile-menu-toggle': [open: boolean]
}>()

// 響應式狀態
const mobileMenuOpen = ref(false)
const sidebarCollapsed = ref(props.sidebarDefaultCollapsed)
const showBackToTopBtn = ref(false)
const scrollY = ref(0)

// 計算屬性
const isDesktop = computed(() => {
  if (typeof window === 'undefined') return true
  return window.innerWidth >= 1024
})

// 方法
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
  emit('mobile-menu-toggle', mobileMenuOpen.value)
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
  emit('mobile-menu-toggle', false)
}

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
  emit('sidebar-toggle', sidebarCollapsed.value)
}

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

const handleScroll = () => {
  scrollY.value = window.scrollY
  showBackToTopBtn.value = scrollY.value > 300
}

const handleResize = () => {
  // 桌面模式下自動關閉移動端選單
  if (isDesktop.value && mobileMenuOpen.value) {
    closeMobileMenu()
  }
}

// 生命週期
onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('resize', handleResize)
  handleScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('resize', handleResize)
})

// 暴露方法
defineExpose({
  toggleMobileMenu,
  closeMobileMenu,
  toggleSidebar,
  scrollToTop
})
</script>

<style scoped>
.responsive-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
}

/* ========================================
   頂部導航
   ======================================== */

.layout-header {
  position: sticky;
  top: 0;
  z-index: var(--z-sticky);
  background: var(--current-bg-primary, var(--bg-primary));
  border-bottom: 1px solid;
  border-color: var(--current-border, var(--border-primary));
  backdrop-filter: blur(20px);
}

.theme-frontend .layout-header {
  background: rgba(10, 10, 15, 0.9);
  border-color: rgba(0, 255, 255, 0.2);
  box-shadow: 0 0 20px rgba(0, 255, 255, 0.1);
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.brand-title {
  margin: 0;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.mobile-menu-btn {
  padding: var(--space-2);
  min-width: auto;
}

/* ========================================
   佈局容器
   ======================================== */

.layout-container {
  flex: 1;
  display: flex;
  position: relative;
}

/* ========================================
   側邊欄
   ======================================== */

.layout-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 280px;
  background: var(--current-bg-secondary, var(--bg-secondary));
  border-right: 1px solid;
  border-color: var(--current-border, var(--border-primary));
  z-index: var(--z-fixed);
  transition: all var(--transition-normal);
  transform: translateX(-100%);
}

.layout-sidebar.sidebar-open {
  transform: translateX(0);
}

.layout-sidebar.sidebar-collapsed {
  width: 80px;
}

.theme-frontend .layout-sidebar {
  background: var(--cyber-bg-secondary);
  border-color: rgba(0, 255, 255, 0.2);
  backdrop-filter: blur(20px);
}

@media (min-width: 1024px) {
  .layout-sidebar {
    position: sticky;
    top: var(--navbar-height, 64px);
    height: calc(100vh - var(--navbar-height, 64px));
    transform: translateX(0);
  }
  
  .layout-main {
    margin-left: 280px;
    transition: margin-left var(--transition-normal);
  }
  
  .sidebar-collapsed + .layout-main {
    margin-left: 80px;
  }
}

.sidebar-content {
  height: 100%;
  overflow-y: auto;
  padding: var(--space-6) var(--space-4);
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.nav-section-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--current-text-secondary, var(--text-secondary));
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: var(--space-3);
}

.nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.nav-link {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  color: var(--current-text-primary, var(--text-primary));
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  font-weight: 500;
}

.nav-link:hover {
  background: var(--current-bg-tertiary, var(--bg-tertiary));
}

.theme-frontend .nav-link:hover {
  background: rgba(0, 255, 255, 0.1);
  color: var(--cyber-primary);
  box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
}

.sidebar-toggle-btn {
  position: absolute;
  right: -12px;
  top: 20px;
  width: 24px;
  height: 24px;
  background: var(--current-bg-primary, var(--bg-primary));
  border: 1px solid var(--current-border, var(--border-primary));
  border-radius: var(--radius-full);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--transition-fast);
  font-size: 12px;
}

.sidebar-toggle-btn:hover {
  background: var(--current-bg-tertiary, var(--bg-tertiary));
}

.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: calc(var(--z-fixed) - 1);
  backdrop-filter: blur(4px);
}

/* ========================================
   主要內容
   ======================================== */

.layout-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding: var(--space-6) 0;
}

/* ========================================
   麵包屑導航
   ======================================== */

.breadcrumb-nav {
  margin-bottom: var(--space-6);
}

.breadcrumb-list {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: var(--text-sm);
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.breadcrumb-item:not(:last-child):after {
  content: '/';
  color: var(--current-text-muted, var(--text-muted));
}

.breadcrumb-link {
  color: var(--current-text-secondary, var(--text-secondary));
  text-decoration: none;
  transition: color var(--transition-fast);
}

.breadcrumb-link:hover {
  color: var(--current-accent, var(--primary-500));
}

.breadcrumb-item.current span {
  color: var(--current-text-primary, var(--text-primary));
  font-weight: 500;
}

/* ========================================
   頁面標題
   ======================================== */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--space-8);
  gap: var(--space-4);
}

.page-title {
  margin: 0 0 var(--space-2) 0;
}

.page-description {
  color: var(--current-text-secondary, var(--text-secondary));
  margin: 0;
  font-size: var(--text-lg);
}

.page-actions {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-shrink: 0;
}

@media (max-width: 767px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .page-actions {
    justify-content: flex-start;
  }
}

/* ========================================
   內容區域
   ======================================== */

.content-area {
  flex: 1;
}

/* ========================================
   底部
   ======================================== */

.layout-footer {
  background: var(--current-bg-secondary, var(--bg-secondary));
  border-top: 1px solid var(--current-border, var(--border-primary));
  padding: var(--space-6) 0;
  margin-top: auto;
}

.footer-content {
  text-align: center;
}

.footer-text {
  color: var(--current-text-secondary, var(--text-secondary));
  margin: 0;
  font-size: var(--text-sm);
}

/* ========================================
   返回頂部按鈕
   ======================================== */

.back-to-top-btn {
  position: fixed;
  bottom: var(--space-6);
  right: var(--space-6);
  width: 48px;
  height: 48px;
  border-radius: var(--radius-full);
  z-index: var(--z-sticky);
  padding: 0;
  min-width: auto;
  box-shadow: var(--shadow-lg);
}

.theme-frontend .back-to-top-btn {
  box-shadow: var(--shadow-glow-md);
}

/* ========================================
   佈局變體
   ======================================== */

.layout-centered .main-content {
  max-width: 800px;
  margin: 0 auto;
}

.layout-wide .main-content {
  max-width: none;
}

/* ========================================
   響應式調整
   ======================================== */

@media (max-width: 1023px) {
  .layout-main {
    margin-left: 0 !important;
  }
}

@media (max-width: 767px) {
  .main-content {
    padding: var(--space-4) 0;
  }
  
  .page-header {
    margin-bottom: var(--space-6);
  }
  
  .back-to-top-btn {
    bottom: var(--space-4);
    right: var(--space-4);
    width: 40px;
    height: 40px;
  }
}

@media (max-width: 639px) {
  .nav-actions {
    gap: var(--space-2);
  }
  
  .breadcrumb-nav {
    margin-bottom: var(--space-4);
  }
}

/* ========================================
   載入狀態
   ======================================== */

.layout-loading {
  pointer-events: none;
  opacity: 0.6;
}

.layout-loading * {
  cursor: wait !important;
}
</style>