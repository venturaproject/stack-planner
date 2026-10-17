import { axios } from '@/lib/axios'

export interface PlannerProject {
  id: number
  name: string
  key: string
  description: string
  status: 'active' | 'archived'
  coverImage: string
  color: string
  startDate: string | null
  endDate: string | null
  issueCount: number
  openIssueCount: number
  completedIssueCount: number
  activeSprintName: string | null
  members: { name: string; avatar: string | null; role: string }[]
  createdAt: string
}

export interface CreatePlannerProject {
  name: string
  key: string
  description: string
}

export const projectsApi = {
  async list() {
    const { data } = await axios.get<{ data: PlannerProject[]; meta: { total: number } }>(
      '/api/projects',
    )
    return data
  },

  async detail(id: number) {
    const result = await projectsApi.list()
    const project = result.data.find((item) => item.id === id)
    if (!project) throw new Error('Proyecto no encontrado')
    return project
  },

  async create(payload: CreatePlannerProject) {
    const { data } = await axios.post<PlannerProject>('/api/projects', payload)
    return data
  },
}
