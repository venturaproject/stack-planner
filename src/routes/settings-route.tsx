import { useParams } from 'react-router-dom'

// Settings pages
import ProfilePage from '@/pages/settings/profile'
import AppearancePage from '@/pages/settings/appearance'
import NotificationsPage from '@/pages/settings/notifications'
import DisplayPage from '@/pages/settings/display'
import AccountPage from '@/pages/settings/account'
import SecurityPage from '@/pages/settings/security'
import PermissionsPage from '@/pages/settings/permissions'

export default function SettingsRoute() {
  const { section } = useParams()

  switch (section) {
    case 'permissions':
      return <PermissionsPage />
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
