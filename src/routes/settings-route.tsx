import { useParams } from 'react-router-dom'

// Settings pages
import ProfilePage from '@/pages/settings/profile'
import AppearancePage from '@/pages/settings/appearance'
import NotificationsPage from '@/pages/settings/notifications'
import DisplayPage from '@/pages/settings/display'
import AccountPage from '@/pages/settings/account'
import SecurityPage from '@/pages/settings/security'

export default function SettingsRoute() {
  const { section } = useParams()

  switch (section) {
    case 'appearance':
      return <AppearancePage />
    case 'notifications':
      return <NotificationsPage />
    case 'display':
      return <DisplayPage />
    case 'account':
      return <AccountPage />
    case 'security':
      return <SecurityPage />
    default:
      return <ProfilePage />
  }
}
