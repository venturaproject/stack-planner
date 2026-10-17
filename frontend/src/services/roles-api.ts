import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { normalizeCollectionPayload, normalizeEntityPayload } from '@/lib/api-utils'
import type { Role } from '@/types'

export type RoleMutationPayload = object

export const rolesApi = {
  async list(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.roles.list, { params })
    return data
  },

  async all(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.roles.list, { params: { size: 1000, ...params } })
    return normalizeCollectionPayload<Role>(data)
  },

  async detail(id: string | number) {
    const { data } = await axios.get(endpoints.roles.detail(id))
    return normalizeEntityPayload<Role>(data)
  },

  async create(payload: RoleMutationPayload) {
    const { data } = await axios.post(endpoints.roles.list, payload)
    return data
  },

  async update(id: string | number, payload: RoleMutationPayload) {
    const { data } = await axios.patch(endpoints.roles.detail(id), payload)
    return data
  },

  async delete(id: string | number) {
    await axios.delete(endpoints.roles.detail(id))
  },
}
