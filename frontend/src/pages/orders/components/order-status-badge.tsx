import { Clock, CheckCircle2, XCircle } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { useI18n } from '@/i18n/context'
import type { OrderStatus } from '@/pages/orders/types'

const STATUS_STYLES: Record<OrderStatus, { tone: string; icon: typeof Clock; labelKey: string }> = {
  draft: {
    tone: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    icon: Clock,
    labelKey: 'order_status_draft',
  },
  placed: {
    tone: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    icon: CheckCircle2,
    labelKey: 'order_status_placed',
  },
  cancelled: {
    tone: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
    icon: XCircle,
    labelKey: 'order_status_cancelled',
  },
}

/** Single source of truth for how an order status renders — icon, color, and label — used everywhere an order status is shown (orders list/detail, dashboard). */
export function OrderStatusBadge({ status }: { status: OrderStatus }) {
  const { t } = useI18n()
  const { tone, icon: Icon, labelKey } = STATUS_STYLES[status]

  return (
    <Badge variant="outline" className={`gap-1 border-transparent font-medium ${tone}`}>
      <Icon className="h-3 w-3" />
      {t(labelKey)}
    </Badge>
  )
}
