export interface Customer {
  id: number
  name: string
  email: string
  addressStreet: string | null
  addressCity: string | null
  addressPostalCode: string | null
  addressCountry: string | null
  createdAt: string
}
