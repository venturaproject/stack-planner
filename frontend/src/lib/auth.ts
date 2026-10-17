import { create } from 'zustand'
import { getAuthAdapter } from './auth-adapter'
import { markSessionExpired } from './session-expiry'

export interface AuthUser {
  id: number
  name: string
  username?: string | null
  email: string
  role?: string
  avatar: string | null
  preferredLanguage?: 'en' | 'es'
  permissions?: string[]
  roles?: string[]
  totpEnabled?: boolean
}

interface AuthState {
  user: AuthUser | null
  permissions: string[]
  roles: string[]
  /** Set while impersonating another user — the impersonator's own email. */
  impersonatorEmail: string | null
  /** True when the org-wide 2FA policy is on and this user hasn't configured TOTP yet. */
  mustSetupTotp: boolean
  /** True once fetchSession has completed at least once (success or failure). */
  initialized: boolean

  setAuth: (user: AuthUser, permissions?: string[], roles?: string[]) => void
  /** Clear the mustSetupTotp flag once the user has completed TOTP setup. */
  clearMustSetupTotp: () => void
  logout: () => void
  isAuthenticated: () => boolean
  /** Hydrate session from the backend. Safe to call multiple times — only runs once. */
  hydrate: () => Promise<void>
  /** Like hydrate() but always re-fetches regardless of initialized state. */
  rehydrate: () => Promise<void>
  /** Start impersonating the given user; throws on failure (e.g. non-admin, 403). */
  impersonate: (userId: string | number) => Promise<void>
  /** Return control to the impersonator's own session. */
  stopImpersonating: () => Promise<void>
}

// BroadcastChannel syncs auth events across tabs; does NOT fire in the sending tab.
const authChannel = typeof window !== 'undefined' ? new BroadcastChannel('auth') : null

export const useAuthStore = create<AuthState>()((set, get) => {
  const _fetchAndSet = async () => {
    const session = await getAuthAdapter().fetchSession()
    if (session) {
      set({
        user: session.user,
        permissions: session.permissions,
        roles: session.roles,
        impersonatorEmail: session.impersonatorEmail ?? null,
        mustSetupTotp: session.mustSetupTotp ?? false,
        initialized: true,
      })
    } else {
      // Only a genuine expiry if we were actually authenticated before this
      // check — hydrate()'s very first call also lands here for a plain
      // unauthenticated visit, and that's not "your session expired". This is
      // what catches the case axios.ts's interceptor doesn't: a background tab
      // whose session died server-side while unfocused, discovered here by
      // rehydrate() on refocus rather than by a failed API request.
      if (get().user !== null) {
        markSessionExpired()
      }
      set({
        user: null,
        permissions: [],
        roles: [],
        impersonatorEmail: null,
        mustSetupTotp: false,
        initialized: true,
      })
    }
  }

  // When another tab logs out, clear this tab's state and redirect.
  authChannel?.addEventListener('message', (e) => {
    if (e.data?.type === 'LOGOUT') {
      set({
        user: null,
        permissions: [],
        roles: [],
        impersonatorEmail: null,
        mustSetupTotp: false,
        initialized: true,
      })
      window.location.replace('/login')
    }
  })

  return {
    user: null,
    permissions: [],
    roles: [],
    impersonatorEmail: null,
    mustSetupTotp: false,
    initialized: false,

    setAuth: (user, permissions = [], roles = []) =>
      set({ user, permissions, roles, impersonatorEmail: null, initialized: true }),

    clearMustSetupTotp: () => set({ mustSetupTotp: false }),

    logout: () => {
      getAuthAdapter()
        .logout()
        .catch(() => {})
      authChannel?.postMessage({ type: 'LOGOUT' })
      set({
        user: null,
        permissions: [],
        roles: [],
        impersonatorEmail: null,
        mustSetupTotp: false,
        initialized: true,
      })
    },

    // Both re-fetch /me afterwards rather than trusting the impersonate/stop-impersonating
    // response directly: that response only carries the *new* user's data, not who's
    // impersonating whom — /me is the one endpoint that computes that from the JWT claim.
    impersonate: async (userId) => {
      await getAuthAdapter().impersonate(userId)
      await _fetchAndSet()
    },

    stopImpersonating: async () => {
      await getAuthAdapter().stopImpersonating()
      await _fetchAndSet()
    },

    isAuthenticated: () => get().initialized && get().user !== null,

    hydrate: async () => {
      if (get().initialized) return
      await _fetchAndSet()
    },

    rehydrate: async () => {
      await _fetchAndSet()
    },
  }
})

// Re-validate the session when the tab comes back into focus, throttled to once per minute.
// If the session has expired, PrivateGuard will redirect to /login on the next render.
let _lastRehydrateAt = 0

if (typeof document !== 'undefined') {
  document.addEventListener('visibilitychange', () => {
    if (
      document.visibilityState === 'visible' &&
      useAuthStore.getState().isAuthenticated() &&
      Date.now() - _lastRehydrateAt > 60_000
    ) {
      _lastRehydrateAt = Date.now()
      useAuthStore.getState().rehydrate()
    }
  })
}
