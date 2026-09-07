import { axios } from '@/lib/axios'

/**
 * Downloads a bulk-export response (served as a file attachment by the backend)
 * and triggers a browser save via a synthetic link click.
 */
export interface ExportSelection {
  all: boolean
  ids: Array<string | number>
  filters: Record<string, string | undefined>
}

export async function downloadExport(
  url: string,
  selection: ExportSelection,
  filename: string,
): Promise<void> {
  const response = await axios.get(url, {
    params: selection.all ? { ...selection.filters, all: true } : { ids: selection.ids },
    responseType: 'blob',
  })

  const blobUrl = URL.createObjectURL(response.data as Blob)
  const link = document.createElement('a')
  link.href = blobUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(blobUrl)
}
