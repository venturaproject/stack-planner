import React from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import NotificationList from '@/components/notification/notification-list'
import { useI18n } from '@/i18n/context'

interface NotificationSlideoutProps {
  isOpen: boolean
  onClose: () => void
}

const NotificationSlideout: React.FC<NotificationSlideoutProps> = ({ isOpen, onClose }) => {
  const { t } = useI18n()
  return (
    <div
      className={`fixed inset-0 z-50 overflow-hidden transition-all duration-300 ${
        isOpen ? 'pointer-events-auto' : 'pointer-events-none'
      }`}
    >
      <div
        className={`absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 ${
          isOpen ? 'opacity-100' : 'opacity-0'
        }`}
        onClick={onClose}
      />
      <div
        className={`absolute right-0 top-0 h-full w-full max-w-sm bg-background shadow-xl transition-transform duration-300 ease-out ${
          isOpen ? 'translate-x-0' : 'translate-x-full'
        }`}
      >
        <div className="flex h-full flex-col">
          <div className="flex items-center justify-between border-b p-4">
            <h2 className="text-lg font-semibold">{t('settings_nav_notifications')}</h2>
            <Button variant="ghost" size="icon" onClick={onClose}>
              <X className="h-5 w-5" />
            </Button>
          </div>
          <ScrollArea className="flex-1 p-4">
            <NotificationList />
          </ScrollArea>
        </div>
      </div>
    </div>
  )
}

export default NotificationSlideout
