import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { normalizeEntityPayload } from '@/lib/api-utils'
import type { User } from '@/types'

export type UserMutationPayload = object

function resolveFirstRole(roles: unknown): string {
  if (!Array.isArray(roles) || roles.length === 0) return ''
  const first = roles[0] as { name?: string } | string
  return typeof first === 'string' ? first : (first?.name ?? '')
}

export function normalizeUser(raw: Record<string, unknown>) {
  const firstName = (raw.firstName ?? raw.first_name ?? '') as string
  const lastName = (raw.lastName ?? raw.last_name ?? '') as string

  return {
    ...raw,
    name: raw.name ?? `${firstName} ${lastName}`.trim(),
    username: raw.username ?? null,
    avatar: raw.avatar ?? raw.avatarUrl ?? raw.avatar_url ?? null,
    status: raw.status ?? (raw.active !== false ? 'active' : 'inactive'),
    role: raw.role ?? resolveFirstRole(raw.roles),
    lastActivity: raw.lastActivity ?? raw.last_activity ?? raw.updatedAt ?? raw.updated_at ?? null,
    createdAt: raw.createdAt ?? raw.created_at ?? null,
  }
}

export const usersApi = {
  async list(params?: Record<string, string>) {
    const { data } = await axios.get(endpoints.users.list, { params })
    if (data?.data && Array.isArray(data.data)) {
      data.data = data.data.map(normalizeUser)
    }
    return data
  },

  async detail(id: string | number) {
    const { data } = await axios.get(endpoints.users.detail(id))
    const entity = normalizeEntityPayload<Record<string, unknown>>(data)
    return (entity ? normalizeUser(entity) : entity) as User
  },

  async create(payload: UserMutationPayload) {
    const { data } = await axios.post(endpoints.users.list, payload)
    return data
  },

  async update(id: string | number, payload: UserMutationPayload) {
    const { data } = await axios.patch(endpoints.users.detail(id), payload)
    return data
  },

  async delete(id: string | number) {
    await axios.delete(endpoints.users.detail(id))
  },

  async auditLog(id: string | number, params?: { page?: number; size?: number }) {
    const { data } = await axios.get(endpoints.users.auditLog(id), { params })
    const raw = data as Record<string, unknown>
    const entries = (raw.data ?? []) as Record<string, unknown>[]
    return {
      data: entries.map((e): AuditLogEntry => ({
        id: String(e.id ?? ''),
        actorId: (e.actor_id ?? e.actorId ?? null) as string | null,
        actorEmail: (e.actor_email ?? e.actorEmail ?? null) as string | null,
        action: String(e.action ?? ''),
        resourceType: String(e.resource_type ?? e.resourceType ?? ''),
        resourceId: (e.resource_id ?? e.resourceId ?? null) as string | null,
        metadata: (e.metadata ?? {}) as Record<string, unknown>,
        ipAddress: (e.ip_address ?? e.ipAddress ?? null) as string | null,
        previousHash: String(e.previous_hash ?? e.previousHash ?? ''),
        hash: String(e.hash ?? ''),
        createdAt: String(e.created_at ?? e.createdAt ?? ''),
      })),
      total: Number(raw.total ?? 0),
      page: Number(raw.page ?? 1),
      lastPage: Number(raw.last_page ?? raw.lastPage ?? 1),
      perPage: Number(raw.per_page ?? raw.perPage ?? 20),
    }
  },
}

export interface AuditLogEntry {
  id: string
  actorId: string | null
  actorEmail: string | null
  action: string
  resourceType: string
  resourceId: string | null
  metadata: Record<string, unknown>
  ipAddress: string | null
  previousHash: string
  hash: string
  createdAt: string
}
