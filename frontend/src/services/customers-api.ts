import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { normalizeEntityPayload } from '@/lib/api-utils'
import type { Customer } from '@/pages/customers/types'

export interface CreateCustomerPayload {
  name: string
  email: string
  addressStreet?: string | null
  addressCity?: string | null
  addressPostalCode?: string | null
  addressCountry?: string | null
}

export interface UpdateCustomerAddressPayload {
  street: string
  city: string
  postalCode: string
  country: string
}

export const customersApi = {
  async list(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.customers.list, { params })
    return data
  },

  async detail(id: string | number) {
    const { data } = await axios.get(endpoints.customers.detail(id))
    return normalizeEntityPayload<Customer>(data)
  },

  async all(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.customers.list, {
      params: { size: 1000, ...params },
    })
    return (data?.data ?? []) as Customer[]
  },

  async create(payload: CreateCustomerPayload) {
    const { data } = await axios.post(endpoints.customers.list, payload)
    return data as Customer
  },

  async updateAddress(id: string | number, payload: UpdateCustomerAddressPayload) {
    const { data } = await axios.patch(endpoints.customers.address(id), payload)
    return data as Customer
  },
}
