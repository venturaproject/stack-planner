import { SettingLayout } from '@/layouts'
import ContentSection from '../components/content-section'
import { NotificationsForm } from './notifications-form'
import { useI18n } from '@/i18n/context'

export default function SettingsNotifications() {
  const { t } = useI18n()
  return (
    <SettingLayout title={t('settings_notifications_page_title')}>
      <ContentSection
        title={t('settings_nav_notifications')}
        desc={t('settings_notifications_section_desc')}
      >
        <NotificationsForm />
      </ContentSection>
    </SettingLayout>
  )
}
