export interface Product {
  id: number
  sku: string
  name: string
  description: string | null
  priceAmountMinorUnits: number
  priceCurrency: string
  stockQuantity: number
  imageFilename: string | null
  createdAt: string
}
