import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { MetricStatCard } from '@/components/metric-stat-card'
import { X, Plus, PackagePlus, Package, AlertTriangle, PackageX, Download } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
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
import { ProductFilters as ProductFiltersPopover } from './product-filters'
import { useTableFilters } from '@/hooks/use-table-filters'
import { useBulkSelection } from '@/hooks/use-bulk-selection'
import { useColumnReorder } from '@/hooks/use-column-reorder'
import { buildProductsColumns, productColumnLabels } from './columns'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { pathFor } from '@/lib/app-routes'
import { useI18n } from '@/i18n/context'
import { useState } from 'react'
import { productsApi } from '@/services/products-api'
import { endpoints } from '@/lib/endpoints'
import { downloadExport } from '@/services/exports-api'
import { formatMoney } from '@/lib/formatters'
import type { Product } from './types'

interface ProductStats {
  total: number
  lowStock: number
  outOfStock: number
}

interface ProductFilters {
  search?: string
  stockLevel?: string
  createdFrom?: string
  createdTo?: string
  stockStatus?: string
  priceMin?: string
  priceMax?: string
  [key: string]: string | undefined
}

interface ProductsPageProps extends PageProps {
  products: PaginatedData<Product>
  stats: ProductStats
  filters?: ProductFilters
}

