import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'

export interface Notification {
  id: string
  title: string
  message: string
  createdAt: string
  readAt: string | null
  typeColor: string
}

export const notificationsApi = {
  async list() {
    const { data } = await axios.get<{ notifications?: Notification[] }>(
      endpoints.notifications.list,
    )
    return data.notifications ?? []
  },

  async markRead(id: string) {
    await axios.patch(endpoints.notifications.markRead(id))
  },

  async markAllRead() {
    await axios.patch(endpoints.notifications.markAllRead)
  },

  async delete(id: string) {
    await axios.delete(endpoints.notifications.detail(id))
  },

  async deleteAll() {
    await axios.delete(endpoints.notifications.list)
  },
}
