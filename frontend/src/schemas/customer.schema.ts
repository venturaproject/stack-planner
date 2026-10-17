import { z } from 'zod'

export const createCustomerSchema = z.object({
  name: z.string().min(1),
  email: z.string().min(1).email(),
  street: z.string().optional(),
  city: z.string().optional(),
  postalCode: z.string().optional(),
  country: z.string().optional(),
})

export type CreateCustomerFormValues = z.infer<typeof createCustomerSchema>

export const editCustomerAddressSchema = z.object({
  street: z.string().min(1),
  city: z.string().min(1),
  postalCode: z.string().min(1),
  country: z.string().min(1),
})

export type EditCustomerAddressFormValues = z.infer<typeof editCustomerAddressSchema>
