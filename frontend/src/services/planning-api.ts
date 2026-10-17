import { axios } from '@/lib/axios'

export interface PlannerEpic {
  id: number
  projectId: number
  key: string
  name: string
  description: string
  status: string
  progress: number
}

export interface PlannerSprint {
  id: number
  projectId: number
  name: string
  goal: string
  status: 'active' | 'planning' | 'planned' | 'completed'
  startAt: string
  endAt: string
}

export const planningApi = {
  async epics(projectId: number) {
    const { data } = await axios.get<{ data: PlannerEpic[] }>('/api/epics', {
      params: { projectId },
    })
    return data.data
  },

  async sprints(projectId: number) {
    const { data } = await axios.get<{ data: PlannerSprint[] }>('/api/sprints', {
      params: { projectId },
    })
    return data.data
  },
}
