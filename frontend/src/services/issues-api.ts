import { axios } from '@/lib/axios'

export interface PlannerIssue {
  id: number
  projectId: number
  key: string
  title: string
  status: 'todo' | 'in_progress' | 'in_review' | 'done'
  priority: 'low' | 'medium' | 'high' | 'urgent' | 'critical'
  dueAt: string | null
  startAt: string | null
  epicId: number | null
  sprintId: number | null
  type: 'story' | 'task' | 'bug' | 'subtask'
  storyPoints: number | null
  reporterName: string
  assigneeNames: string[]
  createdAt: string
  description: string | null
}

export interface PlannerIssueDetail extends PlannerIssue {
  comments: { id: number; authorName: string; content: string; createdAt: string }[]
  checklist: { id: number; text: string; isDone: boolean; position: number }[]
  worklogs: {
    id: number
    authorName: string
    minutes: number
    description: string
    loggedAt: string
  }[]
  links: {
    id: number
    type: string
    targetId: number | null
    targetKey: string | null
    targetTitle: string | null
  }[]
  activities: {
    id: number
    actorName: string
    field: string
    oldValue: string | null
    newValue: string
    createdAt: string
  }[]
  attachments: { id: number; filename: string; mimeType: string; sizeBytes: number; url: string }[]
}

export interface CreatePlannerIssue {
  projectId: number
  title: string
  priority: PlannerIssue['priority']
  dueAt?: string | null
  type?: 'story' | 'task' | 'bug' | 'subtask'
  storyPoints?: number | null
  description?: string | null
  epicId?: number | null
  sprintId?: number | null
  assigneeNames?: string[]
}

export type UpdatePlannerIssue = Omit<CreatePlannerIssue, 'projectId'>

export const issuesApi = {
  async list(projectId?: number) {
    const { data } = await axios.get<{ data: PlannerIssue[] }>('/api/issues', {
      params: { projectId },
    })
    return data
  },

  async create(payload: CreatePlannerIssue) {
    const { data } = await axios.post<PlannerIssue>('/api/issues', payload)
    return data
  },

  async detail(id: number) {
    const { data } = await axios.get<PlannerIssueDetail>(`/api/issues/${id}`)
    return data
  },

  async updateStatus(id: number, status: PlannerIssue['status']) {
    const { data } = await axios.patch<{ id: number; status: PlannerIssue['status'] }>(
      `/api/issues/${id}/status`,
      { status },
    )
    return data
  },

  async update(id: number, payload: UpdatePlannerIssue) {
    const { data } = await axios.patch<PlannerIssueDetail>(`/api/issues/${id}`, payload)
    return data
  },
}
