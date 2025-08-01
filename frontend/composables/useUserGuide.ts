interface GuideStep {
  target: string | HTMLElement
  title?: string
  description: string
  position?: 'top' | 'bottom' | 'left' | 'right' | 'auto'
  component?: any
  props?: Record<string, any>
  beforeShow?: () => Promise<void> | void
  afterShow?: () => Promise<void> | void
  allowClickTarget?: boolean
  showOverlay?: boolean
}

interface GuideConfig {
  id: string
  name: string
  steps: GuideStep[]
  autoStart?: boolean
  showOnFirstVisit?: boolean
  skipCondition?: () => boolean
}

/**
 * User guide management composable
 */
export const useUserGuide = () => {
  const showGuide = ref(false)
  const currentGuide = ref<GuideConfig | null>(null)
  const completedGuides = ref<string[]>([])

  // Load completed guides from storage
  const loadCompletedGuides = () => {
    if (process.client) {
      const stored = localStorage.getItem('completed-guides')
      if (stored) {
        try {
          completedGuides.value = JSON.parse(stored)
        } catch (error) {
          console.warn('Failed to parse completed guides:', error)
        }
      }
    }
  }

  // Save completed guides to storage
  const saveCompletedGuides = () => {
    if (process.client) {
      localStorage.setItem('completed-guides', JSON.stringify(completedGuides.value))
    }
  }

  // Mark guide as completed
  const markGuideCompleted = (guideId: string) => {
    if (!completedGuides.value.includes(guideId)) {
      completedGuides.value.push(guideId)
      saveCompletedGuides()
    }
  }

  // Check if guide is completed
  const isGuideCompleted = (guideId: string): boolean => {
    return completedGuides.value.includes(guideId)
  }

  // Start a guide
  const startGuide = (guide: GuideConfig) => {
    // Check skip condition
    if (guide.skipCondition && guide.skipCondition()) {
      return
    }

    // Check if already completed and shouldn't show again
    if (guide.showOnFirstVisit !== false && isGuideCompleted(guide.id)) {
      return
    }

    currentGuide.value = guide
    showGuide.value = true
  }

  // Finish current guide
  const finishGuide = () => {
    if (currentGuide.value) {
      markGuideCompleted(currentGuide.value.id)
      currentGuide.value = null
    }
    showGuide.value = false
  }

  // Skip current guide
  const skipGuide = () => {
    if (currentGuide.value) {
      markGuideCompleted(currentGuide.value.id)
      currentGuide.value = null
    }
    showGuide.value = false
  }

  // Reset guide completion (for development/testing)
  const resetGuide = (guideId: string) => {
    completedGuides.value = completedGuides.value.filter(id => id !== guideId)
    saveCompletedGuides()
  }

  // Reset all guides
  const resetAllGuides = () => {
    completedGuides.value = []
    saveCompletedGuides()
  }

  // Initialize on mount
  onMounted(() => {
    loadCompletedGuides()
  })

  return {
    showGuide: readonly(showGuide),
    currentGuide: readonly(currentGuide),
    completedGuides: readonly(completedGuides),
    startGuide,
    finishGuide,
    skipGuide,
    isGuideCompleted,
    markGuideCompleted,
    resetGuide,
    resetAllGuides
  }
}

/**
 * Predefined guide configurations
 */
