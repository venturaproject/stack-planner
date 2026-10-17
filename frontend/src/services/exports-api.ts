import { axios } from '@/lib/axios'

export interface ExportSelection {
  all: boolean
  ids: Array<string | number>
  filters: Record<string, string | undefined>
}

/** Fetches an export and triggers a browser download. */
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
