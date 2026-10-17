import { AppSidebar } from '@/components/layout/app-sidebar'
import { Header } from '@/components/layout/header'
import { ImpersonationBanner } from '@/components/impersonation-banner'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import NotificationButton from '@/components/notification/notification-button'

import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar'
import { ThemeSwitch } from '@/components/theme-switch'
import { useEffect, type ReactNode } from 'react'
import { useAuthStore } from '@/lib/auth'
import { useI18n } from '@/i18n/context'

const appName = import.meta.env.VITE_APP_NAME || 'Shadcn Laravel Admin'

interface AuthenticatedLayoutProps {
  children: ReactNode
  title?: string
  showHeader?: boolean
}

export function AuthenticatedLayout({
  children,
  title,
  showHeader = true,
}: AuthenticatedLayoutProps) {
  const preferredLanguage = useAuthStore((s) => s.user?.preferredLanguage)
  const { currentLang, changeLanguage } = useI18n()

  useEffect(() => {
    document.title = title ? `${title} - ${appName}` : appName
  }, [title])

  // Apply the user's saved language preference once their session loads,
  // overriding whatever locale the browser previously had via localStorage.
  useEffect(() => {
    if (preferredLanguage && preferredLanguage !== currentLang) {
      changeLanguage(preferredLanguage)
    }
  }, [preferredLanguage, currentLang, changeLanguage])

  return (
    <>
      <SidebarProvider>
        <AppSidebar />
        <SidebarInset>
          <ImpersonationBanner />
          {showHeader && (
            <Header>
              <div className="ml-auto flex items-center space-x-2 sm:space-x-4">
                <Search />
                <NotificationButton />
                <ThemeSwitch />
                <ProfileDropdown />
              </div>
            </Header>
          )}

          {children}
        </SidebarInset>
      </SidebarProvider>
    </>
  )
}
