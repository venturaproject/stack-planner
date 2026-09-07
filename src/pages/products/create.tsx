import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, Package, Loader2, ImagePlus, X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Main } from '@/components/layout'
import { toast } from 'sonner'
import { useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { useQueryClient } from '@tanstack/react-query'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'
import { productsApi } from '@/services/products-api'
import { createProductSchema, CreateProductFormValues } from '@/schemas/product.schema'

export default function CreateProduct() {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const fileInputRef = useRef<HTMLInputElement>(null)
  const [imageFile, setImageFile] = useState<File | null>(null)
  const [imagePreview, setImagePreview] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<CreateProductFormValues>({
    resolver: zodResolver(createProductSchema),
    defaultValues: {
      sku: '',
      name: '',
      description: '',
      price: '',
      currency: 'USD',
      initialStock: '0',
    },
  })

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return
    setImageFile(file)
    setImagePreview(URL.createObjectURL(file))
  }

  const handleRemoveImage = () => {
    setImageFile(null)
    setImagePreview(null)
    if (fileInputRef.current) fileInputRef.current.value = ''
  }

  const onSubmit = async (values: CreateProductFormValues) => {
    try {
      const created = await productsApi.create({
        sku: values.sku.toUpperCase(),
        name: values.name,
        description: values.description || null,
        priceAmountMinorUnits: Math.round(Number(values.price) * 100),
        priceCurrency: values.currency.toUpperCase(),
        initialStock: Number(values.initialStock),
      })

      if (imageFile) {
        try {
          await productsApi.uploadImage(created.id, imageFile)
        } catch {
          toast.error(t('product_image_upload_error'))
        }
      }

      toast.success(t('product_create_success'))
      queryClient.invalidateQueries({ queryKey: ['products'] })
      navigate(pathFor('admin.products.index'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('product_create_error')))
    }
  }

  return (
    <AuthenticatedLayout title={t('product_create_title')}>
      <Main>
        <div className="mx-auto grid w-full max-w-2xl flex-1 items-start gap-4 md:gap-6">
          {/* Header */}
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              className="h-7 w-7"
              onClick={() => navigate(pathFor('admin.products.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
              <span className="sr-only">{t('common_back')}</span>
            </Button>
            <h1 className="whitespace-nowrap text-xl font-semibold tracking-tight">
              {t('product_create_title')}
            </h1>
            <div className="hidden items-center gap-2 md:ml-auto md:flex">
              <Button
                variant="outline"
                onClick={() => navigate(pathFor('admin.products.index'))}
                disabled={isSubmitting}
              >
                {t('common_cancel')}
              </Button>
              <Button size="sm" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isSubmitting ? t('product_create_submitting') : t('product_create_submit')}
              </Button>
            </div>
          </div>

          <Card>
            <CardHeader className="items-center text-center">
              <div className="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Package className="h-6 w-6" />
              </div>
              <CardTitle>{t('product_create_card_title')}</CardTitle>
              <CardDescription>{t('product_create_card_description')}</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
              <div className="grid gap-2">
                <Label>{t('product_field_image')}</Label>
                <div className="flex items-center gap-4">
                  <div className="relative flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                    {imagePreview ? (
                      <img src={imagePreview} alt="" className="h-full w-full object-cover" />
                    ) : (
                      <Package className="h-8 w-8 text-muted-foreground" />
                    )}
                  </div>
                  <div className="flex flex-col gap-2">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => fileInputRef.current?.click()}
                    >
                      <ImagePlus className="mr-2 h-4 w-4" />
                      {t('product_field_image_upload')}
                    </Button>
                    {imagePreview && (
                      <Button type="button" variant="ghost" size="sm" onClick={handleRemoveImage}>
                        <X className="mr-2 h-4 w-4" />
                        {t('common_delete')}
                      </Button>
                    )}
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept="image/*"
                      className="hidden"
                      onChange={handleImageChange}
                    />
                  </div>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="sku">{t('product_field_sku')}</Label>
                  <Input
                    id="sku"
                    {...register('sku')}
                    onChange={(e) => (e.target.value = e.target.value.toUpperCase())}
                    placeholder="TSH-0001"
                  />
                  {errors.sku && <p className="text-sm text-destructive">{errors.sku.message}</p>}
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="initialStock">{t('product_field_initial_stock')}</Label>
                  <Input id="initialStock" type="number" min="0" {...register('initialStock')} />
                  {errors.initialStock && (
                    <p className="text-sm text-destructive">{errors.initialStock.message}</p>
                  )}
                </div>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="name">{t('product_field_name')}</Label>
                <Input id="name" {...register('name')} placeholder="T-Shirt" />
                {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
              </div>

              <div className="grid gap-2">
                <Label htmlFor="description">{t('product_field_description')}</Label>
                <Textarea
                  id="description"
                  rows={3}
                  {...register('description')}
                  placeholder={t('product_field_description_placeholder')}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="price">{t('product_field_price')}</Label>
                  <Input
                    id="price"
                    type="number"
                    min="0"
                    step="0.01"
                    {...register('price')}
                    placeholder="19.99"
                  />
                  {errors.price && (
                    <p className="text-sm text-destructive">{errors.price.message}</p>
                  )}
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="currency">{t('product_field_currency')}</Label>
                  <Input id="currency" maxLength={3} {...register('currency')} />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Mobile buttons */}
          <div className="flex items-center gap-2 md:hidden">
            <Button
              variant="outline"
              className="flex-1"
              onClick={() => navigate(pathFor('admin.products.index'))}
              disabled={isSubmitting}
            >
              {t('common_cancel')}
            </Button>
            <Button className="flex-1" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isSubmitting ? t('product_create_submitting') : t('product_create_submit')}
            </Button>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
