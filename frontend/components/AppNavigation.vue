<template>
  <div class="app-navigation" :class="{ 'mobile-nav': isMobile }">
    <!-- Mobile Menu Toggle -->
    <div v-if="isMobile" class="mobile-menu-toggle">
      <el-button 
        @click="toggleMobileMenu"
        :icon="mobileMenuOpen ? Close : Menu"
        circle
        size="small"
      />
    </div>

    <!-- Navigation Menu -->
    <el-menu 
      :default-active="activeMenu" 
      :collapse="collapsed && !isMobile"
      :unique-opened="true"
      router
      class="navigation-menu"
      :class="{ 
        'mobile-menu': isMobile,
        'mobile-menu-open': isMobile && mobileMenuOpen 
      }"
      @select="handleMenuSelect"
    >
      <template v-for="item in menuItems" :key="item.path">
        <!-- Single menu item -->
        <el-menu-item 
          v-if="!item.children" 
          :index="item.path"
          :disabled="item.disabled"
        >
          <el-icon>
            <component :is="item.icon" />
          </el-icon>
          <template #title>
            <span>{{ item.title }}</span>
            <el-badge 
              v-if="item.badge" 
              :value="item.badge" 
              :hidden="item.badge === 0"
              class="menu-badge"
            />
          </template>
        </el-menu-item>
        
        <!-- Submenu -->
        <el-sub-menu 
          v-else 
          :index="item.path"
          :disabled="item.disabled"
        >
          <template #title>
            <el-icon>
              <component :is="item.icon" />
            </el-icon>
            <span>{{ item.title }}</span>
            <el-badge 
              v-if="item.badge" 
              :value="item.badge" 
              :hidden="item.badge === 0"
              class="menu-badge"
            />
          </template>
          
          <el-menu-item 
            v-for="child in item.children" 
            :key="child.path"
            :index="child.path"
            :disabled="child.disabled"
          >
            <el-icon v-if="child.icon">
              <component :is="child.icon" />
            </el-icon>
            <template #title>
              <span>{{ child.title }}</span>
              <el-badge 
                v-if="child.badge" 
                :value="child.badge" 
                :hidden="child.badge === 0"
                class="menu-badge"
              />
            </template>
          </el-menu-item>
        </el-sub-menu>
      </template>
    </el-menu>
  </div>
</template>

<script setup lang="ts">
import {
  Odometer,
  Share,
  Monitor,
  Document,
  Setting,
  User,
  DataAnalysis,
  Calendar,
  Bell,
  Files,
  Management,
  UserFilled,
  Promotion,
  Trophy,
  ChatRound,
  Menu,
  Close
} from '@element-plus/icons-vue'

interface MenuItem {
  title: string
  path: string
  icon: string
  roles: string[]
  badge?: number
  disabled?: boolean
  children?: MenuItem[]
}

interface Props {
  collapsed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  collapsed: false
})

const route = useRoute()
const { getRoleBasedMenuItems, hasAnyRole, canPerformAction } = usePermissions()

// Mobile responsiveness
const { width } = useWindowSize()
const isMobile = computed(() => width.value < 768)
const mobileMenuOpen = ref(false)

// Close mobile menu when route changes
watch(() => route.path, () => {
  if (isMobile.value) {
    mobileMenuOpen.value = false
  }
})

// Close mobile menu when screen size changes
watch(isMobile, (newValue) => {
  if (!newValue) {
    mobileMenuOpen.value = false
  }
})

// Get active menu item
const activeMenu = computed(() => {
  return route.path
})

