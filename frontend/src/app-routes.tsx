/**
 * Application route registry.
 *
 * This is the only file that should need to change when adding, removing,
 * or renaming business-module routes. The core router (lib/router.tsx) has
 * no knowledge of these paths.
 *
 * Every page/route-wrapper is lazy-loaded: each module ships as its own
 * chunk, fetched on first visit instead of bundled into the initial load.
 */
import type { RouteObject } from 'react-router-dom'
import { routeModules } from '@/lib/route-modules'
import { lazyWithRetry } from '@/lib/lazy-with-retry'

// Auth pages
const SignIn = lazyWithRetry(() => import('@/pages/auth/sign-in/sign-in-2'))
const ForgotPasswordPage = lazyWithRetry(() => import('@/pages/auth/forgot-password'))
const ResetPasswordPage = lazyWithRetry(() => import('@/pages/auth/reset-password'))
const TotpSetupPage = lazyWithRetry(() => import('@/pages/auth/totp-setup'))

// Route wrappers (each wraps one or more pages for a domain module) — the
// import() specifiers live in lib/route-modules.ts so the sidebar can warm
// the same chunks on hover, before the click that lazyWithRetry() resolves
// here. See lazyWithRetry's own doc comment for why chunk loads need a
// timeout/retry wrapper instead of plain lazy().
const DashboardRoute = lazyWithRetry(routeModules['/admin'])
const ProjectsRoute = lazyWithRetry(routeModules['/admin/projects'])
const IssuesRoute = lazyWithRetry(routeModules['/admin/issues'])
const IssueDetailRoute = lazyWithRetry(routeModules['/admin/issues/detail'])
const IssueFormRoute = lazyWithRetry(routeModules['/admin/issues/form'])
const MyWorkRoute = lazyWithRetry(routeModules['/admin/my-work'])
const HelpCenterRoute = lazyWithRetry(routeModules['/admin/help-center'])
const UsersRoute = lazyWithRetry(routeModules['/admin/users'])
const RolesRoute = lazyWithRetry(routeModules['/admin/roles'])
const PermissionsRoute = lazyWithRetry(routeModules['/admin/permissions'])
const SecurityPolicyRoute = lazyWithRetry(routeModules['/admin/security'])
const ProductsRoute = lazyWithRetry(routeModules['/admin/products'])
const CustomersRoute = lazyWithRetry(routeModules['/admin/customers'])
const OrdersRoute = lazyWithRetry(routeModules['/admin/orders'])
const SettingsRoute = lazyWithRetry(routeModules['/admin/settings'])

// ── Guest routes (unauthenticated only) ───────────────────────────────────────

export const guestRoutes: RouteObject[] = [
  { path: '/login', element: <SignIn canResetPassword={true} /> },
  { path: '/forgot-password', element: <ForgotPasswordPage /> },
  { path: '/reset-password', element: <ResetPasswordPage /> },
]

// ── Private routes (authenticated only) ──────────────────────────────────────

export const privateRoutes: RouteObject[] = [
  // Mandatory 2FA setup — reachable regardless of the requiredStep redirect (it IS the target)
  { path: '/totp-setup', element: <TotpSetupPage /> },

  // Dashboard
  { path: '/admin', element: <DashboardRoute /> },

  // Planner demo modules
  { path: '/admin/projects', element: <ProjectsRoute /> },
  { path: '/admin/projects/:id', element: <ProjectsRoute /> },
  { path: '/admin/projects/:id/:section', element: <ProjectsRoute /> },
  { path: '/admin/issues', element: <IssuesRoute /> },
  { path: '/admin/projects/:projectId/issues/new', element: <IssueFormRoute /> },
  { path: '/admin/issues/:id/edit', element: <IssueFormRoute /> },
  { path: '/admin/issues/:id', element: <IssueDetailRoute /> },
  { path: '/admin/my-work', element: <MyWorkRoute /> },
  { path: '/admin/help-center', element: <HelpCenterRoute /> },

  // Users module
  { path: '/admin/users', element: <UsersRoute /> },
  { path: '/admin/users/create', element: <UsersRoute /> },
  { path: '/admin/users/:id', element: <UsersRoute /> },
  { path: '/admin/users/:id/edit', element: <UsersRoute /> },

  // Roles module
  { path: '/admin/roles', element: <RolesRoute /> },
  { path: '/admin/roles/create', element: <RolesRoute /> },
  { path: '/admin/roles/:id/edit', element: <RolesRoute /> },

  // Permissions module
  { path: '/admin/permissions', element: <PermissionsRoute /> },
  { path: '/admin/permissions/create', element: <PermissionsRoute /> },
  { path: '/admin/permissions/:id/edit', element: <PermissionsRoute /> },

  // Security policy (admin-only — org-wide 2FA enforcement toggle)
  { path: '/admin/security', element: <SecurityPolicyRoute /> },

  // Products module
  { path: '/admin/products', element: <ProductsRoute /> },
  { path: '/admin/products/create', element: <ProductsRoute /> },
  { path: '/admin/products/:id', element: <ProductsRoute /> },
  { path: '/admin/products/:id/edit', element: <ProductsRoute /> },

  // Customers module
  { path: '/admin/customers', element: <CustomersRoute /> },
  { path: '/admin/customers/create', element: <CustomersRoute /> },
  { path: '/admin/customers/:id', element: <CustomersRoute /> },
  { path: '/admin/customers/:id/edit', element: <CustomersRoute /> },

  // Orders module
  { path: '/admin/orders', element: <OrdersRoute /> },
  { path: '/admin/orders/create', element: <OrdersRoute /> },
  { path: '/admin/orders/:id', element: <OrdersRoute /> },

  // Settings
  { path: '/admin/settings', element: <SettingsRoute /> },
  { path: '/admin/settings/:section', element: <SettingsRoute /> },
]
