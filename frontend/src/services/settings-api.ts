import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'

export interface SecuritySettings {
  require2fa: boolean
}

export const settingsApi = {
  async security() {
    const { data } = await axios.get<SecuritySettings>(endpoints.securitySettings)
    return data
  },

  async updateSecurity(payload: SecuritySettings) {
    await axios.patch(endpoints.securitySettings, payload)
  },

  async updateAppearance(payload: { theme: string; font: string }) {
    await axios.put(endpoints.settings.appearance, payload)
  },

  async updateNotifications(payload: Record<string, boolean | string>) {
    await axios.put(endpoints.settings.notifications, payload)
  },

  async updateDisplay(payload: { items: string[] }) {
    await axios.put(endpoints.settings.display, payload)
  },
}
