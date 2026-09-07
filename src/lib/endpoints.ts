// Central API endpoint registry — change the base path here to swap backends
const API_BASE = '/api'

export const endpoints = {
  auth: {
    login: `${API_BASE}/auth/login`,
    logout: `${API_BASE}/auth/logout`,
    refresh: `${API_BASE}/auth/refresh`,
    me: `${API_BASE}/auth/me`,
    avatar: `${API_BASE}/auth/me/avatar`,
    forgotPassword: `${API_BASE}/auth/forgot-password`,
    resetPassword: `${API_BASE}/auth/reset-password`,
    verificationNotification: `${API_BASE}/auth/email/verification-notification`,
  },
  totp: {
    setup: `${API_BASE}/auth/totp/setup`,
    confirm: `${API_BASE}/auth/totp/confirm`,
    disable: `${API_BASE}/auth/totp/disable`,
  },
  securitySettings: `${API_BASE}/admin/security-settings`,
  users: {
    list: `${API_BASE}/users`,
    detail: (id: string | number) => `${API_BASE}/users/${id}`,
    auditLog: (id: string | number) => `${API_BASE}/users/${id}/audit-log`,
  },
  roles: {
    list: `${API_BASE}/roles`,
    detail: (id: string | number) => `${API_BASE}/roles/${id}`,
  },
  permissions: {
    list: `${API_BASE}/permissions`,
    detail: (id: string | number) => `${API_BASE}/permissions/${id}`,
  },
  apiClients: {
    list: `${API_BASE}/admin/api-clients`,
    detail: (id: string | number) => `${API_BASE}/admin/api-clients/${id}`,
  },
  dashboard: `${API_BASE}/dashboard/`,
  products: {
    list: `${API_BASE}/products`,
    detail: (id: string | number) => `${API_BASE}/products/${id}`,
    stockAdjustments: (id: string | number) => `${API_BASE}/products/${id}/stock-adjustments`,
    image: (id: string | number) => `${API_BASE}/products/${id}/image`,
    export: `${API_BASE}/products/export`,
  },
  customers: {
    list: `${API_BASE}/customers`,
    detail: (id: string | number) => `${API_BASE}/customers/${id}`,
    address: (id: string | number) => `${API_BASE}/customers/${id}/address`,
    export: `${API_BASE}/customers/export`,
  },
  orders: {
    list: `${API_BASE}/orders`,
    detail: (id: string | number) => `${API_BASE}/orders/${id}`,
    lines: `${API_BASE}/orders/lines`,
    line: (orderId: string | number, lineId: string | number) =>
      `${API_BASE}/orders/${orderId}/lines/${lineId}`,
    place: (id: string | number) => `${API_BASE}/orders/${id}/place`,
    cancel: (id: string | number) => `${API_BASE}/orders/${id}/cancel`,
    export: `${API_BASE}/orders/export`,
  },
  notifications: {
    list: `${API_BASE}/notifications/`,
    detail: (id: string | number) => `${API_BASE}/notifications/${id}`,
    markRead: (id: string | number) => `${API_BASE}/notifications/${id}/read`,
    markAllRead: `${API_BASE}/notifications/read-all/`,
  },
  upload: {
    presignedUrl: `${API_BASE}/upload/presigned-url/`,
  },
  settings: {
    appearance: `${API_BASE}/settings/appearance/`,
    notifications: `${API_BASE}/settings/notifications/`,
    display: `${API_BASE}/settings/display/`,
  },
}