export default function ProductsIndex({
  products,
  stats,
  filters: initialFilters = {},
}: ProductsPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [restockDialogOpen, setRestockDialogOpen] = useState(false)
  const [restockQuantity, setRestockQuantity] = useState('10')
  const [isRestocking, setIsRestocking] = useState(false)

  const pageIds = products.data.map((p) => p.id)
  const bulk = useBulkSelection(pageIds, products.total)

  const {
    filters,
    searchTerm,
    navigate: navigateFilters,
    handleSearch,
    handlePageChange,
    handlePerPageChange,
    perPage,
  } = useTableFilters<ProductFilters>({
    basePath: pathFor('admin.products.index'),
    initialFilters: initialFilters ?? {},
    initialPerPage: products.per_page,
    onNavigate: bulk.clearSelection,
  })

  const handleTabChange = (value: string) => {
    const newFilters = { ...filters }
    if (value === 'all') delete newFilters.stockLevel
    else newFilters.stockLevel = value
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

  const handleStockStatusChange = (value: string | undefined) => {
    const newFilters = { ...filters }
    if (value) newFilters.stockStatus = value
    else delete newFilters.stockStatus
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const handlePriceChange = (min: number | undefined, max: number | undefined) => {
    const newFilters = { ...filters }
    if (min !== undefined) newFilters.priceMin = String(min)
    else delete newFilters.priceMin
    if (max !== undefined) newFilters.priceMax = String(max)
    else delete newFilters.priceMax
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const hasActiveFilters = !!(
    filters.createdFrom ||
    filters.stockStatus ||
    filters.priceMin ||
    filters.priceMax
  )

  const clearAllFilters = () => {
    const newFilters = { ...filters }
    delete newFilters.createdFrom
    delete newFilters.createdTo
    delete newFilters.stockStatus
    delete newFilters.priceMin
    delete newFilters.priceMax
    newFilters.page = '1'
    navigateFilters(newFilters)
  }

  const stockStatusLabel: Record<string, string> = {
    positive: t('products_filter_stock_positive'),
    zero: t('products_filter_stock_zero'),
    negative: t('products_filter_stock_negative'),
  }

  const handleExport = async () => {
    try {
      await downloadExport(
        endpoints.products.export,
        {
          all: bulk.selectAllRecords,
          ids: Array.from(bulk.selectedIds),
          filters,
        },
        'products.xls',
      )
    } catch {
      toast.error(t('common_export_error'))
    }
  }

  const handleBulkRestock = async () => {
    const qty = Number(restockQuantity)
    if (!Number.isFinite(qty) || qty <= 0) {
      toast.error(t('products_bulk_restock_invalid_qty'))
      return
    }

    setIsRestocking(true)
    try {
      const ids = bulk.selectAllRecords ? pageIds : Array.from(bulk.selectedIds)
      for (const id of ids) {
        await productsApi.adjustStock(id, qty, 'bulk_restock')
      }
      toast.success(t('products_bulk_restock_success', { count: ids.length }))
      setRestockDialogOpen(false)
      bulk.clearSelection()
      queryClient.invalidateQueries({ queryKey: ['products'] })
    } catch {
      toast.error(t('product_stock_adjust_error'))
    } finally {
      setIsRestocking(false)
    }
  }

  const columns = buildProductsColumns({
    t,
    selectedIds: bulk.selectedIds,
    allPageSelected: bulk.allPageSelected,
    toggleSelectAll: bulk.toggleSelectAll,
    toggleSelectRow: bulk.toggleSelectRow,
    onView: (id) => navigate(pathFor('admin.products.show', id)),
    onEdit: (id) => navigate(pathFor('admin.products.edit', id)),
  })

  const { columnOrder, columnVisibility, setColumnVisibility, handleDragStart, handleDrop } =
    useColumnReorder(columns.map((c) => c.id as string))

  const table = useReactTable({
    data: products.data,
    columns,
    state: { columnOrder, columnVisibility },
    onColumnOrderChange: () => {},
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
  })

  const currentTab = filters.stockLevel ?? 'all'
  const countLabel = t('products_selected_count', {
    count: bulk.selectAllRecords ? products.total : bulk.selectedCount,
  })

  return (
    <AuthenticatedLayout title={t('products_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-2xl font-bold tracking-tight">{t('products_title')}</h2>
              <p className="text-muted-foreground">{t('products_page_description')}</p>
            </div>
            <Button onClick={() => navigate(pathFor('admin.products.create'))} className="w-fit">
              <Plus className="mr-2 h-4 w-4" />
              {t('products_add_button')}
            </Button>
          </div>

          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <MetricStatCard
              title={t('stat_total_products_title')}
              value={stats.total}
              subtitle={t('stat_total_products_subtitle')}
              icon={Package}
              sparklineColor="#6366f1"
              sparklineData={[4, 5, 5, 6, 6, 7, stats.total]}
            />
            <MetricStatCard
              title={t('stat_low_stock_title')}
              value={stats.lowStock}
              subtitle={t('stat_low_stock_subtitle')}
              icon={AlertTriangle}
              sparklineColor="#f59e0b"
              sparklineData={[1, 2, 1, 3, 2, 3, stats.lowStock]}
            />
            <MetricStatCard
              title={t('stat_out_of_stock_title')}
              value={stats.outOfStock}
              subtitle={t('stat_out_of_stock_subtitle')}
              icon={PackageX}
              sparklineColor="#ef4444"
              sparklineData={[0, 1, 0, 1, 0, 1, stats.outOfStock]}
            />
          </div>

          <Tabs value={currentTab} onValueChange={handleTabChange} className="space-y-4">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
              <div className="w-full overflow-x-auto pb-2 lg:pb-0">
                <TabsList>
                  <TabsTrigger value="all">{t('products_tab_all')}</TabsTrigger>
                  <TabsTrigger value="low">{t('stat_low_stock_title')}</TabsTrigger>
                  <TabsTrigger value="out">{t('stat_out_of_stock_title')}</TabsTrigger>
                </TabsList>
              </div>
              <div className="flex flex-col gap-2 sm:flex-row sm:items-center lg:ml-auto">
                <div className="relative w-full sm:w-72">
                  <Input
                    placeholder={t('products_search_placeholder')}
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
                <DataTableViewOptions table={table} columnLabels={productColumnLabels(t)} />
                <ProductFiltersPopover
                  stockStatus={filters.stockStatus}
                  priceMin={filters.priceMin ? Number(filters.priceMin) : undefined}
                  priceMax={filters.priceMax ? Number(filters.priceMax) : undefined}
                  dateRange={dateRange}
                  hasActiveFilters={hasActiveFilters}
                  onStockStatusChange={handleStockStatusChange}
                  onPriceChange={handlePriceChange}
                  onDateChange={handleDateRangeChange}
                  onClearAll={clearAllFilters}
                />
              </div>
            </div>

            {hasActiveFilters && (
              <div className="flex items-center gap-2 flex-wrap">
                {filters.stockStatus && (
                  <Badge variant="secondary" className="gap-1">
                    {t('products_filter_stock_status')}: {stockStatusLabel[filters.stockStatus]}
                    <button
                      onClick={() => handleStockStatusChange(undefined)}
                      className="ml-1 hover:bg-secondary-foreground/20 rounded-full"
                    >
                      ×
                    </button>
                  </Badge>
                )}
                {(filters.priceMin || filters.priceMax) && (
                  <Badge variant="secondary" className="gap-1">
                    {t('products_filter_price')}:{' '}
                    {formatMoney(Number(filters.priceMin ?? 0), 'USD')}
                    {' – '}
                    {filters.priceMax ? formatMoney(Number(filters.priceMax), 'USD') : '∞'}
                    <button
                      onClick={() => handlePriceChange(undefined, undefined)}
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
              total={products.total}
              allPageSelected={bulk.allPageSelected}
              selectAllRecords={bulk.selectAllRecords}
              onSelectAllRecords={() => bulk.setSelectAllRecords(true)}
              onSelectPageOnly={() => bulk.setSelectAllRecords(false)}
              onClearSelection={bulk.clearSelection}
              countLabel={countLabel}
              selectAllLabel={t('products_select_all_label', { total: products.total })}
              actions={
                <>
                  <DropdownMenuItem onClick={() => setRestockDialogOpen(true)}>
                    <PackagePlus className="mr-2 h-4 w-4" />
                    {t('products_bulk_restock_action')}
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
                  <CardTitle>{t('products_title')}</CardTitle>
                  <CardDescription>{t('products_page_description')}</CardDescription>
                </CardHeader>
                <CardContent>
                  <DataTable
                    table={table}
                    colCount={columns.length}
                    emptyMessage={t('products_empty')}
                    onDragStart={handleDragStart}
                    onDrop={handleDrop}
                  />
                </CardContent>
                <DataTablePagination
                  currentPage={products.current_page}
                  lastPage={products.last_page}
                  perPage={perPage}
                  total={products.total}
                  selectedCount={bulk.selectedCount}
                  onPageChange={handlePageChange}
                  onPerPageChange={handlePerPageChange}
                />
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </Main>

      <Dialog open={restockDialogOpen} onOpenChange={setRestockDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('products_bulk_restock_action')}</DialogTitle>
            <DialogDescription>
              {t('products_bulk_restock_description', {
                count: bulk.selectAllRecords ? products.total : bulk.selectedCount,
              })}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            <Label htmlFor="restock-qty">{t('products_bulk_restock_qty_label')}</Label>
            <Input
              id="restock-qty"
              type="number"
              min="1"
              value={restockQuantity}
              onChange={(e) => setRestockQuantity(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setRestockDialogOpen(false)}
              disabled={isRestocking}
            >
              {t('common_cancel')}
            </Button>
            <Button onClick={handleBulkRestock} disabled={isRestocking}>
              {isRestocking ? t('common_saving') : t('products_bulk_restock_action')}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AuthenticatedLayout>
  )
}