export const useGuideConfigs = () => {
  const { hasAnyRole } = usePermissions()

  // Dashboard guide for new users
  const dashboardGuide: GuideConfig = {
    id: 'dashboard-intro',
    name: '儀表板介紹',
    showOnFirstVisit: true,
    steps: [
      {
        target: '.layout-header .mobile-logo, .sidebar-header .logo',
        title: '歡迎使用遊戲推廣平台！',
        description: '讓我們來快速了解一下平台的主要功能。這裡是平台標誌，您可以隨時點擊回到首頁。',
        position: 'bottom'
      },
      {
        target: '.navigation-menu',
        title: '導航功能表',
        description: '左側是主要的導航功能表，您可以在這裡找到所有可用的功能模組。',
        position: 'right'
      },
      {
        target: '.header-right',
        title: '用戶功能區',
        description: '右上角是您的個人功能區，包括通知中心和個人設定。',
        position: 'bottom'
      }
    ]
  }

  // Promotion guide for regular users
  const promotionGuide: GuideConfig = {
    id: 'promotion-intro',
    name: '推廣功能介紹',
    showOnFirstVisit: true,
    skipCondition: () => !hasAnyRole(['超管', '管理員', '服主', '用戶']),
    steps: [
      {
        target: '[href="/promotion"]',
        title: '推廣管理',
        description: '這裡是推廣管理功能，您可以建立推廣連結、查看推廣記錄和統計數據。',
        position: 'right',
        beforeShow: async () => {
          // Ensure menu is expanded
          const menuItem = document.querySelector('[href="/promotion"]')
          if (menuItem) {
            const parent = menuItem.closest('.el-sub-menu')
            if (parent) {
              const titleElement = parent.querySelector('.el-sub-menu__title') as HTMLElement
              titleElement?.click()
              await new Promise(resolve => setTimeout(resolve, 300))
            }
          }
        }
      },
      {
        target: '[href="/promotion/tools"]',
        title: '推廣工具',
        description: '在推廣工具中，您可以生成專屬的推廣連結和推廣素材。',
        position: 'right'
      },
      {
        target: '[href="/promotion/records"]',
        title: '推廣記錄',
        description: '這裡可以查看您的推廣歷史和詳細記錄。',
        position: 'right'
      }
    ]
  }

  // Server management guide for server owners
  const serverGuide: GuideConfig = {
    id: 'server-intro',
    name: '伺服器管理介紹',
    showOnFirstVisit: true,
    skipCondition: () => !hasAnyRole(['超管', '管理員', '服主']),
    steps: [
      {
        target: '[href="/server"]',
        title: '伺服器管理',
        description: '作為服主，您可以在這裡管理您的遊戲伺服器相關設定。',
        position: 'right'
      },
      {
        target: '[href="/server/list"]',
        title: '伺服器列表',
        description: '查看和管理您擁有的所有伺服器。',
        position: 'right'
      },
      {
        target: '[href="/server/register"]',
        title: '註冊新伺服器',
        description: '如果您有新的伺服器要加入平台，可以在這裡提交申請。',
        position: 'right'
      }
    ]
  }

  // Admin guide for administrators
  const adminGuide: GuideConfig = {
    id: 'admin-intro',
    name: '管理員功能介紹',
    showOnFirstVisit: true,
    skipCondition: () => !hasAnyRole(['超管', '管理員']),
    steps: [
      {
        target: '[href="/admin"]',
        title: '系統管理',
        description: '作為管理員，您可以在這裡管理整個平台的設定和用戶。',
        position: 'right'
      },
      {
        target: '[href="/admin/users"]',
        title: '用戶管理',
        description: '管理平台上的所有用戶帳號和權限。',
        position: 'right'
      },
      {
        target: '[href="/reviewer"]',
        title: '審核管理',
        description: '處理推廣申請和伺服器註冊等審核工作。',
        position: 'right'
      }
    ]
  }

  return {
    dashboardGuide,
    promotionGuide,
    serverGuide,
    adminGuide
  }
}

/**
 * Auto-start guides based on user role and route
 */
export const useAutoGuide = () => {
  const route = useRoute()
  const { startGuide } = useUserGuide()
  const { dashboardGuide, promotionGuide, serverGuide, adminGuide } = useGuideConfigs()

  const checkAutoStart = () => {
    const path = route.path

    // Start appropriate guide based on current route
    if (path === '/dashboard') {
      startGuide(dashboardGuide)
    } else if (path.startsWith('/promotion')) {
      startGuide(promotionGuide)
    } else if (path.startsWith('/server')) {
      startGuide(serverGuide)
    } else if (path.startsWith('/admin')) {
      startGuide(adminGuide)
    }
  }

  // Watch route changes
  watch(() => route.path, () => {
    // Delay to ensure page is fully loaded
    setTimeout(checkAutoStart, 1000)
  }, { immediate: true })

  return {
    checkAutoStart
  }
}