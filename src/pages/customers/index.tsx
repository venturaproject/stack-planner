import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { MetricStatCard } from '@/components/metric-stat-card'
import { X, Plus, Contact, MapPin, MapPinOff, Download } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Input } from '@/components/ui/input'
import { useNavigate } from 'react-router-dom'
import { PageProps, PaginatedData } from '@/types'
import { getCoreRowModel, useReactTable } from '@tanstack/react-table'
import {
  BulkSelectionBar,
  DataTable,
  DataTablePagination,
  DataTableViewOptions,
} from '@/components/data-table'
import type { DateRange } from 'react-day-picker'
import { format } from 'date-fns'
import { CustomerFilters as CustomerFiltersPopover } from './customer-filters'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { useTableFilters } from '@/hooks/use-table-filters'
import { useColumnReorder } from '@/hooks/use-column-reorder'
import { useBulkSelection } from '@/hooks/use-bulk-selection'
import { buildCustomersColumns, customerColumnLabels } from './columns'
import { pathFor } from '@/lib/app-routes'
import { useI18n } from '@/i18n/context'
import type { Customer } from './types'
import { endpoints } from '@/lib/endpoints'
import { downloadExport } from '@/lib/export'
import { toast } from 'sonner'

interface CustomerStats {
  total: number
  withAddress: number
  withoutAddress: number
}

interface CustomerFilters {
  search?: string
  hasAddress?: string
  createdFrom?: string
  createdTo?: string
  [key: string]: string | undefined
}

interface CustomersPageProps extends PageProps {
  customers: PaginatedData<Customer>
  stats: CustomerStats
  filters?: CustomerFilters
}

