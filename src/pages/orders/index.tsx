import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { MetricStatCard } from '@/components/metric-stat-card'
import { X, Plus, XCircle, ShoppingCart, FileClock, CheckCircle2, Download } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { useNavigate } from 'react-router-dom'
import { PageProps, PaginatedData } from '@/types'
import { getCoreRowModel, useReactTable } from '@tanstack/react-table'
import {
  DataTable,
  DataTablePagination,
  DataTableViewOptions,
  BulkSelectionBar,
} from '@/components/data-table'
import type { DateRange } from 'react-day-picker'
import { format } from 'date-fns'
import { OrderFilters } from './order-filters'
import { useTableFilters } from '@/hooks/use-table-filters'
import { useBulkSelection } from '@/hooks/use-bulk-selection'
import { useColumnReorder } from '@/hooks/use-column-reorder'
import { buildOrdersColumns, orderColumnLabels } from './columns'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { pathFor } from '@/lib/app-routes'
import { useI18n } from '@/i18n/context'
import { useState } from 'react'
import { ordersApi } from '@/services/orders-api'
import { customersApi } from '@/services/customers-api'
import { endpoints } from '@/lib/endpoints'
import { downloadExport } from '@/lib/export'
import type { Order } from './types'

interface OrderStats {
  total: number
  draft: number
  placed: number
  cancelled: number
}

interface OrderFilters {
  status?: string
  customerId?: string
  createdFrom?: string
  createdTo?: string
  [key: string]: string | undefined
}

interface OrdersPageProps extends PageProps {
  orders: PaginatedData<Order>
  stats: OrderStats
  filters?: OrderFilters
}

