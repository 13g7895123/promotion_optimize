// 推廣系統相關類型定義

export interface Server {
  id: number
  name: string
  code: string
  image_url?: string
  game_type: string
  description?: string
  status: 'active' | 'inactive' | 'maintenance'
  owner_id: number
  created_at: string
  updated_at: string
}

export interface Promotion {
  id: number
  server_id: number
  title: string
  description: string
  reward_type: 'points' | 'items' | 'experience' | 'currency'
  reward_value: number
  reward_description?: string
  max_uses: number
  current_uses: number
  start_date: string
  end_date: string
  status: 'active' | 'inactive' | 'expired' | 'paused'
  promotion_link?: string
  qr_code?: string
  created_by: number
  created_at: string
  updated_at: string
  
  // 關聯數據 (可選)
  server?: Server
  statistics?: PromotionStatistics
}

export interface PromotionStatistics {
  id: number
  promotion_id: number
  total_clicks: number
  unique_clicks: number
  conversions: number
  conversion_rate: number
  total_rewards_given: number
  last_click_at?: string
  updated_at: string
}

export interface PromotionClick {
  id: number
  promotion_id: number
  user_id?: number
  ip_address: string
  user_agent: string
  referrer?: string
  click_time: string
  converted: boolean
  conversion_time?: string
}

export interface PromotionForm {
  server_id: number
  title: string
  description: string
  reward_type: 'points' | 'items' | 'experience' | 'currency'
  reward_value: number
  reward_description: string
  max_uses: number
  start_date: string
  end_date: string
  custom_image?: File
}

export interface PromotionFilter {
  server_id?: number
  status?: 'active' | 'inactive' | 'expired' | 'paused'
  start_date?: string
  end_date?: string
  search?: string
}

export interface PromotionAnalytics {
  daily_clicks: Array<{
    date: string
    clicks: number
    conversions: number
  }>
  hourly_distribution: Array<{
    hour: number
    clicks: number
  }>
  top_referrers: Array<{
    referrer: string
    clicks: number
  }>
  geographic_data: Array<{
    country: string
    clicks: number
  }>
  device_types: Array<{
    device: string
    clicks: number
  }>
}

export interface QRCodeOptions {
  size: number
  errorCorrectionLevel: 'L' | 'M' | 'Q' | 'H'
  margin: number
  color: {
    dark: string
    light: string
  }
  logo?: string
}

export interface PromotionMaterial {
  link: string
  qr_code: string
  banner_image?: string
  social_media_text: string
  email_template: string
}

// 推廣工具頁面的步驟狀態
export interface PromotionToolsStep {
  id: number
  title: string
  description: string
  completed: boolean
  active: boolean
}

// API 響應類型
export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}