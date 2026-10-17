import { useQuery } from '@tanstack/react-query'
import { useSearchParams, useParams, useLocation } from 'react-router-dom'
import OrdersPage from '@/pages/orders/index'
import CreateOrderPage from '@/pages/orders/create'
import ShowOrderPage from '@/pages/orders/show'
import { RoutePending } from '@/components/route-pending'
import NotFoundError from '@/pages/errors/not-found-error'
import GeneralError from '@/pages/errors/general-error'
import { normalizePaginatedPayload } from '@/lib/api-utils'
import { ordersApi } from '@/services/orders-api'
import type { Order } from '@/pages/orders/types'

export default function OrdersRoute() {
  const [searchParams] = useSearchParams()
  const { id } = useParams()
  const location = useLocation()
  const filters = Object.fromEntries(searchParams.entries())

  const isCreate = location.pathname.endsWith('/create')
  const isShow = !!id

  const {
    data: listData,
    isLoading: listLoading,
    isError: listError,
  } = useQuery({
    queryKey: ['orders', filters],
    queryFn: () => ordersApi.list(filters),
    enabled: !isCreate && !isShow,
  })

  const {
    data: orderData,
    isLoading: orderLoading,
    isError: orderError,
  } = useQuery({
    queryKey: ['orders', id],
    queryFn: () => ordersApi.detail(id!),
    enabled: isShow,
  })

  const orders = normalizePaginatedPayload<Order>(listData)
  const stats = listData?.stats ?? { total: 0, draft: 0, placed: 0, cancelled: 0 }

  if (isShow) {
    if (orderLoading) return <RoutePending />
    if (orderError || !orderData) return <NotFoundError />
    return <ShowOrderPage order={orderData} />
  }

  if (isCreate) {
    return <CreateOrderPage />
  }

  if (listLoading) return <RoutePending />
  if (listError) return <GeneralError minimal />

  return <OrdersPage orders={orders} stats={stats} filters={filters} />
}
