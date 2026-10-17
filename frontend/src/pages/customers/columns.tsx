import { createColumnHelper } from '@tanstack/react-table'
import { MoreHorizontal, Edit, Eye, ShoppingCart } from 'lucide-react'
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
import type { Customer } from './types'

const columnHelper = createColumnHelper<Customer>()

interface BuildColumnsOptions {
  t: (key: string, params?: Record<string, string | number>) => string
  onView: (id: number) => void
  onEdit: (id: number) => void
  onNewOrder: (id: number) => void
  selectedIds: Set<number>
  allPageSelected: boolean
  toggleSelectAll: (checked: boolean) => void
  toggleSelectRow: (id: number, checked: boolean) => void
}

export function buildCustomersColumns({
  t,
  onView,
  onEdit,
  onNewOrder,
  selectedIds,
  allPageSelected,
  toggleSelectAll,
  toggleSelectRow,
}: BuildColumnsOptions) {
  return [
    columnHelper.display({
      id: 'select',
      enableHiding: false,
      header: () => (
        <Checkbox
          checked={allPageSelected}
          onCheckedChange={(checked) => toggleSelectAll(!!checked)}
          aria-label={t('common_select_all')}
        />
      ),
      cell: (info) => (
        <Checkbox
          checked={selectedIds.has(info.row.original.id)}
          onCheckedChange={(checked) => toggleSelectRow(info.row.original.id, !!checked)}
          aria-label={t('common_select_row')}
          onClick={(event) => event.stopPropagation()}
        />
      ),
    }),
    columnHelper.accessor('name', {
      id: 'name',
      header: () => t('customers_table_name'),
      cell: (info) => <span className="font-medium">{info.getValue()}</span>,
    }),
    columnHelper.accessor('email', {
      id: 'email',
      header: () => t('customers_table_email'),
    }),
    columnHelper.display({
      id: 'address',
      header: () => t('customers_table_address'),
      cell: (info) =>
        info.row.original.addressCity ? (
          <span className="text-sm text-muted-foreground">
            {info.row.original.addressCity}, {info.row.original.addressCountry}
          </span>
        ) : (
          <span className="text-sm text-muted-foreground">—</span>
        ),
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
            <DropdownMenuItem onClick={() => onNewOrder(info.row.original.id)}>
              <ShoppingCart className="mr-2 h-4 w-4" />
              {t('customers_new_order_tooltip')}
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

export const customerColumnLabels = (t: (key: string) => string): Record<string, string> => ({
  name: t('customers_table_name'),
  email: t('customers_table_email'),
  address: t('customers_table_address'),
})
