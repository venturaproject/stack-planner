import { SettingLayout } from '@/layouts'
import ContentSection from '../components/content-section'
import { DisplayForm } from './display-form'
import { useI18n } from '@/i18n/context'

export default function SettingsDisplay() {
  const { t } = useI18n()
  return (
    <SettingLayout title={t('settings_display_page_title')}>
      <ContentSection title={t('settings_nav_display')} desc={t('settings_display_section_desc')}>
        <DisplayForm />
      </ContentSection>
    </SettingLayout>
  )
}
