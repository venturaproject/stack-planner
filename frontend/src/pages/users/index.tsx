import { AuthenticatedLayout } from '@/layouts'
import { AccessControlTabs } from './access-control-tabs'
import { Main } from '@/components/layout'
import { MetricStatCard } from '@/components/metric-stat-card'
import { X, Download, Users, UserCheck, UserX, ShieldAlert } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Input } from '@/components/ui/input'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { useNavigate } from 'react-router-dom'
import { PageProps } from '@/types'
import { getCoreRowModel, useReactTable } from '@tanstack/react-table'
import {
  DataTable,
  DataTablePagination,
  DataTableViewOptions,
  BulkSelectionBar,
} from '@/components/data-table'
import { useTableFilters } from '@/hooks/use-table-filters'
import { useBulkSelection } from '@/hooks/use-bulk-selection'
import { useColumnReorder } from '@/hooks/use-column-reorder'
import { submitBulkActionForm } from '@/lib/submit-bulk-action-form'
import { buildUsersColumns, userColumnLabels, User } from './columns'
import { useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { pathFor } from '@/lib/app-routes'
import { ConfirmDialog } from '@/components/dialogs/confirm-dialog'
import { useState } from 'react'
import { usersApi } from '@/services/users-api'
import { useI18n } from '@/i18n/context'
import { ApiClientsTab } from './components/api-clients-tab'

interface UserStats {
  total: number
  activos: number
  inactivos: number
  suspendidos: number
}

interface UserFilters {
  search?: string
  status?: string
  role?: string
  [key: string]: string | undefined
}

interface Role {
  id: number
  name: string
}

interface UsersPageProps extends PageProps {
  users?: {
    data: User[]
    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
  }
  filters?: UserFilters
  roles?: Role[]
  stats?: UserStats
}

const statSeries = {
  total: [6, 8, 7, 9, 8, 11, 9, 12],
  active: [5, 7, 6, 8, 7, 10, 8, 11],
  inactive: [8, 7, 8, 6, 7, 5, 6, 4],
  suspended: [6, 5, 6, 4, 5, 3, 4, 3],
}

export default function UsersPage({
  users = { data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 },
  filters: initialFilters = {},
  stats = { total: 0, activos: 0, inactivos: 0, suspendidos: 0 },
}: UsersPageProps) {
  const { t } = useI18n()
  const navigateReact = useNavigate()
  const queryClient = useQueryClient()
  const [confirmDelete, setConfirmDelete] = useState<{ open: boolean; id: string | null }>({
    open: false,
    id: null,
  })
  const [mainTab, setMainTab] = useState<'users' | 'api-clients'>('users')

  const pagination = {
    current_page: users.current_page ?? 1,
    last_page: users.last_page ?? 1,
    per_page: users.per_page ?? 20,
    total: users.total ?? users.data.length,
  }

  const pageIds = users.data.map((u) => u.id)

  const bulk = useBulkSelection(pageIds, pagination.total)

  const {
    filters,
    searchTerm,
    navigate,
    handleSearch,
    handlePageChange,
    handlePerPageChange,
    perPage,
  } = useTableFilters<UserFilters>({
    basePath: pathFor('admin.users.index'),
    initialFilters: initialFilters ?? {},
    initialPerPage: pagination.per_page,
    onNavigate: bulk.clearSelection,
  })

  const handleTabChange = (value: string) => {
    const newFilters = { ...filters }
    if (value === 'active') newFilters.status = 'active'
    else if (value === 'inactive') newFilters.status = 'inactive'
    else if (value === 'suspended') newFilters.status = 'suspended'
    else delete newFilters.status
    newFilters.page = '1'
    navigate(newFilters)
  }

  const handleDeleteUser = async (id: string) => {
    setConfirmDelete({ open: true, id })
  }

  const confirmDeleteUser = async () => {
    if (!confirmDelete.id) return
    const id = confirmDelete.id
    setConfirmDelete({ open: false, id: null })

    try {
      await usersApi.delete(id)
      toast.success(t('user_deleted_success'))
      queryClient.invalidateQueries({ queryKey: ['users'] })
    } catch (_error: unknown) {
      toast.error(t('user_deleted_error'))
    }
  }

  const columns = buildUsersColumns({
    t,
    selectedIds: bulk.selectedIds,
    allPageSelected: bulk.allPageSelected,
    toggleSelectAll: bulk.toggleSelectAll,
    toggleSelectRow: bulk.toggleSelectRow,
    onView: (id) => navigateReact(pathFor('admin.users.show', id)),
    onEdit: (id) => navigateReact(pathFor('admin.users.edit', id)),
    onDelete: handleDeleteUser,
  })

  const { columnOrder, columnVisibility, setColumnVisibility, handleDragStart, handleDrop } =
    useColumnReorder(columns.map((c) => c.id as string))

  const table = useReactTable({
    data: users.data,
    columns,
    state: { columnOrder, columnVisibility },
    onColumnOrderChange: () => {},
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
  })

  const currentTab = filters.status ?? 'all'

  const countLabel = t('users_selected_count', {
    count: bulk.selectAllRecords ? pagination.total : bulk.selectedCount,
  })

  return (
    <AuthenticatedLayout title={t('users_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <AccessControlTabs />

          <Tabs
            value={mainTab}
            onValueChange={(v) => setMainTab(v as 'users' | 'api-clients')}
            className="space-y-4"
          >
            <TabsList>
              <TabsTrigger value="users">{t('users_tab_application')}</TabsTrigger>
              <TabsTrigger value="api-clients">{t('api_clients_title')}</TabsTrigger>
            </TabsList>

            <TabsContent value="api-clients">
              <ApiClientsTab />
            </TabsContent>

            <TabsContent value="users" className="space-y-4 md:space-y-8">
              <div>
                <h2 className="text-2xl font-bold tracking-tight">{t('users_list_title')}</h2>
                <p className="text-muted-foreground">{t('users_list_description')}</p>
              </div>

              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <MetricStatCard
                  title={t('users_stat_total_title')}
                  value={stats.total}
                  subtitle={t('users_stat_total_subtitle')}
                  icon={Users}
                  tooltip={t('users_stat_total_tooltip')}
                  trend={14.2}
                  sparklineColor="#6366f1"
                  sparklineData={statSeries.total}
                  detailsLabel={t('users_stat_total_subtitle')}
                />
                <MetricStatCard
                  title={t('users_stat_active_title')}
                  value={stats.activos}
                  subtitle={t('users_stat_active_subtitle')}
                  icon={UserCheck}
                  tooltip={t('users_stat_active_subtitle')}
                  trend={11.5}
                  sparklineColor="#10b981"
                  sparklineData={statSeries.active}
                  detailsLabel={t('users_stat_active_title')}
                />
                <MetricStatCard
                  title={t('users_stat_inactive_title')}
                  value={stats.inactivos}
                  subtitle={t('users_stat_inactive_subtitle')}
                  icon={UserX}
                  tooltip={t('users_stat_inactive_subtitle')}
                  trend={-5.8}
                  sparklineColor="#94a3b8"
                  sparklineData={statSeries.inactive}
                  detailsLabel={t('users_stat_inactive_title')}
                />
                <MetricStatCard
                  title={t('users_stat_suspended_title')}
                  value={stats.suspendidos}
                  subtitle={t('users_stat_suspended_subtitle')}
                  icon={ShieldAlert}
                  tooltip={t('users_stat_suspended_subtitle')}
                  trend={-3.4}
                  sparklineColor="#f87171"
                  sparklineData={statSeries.suspended}
                  detailsLabel={t('users_stat_suspended_title')}
                />
              </div>

              <Tabs value={currentTab} onValueChange={handleTabChange} className="space-y-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
                  <div className="w-full overflow-x-auto pb-2 lg:pb-0">
                    <TabsList>
                      <TabsTrigger value="all">{t('users_tab_all')}</TabsTrigger>
                      <TabsTrigger value="active">{t('users_stat_active_title')}</TabsTrigger>
                      <TabsTrigger value="inactive">{t('users_stat_inactive_title')}</TabsTrigger>
                      <TabsTrigger value="suspended">{t('users_stat_suspended_title')}</TabsTrigger>
                    </TabsList>
                  </div>
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-center lg:ml-auto">
                    <div className="relative w-full sm:w-72">
                      <Input
                        placeholder={t('common_search_placeholder')}
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
                    <div className="flex items-center gap-2">
                      <DataTableViewOptions table={table} columnLabels={userColumnLabels(t)} />
                      <Button
                        variant="default"
                        size="sm"
                        className="h-9 gap-1"
                        onClick={() => navigateReact(pathFor('admin.users.create'))}
                      >
                        {t('users_add_button')}
                      </Button>
                    </div>
                  </div>
                </div>

                <BulkSelectionBar
                  visible={bulk.selectedIds.size > 0}
                  selectedCount={bulk.selectedCount}
                  total={pagination.total}
                  allPageSelected={bulk.allPageSelected}
                  selectAllRecords={bulk.selectAllRecords}
                  onSelectAllRecords={() => bulk.setSelectAllRecords(true)}
                  onSelectPageOnly={() => bulk.setSelectAllRecords(false)}
                  onClearSelection={bulk.clearSelection}
                  countLabel={countLabel}
                  selectAllLabel={t('users_select_all_label', { total: pagination.total })}
                  actions={
                    <DropdownMenuItem
                      onClick={() =>
                        submitBulkActionForm(
                          pathFor('admin.users.bulk-export'),
                          bulk.selectedIds,
                          bulk.selectAllRecords,
                          filters,
                        )
                      }
                    >
                      <Download className="mr-2 h-4 w-4" />
                      {t('common_export_excel')}
                    </DropdownMenuItem>
                  }
                />

                <TabsContent value={currentTab}>
                  <Card>
                    <CardHeader>
                      <CardTitle>{t('users_card_title')}</CardTitle>
                      <CardDescription>{t('users_card_description')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                      <DataTable
                        table={table}
                        colCount={columns.length}
                        emptyMessage={t('common_no_results')}
                        onDragStart={handleDragStart}
                        onDrop={handleDrop}
                      />
                    </CardContent>
                    <DataTablePagination
                      currentPage={pagination.current_page}
                      lastPage={pagination.last_page}
                      perPage={perPage}
                      total={pagination.total}
                      selectedCount={bulk.selectedCount}
                      onPageChange={handlePageChange}
                      onPerPageChange={handlePerPageChange}
                    />
                  </Card>
                </TabsContent>
              </Tabs>
            </TabsContent>
          </Tabs>
        </div>
      </Main>

      <ConfirmDialog
        open={confirmDelete.open}
        onOpenChange={(open) => setConfirmDelete((prev) => ({ ...prev, open }))}
        title={t('users_delete_dialog_title')}
        desc={t('users_delete_dialog_desc')}
        confirmText={t('common_delete')}
        destructive
        handleConfirm={confirmDeleteUser}
      />
    </AuthenticatedLayout>
  )
}
