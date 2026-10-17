/**
 * Single registry of the lazy-loaded route chunks (see app-routes.tsx).
 *
 * Kept separate so both the router (which needs the `lazy()` factories) and
 * the sidebar (which just wants to warm the chunk on hover, before the click
 * actually navigates) call the exact same `import()` specifiers — Vite/Rollup
 * dedupe by module path, so a hover-triggered import and the later lazy()
 * resolution share one cached promise instead of fetching twice.
 */
export const routeModules = {
  '/admin': () => import('@/routes/dashboard-route'),
  '/admin/projects': () => import('@/routes/projects-route'),
  '/admin/issues': () => import('@/routes/issues-route'),
  '/admin/issues/detail': () => import('@/routes/issue-detail-route'),
  '/admin/issues/form': () => import('@/routes/issue-form-route'),
  '/admin/my-work': () => import('@/routes/my-work-route'),
  '/admin/help-center': () => import('@/routes/help-center-route'),
  '/admin/users': () => import('@/routes/users-route'),
  '/admin/roles': () => import('@/routes/roles-route'),
  '/admin/permissions': () => import('@/routes/permissions-route'),
  '/admin/security': () => import('@/routes/security-policy-route'),
  '/admin/products': () => import('@/routes/products-route'),
  '/admin/customers': () => import('@/routes/customers-route'),
  '/admin/orders': () => import('@/routes/orders-route'),
  '/admin/settings': () => import('@/routes/settings-route'),
} as const

const prefixesByLength = Object.keys(routeModules).sort((a, b) => b.length - a.length)

/** Start fetching the chunk for `path` without waiting for it — call on link hover/focus. */
export function preloadRouteModule(path: string): void {
  const match = prefixesByLength.find((p) => path === p || path.startsWith(p + '/'))
  if (match) {
    routeModules[match as keyof typeof routeModules]().catch(() => {})
  }
}
