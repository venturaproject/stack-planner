import { AuthenticatedLayout } from '@/layouts'
import {
  ChevronLeft,
  Loader2,
  ImagePlus,
  Trash2,
  Package,
  CheckCircle2,
  AlertTriangle,
  XCircle,
  Eye,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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
import { formatDate } from '@/lib/formatters'
import { useQueryClient } from '@tanstack/react-query'
import { useI18n } from '@/i18n/context'
import { endpoints } from '@/lib/endpoints'
import { getErrorMessage } from '@/lib/errors'
import { productsApi } from '@/services/products-api'
import { editProductSchema, EditProductFormValues } from '@/schemas/product.schema'
import type { Product } from './types'

interface EditProductPageProps {
  product: Product
}

export default function EditProduct({ product }: EditProductPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const [stockQuantity, setStockQuantity] = useState(product.stockQuantity)
  const [stockInput, setStockInput] = useState(String(product.stockQuantity))

  const fileInputRef = useRef<HTMLInputElement>(null)
  const [hasImage, setHasImage] = useState(Boolean(product.imageFilename))
  const [imagePreview, setImagePreview] = useState<string | null>(null)
  const [imageVersion, setImageVersion] = useState(() => Date.now())
  const [isUploadingImage, setIsUploadingImage] = useState(false)

  const imageSrc =
    imagePreview ?? (hasImage ? `${endpoints.products.image(product.id)}?v=${imageVersion}` : null)

  const handleImageChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    setImagePreview(URL.createObjectURL(file))
    setIsUploadingImage(true)
    try {
      await productsApi.uploadImage(product.id, file)
      setHasImage(true)
      setImageVersion(Date.now())
      queryClient.invalidateQueries({ queryKey: ['products'] })
      toast.success(t('product_image_upload_success'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('product_image_upload_error')))
    } finally {
      setImagePreview(null)
      setIsUploadingImage(false)
      if (fileInputRef.current) fileInputRef.current.value = ''
    }
  }

  const handleRemoveImage = async () => {
    setIsUploadingImage(true)
    try {
      await productsApi.removeImage(product.id)
      setHasImage(false)
      queryClient.invalidateQueries({ queryKey: ['products'] })
      toast.success(t('product_image_remove_success'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('product_image_remove_error')))
    } finally {
      setIsUploadingImage(false)
    }
  }

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<EditProductFormValues>({
    resolver: zodResolver(editProductSchema),
    defaultValues: {
      name: product.name,
      description: product.description ?? '',
      price: (product.priceAmountMinorUnits / 100).toFixed(2),
      currency: product.priceCurrency,
    },
  })

  const onSubmit = async (values: EditProductFormValues) => {
    try {
      await productsApi.update(product.id, {
        name: values.name,
        description: values.description || null,
        priceAmountMinorUnits: Math.round(Number(values.price) * 100),
        priceCurrency: values.currency.toUpperCase(),
      })

      const target = Number(stockInput)
      const delta = Number.isFinite(target) && Number.isInteger(target) ? target - stockQuantity : 0
      if (delta !== 0) {
        const updated = await productsApi.adjustStock(product.id, delta, 'manual_adjustment')
        setStockQuantity(updated.stockQuantity)
        setStockInput(String(updated.stockQuantity))
      }

      toast.success(t('product_edit_success'))
      queryClient.invalidateQueries({ queryKey: ['products'] })
      navigate(pathFor('admin.products.show', product.id))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('product_edit_error')))
    }
  }

  return (
    <AuthenticatedLayout title={t('product_edit_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="grid flex-1 auto-rows-max gap-4">
            {/* Header */}
            <div className="flex items-center gap-4">
              <Button
                variant="outline"
                size="icon"
                className="h-7 w-7"
                onClick={() => navigate(-1)}
              >
                <ChevronLeft className="h-4 w-4" />
                <span className="sr-only">{t('common_back')}</span>
              </Button>
              <div className="flex-1">
                <h1 className="whitespace-nowrap text-xl font-semibold tracking-tight">
                  {t('product_edit_title')}
                </h1>
                <p className="text-sm text-muted-foreground font-mono">{product.sku}</p>
              </div>
              <div className="hidden items-center gap-2 md:flex">
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.products.show', product.id))}
                  disabled={isSubmitting}
                >
                  <Eye className="mr-2 h-4 w-4" />
                  {t('common_view')}
                </Button>
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.products.index'))}
                  disabled={isSubmitting}
                >
                  {t('common_cancel')}
                </Button>
                <Button size="sm" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                  {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  {isSubmitting ? t('common_saving') : t('common_save_changes')}
                </Button>
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:gap-6">
              {/* Left: product data */}
              <Card className="h-full">
                <CardHeader>
                  <CardTitle>{t('product_edit_card_title')}</CardTitle>
                  <CardDescription>{t('product_edit_card_description')}</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4">
                  <div className="grid gap-2">
                    <Label>{t('product_field_image')}</Label>
                    <div className="flex items-center gap-4">
                      <div className="relative flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted">
                        {imageSrc ? (
                          <img
                            src={imageSrc}
                            alt={product.name}
                            className="h-full w-full object-cover"
                          />
                        ) : (
                          <Package className="h-8 w-8 text-muted-foreground" />
                        )}
                      </div>
                      <div className="flex flex-col gap-2">
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          disabled={isUploadingImage}
                          onClick={() => fileInputRef.current?.click()}
                        >
                          {isUploadingImage ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          ) : (
                            <ImagePlus className="mr-2 h-4 w-4" />
                          )}
                          {t('product_field_image_upload')}
                        </Button>
                        {hasImage && (
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            disabled={isUploadingImage}
                            onClick={handleRemoveImage}
                          >
                            <Trash2 className="mr-2 h-4 w-4" />
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
                  <div className="grid gap-2">
                    <Label htmlFor="name">{t('product_field_name')}</Label>
                    <Input id="name" {...register('name')} />
                    {errors.name && (
                      <p className="text-sm text-destructive">{errors.name.message}</p>
                    )}
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="description">{t('product_field_description')}</Label>
                    <Textarea id="description" rows={4} {...register('description')} />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                      <Label htmlFor="price">{t('product_field_price')}</Label>
                      <Input id="price" type="number" min="0" step="0.01" {...register('price')} />
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

              {/* Right: stock adjustment + info */}
              <Card className="h-full">
                <CardHeader>
                  <CardTitle>{t('product_edit_stock_title')}</CardTitle>
                  <CardDescription>{t('product_edit_stock_description')}</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-5">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                      {t('products_table_stock')}
                    </span>
                    <Badge
                      variant="outline"
                      className={`gap-1 border-transparent font-medium ${
                        stockQuantity <= 0
                          ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400'
                          : stockQuantity <= 5
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                      }`}
                    >
                      {stockQuantity <= 0 ? (
                        <XCircle className="h-3 w-3" />
                      ) : stockQuantity <= 5 ? (
                        <AlertTriangle className="h-3 w-3" />
                      ) : (
                        <CheckCircle2 className="h-3 w-3" />
                      )}
                      {t('products_stock_in_stock', { count: stockQuantity })}
                    </Badge>
                  </div>

                  <div className="grid gap-2">
                    <Label htmlFor="stock-quantity">{t('product_edit_stock_quantity_label')}</Label>
                    <Input
                      id="stock-quantity"
                      type="number"
                      value={stockInput}
                      onChange={(e) => setStockInput(e.target.value)}
                    />
                  </div>

                  <div className="border-t pt-4 grid gap-3 text-sm">
                    <div>
                      <p className="text-muted-foreground text-xs">{t('common_created_at')}</p>
                      <p className="font-medium mt-0.5">{formatDate(product.createdAt)}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Mobile buttons */}
            <div className="flex items-center gap-2 md:hidden">
              <Button
                variant="outline"
                className="flex-1"
                onClick={() => navigate(pathFor('admin.products.show', product.id))}
                disabled={isSubmitting}
              >
                <Eye className="mr-2 h-4 w-4" />
                {t('common_view')}
              </Button>
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
                {isSubmitting ? t('common_saving') : t('common_save_changes')}
              </Button>
            </div>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
