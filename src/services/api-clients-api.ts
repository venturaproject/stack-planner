import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'

export interface ApiClient {
  id: string
  name: string
  clientId: string
  scopes: string[]
  active: boolean
  lastUsedAt: string | null
  createdAt: string
}

export interface CreateApiClientPayload {
  name: string
  scopes: string[]
}

export interface CreateApiClientResult {
  client: ApiClient
  secret: string
}

export const AVAILABLE_SCOPES = [
  { value: 'customers:read', label: 'Customers — read' },
  { value: 'customers:write', label: 'Customers — write' },
  { value: 'products:read', label: 'Products — read' },
  { value: 'products:write', label: 'Products — write' },
  { value: 'orders:read', label: 'Orders — read' },
  { value: 'orders:write', label: 'Orders — write' },
  { value: 'dashboard:read', label: 'Dashboard — read' },
  { value: '*', label: 'Full access' },
]

export const apiClientsApi = {
  async list(): Promise<ApiClient[]> {
    const { data } = await axios.get(endpoints.apiClients.list)
    return data as ApiClient[]
  },

  async create(payload: CreateApiClientPayload): Promise<CreateApiClientResult> {
    const { data } = await axios.post(endpoints.apiClients.list, payload)
    return data as CreateApiClientResult
  },

  async revoke(id: string): Promise<void> {
    await axios.delete(endpoints.apiClients.detail(id))
  },
}
