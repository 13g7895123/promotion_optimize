// 響應式設計工具 composable

import type { Breakpoints } from '~/types'

// 預設斷點
const defaultBreakpoints: Breakpoints = {
  xs: 0,
  sm: 576,
  md: 768,
  lg: 992,
  xl: 1200,
  xxl: 1400,
}

export const useResponsive = (customBreakpoints?: Partial<Breakpoints>) => {
  const breakpoints = { ...defaultBreakpoints, ...customBreakpoints }
  
  // 當前螢幕寬度
  const screenWidth = ref(0)
  
  // 更新螢幕寬度
  const updateScreenWidth = () => {
    if (process.client) {
      screenWidth.value = window.innerWidth
    }
  }

  // 各種螢幕尺寸的響應式狀態
  const isXs = computed(() => screenWidth.value < breakpoints.sm)
  const isSm = computed(() => screenWidth.value >= breakpoints.sm && screenWidth.value < breakpoints.md)
  const isMd = computed(() => screenWidth.value >= breakpoints.md && screenWidth.value < breakpoints.lg)
  const isLg = computed(() => screenWidth.value >= breakpoints.lg && screenWidth.value < breakpoints.xl)
  const isXl = computed(() => screenWidth.value >= breakpoints.xl && screenWidth.value < breakpoints.xxl)
  const isXxl = computed(() => screenWidth.value >= breakpoints.xxl)

  // 小於等於某個斷點
  const isSmAndDown = computed(() => screenWidth.value < breakpoints.md)
  const isMdAndDown = computed(() => screenWidth.value < breakpoints.lg)
  const isLgAndDown = computed(() => screenWidth.value < breakpoints.xl)
  const isXlAndDown = computed(() => screenWidth.value < breakpoints.xxl)

  // 大於等於某個斷點
  const isSmAndUp = computed(() => screenWidth.value >= breakpoints.sm)
  const isMdAndUp = computed(() => screenWidth.value >= breakpoints.md)
  const isLgAndUp = computed(() => screenWidth.value >= breakpoints.lg)
  const isXlAndUp = computed(() => screenWidth.value >= breakpoints.xl)

  // 當前斷點名稱
  const currentBreakpoint = computed(() => {
    if (isXxl.value) return 'xxl'
    if (isXl.value) return 'xl'
    if (isLg.value) return 'lg'
    if (isMd.value) return 'md'
    if (isSm.value) return 'sm'
    return 'xs'
  })

  // 是否為移動裝置
  const isMobile = computed(() => isXs.value || isSm.value)
  
  // 是否為平板裝置
  const isTablet = computed(() => isMd.value)
  
  // 是否為桌面裝置
  const isDesktop = computed(() => isLgAndUp.value)

  // 響應式網格列數
  const gridCols = computed(() => {
    if (isXs.value) return 1
    if (isSm.value) return 2
    if (isMd.value) return 3
    if (isLg.value) return 4
    return 6
  })

  // 響應式間距
  const spacing = computed(() => {
    if (isMobile.value) return {
      xs: 8,
      sm: 12,
      md: 16,
      lg: 20,
      xl: 24,
    }
    return {
      xs: 12,
      sm: 16,
      md: 20,
      lg: 24,
      xl: 32,
    }
  })

  // 響應式字體大小
  const fontSize = computed(() => {
    const baseFontSize = isMobile.value ? 14 : 16
    return {
      xs: baseFontSize - 2,
      sm: baseFontSize,
      md: baseFontSize + 2,
      lg: baseFontSize + 4,
      xl: baseFontSize + 6,
      xxl: baseFontSize + 8,
    }
  })

  // 檢查是否在指定斷點範圍內
  const between = (min: keyof Breakpoints, max: keyof Breakpoints) => {
    return computed(() => 
      screenWidth.value >= breakpoints[min] && 
      screenWidth.value < breakpoints[max]
    )
  }

  // 檢查是否大於指定斷點
  const greaterThan = (breakpoint: keyof Breakpoints) => {
    return computed(() => screenWidth.value > breakpoints[breakpoint])
  }

  // 檢查是否小於指定斷點
  const lessThan = (breakpoint: keyof Breakpoints) => {
    return computed(() => screenWidth.value < breakpoints[breakpoint])
  }

  // 根據斷點返回不同的值
  const responsiveValue = <T>(values: {
    xs?: T
    sm?: T
    md?: T
    lg?: T
    xl?: T
    xxl?: T
    default: T
  }) => {
    return computed(() => {
      if (isXxl.value && values.xxl !== undefined) return values.xxl
      if (isXl.value && values.xl !== undefined) return values.xl
      if (isLg.value && values.lg !== undefined) return values.lg
      if (isMd.value && values.md !== undefined) return values.md
      if (isSm.value && values.sm !== undefined) return values.sm
      if (isXs.value && values.xs !== undefined) return values.xs
      return values.default
    })
  }

  // 監聽視窗大小變化
  onMounted(() => {
    updateScreenWidth()
    if (process.client) {
      window.addEventListener('resize', updateScreenWidth)
    }
  })

  onUnmounted(() => {
    if (process.client) {
      window.removeEventListener('resize', updateScreenWidth)
    }
  })

  return {
    // 斷點配置
    breakpoints,
    
    // 螢幕寬度
    screenWidth: readonly(screenWidth),
    
    // 斷點狀態
    isXs,
    isSm,
    isMd,
    isLg,
    isXl,
    isXxl,
    
    // 範圍狀態
    isSmAndDown,
    isMdAndDown,
    isLgAndDown,
    isXlAndDown,
    isSmAndUp,
    isMdAndUp,
    isLgAndUp,
    isXlAndUp,
    
    // 設備類型
    isMobile,
    isTablet,
    isDesktop,
    
    // 當前斷點
    currentBreakpoint,
    
    // 響應式值
    gridCols,
    spacing,
    fontSize,
    
    // 工具函數
    between,
    greaterThan,
    lessThan,
    responsiveValue,
  }
}

