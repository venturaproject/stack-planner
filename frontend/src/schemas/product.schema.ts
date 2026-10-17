import { z } from 'zod'

export const createProductSchema = z.object({
  sku: z.string().regex(/^[A-Za-z0-9]{3}-[A-Za-z0-9]{4,}$/, 'Format: ABC-1234'),
  name: z.string().min(1),
  description: z.string().optional(),
  price: z
    .string()
    .min(1)
    .refine((v) => !Number.isNaN(Number(v)) && Number(v) >= 0, {
      message: 'Must be a non-negative number',
    }),
  currency: z.string().length(3),
  initialStock: z
    .string()
    .refine((v) => !Number.isNaN(Number(v)) && Number.isInteger(Number(v)) && Number(v) >= 0, {
      message: 'Must be a non-negative whole number',
    }),
})

export type CreateProductFormValues = z.infer<typeof createProductSchema>

export const editProductSchema = z.object({
  name: z.string().min(1),
  description: z.string().optional(),
  price: z
    .string()
    .min(1)
    .refine((v) => !Number.isNaN(Number(v)) && Number(v) >= 0, {
      message: 'Must be a non-negative number',
    }),
  currency: z.string().length(3),
})

export type EditProductFormValues = z.infer<typeof editProductSchema>