export default function OrdersIndex({
  orders,
  stats,
  filters: initialFilters = {},
}: OrdersPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [isCancelling, setIsCancelling] = useState(false)

  const pageIds = orders.data.map((o) => o.id)
  const bulk = useBulkSelection(pageIds, orders.total)

  const { data: customers = [] } = useQuery({
    queryKey: ['customers', 'all'],
    queryFn: () => customersApi.all(),
  })

  const {
    filters,
    navigate: navigateFilters,
    handlePageChange,
    handlePerPageChange,
    perPage,
  } = useTableFilters<OrderFilters>({
    basePath: pathFor('admin.orders.index'),
    initialFilters: initialFilters ?? {},
    initialPerPage: orders.per_page,
    onNavigate: bulk.clearSelection,
  })

  const handleTabChange = (value: string) => {
    const newFilters = { ...filters }
    if (value === 'all') delete newFilters.status
    else newFilters.status = value
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const handleCustomerFilterChange = (value: string) => {
    const newFilters = { ...filters }
    if (value === 'all') delete newFilters.customerId
    else newFilters.customerId = value
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const dateRange: DateRange | undefined = filters.createdFrom
    ? {
        from: new Date(filters.createdFrom),
        to: filters.createdTo ? new Date(filters.createdTo) : undefined,
      }
    : undefined

  const handleDateRangeChange = (range: DateRange | undefined) => {
    const newFilters = { ...filters }
    if (range?.from) {
      newFilters.createdFrom = format(range.from, 'yyyy-MM-dd')
      newFilters.createdTo = format(range.to ?? range.from, 'yyyy-MM-dd')
    } else {
      delete newFilters.createdFrom
      delete newFilters.createdTo
    }
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const hasActiveFilters = !!(filters.customerId || filters.createdFrom)

  const clearAllFilters = () => {
    const newFilters = { ...filters }
    delete newFilters.customerId
    delete newFilters.createdFrom
    delete newFilters.createdTo
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const filterCustomerName = filters.customerId
    ? customers.find((c) => String(c.id) === filters.customerId)?.name
    : undefined

  const handleBulkCancel = async () => {
    setIsCancelling(true)
    try {
      const ids = bulk.selectAllRecords ? pageIds : Array.from(bulk.selectedIds)
      for (const id of ids) {
        await ordersApi.cancel(id)
      }
      toast.success(t('orders_bulk_cancel_success', { count: ids.length }))
      bulk.clearSelection()
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      queryClient.invalidateQueries({ queryKey: ['products'] })
    } catch {
      toast.error(t('order_cancel_error'))
    } finally {
      setIsCancelling(false)
    }
  }

  const handleExport = async () => {
    try {
      await downloadExport(
        endpoints.orders.export,
        {
          all: bulk.selectAllRecords,
          ids: Array.from(bulk.selectedIds),
          filters,
        },
        'orders.xls',
      )
    } catch {
      toast.error(t('common_export_error'))
    }
  }

  const columns = buildOrdersColumns({
    t,
    selectedIds: bulk.selectedIds,
    allPageSelected: bulk.allPageSelected,
    toggleSelectAll: bulk.toggleSelectAll,
    toggleSelectRow: bulk.toggleSelectRow,
    onView: (id) => navigate(pathFor('admin.orders.show', id)),
  })

  const { columnOrder, columnVisibility, setColumnVisibility, handleDragStart, handleDrop } =
    useColumnReorder(columns.map((c) => c.id as string))

  const table = useReactTable({
    data: orders.data,
    columns,
    state: { columnOrder, columnVisibility },
    onColumnOrderChange: () => {},
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
  })

  const currentTab = filters.status ?? 'all'
  const countLabel = t('orders_selected_count', {
    count: bulk.selectAllRecords ? orders.total : bulk.selectedCount,
  })

  return (
    <AuthenticatedLayout title={t('orders_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">{t('orders_title')}</h2>
              <p className="text-muted-foreground">{t('orders_page_description')}</p>
            </div>
            <Button onClick={() => navigate(pathFor('admin.orders.create'))} className="w-fit">
              <Plus className="mr-2 h-4 w-4" />
              {t('orders_new_button')}
            </Button>
          </div>

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <MetricStatCard
              title={t('stat_total_orders_title')}
              value={stats.total}
              subtitle={t('stat_total_orders_subtitle')}
              icon={ShoppingCart}
              sparklineColor="#06b6d4"
              sparklineData={[1, 1, 2, 2, 3, 3, stats.total]}
            />
            <MetricStatCard
              title={t('stat_draft_orders_title')}
              value={stats.draft}
              subtitle={t('stat_draft_orders_subtitle')}
              icon={FileClock}
              sparklineColor="#94a3b8"
              sparklineData={[0, 1, 0, 1, 1, 0, stats.draft]}
            />
            <MetricStatCard
              title={t('stat_placed_orders_title')}
              value={stats.placed}
              subtitle={t('stat_placed_orders_subtitle')}
              icon={CheckCircle2}
              sparklineColor="#10b981"
              sparklineData={[0, 0, 1, 1, 1, 1, stats.placed]}
            />
            <MetricStatCard
              title={t('stat_cancelled_orders_title')}
              value={stats.cancelled}
              subtitle={t('stat_cancelled_orders_subtitle')}
              icon={XCircle}
              sparklineColor="#ef4444"
              sparklineData={[0, 0, 0, 1, 1, 1, stats.cancelled]}
            />
          </div>

          <Tabs value={currentTab} onValueChange={handleTabChange} className="space-y-4">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
              <div className="w-full overflow-x-auto pb-2 lg:pb-0">
                <TabsList>
                  <TabsTrigger value="all">{t('products_tab_all')}</TabsTrigger>
                  <TabsTrigger value="draft">{t('order_status_draft')}</TabsTrigger>
                  <TabsTrigger value="placed">{t('order_status_placed')}</TabsTrigger>
                  <TabsTrigger value="cancelled">{t('order_status_cancelled')}</TabsTrigger>
                </TabsList>
              </div>
              <div className="flex items-center gap-2 lg:ml-auto">
                <DataTableViewOptions table={table} columnLabels={orderColumnLabels(t)} />
                <OrderFilters
                  customerId={filters.customerId}
                  customers={customers}
                  dateRange={dateRange}
                  hasActiveFilters={hasActiveFilters}
                  onCustomerChange={handleCustomerFilterChange}
                  onDateChange={handleDateRangeChange}
                  onClearAll={clearAllFilters}
                />
              </div>
            </div>

            {hasActiveFilters && (
              <div className="flex items-center gap-2 flex-wrap">
                {filterCustomerName && (
                  <Badge variant="secondary" className="gap-1">
                    {t('order_field_customer')}: {filterCustomerName}
                    <button
                      onClick={() => handleCustomerFilterChange('all')}
                      className="ml-1 hover:bg-secondary-foreground/20 rounded-full"
                    >
                      ×
                    </button>
                  </Badge>
                )}
                {filters.createdFrom && (
                  <Badge variant="secondary" className="gap-1">
                    {t('label_created_at')}: {filters.createdFrom}
                    {filters.createdTo && filters.createdTo !== filters.createdFrom
                      ? ` – ${filters.createdTo}`
                      : ''}
                    <button
                      onClick={() => handleDateRangeChange(undefined)}
                      className="ml-1 hover:bg-secondary-foreground/20 rounded-full"
                    >
                      ×
                    </button>
                  </Badge>
                )}
              </div>
            )}

            <BulkSelectionBar
              visible={bulk.selectedIds.size > 0}
              selectedCount={bulk.selectedCount}
              total={orders.total}
              allPageSelected={bulk.allPageSelected}
              selectAllRecords={bulk.selectAllRecords}
              onSelectAllRecords={() => bulk.setSelectAllRecords(true)}
              onSelectPageOnly={() => bulk.setSelectAllRecords(false)}
              onClearSelection={bulk.clearSelection}
              countLabel={countLabel}
              selectAllLabel={t('orders_select_all_label', { total: orders.total })}
              actions={
                <>
                  <DropdownMenuItem
                    onClick={handleBulkCancel}
                    disabled={isCancelling}
                    className="text-destructive focus:text-destructive"
                  >
                    <X className="mr-2 h-4 w-4" />
                    {t('orders_bulk_cancel_action')}
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={handleExport}>
                    <Download className="mr-2 h-4 w-4" />
                    {t('common_export_excel')}
                  </DropdownMenuItem>
                </>
              }
            />

            <TabsContent value={currentTab}>
              <Card>
                <CardHeader>
                  <CardTitle>{t('orders_title')}</CardTitle>
                  <CardDescription>{t('orders_page_description')}</CardDescription>
                </CardHeader>
                <CardContent>
                  <DataTable
                    table={table}
                    colCount={columns.length}
                    emptyMessage={t('orders_empty')}
                    onDragStart={handleDragStart}
                    onDrop={handleDrop}
                  />
                </CardContent>
                <DataTablePagination
                  currentPage={orders.current_page}
                  lastPage={orders.last_page}
                  perPage={perPage}
                  total={orders.total}
                  selectedCount={bulk.selectedCount}
                  onPageChange={handlePageChange}
                  onPerPageChange={handlePerPageChange}
                />
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
