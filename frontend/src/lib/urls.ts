import { env } from '@/config'

export function assetUrl(path: string | undefined) {
  if (!path) return ''
  if (path.startsWith('http')) return path

  if (path.startsWith('/')) {
    path = path.slice(1)
  }

  return `${env.ASSET_URL}/${path}`
}

export function appUrl(
  path: string | Record<string, string> = '',
  params: Record<string, string> | undefined = undefined,
) {
  if (!params && typeof path === 'object') {
    params = path
    path = ''
  }

  if (params) {
    const query = new URLSearchParams(params)
    path = `${path}?${query.toString()}`
  }

  const strPath = path as string
  const cleanPath = strPath.startsWith('/') ? strPath.slice(1) : strPath

  return `${env.APP_URL}/${cleanPath}`.replace(/\/$/, '')
}
