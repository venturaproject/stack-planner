import { useQuery } from '@tanstack/react-query'
import { useSearchParams, useParams, useLocation } from 'react-router-dom'
import UsersPage from '@/pages/users/index'
import CreateUserPage from '@/pages/users/create'
import EditUserPage from '@/pages/users/edit'
import ShowUserPage from '@/pages/users/show'
import { RoutePending } from '@/components/route-pending'
import NotFoundError from '@/pages/errors/not-found-error'
import GeneralError from '@/pages/errors/general-error'
import { normalizePaginatedPayload } from '@/lib/api-utils'
import { usersApi } from '@/services/users-api'
import { rolesApi } from '@/services/roles-api'
import { permissionsApi } from '@/services/permissions-api'
import type { Role, Permission } from '@/types'
import type { User as UserListItem } from '@/pages/users/columns'

export default function UsersRoute() {
  const [searchParams] = useSearchParams()
  const { id } = useParams()
  const location = useLocation()
  const filters = Object.fromEntries(searchParams.entries())

  const isCreate = location.pathname.endsWith('/create')
  const isEdit = !!id && location.pathname.includes(`/${id}/edit`)
  const isShow = !!id && !isEdit && location.pathname.includes(`/users/${id}`)

  const {
    data: listData,
    isLoading: listLoading,
    isError: listError,
  } = useQuery({
    queryKey: ['users', filters],
    queryFn: () => usersApi.list(filters),
    enabled: !isCreate && !isEdit && !isShow,
  })

  const { data: rolesData, isLoading: rolesLoading } = useQuery({
    queryKey: ['roles', 'all'],
    queryFn: () => rolesApi.all(),
    enabled: isCreate || isEdit,
  })

  const { data: permissionsData, isLoading: permissionsLoading } = useQuery({
    queryKey: ['permissions', 'all'],
    queryFn: () => permissionsApi.all(),
    enabled: isEdit,
  })

  const {
    data: userData,
    isLoading: userLoading,
    isError: userError,
  } = useQuery({
    queryKey: ['users', id],
    queryFn: () => usersApi.detail(id!),
    enabled: !!id,
  })

  const users = normalizePaginatedPayload<UserListItem>(listData)
  const roles: Role[] = rolesData ?? []
  const permissions: Permission[] = permissionsData ?? []

  const listUsers = (listData?.data ?? []) as UserListItem[]
  const computedStats = {
    total: users.total,
    activos: listUsers.filter((u) => u.status !== 'inactive').length,
    inactivos: listUsers.filter((u) => u.status === 'inactive').length,
    suspendidos: 0,
  }

  if (isEdit) {
    if (userLoading || rolesLoading || permissionsLoading) return <RoutePending />
    if (userError || !userData) return <NotFoundError />
    return <EditUserPage user={userData} roles={roles} permissions={permissions} />
  }

  if (isCreate) {
    return <CreateUserPage roles={roles} />
  }

  if (isShow) {
    if (userLoading) return <RoutePending />
    if (userError || !userData) return <NotFoundError />
    return <ShowUserPage user={userData} />
  }

  if (listLoading) return <RoutePending />
  if (listError) return <GeneralError minimal />

  return <UsersPage users={users} stats={computedStats} filters={filters} roles={roles} />
}
