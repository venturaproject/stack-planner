export interface OrderLine {
  id: number
  productId: number
  productSku: string
  productName: string
  unitPriceAmountMinorUnits: number
  unitPriceCurrency: string
  quantity: number
  lineTotalAmountMinorUnits: number
}

export type OrderStatus = 'draft' | 'placed' | 'cancelled'

export interface Order {
  id: number
  customerId: number
  status: OrderStatus
  shippingAddressStreet: string | null
  shippingAddressCity: string | null
  shippingAddressPostalCode: string | null
  shippingAddressCountry: string | null
  totalAmountMinorUnits: number | null
  totalCurrency: string | null
  lines: OrderLine[]
  createdAt: string
}
