import { createColumnHelper } from '@tanstack/react-table'
import {
  MoreHorizontal,
  Edit,
  Eye,
  Package,
  CheckCircle2,
  AlertTriangle,
  XCircle,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { formatMoney } from '@/lib/formatters'
import { endpoints } from '@/lib/endpoints'
import type { Product } from './types'

const columnHelper = createColumnHelper<Product>()

interface BuildColumnsOptions {
  t: (key: string, params?: Record<string, string | number>) => string
  selectedIds: Set<number>
  allPageSelected: boolean
  toggleSelectAll: (checked: boolean) => void
  toggleSelectRow: (id: number, checked: boolean) => void
  onView: (id: number) => void
  onEdit: (id: number) => void
}

export function buildProductsColumns({
  t,
  selectedIds,
  allPageSelected,
  toggleSelectAll,
  toggleSelectRow,
  onView,
  onEdit,
}: BuildColumnsOptions) {
  return [
    columnHelper.display({
      id: 'select',
      enableHiding: false,
      header: () => (
        <Checkbox
          checked={allPageSelected}
          onCheckedChange={(c) => toggleSelectAll(!!c)}
          aria-label={t('common_select_all')}
        />
      ),
      cell: (info) => (
        <Checkbox
          checked={selectedIds.has(info.row.original.id)}
          onCheckedChange={(c) => toggleSelectRow(info.row.original.id, !!c)}
          aria-label={t('common_select_row')}
          onClick={(e) => e.stopPropagation()}
        />
      ),
    }),
    columnHelper.display({
      id: 'image',
      enableHiding: false,
      header: () => <span className="sr-only">{t('product_field_image')}</span>,
      cell: (info) => (
        <div className="flex h-9 w-9 items-center justify-center overflow-hidden rounded-md border bg-muted">
          {info.row.original.imageFilename ? (
            <img
              src={endpoints.products.image(info.row.original.id)}
              alt={info.row.original.name}
              className="h-full w-full object-cover"
            />
          ) : (
            <Package className="h-4 w-4 text-muted-foreground" />
          )}
        </div>
      ),
    }),
    columnHelper.accessor('sku', {
      id: 'sku',
      header: () => t('products_table_sku'),
      cell: (info) => <span className="font-mono text-xs">{info.getValue()}</span>,
    }),
    columnHelper.accessor('name', {
      id: 'name',
      header: () => t('products_table_name'),
      cell: (info) => <span className="font-medium">{info.getValue()}</span>,
    }),
    columnHelper.accessor('priceAmountMinorUnits', {
      id: 'price',
      header: () => t('products_table_price'),
      cell: (info) => formatMoney(info.getValue(), info.row.original.priceCurrency),
    }),
    columnHelper.accessor('stockQuantity', {
      id: 'stockQuantity',
      header: () => t('products_table_stock'),
      cell: (info) => {
        const qty = info.getValue()
        const tone =
          qty <= 0
            ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400'
            : qty <= 5
              ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
              : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
        const Icon = qty <= 0 ? XCircle : qty <= 5 ? AlertTriangle : CheckCircle2

        return (
          <Badge variant="outline" className={`gap-1 border-transparent font-medium ${tone}`}>
            <Icon className="h-3 w-3" />
            {t('products_stock_in_stock', { count: qty })}
          </Badge>
        )
      },
    }),
    columnHelper.display({
      id: 'actions',
      enableHiding: false,
      header: () => <span className="sr-only">{t('common_actions')}</span>,
      cell: (info) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button aria-haspopup="true" size="icon" variant="ghost">
              <MoreHorizontal className="h-4 w-4" />
              <span className="sr-only">{t('common_actions')}</span>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>{t('common_actions')}</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => onView(info.row.original.id)}>
              <Eye className="mr-2 h-4 w-4" />
              {t('common_view')}
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onEdit(info.row.original.id)}>
              <Edit className="mr-2 h-4 w-4" />
              {t('common_edit')}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    }),
  ]
}

export const productColumnLabels = (t: (key: string) => string): Record<string, string> => ({
  sku: t('products_table_sku'),
  name: t('products_table_name'),
  price: t('products_table_price'),
  stockQuantity: t('products_table_stock'),
})
