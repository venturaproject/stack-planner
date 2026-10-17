import axios from 'axios'
import { env } from '@/config'
import type { AuthUser } from './auth'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface SessionData {
  user: AuthUser
  permissions: string[]
  roles: string[]
  /** Set when this session is an admin impersonating another user — their email. */
  impersonatorEmail?: string | null
  /**
   * True when the org-wide 2FA policy is on and this user hasn't configured TOTP
   * yet — the session is valid but scoped to /auth/* endpoints until setup completes.
   */
  mustSetupTotp?: boolean
}

/** Thrown by login() when the account has TOTP enabled and a code/backup code is needed. */
export class TotpRequiredError extends Error {
  constructor() {
    super('TOTP code required')
    this.name = 'TotpRequiredError'
  }
}

export interface AuthAdapter {
  /** Called before protected requests to attach headers (e.g. CSRF token). */
  getRequestHeaders(): Record<string, string>
  /** Perform login; returns session data on success, throws on failure (see TotpRequiredError). */
  login(credentials: Record<string, unknown>): Promise<SessionData>
  /** Perform logout. */
  logout(): Promise<void>
  /** Fetch the current session from the backend. Returns null if unauthenticated. */
  fetchSession(): Promise<SessionData | null>
  /** Attempt to refresh the access token. Returns true if successful. */
  refresh(): Promise<boolean>
  /** Start impersonating the given user (admin-only backend check). */
  impersonate(userId: string | number): Promise<SessionData>
  /** Return control to the impersonator's own session. */
  stopImpersonating(): Promise<SessionData>
  /**
   * Request a password-reset email. Always resolves — the backend responds
   * identically whether or not the address exists, so the UI can't (and shouldn't
   * try to) distinguish "sent" from "no such account" either.
   */
  forgotPassword(email: string): Promise<void>
  /** Complete a password reset using the signed link params from the emailed URL. */
  resetPassword(params: {
    user: string
    version: number
    expires: number
    signature: string
    password: string
  }): Promise<void>
  /** Called when the session cannot be recovered (e.g. redirect to /login). */
  onUnauthorized(): void
}

// ── Default implementation: httpOnly session cookies + double-submit CSRF ─────

interface CookieSessionAdapterConfig {
  loginUrl: string
  logoutUrl: string
  meUrl: string
  refreshUrl: string
  impersonateUrl: (userId: string | number) => string
  stopImpersonatingUrl: string
  forgotPasswordUrl: string
  resetPasswordUrl: string
  /** If set, a GET to this URL is made before login to seed the CSRF cookie. */
  csrfUrl?: string
  /** Redirect path after an unrecoverable 401. Defaults to '/login'. */
  unauthorizedRedirect?: string
}

// Bare axios instance used only by the adapter — no interceptors, no cycles.
const _http = axios.create({
  baseURL: env.PUBLIC_API_URL,
  withCredentials: true,
  headers: { 'Content-Type': 'application/json' },
  timeout: 20_000,
})

let csrfToken = ''

const updateCsrfToken = (data: unknown): void => {
  if (typeof data === 'object' && data !== null && 'csrfToken' in data && typeof data.csrfToken === 'string') {
    csrfToken = data.csrfToken
  }
}

// Attach the double-submit CSRF token (see backend's AuthCookieFactory/CsrfProtectionListener)
// to mutating requests so logout/refresh POSTs are accepted.
_http.interceptors.request.use((config) => {
  if (config.method && ['post', 'put', 'patch', 'delete'].includes(config.method)) {
    const token = csrfToken || document.cookie
      .split('; ')
      .find((r) => r.startsWith('csrf_token='))
      ?.split('=')[1]
    if (token) config.headers['X-CSRF-Token'] = token
  }
  return config
})

export class CookieSessionAuthAdapter implements AuthAdapter {
  private readonly cfg: CookieSessionAdapterConfig
  // Shared by refresh() callers (axios.ts's 401 interceptor and fetchSession()'s own
  // recovery path) so a refresh already in flight is awaited instead of duplicated —
  // two concurrent /auth/refresh calls can race a single-use/rotated refresh token and
  // spuriously fail one of them.
  private _refreshPromise: Promise<boolean> | null = null

  constructor(cfg: CookieSessionAdapterConfig) {
    this.cfg = cfg
  }

  getRequestHeaders(): Record<string, string> {
    const token = csrfToken || document.cookie
      .split('; ')
      .find((row) => row.startsWith('csrf_token='))
      ?.split('=')[1]
    return token ? { 'X-CSRF-Token': token } : {}
  }

  private mapUser(raw: Record<string, unknown>): AuthUser {
    const roles = (raw.roles ?? []) as Array<
      string | { name: string; permissions?: Array<string | { name: string }> }
    >
    const roleNames: string[] = roles.map((r) => (typeof r === 'string' ? r : r.name))
    const permissions: string[] =
      (raw.permissions as string[] | undefined) ??
      roles.flatMap((r) =>
        typeof r === 'string'
          ? []
          : (r.permissions ?? []).map((p) => (typeof p === 'string' ? p : p.name)),
      )
    // The backend's UserResponse DTO (App\Application\Auth\DTO\UserResponse) is always
    // camelCase — firstName/lastName/avatarUrl/preferredLanguage/totpEnabled, no `name`
    // or `avatar` fields, ever. login()/fetchSession()/impersonate()/stopImpersonating()
    // all map through that same DTO, so there's no snake_case shape to guard against here.
    return {
      id: raw.id as number,
      name: `${raw.firstName ?? ''} ${raw.lastName ?? ''}`.trim() as string,
      email: raw.email as string,
      avatar: (raw.avatarUrl ?? null) as string | null,
      preferredLanguage: raw.preferredLanguage as 'en' | 'es' | undefined,
      roles: roleNames,
      permissions,
      totpEnabled: (raw.totpEnabled ?? false) as boolean,
    }
  }

