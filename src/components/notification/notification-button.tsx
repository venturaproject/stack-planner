import React, { useState } from 'react'
import { Bell } from 'lucide-react'
import { Button } from '@/components/ui/button'
import NotificationSlideout from '@/components/notification/notification-slideout'
import { useNotifications } from '@/hooks/use-notifications'

const NotificationButton = () => {
  const [isOpen, setIsOpen] = useState(false)
  const { unreadCount } = useNotifications()

  return (
    <>
      <Button variant="ghost" size="icon" onClick={() => setIsOpen(!isOpen)} className="relative">
        <Bell className="h-5 w-5" />
        {unreadCount > 0 && (
          <span className="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500" />
        )}
      </Button>
      <NotificationSlideout isOpen={isOpen} onClose={() => setIsOpen(false)} />
    </>
  )
}

export default NotificationButton
