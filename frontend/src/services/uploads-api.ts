import { axios } from '@/lib/axios'

export interface UploadedAttachment {
  id: number | null
  issue_id: number
  user_id: string | null
  path: string
  original_name: string
  mime_type: string | null
  size: number
  user: { id: string | null; name: string; email: string; avatar?: string | null }
  created_at: string
  updated_at: string
}

export interface ImageUploadResponse {
  url: string
  attachment?: UploadedAttachment
}

export const uploadsApi = {
  async image(issueId: number, file: File) {
    const form = new FormData()
    form.append('file', file)

    const { data } = await axios.post<ImageUploadResponse>(
      `/api/issues/${issueId}/attachments`,
      form,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      },
    )

    return data
  },
}
