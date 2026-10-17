import { AuthenticatedLayout } from '@/layouts'
import {
  ChevronLeft,
  ShoppingCart,
  CheckCircle2,
  XCircle,
  Loader2,
  Trash2,
  Plus,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Main } from '@/components/layout'
import { toast } from 'sonner'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { pathFor } from '@/lib/app-routes'
import { formatMoney } from '@/lib/formatters'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'
import { ordersApi } from '@/services/orders-api'
import { productsApi } from '@/services/products-api'
import { OrderStatusBadge } from './components/order-status-badge'
import type { Order } from './types'

interface ShowOrderPageProps {
  order: Order
}

export default function ShowOrder({ order }: ShowOrderPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [lineBusyId, setLineBusyId] = useState<number | null>(null)
  const [quantityDrafts, setQuantityDrafts] = useState<Record<number, string>>({})
  const [newProductId, setNewProductId] = useState<string>('')
  const [newQuantity, setNewQuantity] = useState('1')
  const [isAddingLine, setIsAddingLine] = useState(false)

  const isDraft = order.status === 'draft'

  const { data: products = [] } = useQuery({
    queryKey: ['products', 'all'],
    queryFn: () => productsApi.all(),
    enabled: isDraft,
  })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['orders'] })
    queryClient.invalidateQueries({ queryKey: ['products'] })
  }

  const handlePlace = async () => {
    setIsSubmitting(true)
    try {
      await ordersApi.place(order.id)
      toast.success(t('order_place_success'))
      refresh()
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('order_place_error')))
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleCancel = async () => {
    setIsSubmitting(true)
    try {
      await ordersApi.cancel(order.id)
      toast.success(t('order_cancel_success'))
      refresh()
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('order_cancel_error')))
    } finally {
      setIsSubmitting(false)
    }
  }

  const commitQuantity = async (lineId: number, rawValue: string) => {
    const quantity = Math.max(1, Number(rawValue) || 1)
    const current = order.lines.find((l) => l.id === lineId)
    if (!current || current.quantity === quantity) {
      setQuantityDrafts((prev) => {
        const next = { ...prev }
        delete next[lineId]
        return next
      })
      return
    }
    setLineBusyId(lineId)
    try {
      await ordersApi.updateLineQuantity(order.id, lineId, quantity)
      refresh()
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('order_line_qty_update_error')))
    } finally {
      setLineBusyId(null)
      setQuantityDrafts((prev) => {
        const next = { ...prev }
        delete next[lineId]
        return next
      })
    }
  }

  const handleRemoveLine = async (lineId: number) => {
    setLineBusyId(lineId)
    try {
      await ordersApi.removeLine(order.id, lineId)
      refresh()
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('order_line_remove_error')))
    } finally {
      setLineBusyId(null)
    }
  }

  const handleAddLine = async () => {
    if (!newProductId) return
    setIsAddingLine(true)
    try {
      await ordersApi.addLine({
        orderId: order.id,
        productId: Number(newProductId),
        quantity: Math.max(1, Number(newQuantity) || 1),
      })
      setNewProductId('')
      setNewQuantity('1')
      refresh()
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('order_create_error')))
    } finally {
      setIsAddingLine(false)
    }
  }

  const address = [
    order.shippingAddressStreet,
    order.shippingAddressCity,
    order.shippingAddressPostalCode,
    order.shippingAddressCountry,
  ]
    .filter(Boolean)
    .join(', ')

  const availableProducts = products.filter((p) => !order.lines.some((l) => l.productId === p.id))

  return (
    <AuthenticatedLayout title={`Order #${order.id}`}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8 max-w-3xl">
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              onClick={() => navigate(pathFor('admin.orders.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <h1 className="text-xl font-semibold flex items-center gap-2">
              <ShoppingCart className="h-5 w-5" />
              Order #{order.id}
            </h1>
            <OrderStatusBadge status={order.status} />
          </div>

          <Card>
            <CardHeader>
              <CardTitle>{t('order_show_customer_label')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-muted-foreground">{t('order_show_customer_label')}</p>
                  <p className="font-medium">#{order.customerId}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">{t('order_show_address_label')}</p>
                  <p className="font-medium">{address || '—'}</p>
                </div>
              </div>

              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t('order_show_table_sku')}</TableHead>
                    <TableHead>{t('order_show_table_product')}</TableHead>
                    <TableHead>{t('order_show_table_unit_price')}</TableHead>
                    <TableHead>{t('order_show_table_qty')}</TableHead>
                    <TableHead>{t('order_show_table_line_total')}</TableHead>
                    {isDraft && <TableHead className="w-10" />}
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {order.lines.map((line) => (
                    <TableRow key={line.id}>
                      <TableCell className="font-mono text-xs">{line.productSku}</TableCell>
                      <TableCell>{line.productName}</TableCell>
                      <TableCell>
                        {formatMoney(line.unitPriceAmountMinorUnits, line.unitPriceCurrency)}
                      </TableCell>
                      <TableCell>
                        {isDraft ? (
                          <Input
                            type="number"
                            min="1"
                            className="w-20"
                            disabled={lineBusyId === line.id}
                            value={quantityDrafts[line.id] ?? line.quantity}
                            onChange={(e) =>
                              setQuantityDrafts((prev) => ({ ...prev, [line.id]: e.target.value }))
                            }
                            onBlur={(e) => commitQuantity(line.id, e.target.value)}
                          />
                        ) : (
                          line.quantity
                        )}
                      </TableCell>
                      <TableCell>
                        {formatMoney(line.lineTotalAmountMinorUnits, line.unitPriceCurrency)}
                      </TableCell>
                      {isDraft && (
                        <TableCell>
                          <Button
                            size="icon"
                            variant="ghost"
                            disabled={lineBusyId === line.id}
                            onClick={() => handleRemoveLine(line.id)}
                          >
                            {lineBusyId === line.id ? (
                              <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                              <Trash2 className="h-4 w-4" />
                            )}
                          </Button>
                        </TableCell>
                      )}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {isDraft && (
                <div className="flex items-center gap-2 border-t pt-4">
                  <Select value={newProductId} onValueChange={setNewProductId}>
                    <SelectTrigger className="flex-1">
                      <SelectValue placeholder={t('order_field_product_placeholder')} />
                    </SelectTrigger>
                    <SelectContent>
                      {availableProducts.map((p) => (
                        <SelectItem key={p.id} value={String(p.id)}>
                          {p.name} — {formatMoney(p.priceAmountMinorUnits, p.priceCurrency)} (
                          {p.stockQuantity} in stock)
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <Input
                    type="number"
                    min="1"
                    value={newQuantity}
                    onChange={(e) => setNewQuantity(e.target.value)}
                    className="w-20"
                  />
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={handleAddLine}
                    disabled={!newProductId || isAddingLine}
                  >
                    {isAddingLine ? (
                      <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                    ) : (
                      <Plus className="mr-1 h-4 w-4" />
                    )}
                    {t('order_add_product_button')}
                  </Button>
                </div>
              )}

              <div className="flex justify-end border-t pt-4 text-lg font-semibold">
                {t('order_show_total_label')}:{' '}
                {order.totalAmountMinorUnits !== null && order.totalCurrency
                  ? formatMoney(order.totalAmountMinorUnits, order.totalCurrency)
                  : '—'}
              </div>

              {order.status !== 'cancelled' && (
                <div className="flex justify-end gap-2 border-t pt-4">
                  <Button variant="outline" onClick={handleCancel} disabled={isSubmitting}>
                    {isSubmitting ? (
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    ) : (
                      <XCircle className="mr-2 h-4 w-4" />
                    )}
                    {t('order_cancel_button')}
                  </Button>
                  {order.status === 'draft' && (
                    <Button onClick={handlePlace} disabled={isSubmitting}>
                      {isSubmitting ? (
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      ) : (
                        <CheckCircle2 className="mr-2 h-4 w-4" />
                      )}
                      {t('order_place_button')}
                    </Button>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
