export const useSettingsStore = defineStore('settings', () => {
  const showFootbar = ref(true)
  const sidebarMenuItems = ref([
    {
      name: 'Dashboards',
      icon: 'ChartBarIcon',
      children: [
        { name: '分析概覽', href: '/dashboard/analytics' },
        { name: 'CRM', href: '/dashboard/crm' },
        { name: 'eCommerce', href: '/dashboard/ecommerce' }
      ]
    },
    {
      name: 'Settings',
      icon: 'CogIcon',
      children: [
        { name: '一般設定', href: '/settings/general' },
        { name: '主題設定', href: '/settings/theme' },
        { name: '介面設定', href: '/settings/ui' },
        { name: '用戶管理', href: '/settings/users' }
      ]
    },
    {
      name: 'Help Center',
      icon: 'QuestionMarkCircleIcon',
      children: [
        { name: 'FAQ', href: '/help/faq' },
        { name: '聯絡支援', href: '/help/support' },
        { name: '文件', href: '/help/docs' }
      ]
    }
  ])
  
  const toggleFootbar = () => {
    showFootbar.value = !showFootbar.value
  }
  
  const updateMenuItems = (newItems) => {
    sidebarMenuItems.value = newItems
  }
  
  return {
    showFootbar,
    sidebarMenuItems,
    toggleFootbar,
    updateMenuItems
  }
})