import { AxiosError, isAxiosError } from 'axios'

export { AxiosError, isAxiosError }

export function isNotFound(error: unknown): boolean {
  const statusCode = isAxiosError(error)
    ? error?.response?.status
    : ((error as { statusCode?: unknown })?.statusCode ?? null)

  return parseInt(String(statusCode), 10) === 404
}

export function getErrorMessage(error: unknown, fallback?: string): string {
  let message: string | undefined

  if (isAxiosError(error)) {
    if (error.code === 'ECONNABORTED') {
      return 'Request timed out, please try again.'
    }
    if (!error.response) {
      return 'Network error, please check your connection and try again.'
    }
    message = error?.response?.data?.message ?? error?.response?.data?.status?.message ?? undefined
  } else if (error instanceof Error) {
    message = error.message
  }

  return message ?? fallback ?? 'Server error, please try again!'
}
