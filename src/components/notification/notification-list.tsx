import React from 'react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Trash2, CheckCircle, X } from 'lucide-react'
import { useNotifications } from '@/hooks/use-notifications'
import { useI18n } from '@/i18n/context'

const TYPE_COLORS: Record<string, string> = {
  success: 'bg-green-100 text-green-800 border-green-200',
  warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  error: 'bg-red-100 text-red-800 border-red-200',
  info: 'bg-blue-100 text-blue-800 border-blue-200',
}

const NotificationList: React.FC = () => {
  const { t } = useI18n()
  const { notifications, markRead, markAllRead, deleteOne, deleteAll } = useNotifications()

  const TYPE_LABELS: Record<string, string> = {
    success: t('notification_type_success'),
    warning: t('notification_type_warning'),
    error: t('notification_type_error'),
    info: t('notification_type_info'),
  }

  if (notifications.length === 0) {
    return <div className="text-center py-8 text-muted-foreground">{t('notification_empty')}</div>
  }

  return (
    <div className="space-y-4">
      <div className="flex gap-2 mb-4">
        <Button
          variant="outline"
          size="sm"
          onClick={markAllRead}
          disabled={notifications.every((n) => n.readAt !== null)}
        >
          <CheckCircle className="h-4 w-4 mr-2" />
          {t('notification_mark_all_read')}
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={deleteAll}
          className="text-destructive hover:text-destructive"
        >
          <Trash2 className="h-4 w-4 mr-2" />
          {t('notification_delete_all')}
        </Button>
      </div>

      {notifications.map((n) => (
        <Card key={n.id} className={!n.readAt ? 'border-l-4 border-l-blue-500' : ''}>
          <CardHeader className="pb-2">
            <div className="flex items-start justify-between">
              <CardTitle className="text-sm">{n.title}</CardTitle>
              <div className="flex gap-2">
                <Badge
                  variant="secondary"
                  className={`text-xs ${TYPE_COLORS[n.typeColor] ?? TYPE_COLORS.info}`}
                >
                  {TYPE_LABELS[n.typeColor] ??
                    n.typeColor.charAt(0).toUpperCase() + n.typeColor.slice(1)}
                </Badge>
                {!n.readAt && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => markRead(n.id)}
                    className="h-6 w-6 p-0"
                  >
                    <CheckCircle className="h-4 w-4" />
                  </Button>
                )}
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => deleteOne(n.id)}
                  className="h-6 w-6 p-0 text-destructive hover:text-destructive"
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            </div>
            <p className="text-xs text-muted-foreground">
              {new Date(n.createdAt).toLocaleDateString()}
            </p>
          </CardHeader>
          <CardContent>
            <p className="text-sm">{n.message}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  )
}

export default NotificationList
