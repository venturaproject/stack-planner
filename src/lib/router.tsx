import { Suspense, useEffect } from 'react'
import {
  createBrowserRouter,
  Navigate,
  Outlet,
  type RouteObject,
  useLocation,
  useNavigate,
} from 'react-router-dom'
import { Loader2 } from 'lucide-react'
import { NavigationLoader } from '@/components/navigation-loader'
import { useAuthStore } from '@/lib/auth'
import { setNavigate } from '@/lib/router-singleton'
import NotFoundError from '@/pages/errors/not-found-error'
import RouteError from '@/pages/errors/route-error'
import ForbiddenError from '@/pages/errors/forbidden'
import { FULL_ACCESS_ROLES } from '@/config/env'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface RouterConfig {
  /** Routes that require an authenticated session. */
  privateRoutes: RouteObject[]
  /** Routes accessible only when NOT authenticated. */
  guestRoutes?: RouteObject[]
  /** Where to redirect the root path '/' and after login. Defaults to '/admin'. */
  homePath?: string
  /** Where to send unauthenticated users. Defaults to '/login'. */
  loginPath?: string
  /**
   * Forces authenticated sessions to a specific route until a condition clears
   * (e.g. mandatory 2FA setup) — checked on every render, so it reacts as soon
   * as the underlying auth state changes.
   */
  requiredStep?: {
    isIncomplete: () => boolean
    path: string
  }
}

// ── Internal layout pieces ────────────────────────────────────────────────────

function RootLayout() {
  return (
    <>
      <NavigationLoader />
      <Suspense fallback={<HydrationSpinner />}>
        <Outlet />
      </Suspense>
    </>
  )
}

function NavigateSetter() {
  const navigate = useNavigate()
  useEffect(() => {
    setNavigate(navigate)
  }, [navigate])
  return null
}

function HydrationSpinner() {
  return (
    <div className="flex h-screen items-center justify-center">
      <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
    </div>
  )
}

// ── Guard factories ───────────────────────────────────────────────────────────

function makePrivateGuard(loginPath: string, requiredStep?: RouterConfig['requiredStep']) {
  return function PrivateGuard() {
    const { initialized, isAuthenticated, hydrate, permissions, roles } = useAuthStore()
    const location = useLocation()
    useEffect(() => {
      hydrate()
    }, [hydrate])
    if (!initialized) return <HydrationSpinner />
    if (!isAuthenticated()) return <Navigate to={loginPath} replace />
    if (requiredStep && requiredStep.isIncomplete() && location.pathname !== requiredStep.path) {
      return <Navigate to={requiredStep.path} replace />
    }
    const fullAccess = roles.some((role) => FULL_ACCESS_ROLES.some((fullRole) => fullRole.toLowerCase() === role.toLowerCase()))
    const match = location.pathname.match(/^\/admin\/(users|roles)(?:\/|$)/)
    if (match && !fullAccess) {
      const action = location.pathname.endsWith('/create') || location.pathname.endsWith('/edit') ? 'write' : 'read'
      if (!permissions.includes(`${match[1]}:${action}`)) return <ForbiddenError />
    }
    if (/^\/admin\/(permissions|security)/.test(location.pathname) && !fullAccess) return <ForbiddenError />
    return (
      <>
        <NavigateSetter />
        <Outlet />
      </>
    )
  }
}

function makeGuestGuard(homePath: string) {
  return function GuestGuard() {
    const { initialized, isAuthenticated, hydrate } = useAuthStore()
    useEffect(() => {
      hydrate()
    }, [hydrate])
    if (!initialized) return <HydrationSpinner />
    if (isAuthenticated()) return <Navigate to={homePath} replace />
    return (
      <>
        <NavigateSetter />
        <Outlet />
      </>
    )
  }
}

// ── Factory ───────────────────────────────────────────────────────────────────

/**
 * Build a browser router from a route config.
 *
 * Usage:
 *   const router = createAppRouter({ privateRoutes, guestRoutes })
 *
 * To change the auth paths or home redirect, pass homePath / loginPath.
 */
export function createAppRouter({
  privateRoutes,
  guestRoutes = [],
  homePath = '/admin',
  loginPath = '/login',
  requiredStep,
}: RouterConfig) {
  const PrivateGuard = makePrivateGuard(loginPath, requiredStep)
  const GuestGuard = makeGuestGuard(homePath)

  return createBrowserRouter([
    {
      element: <RootLayout />,
      errorElement: <RouteError />,
      children: [
        {
          element: <GuestGuard />,
          children: guestRoutes,
        },
        {
          element: <PrivateGuard />,
          children: privateRoutes,
        },
        { path: '/', element: <Navigate to={homePath} replace /> },
        { path: '*', element: <NotFoundError /> },
      ],
    },
  ])
}