// Get menu items based on user roles and permissions
const menuItems = computed((): MenuItem[] => {
  const baseMenuItems: MenuItem[] = [
    {
      title: '儀表板',
      path: '/dashboard',
      icon: 'Odometer',
      roles: ['超管', '管理員', '服主', '審核員', '用戶']
    },
    {
      title: '推廣管理',
      path: '/promotion',
      icon: 'Share',
      roles: ['超管', '管理員', '服主', '用戶'],
      children: [
        {
          title: '推廣工具',
          path: '/promotion/tools',
          icon: 'Promotion',
          roles: ['超管', '管理員', '服主', '用戶']
        },
        {
          title: '推廣記錄',
          path: '/promotion/records',
          icon: 'Files',
          roles: ['超管', '管理員', '服主', '用戶']
        },
        {
          title: '推廣統計',
          path: '/promotion/statistics',
          icon: 'DataAnalysis',
          roles: ['超管', '管理員', '服主']
        }
      ]
    },
    {
      title: '伺服器管理',
      path: '/server',
      icon: 'Monitor',
      roles: ['超管', '管理員', '服主'],
      children: [
        {
          title: '我的伺服器',
          path: '/server/list',
          icon: 'Monitor',
          roles: ['超管', '管理員', '服主']
        },
        {
          title: '註冊申請',
          path: '/server/register',
          icon: 'Document',
          roles: ['服主']
        },
        {
          title: '伺服器設定',
          path: '/server/settings',
          icon: 'Setting',
          roles: ['超管', '管理員', '服主']
        }
      ]
    },
    {
      title: '活動管理',
      path: '/events',
      icon: 'Calendar',
      roles: ['超管', '管理員', '服主'],
      children: [
        {
          title: '活動列表',
          path: '/events/list',
          icon: 'Trophy',
          roles: ['超管', '管理員', '服主']
        },
        {
          title: '每日簽到',
          path: '/events/checkin',
          icon: 'Calendar',
          roles: ['超管', '管理員', '服主', '用戶']
        }
      ]
    },
    {
      title: '審核管理',
      path: '/reviewer',
      icon: 'Document',
      roles: ['超管', '管理員', '審核員'],
      badge: getReviewPendingCount(),
      children: [
        {
          title: '推廣審核',
          path: '/reviewer/promotions',
          icon: 'Document',
          roles: ['超管', '管理員', '審核員'],
          badge: getPromotionReviewCount()
        },
        {
          title: '伺服器審核',
          path: '/reviewer/servers',
          icon: 'Monitor',
          roles: ['超管', '管理員'],
          badge: getServerReviewCount()
        }
      ]
    },
    {
      title: '系統管理',
      path: '/admin',
      icon: 'Setting',
      roles: ['超管', '管理員'],
      children: [
        {
          title: '用戶管理',
          path: '/admin/users',
          icon: 'UserFilled',
          roles: ['超管', '管理員']
        },
        {
          title: '角色權限',
          path: '/admin/roles',
          icon: 'Management',
          roles: ['超管']
        },
        {
          title: '系統設定',
          path: '/admin/settings',
          icon: 'Setting',
          roles: ['超管']
        },
        {
          title: '系統日誌',
          path: '/admin/logs',
          icon: 'Files',
          roles: ['超管', '管理員']
        }
      ]
    },
    {
      title: '報表分析',
      path: '/reports',
      icon: 'DataAnalysis',
      roles: ['超管', '管理員', '服主'],
      children: [
        {
          title: '推廣統計',
          path: '/reports/promotions',
          icon: 'DataAnalysis',
          roles: ['超管', '管理員', '服主']
        },
        {
          title: '用戶分析',
          path: '/reports/users',
          icon: 'User',
          roles: ['超管', '管理員']
        },
        {
          title: '收益分析',
          path: '/reports/revenue',
          icon: 'Trophy',
          roles: ['超管', '管理員', '服主']
        }
      ]
    },
    {
      title: '通知中心',
      path: '/notifications',
      icon: 'Bell',
      roles: ['超管', '管理員', '服主', '審核員', '用戶'],
      badge: getNotificationCount()
    },
    {
      title: '個人資料',
      path: '/profile',
      icon: 'User',
      roles: ['超管', '管理員', '服主', '審核員', '用戶']
    }
  ]

  // Filter menu items based on user roles
  return filterMenuItems(baseMenuItems)
})

// Filter menu items based on user permissions
const filterMenuItems = (items: MenuItem[]): MenuItem[] => {
  return items.filter(item => {
    // Check if user has required roles
    if (!hasAnyRole(item.roles)) {
      return false
    }
    
    // Filter children if exists
    if (item.children) {
      item.children = filterMenuItems(item.children)
      // Hide parent if no children are accessible
      return item.children.length > 0
    }
    
    return true
  })
}

// Mock functions for getting badge counts
// These would be replaced with actual API calls
const getReviewPendingCount = (): number => {
  return 5 // Mock count
}

