import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'

export interface TotpSetupResponse {
  secret: string
  provisioningUri: string
}

export interface TotpConfirmResponse {
  backupCodes: string[]
}

export interface ProfileResponse {
  firstName?: string
  lastName?: string
  avatar_url?: string | null
  avatarUrl?: string | null
  avatar?: string | null
}

export const authApi = {
  async updateProfile(payload: {
    firstName: string
    lastName: string
    preferredLanguage?: string
  }) {
    const { data } = await axios.patch<ProfileResponse>(endpoints.auth.me, payload)
    return data
  },

  async uploadAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)
    const { data } = await axios.post<ProfileResponse>(endpoints.auth.avatar, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  async setupTotp() {
    const { data } = await axios.post<TotpSetupResponse>(endpoints.totp.setup)
    return data
  },

  async confirmTotp(code: string) {
    const { data } = await axios.post<TotpConfirmResponse>(endpoints.totp.confirm, { code })
    return data
  },

  async disableTotp(password: string) {
    await axios.post(endpoints.totp.disable, { password })
  },
}
