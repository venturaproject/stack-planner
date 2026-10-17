import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { normalizeEntityPayload } from '@/lib/api-utils'
import type { Product } from '@/pages/products/types'

export interface CreateProductPayload {
  sku: string
  name: string
  description?: string | null
  priceAmountMinorUnits: number
  priceCurrency: string
  initialStock: number
}

export interface UpdateProductPayload {
  name?: string
  description?: string | null
  priceAmountMinorUnits?: number
  priceCurrency?: string
}

export const productsApi = {
  async list(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.products.list, { params })
    return data
  },

  async detail(id: string | number) {
    const { data } = await axios.get(endpoints.products.detail(id))
    return normalizeEntityPayload<Product>(data)
  },

  async all(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.products.list, { params: { size: 1000, ...params } })
    return (data?.data ?? []) as Product[]
  },

  async create(payload: CreateProductPayload) {
    const { data } = await axios.post(endpoints.products.list, payload)
    return data as Product
  },

  async update(id: string | number, payload: UpdateProductPayload) {
    const { data } = await axios.patch(endpoints.products.detail(id), payload)
    return data as Product
  },

  async adjustStock(id: string | number, delta: number, reason: string) {
    const { data } = await axios.post(endpoints.products.stockAdjustments(id), { delta, reason })
    return data as Product
  },

  async uploadImage(id: string | number, file: File) {
    const formData = new FormData()
    formData.append('image', file)
    const { data } = await axios.post(endpoints.products.image(id), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data as Product
  },

  async removeImage(id: string | number) {
    const { data } = await axios.delete(endpoints.products.image(id))
    return data as Product
  },
}
