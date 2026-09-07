// Token storage is handled server-side via HttpOnly cookies (access_token).
// This file is kept for interface compatibility but the cookie-based adapter
// does not need client-side token storage.
export type ITokenStorage = {
  clearToken: () => void
  getToken: () => string | undefined
  setToken: (token: string) => void
}

export const tokenStorage: ITokenStorage = {
  clearToken: () => {},
  getToken: () => undefined,
  setToken: () => {},
}
