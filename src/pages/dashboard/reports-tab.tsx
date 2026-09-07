import { AlertTriangle, ShoppingCart } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { formatMoney } from '@/lib/formatters'
import { useI18n } from '@/i18n/context'
import { OrderStatusBadge } from '@/pages/orders/components/order-status-badge'
import type { Product } from '@/pages/products/types'
import type { Order } from '@/pages/orders/types'

interface ReportsTabProps {
  restockProducts: Product[]
  recentOrders: Order[]
}

export function ReportsTab({ restockProducts, recentOrders }: ReportsTabProps) {
  const { t } = useI18n()
  const navigate = useNavigate()

  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5" />
            {t('reports_restock_title')}
          </CardTitle>
          <CardDescription>{t('reports_restock_description')}</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{t('products_table_sku')}</TableHead>
                <TableHead>{t('products_table_name')}</TableHead>
                <TableHead>{t('products_table_stock')}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {restockProducts.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={3} className="h-20 text-center text-muted-foreground">
                    {t('reports_restock_empty')}
                  </TableCell>
                </TableRow>
              ) : (
                restockProducts.map((product) => (
                  <TableRow
                    key={product.id}
                    className="cursor-pointer"
                    onClick={() => navigate(pathFor('admin.products.edit', product.id))}
                  >
                    <TableCell className="font-mono text-xs">{product.sku}</TableCell>
                    <TableCell className="font-medium">{product.name}</TableCell>
                    <TableCell>
                      <Badge variant="destructive">{product.stockQuantity}</Badge>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ShoppingCart className="h-5 w-5" />
            {t('reports_recent_orders_title')}
          </CardTitle>
          <CardDescription>{t('reports_recent_orders_description')}</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{t('orders_table_order')}</TableHead>
                <TableHead>{t('orders_table_status')}</TableHead>
                <TableHead>{t('orders_table_total')}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {recentOrders.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={3} className="h-20 text-center text-muted-foreground">
                    {t('orders_empty')}
                  </TableCell>
                </TableRow>
              ) : (
                recentOrders.map((order) => (
                  <TableRow
                    key={order.id}
                    className="cursor-pointer"
                    onClick={() => navigate(pathFor('admin.orders.show', order.id))}
                  >
                    <TableCell className="font-medium">#{order.id}</TableCell>
                    <TableCell>
                      <OrderStatusBadge status={order.status} />
                    </TableCell>
                    <TableCell>
                      {order.totalAmountMinorUnits !== null && order.totalCurrency
                        ? formatMoney(order.totalAmountMinorUnits, order.totalCurrency)
                        : '—'}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}
