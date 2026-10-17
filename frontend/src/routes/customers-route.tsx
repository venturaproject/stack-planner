import { useQuery } from '@tanstack/react-query'
import { useSearchParams, useParams, useLocation } from 'react-router-dom'
import CustomersPage from '@/pages/customers/index'
import CreateCustomerPage from '@/pages/customers/create'
import EditCustomerPage from '@/pages/customers/edit'
import ShowCustomerPage from '@/pages/customers/show'
import { RoutePending } from '@/components/route-pending'
import NotFoundError from '@/pages/errors/not-found-error'
import GeneralError from '@/pages/errors/general-error'
import { normalizePaginatedPayload } from '@/lib/api-utils'
import { customersApi } from '@/services/customers-api'
import type { Customer } from '@/pages/customers/types'

export default function CustomersRoute() {
  const [searchParams] = useSearchParams()
  const { id } = useParams()
  const location = useLocation()
  const filters = Object.fromEntries(searchParams.entries())

  const isCreate = location.pathname.endsWith('/create')
  const isEdit = !!id && location.pathname.includes(`/${id}/edit`)
  const isShow = !!id && !isEdit && location.pathname.includes(`/customers/${id}`)

  const {
    data: listData,
    isLoading: listLoading,
    isError: listError,
  } = useQuery({
    queryKey: ['customers', filters],
    queryFn: () => customersApi.list(filters),
    enabled: !isCreate && !isEdit && !isShow,
  })

  const {
    data: customerData,
    isLoading: customerLoading,
    isError: customerError,
  } = useQuery({
    queryKey: ['customers', id],
    queryFn: () => customersApi.detail(id!),
    enabled: isEdit || isShow,
  })

  const customers = normalizePaginatedPayload<Customer>(listData)
  const stats = listData?.stats ?? { total: 0, withAddress: 0, withoutAddress: 0 }

  if (isEdit) {
    if (customerLoading) return <RoutePending />
    if (customerError || !customerData) return <NotFoundError />
    return <EditCustomerPage customer={customerData} />
  }

  if (isCreate) {
    return <CreateCustomerPage />
  }

  if (isShow) {
    if (customerLoading) return <RoutePending />
    if (customerError || !customerData) return <NotFoundError />
    return <ShowCustomerPage customer={customerData} />
  }

  if (listLoading) return <RoutePending />
  if (listError) return <GeneralError minimal />

  return <CustomersPage customers={customers} stats={stats} filters={filters} />
}
