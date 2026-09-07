import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { normalizeCollectionPayload, normalizeEntityPayload } from '@/lib/api-utils'
import type { Permission } from '@/types'

export type PermissionMutationPayload = object

export const permissionsApi = {
  async list(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.permissions.list, { params })
    return data
  },

  async all(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.permissions.list, { params: { size: 1000, ...params } })
    return normalizeCollectionPayload<Permission>(data)
  },

  async detail(id: string | number) {
    const { data } = await axios.get(endpoints.permissions.detail(id))
    return normalizeEntityPayload<Permission>(data)
  },

  async create(payload: PermissionMutationPayload) {
    const { data } = await axios.post(endpoints.permissions.list, payload)
    return data
  },

  async update(id: string | number, payload: PermissionMutationPayload) {
    const { data } = await axios.patch(endpoints.permissions.detail(id), payload)
    return data
  },

  async delete(id: string | number) {
    await axios.delete(endpoints.permissions.detail(id))
  },
}
