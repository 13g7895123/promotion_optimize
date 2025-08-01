export interface RoutePermission {
  roles?: string[]
  permissions?: string[]
  requireAuth?: boolean
  redirectTo?: string
}

export interface BreadcrumbItem {
  title: string
  path: string
  icon?: string
  disabled?: boolean
}

export interface PageMeta extends RoutePermission {
  title?: string
  breadcrumb?: BreadcrumbItem[]
  layout?: string
  middleware?: string[]
  keepAlive?: boolean
  transition?: string
}

export interface MenuItem {
  id: string
  title: string
  path: string
  icon?: string
  roles: string[]
  permissions?: string[]
  badge?: number
  disabled?: boolean
  external?: boolean
  children?: MenuItem[]
  order?: number
}

export interface DynamicRoute {
  name: string
  path: string
  component: string
  meta: PageMeta
  children?: DynamicRoute[]
}

export type RouteGuardResult = boolean | string | void