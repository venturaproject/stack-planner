// ─── Auth ────────────────────────────────────────────────────────────────────

export interface Permission {
  id: number
  name: string
  description?: string | null
  guard_name: string
  resource?: string
  action?: string
  created_at?: string
  updated_at?: string
}

export interface Role {
  id: number
  name: string
  description?: string | null
  guard_name: string
  permissions?: Permission[]
  permissions_count?: number
  users_count?: number
  created_at?: string
  updated_at?: string
}

export interface User {
  id: string
  name?: string
  firstName?: string
  lastName?: string
  first_name?: string
  last_name?: string
  username?: string | null
  email: string
  status?: string
  role?: string
  active?: boolean
  avatar?: string | null
  avatarUrl?: string | null
  lastActivity?: string | null
  createdAt?: string | null
  created_at?: string | null
  updated_at?: string | null
  roles: Role[]
  permissions?: Permission[]
}

// ─── Pagination ───────────────────────────────────────────────────────────────

export interface PaginatedData<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

/** @deprecated Use PaginatedData<T> */
export type PaginatedResponse<T> = PaginatedData<T>

// ─── Permissions grouping (used by roles pages) ───────────────────────────────

export interface GroupedPermissions {
  [resource: string]: {
    key: string
    label: string
    permissions: {
      id: number
      name: string
      action: string
      actionLabel: string
    }[]
  }
}

// ─── Page props ───────────────────────────────────────────────────────────────

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  auth?: {
    user?: User
    avatar?: string | null
    permissions?: string[]
    roles?: string[]
    hasFullAccess?: boolean
    fullAccessRoles?: string[]
  }
  flash?: {
    success?: string
    error?: string
  }
  errors?: Record<string, string>
}
