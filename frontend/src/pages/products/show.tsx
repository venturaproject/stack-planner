import type { ReactNode } from 'react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { ChevronLeft, Pencil, Package } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { formatMoney, formatDate } from '@/lib/formatters'
import { useI18n } from '@/i18n/context'
import { endpoints } from '@/lib/endpoints'
import type { Product } from './types'

interface ShowProductPageProps {
  product: Product
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="flex items-center justify-between py-3">
      <dt className="text-sm text-muted-foreground">{label}</dt>
      <dd className="text-sm font-medium">{value || '—'}</dd>
    </div>
  )
}

export default function ShowProduct({ product }: ShowProductPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()

  const stockVariant =
    product.stockQuantity < 0
      ? 'destructive'
      : product.stockQuantity === 0
        ? 'destructive'
        : product.stockQuantity <= 5
          ? 'outline'
          : 'secondary'
  const stockLabel =
    product.stockQuantity < 0
      ? t('products_filter_stock_negative')
      : product.stockQuantity === 0
        ? t('products_filter_stock_zero')
        : t('products_stock_in_stock', { count: product.stockQuantity })

  return (
    <AuthenticatedLayout title={product.name}>
      <Main>
        <div className="grid flex-1 items-start gap-6 md:gap-8 max-w-3xl mx-auto">
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              onClick={() => navigate(pathFor('admin.products.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="flex-1">
              <h2 className="text-2xl font-bold tracking-tight">{product.name}</h2>
              <p className="text-muted-foreground text-sm font-mono">{product.sku}</p>
            </div>
            <Button onClick={() => navigate(pathFor('admin.products.edit', product.id))}>
              <Pencil className="mr-2 h-4 w-4" />
              {t('common_edit')}
            </Button>
          </div>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Package className="h-5 w-5" />
                {t('products_show_details_title')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="mb-4 flex h-40 w-40 items-center justify-center overflow-hidden rounded-md border bg-muted">
                {product.imageFilename ? (
                  <img
                    src={endpoints.products.image(product.id)}
                    alt={product.name}
                    className="h-full w-full object-cover"
                  />
                ) : (
                  <Package className="h-10 w-10 text-muted-foreground" />
                )}
              </div>
              <dl className="space-y-1">
                <InfoRow label={t('product_field_name')} value={product.name} />
                <Separator />
                <InfoRow
                  label={t('products_table_sku')}
                  value={<span className="font-mono">{product.sku}</span>}
                />
                <Separator />
                <InfoRow label={t('product_field_description')} value={product.description} />
                <Separator />
                <InfoRow
                  label={t('product_field_price')}
                  value={formatMoney(product.priceAmountMinorUnits, product.priceCurrency)}
                />
                <Separator />
                <div className="flex items-center justify-between py-3">
                  <dt className="text-sm text-muted-foreground">{t('products_table_stock')}</dt>
                  <dd>
                    <Badge variant={stockVariant}>{stockLabel}</Badge>
                  </dd>
                </div>
                <Separator />
                <InfoRow label={t('common_created_at')} value={formatDate(product.createdAt)} />
              </dl>
            </CardContent>
          </Card>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
