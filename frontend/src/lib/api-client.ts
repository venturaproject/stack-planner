import axios from 'axios'

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  withCredentials: true,
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  timeout: 20_000,
})

apiClient.interceptors.request.use((config) => {
  if (config.method && ['post', 'put', 'patch', 'delete'].includes(config.method)) {
    const csrfToken = document.cookie
      .split('; ')
      .find((cookie) => cookie.startsWith('csrf_token='))
      ?.split('=')[1]

    if (csrfToken) config.headers.set('X-CSRF-TOKEN', decodeURIComponent(csrfToken))
  }

  return config
})