export default function CustomersIndex({
  customers,
  stats,
  filters: initialFilters = {},
}: CustomersPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const pageIds = customers.data.map((customer) => customer.id)
  const bulk = useBulkSelection(pageIds, customers.total)

  const {
    filters,
    searchTerm,
    navigate: navigateFilters,
    handleSearch,
    handlePageChange,
    handlePerPageChange,
    perPage,
  } = useTableFilters<CustomerFilters>({
    basePath: pathFor('admin.customers.index'),
    initialFilters: initialFilters ?? {},
    initialPerPage: customers.per_page,
    onNavigate: bulk.clearSelection,
  })

  const handleTabChange = (value: string) => {
    const newFilters = { ...filters }
    if (value === 'all') delete newFilters.hasAddress
    else newFilters.hasAddress = value
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

  const hasActiveFilters = !!filters.createdFrom

  const clearAllFilters = () => handleDateRangeChange(undefined)

  const handleExport = async () => {
    try {
      await downloadExport(
        endpoints.customers.export,
        {
          all: bulk.selectAllRecords,
          ids: Array.from(bulk.selectedIds),
          filters,
        },
        'customers.xls',
      )
    } catch {
      toast.error(t('common_export_error'))
    }
  }

  const columns = buildCustomersColumns({
    t,
    onView: (id) => navigate(pathFor('admin.customers.show', id)),
    onEdit: (id) => navigate(pathFor('admin.customers.edit', id)),
    onNewOrder: (id) => navigate(pathFor('admin.orders.create', { customerId: id })),
    selectedIds: bulk.selectedIds,
    allPageSelected: bulk.allPageSelected,
    toggleSelectAll: bulk.toggleSelectAll,
    toggleSelectRow: bulk.toggleSelectRow,
  })

  const { columnOrder, columnVisibility, setColumnVisibility, handleDragStart, handleDrop } =
    useColumnReorder(columns.map((c) => c.id as string))

  const table = useReactTable({
    data: customers.data,
    columns,
    state: { columnOrder, columnVisibility },
    onColumnOrderChange: () => {},
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
  })

  const currentTab = filters.hasAddress ?? 'all'
  const countLabel = t('customers_selected_count', {
    count: bulk.selectAllRecords ? customers.total : bulk.selectedCount,
  })

  return (
    <AuthenticatedLayout title={t('customers_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">{t('customers_title')}</h2>
              <p className="text-muted-foreground">{t('customers_page_description')}</p>
            </div>

            <BulkSelectionBar
              visible={bulk.selectedIds.size > 0}
              selectedCount={bulk.selectedCount}
              total={customers.total}
              allPageSelected={bulk.allPageSelected}
              selectAllRecords={bulk.selectAllRecords}
              onSelectAllRecords={() => bulk.setSelectAllRecords(true)}
              onSelectPageOnly={() => bulk.setSelectAllRecords(false)}
              onClearSelection={bulk.clearSelection}
              countLabel={countLabel}
              selectAllLabel={t('customers_select_all_label', { total: customers.total })}
              actions={
                <DropdownMenuItem onClick={handleExport}>
                  <Download className="mr-2 h-4 w-4" />
                  {t('common_export_excel')}
                </DropdownMenuItem>
              }
            />
            <Button onClick={() => navigate(pathFor('admin.customers.create'))} className="w-fit">
              <Plus className="mr-2 h-4 w-4" />
              {t('customers_add_button')}
            </Button>
          </div>

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <MetricStatCard
              title={t('stat_total_customers_title')}
              value={stats.total}
              subtitle={t('stat_total_customers_subtitle')}
              icon={Contact}
              sparklineColor="#6366f1"
              sparklineData={[2, 2, 3, 3, 3, 4, stats.total]}
            />
            <MetricStatCard
              title={t('stat_with_address_title')}
              value={stats.withAddress}
              subtitle={t('stat_with_address_subtitle')}
              icon={MapPin}
              sparklineColor="#10b981"
              sparklineData={[1, 1, 2, 2, 2, 3, stats.withAddress]}
            />
            <MetricStatCard
              title={t('stat_without_address_title')}
              value={stats.withoutAddress}
              subtitle={t('stat_without_address_subtitle')}
              icon={MapPinOff}
              sparklineColor="#94a3b8"
              sparklineData={[1, 1, 1, 1, 1, 1, stats.withoutAddress]}
            />
          </div>

          <Tabs value={currentTab} onValueChange={handleTabChange} className="space-y-4">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
              <div className="w-full overflow-x-auto pb-2 lg:pb-0">
                <TabsList>
                  <TabsTrigger value="all">{t('products_tab_all')}</TabsTrigger>
                  <TabsTrigger value="true">{t('stat_with_address_title')}</TabsTrigger>
                  <TabsTrigger value="false">{t('stat_without_address_title')}</TabsTrigger>
                </TabsList>
              </div>
              <div className="flex flex-col gap-2 sm:flex-row sm:items-center lg:ml-auto">
                <div className="relative w-full sm:w-72">
                  <Input
                    placeholder={t('customers_search_placeholder')}
                    value={searchTerm}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="w-full pr-9"
                  />
                  {searchTerm && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="absolute right-1 top-1/2 h-7 w-7 -translate-y-1/2 text-muted-foreground"
                      onClick={() => handleSearch('')}
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  )}
                </div>
                <DataTableViewOptions table={table} columnLabels={customerColumnLabels(t)} />
                <CustomerFiltersPopover
                  dateRange={dateRange}
                  hasActiveFilters={hasActiveFilters}
                  onDateChange={handleDateRangeChange}
                  onClearAll={clearAllFilters}
                />
              </div>
            </div>

            {hasActiveFilters && (
              <div className="flex items-center gap-2 flex-wrap">
                {filters.createdFrom && (
                  <Badge variant="secondary" className="gap-1">
                    {t('label_created_at')}: {filters.createdFrom}
                    {filters.createdTo && filters.createdTo !== filters.createdFrom
                      ? ` – ${filters.createdTo}`
                      : ''}
                    <button
                      onClick={clearAllFilters}
                      className="ml-1 hover:bg-secondary-foreground/20 rounded-full"
                    >
                      ×
                    </button>
                  </Badge>
                )}
              </div>
            )}

            <TabsContent value={currentTab}>
              <Card>
                <CardHeader>
                  <CardTitle>{t('customers_title')}</CardTitle>
                  <CardDescription>{t('customers_page_description')}</CardDescription>
                </CardHeader>
                <CardContent>
                  <DataTable
                    table={table}
                    colCount={columns.length}
                    emptyMessage={t('customers_empty')}
                    onDragStart={handleDragStart}
                    onDrop={handleDrop}
                  />
                </CardContent>
                <DataTablePagination
                  currentPage={customers.current_page}
                  lastPage={customers.last_page}
                  perPage={perPage}
                  total={customers.total}
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
