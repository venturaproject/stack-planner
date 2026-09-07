import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { normalizeEntityPayload } from '@/lib/api-utils'
import type { Order } from '@/pages/orders/types'

export const ordersApi = {
  async list(params?: Record<string, string | number>) {
    const { data } = await axios.get(endpoints.orders.list, { params })
    return data
  },

  async detail(id: string | number) {
    const { data } = await axios.get(endpoints.orders.detail(id))
    return normalizeEntityPayload<Order>(data)
  },

  async addLine(payload: {
    orderId?: number
    customerId?: number
    productId: number
    quantity: number
  }) {
    const { data } = await axios.post(endpoints.orders.lines, payload)
    return data as Order
  },

  async updateLineQuantity(orderId: string | number, lineId: string | number, quantity: number) {
    const { data } = await axios.patch(endpoints.orders.line(orderId, lineId), { quantity })
    return data as Order
  },

  async removeLine(orderId: string | number, lineId: string | number) {
    const { data } = await axios.delete(endpoints.orders.line(orderId, lineId))
    return data as Order
  },

  async place(id: string | number) {
    const { data } = await axios.post(endpoints.orders.place(id))
    return data as Order
  },

  async cancel(id: string | number) {
    const { data } = await axios.post(endpoints.orders.cancel(id))
    return data as Order
  },
}
