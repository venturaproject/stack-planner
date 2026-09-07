import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import type { DashboardStats } from '@/pages/dashboard/types'

export const dashboardApi = {
  async stats() {
    const { data } = await axios.get(endpoints.dashboard)
    return data as DashboardStats
  },
}