const getPromotionReviewCount = (): number => {
  return 3 // Mock count
}

const getServerReviewCount = (): number => {
  return 2 // Mock count
}

const getNotificationCount = (): number => {
  return 8 // Mock count
}

// Mobile menu methods
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const handleMenuSelect = () => {
  if (isMobile.value) {
    mobileMenuOpen.value = false
  }
}
</script>

<style scoped>
.app-navigation {
  height: calc(100vh - 60px);
  overflow-y: auto;
}

.navigation-menu {
  border: none;
  background: transparent;
}

.navigation-menu .el-menu-item,
.navigation-menu .el-sub-menu__title {
  height: 48px;
  line-height: 48px;
  margin: 4px 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.navigation-menu .el-menu-item:hover,
.navigation-menu .el-sub-menu__title:hover {
  background: var(--el-color-primary-light-9);
  color: var(--el-color-primary);
}

.navigation-menu .el-menu-item.is-active {
  background: var(--el-color-primary);
  color: white;
}

.navigation-menu .el-menu-item.is-active .el-icon {
  color: white;
}

.navigation-menu .el-sub-menu .el-menu-item {
  height: 40px;
  line-height: 40px;
  margin: 2px 16px;
  padding-left: 40px !important;
}

.menu-badge {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
}

/* Collapsed state */
.navigation-menu.el-menu--collapse {
  width: 64px;
}

.navigation-menu.el-menu--collapse .menu-badge {
  display: none;
}

/* Scrollbar styling */
.app-navigation::-webkit-scrollbar {
  width: 4px;
}

.app-navigation::-webkit-scrollbar-track {
  background: transparent;
}

.app-navigation::-webkit-scrollbar-thumb {
  background: var(--el-border-color);
  border-radius: 2px;
}

.app-navigation::-webkit-scrollbar-thumb:hover {
  background: var(--el-border-color-darker);
}

/* Mobile Navigation */
.mobile-nav {
  position: relative;
}

.mobile-menu-toggle {
  position: fixed;
  top: 20px;
  left: 20px;
  z-index: 2000;
  background: var(--el-bg-color);
  border-radius: 50%;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.mobile-menu {
  position: fixed;
  top: 0;
  left: -100%;
  height: 100vh;
  width: 280px;
  background: var(--el-bg-color);
  box-shadow: 2px 0 12px rgba(0, 0, 0, 0.1);
  transition: left 0.3s ease;
  z-index: 1500;
  overflow-y: auto;
  padding-top: 60px;
}

.mobile-menu.mobile-menu-open {
  left: 0;
}

/* Mobile overlay */
.mobile-nav::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1400;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.mobile-nav:has(.mobile-menu-open)::before {
  opacity: 1;
  visibility: visible;
}

/* Improved menu item spacing for mobile */
.mobile-menu .el-menu-item,
.mobile-menu .el-sub-menu__title {
  height: 52px;
  line-height: 52px;
  margin: 2px 8px;
  border-radius: 8px;
  font-size: 16px;
}

.mobile-menu .el-sub-menu .el-menu-item {
  height: 44px;
  line-height: 44px;
  margin: 2px 16px;
  padding-left: 48px !important;
  font-size: 14px;
}

/* Desktop responsiveness */
@media (min-width: 769px) {
  .mobile-menu-toggle {
    display: none;
  }
  
  .mobile-menu {
    position: relative;
    left: 0;
    width: 100%;
    height: auto;
    padding-top: 0;
    box-shadow: none;
  }
}

/* Tablet and small desktop adjustments */
@media (max-width: 1024px) and (min-width: 769px) {
  .navigation-menu .el-menu-item,
  .navigation-menu .el-sub-menu__title {
    margin: 2px 4px;
    font-size: 14px;
  }
  
  .menu-badge {
    right: 12px;
  }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .navigation-menu:not(.mobile-menu) {
    display: none;
  }
  
  .mobile-menu .menu-badge {
    position: static;
    margin-left: auto;
    transform: none;
  }
  
  /* Hide badges in collapsed mobile menu */
  .mobile-menu:not(.mobile-menu-open) .menu-badge {
    display: none;
  }
}
</style>