  async login(credentials: Record<string, unknown>): Promise<SessionData> {
    if (this.cfg.csrfUrl) {
      const { data } = await _http.get(this.cfg.csrfUrl)
      updateCsrfToken(data)
    }
    const payload = {
      email: credentials.email ?? credentials.login,
      password: credentials.password,
      ...(credentials.totpCode ? { totpCode: credentials.totpCode } : {}),
      ...(credentials.backupCode ? { backupCode: credentials.backupCode } : {}),
    }
    let data: Record<string, unknown>
    try {
      ;({ data } = await _http.post(this.cfg.loginUrl, payload))
      updateCsrfToken(data)
    } catch (error) {
      if (
        axios.isAxiosError(error) &&
        error.response?.status === 401 &&
        (error.response.data as { totpRequired?: boolean } | undefined)?.totpRequired
      ) {
        throw new TotpRequiredError()
      }
      throw error
    }
    const raw = (data.user ?? data) as Record<string, unknown>
    const user = this.mapUser(raw)
    return {
      user,
      permissions: user.permissions ?? [],
      roles: user.roles ?? [],
      impersonatorEmail: null,
      mustSetupTotp: false,
    }
  }

  async logout(): Promise<void> {
    await _http.post(this.cfg.logoutUrl).catch(() => {})
  }

  async forgotPassword(email: string): Promise<void> {
    await _http.post(this.cfg.forgotPasswordUrl, { email })
  }

  async resetPassword(params: {
    user: string
    version: number
    expires: number
    signature: string
    password: string
  }): Promise<void> {
    const query = new URLSearchParams({
      user: params.user,
      version: String(params.version),
      expires: String(params.expires),
      signature: params.signature,
    })
    await _http.post(`${this.cfg.resetPasswordUrl}?${query.toString()}`, {
      password: params.password,
    })
  }

  private async _fetchMe(): Promise<SessionData> {
    const { data } = await _http.get(this.cfg.meUrl)
    const raw = data.user ?? data
    const user = this.mapUser(raw)
    return {
      user,
      permissions: user.permissions ?? [],
      roles: user.roles ?? [],
      impersonatorEmail: (data.impersonatorEmail as string | null | undefined) ?? null,
      mustSetupTotp: (data.mustSetupTotp as boolean | undefined) ?? false,
    }
  }

  async fetchSession(): Promise<SessionData | null> {
    try {
      return await this._fetchMe()
    } catch (error) {
      // The access token cookie is short-lived (15 min) and expires routinely during
      // normal use, not just during outages. axiosInstance's interceptor (axios.ts)
      // already refreshes-and-retries for the rest of the app's requests; _http is a
      // deliberately separate, interceptor-free client (see its comment above) so
      // fetchSession/refresh don't create an import cycle with axios.ts. Without this,
      // a merely-expired-but-refreshable session got treated as a full logout on the
      // very first /me call after expiry — PrivateGuard never re-checks after its
      // initial hydrate, so that premature "logged out" state stuck until some other
      // page's own data fetch independently discovered the 401.
      if (axios.isAxiosError(error) && error.response?.status === 401) {
        const refreshed = await this.refresh()
        if (refreshed) {
          try {
            return await this._fetchMe()
          } catch {
            return null
          }
        }
      }
      return null
    }
  }

  async refresh(): Promise<boolean> {
    if (this._refreshPromise) return this._refreshPromise
    this._refreshPromise = (async () => {
      try {
        const { data } = await _http.post(this.cfg.refreshUrl)
        updateCsrfToken(data)
        return true
      } catch {
        return false
      } finally {
        this._refreshPromise = null
      }
    })()
    return this._refreshPromise
  }

  async impersonate(userId: string | number): Promise<SessionData> {
    const { data } = await _http.post(this.cfg.impersonateUrl(userId))
    const raw = data.user ?? data
    const user = this.mapUser(raw)
    return {
      user,
      permissions: user.permissions ?? [],
      roles: user.roles ?? [],
      impersonatorEmail: null,
    }
  }

  async stopImpersonating(): Promise<SessionData> {
    const { data } = await _http.post(this.cfg.stopImpersonatingUrl)
    const raw = data.user ?? data
    const user = this.mapUser(raw)
    return {
      user,
      permissions: user.permissions ?? [],
      roles: user.roles ?? [],
      impersonatorEmail: null,
    }
  }

  onUnauthorized(): void {
    window.location.href = this.cfg.unauthorizedRedirect ?? '/login'
  }
}

// ── Singleton ─────────────────────────────────────────────────────────────────
// Replace with setAuthAdapter() in your app entry point to swap backends.

let _adapter: AuthAdapter = new CookieSessionAuthAdapter({
  loginUrl: '/api/auth/login',
  logoutUrl: '/api/auth/logout',
  meUrl: '/api/auth/me',
  refreshUrl: '/api/auth/refresh',
  impersonateUrl: (userId) => `/api/users/${userId}/impersonate`,
  stopImpersonatingUrl: '/api/auth/stop-impersonating',
  forgotPasswordUrl: '/api/auth/forgot-password',
  resetPasswordUrl: '/api/auth/reset-password',
  csrfUrl: '/api/auth/csrf',
})

export const getAuthAdapter = (): AuthAdapter => _adapter

/** Call this once (e.g. in main.tsx) to swap the default adapter. */
export const setAuthAdapter = (adapter: AuthAdapter): void => {
  _adapter = adapter
}
