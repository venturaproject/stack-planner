import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, ShoppingCart, Loader2, Plus, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Combobox } from '@/components/ui/combobox'
import { Main } from '@/components/layout'
import { toast } from 'sonner'
import { useMemo, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { pathFor } from '@/lib/app-routes'
import { formatMoney } from '@/lib/formatters'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'
import { customersApi } from '@/services/customers-api'
import { productsApi } from '@/services/products-api'
import { ordersApi } from '@/services/orders-api'

interface DraftLine {
  productId: number
  quantity: number
}

export default function CreateOrder() {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams] = useSearchParams()

  const [customerId, setCustomerId] = useState<string>(searchParams.get('customerId') ?? '')
  const [lines, setLines] = useState<DraftLine[]>([])
  const [isSubmitting, setIsSubmitting] = useState(false)

  const { data: customers = [] } = useQuery({
    queryKey: ['customers', 'all'],
    queryFn: () => customersApi.all(),
  })
  const { data: products = [] } = useQuery({
    queryKey: ['products', 'all'],
    queryFn: () => productsApi.all(),
  })

  const productsById = useMemo(
    () => new Map(products.map((product) => [product.id, product])),
    [products],
  )
  const customerOptions = useMemo(
    () =>
      customers.map((customer) => ({
        value: String(customer.id),
        label: `${customer.name} (${customer.email})`,
      })),
    [customers],
  )
  const productOptions = useMemo(
    () =>
      products.map((product) => ({
        value: String(product.id),
        label: `${product.name} — ${formatMoney(product.priceAmountMinorUnits, product.priceCurrency)} (${t('products_stock_in_stock', { count: product.stockQuantity })})`,
      })),
    [products, t],
  )

  const addLine = () => {
    if (products.length === 0) return
    setLines((prev) => [...prev, { productId: products[0].id, quantity: 1 }])
  }

  const updateLine = (index: number, patch: Partial<DraftLine>) => {
    setLines((prev) => prev.map((l, i) => (i === index ? { ...l, ...patch } : l)))
  }

  const removeLine = (index: number) => {
    setLines((prev) => prev.filter((_, i) => i !== index))
  }

  const estimatedTotal = useMemo(() => {
    let total = 0
    let currency = 'USD'
    for (const line of lines) {
      const product = productsById.get(line.productId)
      if (!product) continue
      total += product.priceAmountMinorUnits * line.quantity
      currency = product.priceCurrency
    }
    return { total, currency }
  }, [lines, productsById])

  const onSubmit = async () => {
    if (!customerId) {
      toast.error(t('order_create_select_customer_error'))
      return
    }
    if (lines.length === 0) {
      toast.error(t('order_create_no_lines_error'))
      return
    }

    setIsSubmitting(true)
    try {
      let orderId: number | undefined
      for (const line of lines) {
        const order = await ordersApi.addLine({
          orderId,
          customerId: orderId ? undefined : Number(customerId),
          productId: line.productId,
          quantity: line.quantity,
        })
        orderId = order.id
      }
      toast.success(t('order_create_success'))
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      navigate(pathFor('admin.orders.show', orderId))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('order_create_error')))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthenticatedLayout title={t('order_create_title')}>
      <Main>
        <div className="mx-auto grid w-full max-w-2xl flex-1 items-start gap-4 md:gap-6">
          {/* Header */}
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              className="h-7 w-7"
              onClick={() => navigate(pathFor('admin.orders.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
              <span className="sr-only">{t('common_back')}</span>
            </Button>
            <h1 className="whitespace-nowrap text-xl font-semibold tracking-tight">
              {t('order_create_title')}
            </h1>
            <div className="hidden items-center gap-2 md:ml-auto md:flex">
              <Button
                variant="outline"
                onClick={() => navigate(pathFor('admin.orders.index'))}
                disabled={isSubmitting}
              >
                {t('common_cancel')}
              </Button>
              <Button size="sm" onClick={onSubmit} disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isSubmitting ? t('order_create_submitting') : t('order_create_submit')}
              </Button>
            </div>
          </div>

          <Card>
            <CardHeader className="items-center text-center">
              <div className="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <ShoppingCart className="h-6 w-6" />
              </div>
              <CardTitle>{t('order_create_card_title')}</CardTitle>
              <CardDescription>{t('order_create_card_description')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-2">
                <Label>{t('order_field_customer')}</Label>
                <Combobox
                  value={customerId}
                  onChange={setCustomerId}
                  placeholder={t('order_field_customer_placeholder')}
                  searchPlaceholder={t('order_field_customer_search_placeholder')}
                  emptyText={t('order_field_customer_empty')}
                  options={customerOptions}
                />
              </div>

              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <Label>{t('order_field_lines')}</Label>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={addLine}
                    disabled={products.length === 0}
                  >
                    <Plus className="mr-1 h-4 w-4" />
                    {t('order_add_line')}
                  </Button>
                </div>

                {lines.length === 0 ? (
                  <p className="text-sm text-muted-foreground">{t('order_no_lines')}</p>
                ) : (
                  <div className="space-y-2">
                    {lines.map((line, index) => {
                      const product = productsById.get(line.productId)
                      return (
                        <div key={index} className="flex items-center gap-2">
                          <Combobox
                            className="flex-1"
                            value={String(line.productId)}
                            onChange={(value) => updateLine(index, { productId: Number(value) })}
                            searchPlaceholder={t('order_field_product_search_placeholder')}
                            emptyText={t('order_field_product_empty')}
                            options={productOptions}
                          />
                          <Input
                            type="number"
                            min="1"
                            value={line.quantity}
                            onChange={(e) =>
                              updateLine(index, { quantity: Math.max(1, Number(e.target.value)) })
                            }
                            className="w-20"
                          />
                          <span className="w-24 text-right text-sm text-muted-foreground">
                            {product
                              ? formatMoney(
                                  product.priceAmountMinorUnits * line.quantity,
                                  product.priceCurrency,
                                )
                              : '—'}
                          </span>
                          <Button size="icon" variant="ghost" onClick={() => removeLine(index)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      )
                    })}
                  </div>
                )}

                {lines.length > 0 && (
                  <div className="flex justify-end border-t pt-2 text-sm font-medium">
                    {t('order_estimated_total', {
                      amount: formatMoney(estimatedTotal.total, estimatedTotal.currency),
                    })}
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Mobile buttons */}
          <div className="flex items-center gap-2 md:hidden">
            <Button
              variant="outline"
              className="flex-1"
              onClick={() => navigate(pathFor('admin.orders.index'))}
              disabled={isSubmitting}
            >
              {t('common_cancel')}
            </Button>
            <Button className="flex-1" onClick={onSubmit} disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isSubmitting ? t('order_create_submitting') : t('order_create_submit')}
            </Button>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
