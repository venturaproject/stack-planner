import axiosRequest from 'axios'
import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { assetUrl } from '@/lib/urls'

interface UploaderOptions {
  unique?: boolean
  bucketId?: string
  onUploadProgress?: (progressEvent: { loaded: number; total?: number }) => void
}

function createImageUploader({
  unique = true,
  bucketId = 'uploads/images',
  onUploadProgress,
}: UploaderOptions = {}) {
  return async (file: File): Promise<string> => {
    const path: string | undefined = undefined

    const response = await axios.request({
      method: 'POST',
      url: endpoints.upload.presignedUrl,
      data: {
        unique,
        bucketId,
        filename: file.name,
        mimeType: file.type,
        contentLength: file.size,
      },
    })

    const url: string | undefined = response?.data?.url
    const headers: Record<string, string> = response?.data?.headers ?? {}

    await axiosRequest.request({
      method: 'PUT',
      url: url!,
      data: file,
      onUploadProgress,
      headers: {
        'Content-Type': file.type,
        'Content-Disposition': `inline; filename="${file.name}"`,
        ...headers,
      },
    })

    return assetUrl(path!)
  }
}

export { createImageUploader }
