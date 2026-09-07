import { AuthenticatedLayout } from '@/layouts'
import { AccessControlTabs } from '@/pages/users/access-control-tabs'
import { MoreHorizontal, PlusCircle, Edit, Trash2, Shield, Users } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Main } from '@/components/layout'
import { useState } from 'react'
import { ConfirmDialog } from '@/components/dialogs/confirm-dialog'
import { Role, PageProps, PaginatedData } from '@/types'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { useQueryClient } from '@tanstack/react-query'
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import { pathFor } from '@/lib/app-routes'
import { rolesApi } from '@/services/roles-api'
import { useI18n } from '@/i18n/context'

interface RolesPageProps extends PageProps {
  roles: PaginatedData<Role>
  filters?: { search?: string }
}

export default function RolesIndex({ roles, filters: initialFilters = {} }: RolesPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState(initialFilters?.search || '')
  const [confirmDelete, setConfirmDelete] = useState<{ open: boolean; id: number | null }>({
    open: false,
    id: null,
  })
  const fullAccessRoles = ['admin']
  const isFullAccessRole = (name: string) => fullAccessRoles.includes(name.toLowerCase())

  const handleSearch = (value: string) => {
    setSearchTerm(value)
    navigate(pathFor('admin.roles.index', { search: value || undefined }), { replace: true })
  }

  const handleDelete = (role: Role) => {
    if (isFullAccessRole(role.name)) {
      toast.error(t('role_delete_full_access_error'))
      return
    }
    setConfirmDelete({ open: true, id: role.id })
  }

  const confirmDeleteRole = async () => {
    if (!confirmDelete.id) return
    const roleId = confirmDelete.id
    setConfirmDelete({ open: false, id: null })

    try {
      await rolesApi.delete(roleId)
      toast.success(t('role_deleted_success'))
      queryClient.invalidateQueries({ queryKey: ['roles'] })
    } catch (_error: unknown) {
      toast.error(t('role_deleted_error'))
    }
  }

  const handlePageChange = (page: number) => {
    navigate(pathFor('admin.roles.index', { ...initialFilters, page }), { replace: true })
  }

  const generatePageNumbers = () => {
    const pages: (number | string)[] = []
    const delta = 2
    const rangeStart = Math.max(2, roles.current_page - delta)
    const rangeEnd = Math.min(roles.last_page - 1, roles.current_page + delta)

    if (roles.last_page > 1) pages.push(1)
    if (rangeStart > 2) pages.push('...')
    for (let i = rangeStart; i <= rangeEnd; i++) {
      if (i !== 1 && i !== roles.last_page) pages.push(i)
    }
    if (rangeEnd < roles.last_page - 1) pages.push('...')
    if (roles.last_page > 1 && roles.last_page !== 1) pages.push(roles.last_page)

    return pages
  }

  return (
    <AuthenticatedLayout title={t('roles_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <AccessControlTabs />
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    <Shield className="h-5 w-5" />
                    {t('common_roles')}
                  </CardTitle>
                  <CardDescription>{t('roles_page_description')}</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Input
                    placeholder={t('roles_search_placeholder')}
                    value={searchTerm}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="w-64"
                  />
                  <Button
                    size="sm"
                    className="gap-1"
                    onClick={() => navigate(pathFor('admin.roles.create'))}
                  >
                    <PlusCircle className="h-4 w-4" />
                    {t('roles_add_button')}
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t('roles_table_name')}</TableHead>
                    <TableHead>{t('roles_table_permissions')}</TableHead>
                    <TableHead>{t('roles_table_users')}</TableHead>
                    <TableHead>{t('roles_table_guard')}</TableHead>
                    <TableHead>
                      <span className="sr-only">{t('common_actions')}</span>
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {!roles.data || roles.data.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="h-24 text-center">
                        {t('roles_empty')}
                      </TableCell>
                    </TableRow>
                  ) : (
                    roles.data.map((role) => (
                      <TableRow key={role.id}>
                        <TableCell className="font-medium">
                          <div className="flex items-center gap-2">
                            {role.name}
                            {isFullAccessRole(role.name) && (
                              <Badge variant="secondary">{t('roles_system_badge')}</Badge>
                            )}
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline">
                            {isFullAccessRole(role.name)
                              ? t('role_all_permissions_label')
                              : role.permissions_count || 0}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1">
                            <Users className="h-4 w-4 text-muted-foreground" />
                            <span>{role.users_count || 0}</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline">{role.guard_name}</Badge>
                        </TableCell>
                        <TableCell>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button aria-haspopup="true" size="icon" variant="ghost">
                                <MoreHorizontal className="h-4 w-4" />
                                <span className="sr-only">{t('common_toggle_menu')}</span>
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuLabel>{t('common_actions')}</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem
                                onClick={() => navigate(pathFor('admin.roles.edit', role.id))}
                              >
                                <Edit className="mr-2 h-4 w-4" />
                                {t('common_edit')}
                              </DropdownMenuItem>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem
                                className="text-red-600"
                                onClick={() => handleDelete(role)}
                                disabled={isFullAccessRole(role.name)}
                              >
                                <Trash2 className="mr-2 h-4 w-4" />
                                {t('common_delete')}
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
            <CardFooter className="flex flex-col sm:flex-row items-center justify-between gap-4">
              <div className="text-xs text-muted-foreground">
                {t('roles_showing', {
                  start: (roles.current_page - 1) * roles.per_page + 1,
                  end: Math.min(roles.current_page * roles.per_page, roles.total),
                  total: roles.total,
                })}
              </div>

              {roles.last_page > 1 && (
                <Pagination>
                  <PaginationContent>
                    <PaginationItem>
                      <PaginationPrevious
                        href="#"
                        onClick={(e) => {
                          e.preventDefault()
                          if (roles.current_page > 1) handlePageChange(roles.current_page - 1)
                        }}
                        className={roles.current_page === 1 ? 'pointer-events-none opacity-50' : ''}
                      />
                    </PaginationItem>

                    {generatePageNumbers().map((page, index) => (
                      <PaginationItem key={index}>
                        {page === '...' ? (
                          <span className="flex h-9 w-9 items-center justify-center text-sm">
                            ...
                          </span>
                        ) : (
                          <PaginationLink
                            href="#"
                            onClick={(e) => {
                              e.preventDefault()
                              handlePageChange(page as number)
                            }}
                            isActive={page === roles.current_page}
                          >
                            {page}
                          </PaginationLink>
                        )}
                      </PaginationItem>
                    ))}

                    <PaginationItem>
                      <PaginationNext
                        href="#"
                        onClick={(e) => {
                          e.preventDefault()
                          if (roles.current_page < roles.last_page)
                            handlePageChange(roles.current_page + 1)
                        }}
                        className={
                          roles.current_page === roles.last_page
                            ? 'pointer-events-none opacity-50'
                            : ''
                        }
                      />
                    </PaginationItem>
                  </PaginationContent>
                </Pagination>
              )}
            </CardFooter>
          </Card>
        </div>
      </Main>

      <ConfirmDialog
        open={confirmDelete.open}
        onOpenChange={(open) => setConfirmDelete((prev) => ({ ...prev, open }))}
        title={t('roles_delete_dialog_title')}
        desc={t('roles_delete_dialog_desc')}
        confirmText={t('common_delete')}
        destructive
        handleConfirm={confirmDeleteRole}
      />
    </AuthenticatedLayout>
  )
}