// 響應式組件工具
export const useResponsiveComponent = () => {
  const { 
    isMobile, 
    isTablet, 
    isDesktop,
    currentBreakpoint,
    spacing,
  } = useResponsive()

  // 組件響應式 class
  const responsiveClasses = computed(() => ({
    'is-mobile': isMobile.value,
    'is-tablet': isTablet.value,
    'is-desktop': isDesktop.value,
    [`is-${currentBreakpoint.value}`]: true,
  }))

  // 響應式樣式
  const responsiveStyles = computed(() => ({
    '--spacing-xs': `${spacing.value.xs}px`,
    '--spacing-sm': `${spacing.value.sm}px`,
    '--spacing-md': `${spacing.value.md}px`,
    '--spacing-lg': `${spacing.value.lg}px`,
    '--spacing-xl': `${spacing.value.xl}px`,
  }))

  // 響應式容器寬度
  const containerWidth = computed(() => {
    if (isMobile.value) return '100%'
    if (isTablet.value) return '90%'
    return '80%'
  })

  // 響應式列數（用於 CSS Grid）
  const gridTemplate = computed(() => {
    if (isMobile.value) return 'repeat(1, 1fr)'
    if (isTablet.value) return 'repeat(2, 1fr)'
    return 'repeat(auto-fit, minmax(300px, 1fr))'
  })

  return {
    responsiveClasses,
    responsiveStyles,
    containerWidth,
    gridTemplate,
    isMobile,
    isTablet,
    isDesktop,
  }
}

// 響應式表格工具
export const useResponsiveTable = () => {
  const { isMobile, isTablet } = useResponsive()

  // 表格顯示模式
  const tableMode = computed(() => {
    if (isMobile.value) return 'card'
    if (isTablet.value) return 'compact'
    return 'full'
  })

  // 可見列配置
  const visibleColumns = (columns: Array<{
    key: string
    label: string
    mobile?: boolean
    tablet?: boolean
    desktop?: boolean
  }>) => {
    return computed(() => 
      columns.filter(col => {
        if (isMobile.value) return col.mobile !== false
        if (isTablet.value) return col.tablet !== false
        return col.desktop !== false
      })
    )
  }

  return {
    tableMode,
    visibleColumns,
  }
}

// 響應式導航工具
export const useResponsiveNavigation = () => {
  const { isMobile } = useResponsive()
  
  // 導航模式
  const navigationMode = computed(() => 
    isMobile.value ? 'drawer' : 'sidebar'
  )
  
  // 是否顯示漢堡選單
  const showHamburger = computed(() => isMobile.value)
  
  // 導航項目顯示模式
  const navigationItemMode = computed(() => 
    isMobile.value ? 'icon-only' : 'icon-text'
  )

  return {
    navigationMode,
    showHamburger,
    navigationItemMode,
  }
}