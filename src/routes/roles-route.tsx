import { useQuery } from '@tanstack/react-query'
import { useSearchParams, useParams, useLocation } from 'react-router-dom'
import RolesPage from '@/pages/roles/index'
import CreateRolePage from '@/pages/roles/create'
import EditRolePage from '@/pages/roles/edit'
import { RoutePending } from '@/components/route-pending'
import NotFoundError from '@/pages/errors/not-found-error'
import GeneralError from '@/pages/errors/general-error'
import { normalizePaginatedPayload } from '@/lib/api-utils'
import { rolesApi } from '@/services/roles-api'
import { permissionsApi } from '@/services/permissions-api'
import type { Role, Permission, GroupedPermissions } from '@/types'

export default function RolesRoute() {
  const [searchParams] = useSearchParams()
  const { id } = useParams()
  const location = useLocation()
  const filters = Object.fromEntries(searchParams.entries())

  const isCreate = location.pathname.endsWith('/create')
  const isEdit = !!id && location.pathname.includes(`/${id}/edit`)

  const {
    data: listData,
    isLoading: listLoading,
    isError: listError,
  } = useQuery({
    queryKey: ['roles', filters],
    queryFn: () => rolesApi.list(filters),
    enabled: !isCreate && !isEdit,
  })

  const { data: permissionsData } = useQuery({
    queryKey: ['permissions', 'all'],
    queryFn: () => permissionsApi.all(),
    enabled: isCreate || isEdit,
  })

  const {
    data: roleData,
    isLoading: roleLoading,
    isError: roleError,
  } = useQuery({
    queryKey: ['roles', id],
    queryFn: () => rolesApi.detail(id!),
    enabled: isEdit,
  })

  const groupPermissions = (perms: Permission[]): GroupedPermissions => {
    const grouped: GroupedPermissions = {}
    perms.forEach((p) => {
      const parts = p.name.split('.')
      const model = parts[0] || 'other'
      const action = parts[1] || p.name

      if (!grouped[model]) {
        grouped[model] = {
          key: model,
          label: model.charAt(0).toUpperCase() + model.slice(1),
          permissions: [],
        }
      }

      grouped[model].permissions.push({
        id: p.id,
        name: p.name,
        action,
        actionLabel: action.charAt(0).toUpperCase() + action.slice(1),
      })
    })
    return grouped
  }

  const roles = normalizePaginatedPayload<Role>(listData)
  const permissions: Permission[] = permissionsData ?? []
  const groupedPermissions = groupPermissions(permissions)

  if (isEdit) {
    if (roleLoading) return <RoutePending />
    if (roleError || !roleData) return <NotFoundError />
    return (
      <EditRolePage
        role={roleData}
        permissions={permissions}
        groupedPermissions={groupedPermissions}
        rolePermissions={roleData.permissions?.map((p) => p.id) ?? []}
      />
    )
  }

  if (isCreate) {
    return <CreateRolePage permissions={permissions} groupedPermissions={groupedPermissions} />
  }

  if (listLoading) return <RoutePending />
  if (listError) return <GeneralError minimal />

  return <RolesPage roles={roles} filters={filters} permissions={listData?.permissions ?? []} />
}
