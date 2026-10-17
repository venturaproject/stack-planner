import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout/main'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useI18n } from '@/i18n/context'
import { lazy, Suspense, useState } from 'react'
import { OverviewTab } from './overview-tab'
import type { Product } from '@/pages/products/types'
import type { Order } from '@/pages/orders/types'
import type { DashboardStats } from './types'

const AnalyticsTab = lazy(() =>
  import('./analytics-tab').then(({ AnalyticsTab }) => ({ default: AnalyticsTab })),
)
const ReportsTab = lazy(() =>
  import('./reports-tab').then(({ ReportsTab }) => ({ default: ReportsTab })),
)
const NotificationsTab = lazy(() =>
  import('./notifications-tab').then(({ NotificationsTab }) => ({ default: NotificationsTab })),
)

interface ProductStats {
  total: number
  lowStock: number
  outOfStock: number
}

interface CustomerStats {
  total: number
  withAddress: number
  withoutAddress: number
}

interface DashboardPageProps {
  stats: DashboardStats
  productStats: ProductStats
  customerStats: CustomerStats
  restockProducts: Product[]
  recentOrders: Order[]
}

export default function Dashboard({
  stats,
  productStats,
  customerStats,
  restockProducts,
  recentOrders,
}: DashboardPageProps) {
  const { t } = useI18n()
  const [activeTab, setActiveTab] = useState('overview')

  return (
    <AuthenticatedLayout title={t('dashboard_title')}>
      <Main>
        <div className="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <h1 className="text-2xl font-bold tracking-tight">{t('dashboard_title')}</h1>
        </div>

        <Tabs
          orientation="vertical"
          value={activeTab}
          onValueChange={setActiveTab}
          className="space-y-4"
        >
          <div className="w-full overflow-x-auto pb-2">
            <TabsList>
              <TabsTrigger value="overview">{t('overview_tab')}</TabsTrigger>
              <TabsTrigger value="analytics">{t('analytics_tab')}</TabsTrigger>
              <TabsTrigger value="reports">{t('reports_tab')}</TabsTrigger>
              <TabsTrigger value="notifications">{t('notifications_tab')}</TabsTrigger>
            </TabsList>
          </div>

          {activeTab === 'overview' && (
            <TabsContent value="overview" className="space-y-4" forceMount>
              <OverviewTab stats={stats} recentOrders={recentOrders} />
            </TabsContent>
          )}
          {activeTab === 'analytics' && (
            <TabsContent value="analytics" className="space-y-4" forceMount>
              <Suspense fallback={null}>
                <AnalyticsTab
                  stats={stats}
                  productStats={productStats}
                  customerStats={customerStats}
                />
              </Suspense>
            </TabsContent>
          )}
          {activeTab === 'reports' && (
            <TabsContent value="reports" className="space-y-4" forceMount>
              <Suspense fallback={null}>
                <ReportsTab restockProducts={restockProducts} recentOrders={recentOrders} />
              </Suspense>
            </TabsContent>
          )}
          {activeTab === 'notifications' && (
            <TabsContent value="notifications" className="space-y-4" forceMount>
              <div className="max-w-2xl">
                <Suspense fallback={null}>
                  <NotificationsTab />
                </Suspense>
              </div>
            </TabsContent>
          )}
        </Tabs>
      </Main>
    </AuthenticatedLayout>
  )
}
