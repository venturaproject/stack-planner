import { SettingLayout } from '@/layouts'
import ContentSection from '../components/content-section'
import { AppearanceForm } from './appearance-form'
import { useI18n } from '@/i18n/context'

export default function SettingsAppearance() {
  const { t } = useI18n()
  return (
    <SettingLayout title={t('settings_appearance_page_title')}>
      <ContentSection
        title={t('settings_nav_appearance')}
        desc={t('settings_appearance_section_desc')}
      >
        <AppearanceForm />
      </ContentSection>
    </SettingLayout>
  )
}
