export interface User {
  id: number
  username: string
  email: string
  nickname?: string
  avatar?: string
  phone?: string
  status: 'active' | 'inactive' | 'banned'
  created_at: string
  updated_at: string
  last_login_at?: string
}

export interface LoginCredentials {
  username: string
  password: string
  remember?: boolean
}

export interface RegisterData {
  username: string
  email: string
  password: string
  password_confirmation: string
  nickname?: string
  phone?: string
  terms_accepted: boolean
}

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  errors?: Record<string, string[]>
  meta?: {
    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
  }
}

export interface Permission {
  id: number
  name: string
  guard_name: string
  description?: string
  created_at: string
  updated_at: string
}

export interface Role {
  id: number
  name: string
  guard_name: string
  description?: string
  permissions: Permission[]
  created_at: string
  updated_at: string
}

export interface UserRole {
  user_id: number
  role_id: number
  assigned_at: string
  assigned_by: number
}

export type UserRoleLevel = 'super_admin' | 'admin' | 'server_owner' | 'reviewer' | 'user'

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  permissions: string[]
  roles: string[]
}