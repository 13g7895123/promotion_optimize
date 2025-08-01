import type { BreadcrumbItem } from '~/types/route'

/**
 * Breadcrumb navigation composable
 */
export const useBreadcrumb = () => {
  const route = useRoute()
  // TODO: Add i18n support when needed
  // const { t } = useI18n()

  // Static breadcrumb mapping for better UX
  const breadcrumbMap: Record<string, BreadcrumbItem> = {
    '/': { title: '首頁', path: '/', icon: 'House' },
    '/dashboard': { title: '儀表板', path: '/dashboard', icon: 'Odometer' },
    '/profile': { title: '個人資料', path: '/profile', icon: 'User' },
    '/settings': { title: '設定', path: '/settings', icon: 'Setting' },
    
    // Admin routes
    '/admin': { title: '系統管理', path: '/admin', icon: 'Setting' },
    '/admin/users': { title: '用戶管理', path: '/admin/users', icon: 'UserFilled' },
    '/admin/roles': { title: '角色權限', path: '/admin/roles', icon: 'Management' },
    '/admin/settings': { title: '系統設定', path: '/admin/settings', icon: 'Setting' },
    '/admin/logs': { title: '系統日誌', path: '/admin/logs', icon: 'Files' },
    
    // Server routes
    '/server': { title: '伺服器管理', path: '/server', icon: 'Server' },
    '/server/list': { title: '伺服器列表', path: '/server/list', icon: 'Monitor' },
    '/server/register': { title: '註冊申請', path: '/server/register', icon: 'Document' },
    '/server/settings': { title: '伺服器設定', path: '/server/settings', icon: 'Setting' },
    
    // Promotion routes
    '/promotion': { title: '推廣管理', path: '/promotion', icon: 'Share' },
    '/promotion/tools': { title: '推廣工具', path: '/promotion/tools', icon: 'Tools' },
    '/promotion/records': { title: '推廣記錄', path: '/promotion/records', icon: 'Files' },
    '/promotion/statistics': { title: '推廣統計', path: '/promotion/statistics', icon: 'DataAnalysis' },
    
    // Events routes
    '/events': { title: '活動管理', path: '/events', icon: 'Calendar' },
    '/events/list': { title: '活動列表', path: '/events/list', icon: 'Trophy' },
    '/events/checkin': { title: '每日簽到', path: '/events/checkin', icon: 'Calendar' },
    
    // Reviewer routes
    '/reviewer': { title: '審核管理', path: '/reviewer', icon: 'Document' },
    '/reviewer/promotions': { title: '推廣審核', path: '/reviewer/promotions', icon: 'Document' },
    '/reviewer/servers': { title: '伺服器審核', path: '/reviewer/servers', icon: 'Server' },
    
    // Reports routes
    '/reports': { title: '報表分析', path: '/reports', icon: 'DataAnalysis' },
    '/reports/promotions': { title: '推廣統計', path: '/reports/promotions', icon: 'DataAnalysis' },
    '/reports/users': { title: '用戶分析', path: '/reports/users', icon: 'User' },
    '/reports/revenue': { title: '收益分析', path: '/reports/revenue', icon: 'Trophy' },
    
    // Notifications
    '/notifications': { title: '通知中心', path: '/notifications', icon: 'Bell' }
  }

  /**
   * Generate breadcrumb items from current route
   */
  const getBreadcrumbs = computed((): BreadcrumbItem[] => {
    const path = route.path
    const paths = path.split('/').filter(Boolean)
    const breadcrumbs: BreadcrumbItem[] = []
    
    // Always start with home unless we're already at home
    if (path !== '/') {
      breadcrumbs.push(breadcrumbMap['/'])
    }
    
    // Build breadcrumb chain
    let currentPath = ''
    for (let i = 0; i < paths.length; i++) {
      currentPath += '/' + paths[i]
      
      if (breadcrumbMap[currentPath]) {
        breadcrumbs.push(breadcrumbMap[currentPath])
      } else {
        // Fallback for dynamic routes
        const segment = paths[i]
        const title = generateFallbackTitle(segment, currentPath)
        breadcrumbs.push({
          title,
          path: currentPath,
          icon: 'Document'
        })
      }
    }

    return breadcrumbs
  })

  /**
   * Set custom breadcrumb for current route
   */
  const setBreadcrumb = (breadcrumb: BreadcrumbItem | BreadcrumbItem[]) => {
    const route = useRoute()
    const items = Array.isArray(breadcrumb) ? breadcrumb : [breadcrumb]
    
    // Store custom breadcrumb in route meta
    if (route.meta) {
      route.meta.breadcrumb = items
    }
  }

  /**
   * Get breadcrumb items from route meta or generate automatically
   */
  const getCurrentBreadcrumbs = computed((): BreadcrumbItem[] => {
    // Check if route has custom breadcrumb
    const customBreadcrumb = route.meta?.breadcrumb as BreadcrumbItem[] | undefined
    if (customBreadcrumb && customBreadcrumb.length > 0) {
      return customBreadcrumb
    }
    
    return getBreadcrumbs.value
  })

  return {
    breadcrumbs: getCurrentBreadcrumbs,
    setBreadcrumb,
    getBreadcrumbs
  }
}

/**
 * Generate fallback title for dynamic segments
 */
function generateFallbackTitle(segment: string, fullPath: string): string {
  // Handle ID segments
  if (/^\d+$/.test(segment)) {
    const parentPath = fullPath.substring(0, fullPath.lastIndexOf('/'))
    if (parentPath.includes('server')) return `伺服器 #${segment}`
    if (parentPath.includes('user')) return `用戶 #${segment}`
    if (parentPath.includes('promotion')) return `推廣 #${segment}`
    if (parentPath.includes('event')) return `活動 #${segment}`
    return `項目 #${segment}`
  }
  
  // Handle action segments
  const actionMap: Record<string, string> = {
    'create': '新增',
    'edit': '編輯',
    'view': '檢視',
    'delete': '刪除',
    'settings': '設定',
    'config': '配置',
    'manage': '管理',
    'list': '列表',
    'detail': '詳情',
    'new': '新建'
  }
  
  if (actionMap[segment]) {
    return actionMap[segment]
  }
  
  // Capitalize first letter as fallback
  return segment.charAt(0).toUpperCase() + segment.slice(1)
}