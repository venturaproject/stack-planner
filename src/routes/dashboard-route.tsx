import { useQuery } from '@tanstack/react-query'
import Dashboard from '@/pages/dashboard/index'
import { RoutePending } from '@/components/route-pending'
import { dashboardApi } from '@/services/dashboard-api'
import { productsApi } from '@/services/products-api'
import { customersApi } from '@/services/customers-api'
import { ordersApi } from '@/services/orders-api'
import type { Product } from '@/pages/products/types'
import type { Order } from '@/pages/orders/types'
import type { DashboardStats } from '@/pages/dashboard/types'

const EMPTY_STATS: DashboardStats = {
  totalProducts: 0,
  lowStockProducts: 0,
  totalCustomers: 0,
  totalOrders: 0,
  draftOrders: 0,
  placedOrders: 0,
  cancelledOrders: 0,
}

export default function DashboardRoute() {
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => dashboardApi.stats(),
  })

  const { data: productsData, isLoading: productsLoading } = useQuery({
    queryKey: ['products', { size: 100 }],
    queryFn: () => productsApi.list({ size: 100 }),
  })

  const { data: customersData, isLoading: customersLoading } = useQuery({
    queryKey: ['customers', { size: 1 }],
    queryFn: () => customersApi.list({ size: 1 }),
  })

  const { data: ordersData, isLoading: ordersLoading } = useQuery({
    queryKey: ['orders', { size: 5 }],
    queryFn: () => ordersApi.list({ size: 5 }),
  })

  if (statsLoading || productsLoading || customersLoading || ordersLoading) {
    return <RoutePending />
  }

  const products = (productsData?.data ?? []) as Product[]
  const restockProducts = products
    .filter((p) => p.stockQuantity <= 5)
    .sort((a, b) => a.stockQuantity - b.stockQuantity)

  const productStats = productsData?.stats ?? { total: 0, lowStock: 0, outOfStock: 0 }
  const customerStats = customersData?.stats ?? { total: 0, withAddress: 0, withoutAddress: 0 }
  const recentOrders = (ordersData?.data ?? []) as Order[]

  return (
    <Dashboard
      stats={stats ?? EMPTY_STATS}
      productStats={productStats}
      customerStats={customerStats}
      restockProducts={restockProducts}
      recentOrders={recentOrders}
    />
  )
}
