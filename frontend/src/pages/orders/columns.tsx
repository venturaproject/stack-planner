import { createColumnHelper } from '@tanstack/react-table'
import { MoreHorizontal, Eye } from 'lucide-react'
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
import { OrderStatusBadge } from './components/order-status-badge'
import type { Order } from './types'

const columnHelper = createColumnHelper<Order>()

interface BuildColumnsOptions {
  t: (key: string, params?: Record<string, string | number>) => string
  selectedIds: Set<number>
  allPageSelected: boolean
  toggleSelectAll: (checked: boolean) => void
  toggleSelectRow: (id: number, checked: boolean) => void
  onView: (id: number) => void
}

export function buildOrdersColumns({
  t,
  selectedIds,
  allPageSelected,
  toggleSelectAll,
  toggleSelectRow,
  onView,
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
          disabled={info.row.original.status === 'cancelled'}
          onClick={(e) => e.stopPropagation()}
        />
      ),
    }),
    columnHelper.accessor('id', {
      id: 'id',
      header: () => t('orders_table_order'),
      cell: (info) => <span className="font-medium">#{info.getValue()}</span>,
    }),
    columnHelper.accessor('customerId', {
      id: 'customerId',
      header: () => t('orders_table_customer'),
      cell: (info) => <span>#{info.getValue()}</span>,
    }),
    columnHelper.accessor('status', {
      id: 'status',
      header: () => t('orders_table_status'),
      cell: (info) => <OrderStatusBadge status={info.getValue()} />,
    }),
    columnHelper.display({
      id: 'lines',
      header: () => t('orders_table_lines'),
      cell: (info) => info.row.original.lines.length,
    }),
    columnHelper.accessor('totalAmountMinorUnits', {
      id: 'total',
      header: () => t('orders_table_total'),
      cell: (info) => {
        const total = info.getValue()
        const currency = info.row.original.totalCurrency
        return total !== null && currency ? formatMoney(total, currency) : '—'
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
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    }),
  ]
}

export const orderColumnLabels = (t: (key: string) => string): Record<string, string> => ({
  id: t('orders_table_order'),
  customerId: t('orders_table_customer'),
  status: t('orders_table_status'),
  lines: t('orders_table_lines'),
  total: t('orders_table_total'),
})
