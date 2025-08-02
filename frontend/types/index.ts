// 統一導出所有類型定義

export * from './api'
export * from './promotion'
export * from './auth'
export * from './route'

// 通用工具類型
export interface LoadingState {
  loading: boolean
  error: string | null
}

export interface SelectOption {
  label: string
  value: any
  disabled?: boolean
  children?: SelectOption[]
}

export interface TableColumn {
  prop: string
  label: string
  width?: string | number
  minWidth?: string | number
  sortable?: boolean
  filterable?: boolean
  formatter?: (row: any, column: any, value: any, index: number) => string
}

export interface ChartData {
  labels: string[]
  datasets: Array<{
    label: string
    data: number[]
    backgroundColor?: string | string[]
    borderColor?: string
    borderWidth?: number
  }>
}

export interface DateRange {
  start: string
  end: string
}

export interface Coordinates {
  lat: number
  lng: number
}

// 表單驗證相關
export interface ValidationRule {
  required?: boolean
  message?: string
  trigger?: 'blur' | 'change'
  min?: number
  max?: number
  pattern?: RegExp
  validator?: (rule: any, value: any, callback: Function) => void
}

export interface FormRules {
  [key: string]: ValidationRule[]
}

// 通知相關
export interface NotificationOptions {
  title: string
  message?: string
  type?: 'success' | 'warning' | 'info' | 'error'
  duration?: number
  showClose?: boolean
  onClose?: () => void
}

// 確認對話框
export interface ConfirmOptions {
  title: string
  message?: string
  type?: 'warning' | 'info' | 'success' | 'error'
  confirmButtonText?: string
  cancelButtonText?: string
  showCancelButton?: boolean
}

// 主題相關
export interface ThemeConfig {
  primaryColor: string
  successColor: string
  warningColor: string
  errorColor: string
  infoColor: string
  darkMode: boolean
}

// 響應式斷點
export interface Breakpoints {
  xs: number  // < 576px
  sm: number  // >= 576px
  md: number  // >= 768px
  lg: number  // >= 992px
  xl: number  // >= 1200px
  xxl: number // >= 1400px
}