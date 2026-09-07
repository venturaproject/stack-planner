import { SettingLayout } from '@/layouts'
import ContentSection from '../components/content-section'
import { SecurityForm } from './security-form'
import { useI18n } from '@/i18n/context'

export default function SettingsSecurity() {
  const { t } = useI18n()
  return (
    <SettingLayout title={t('settings_security_page_title')}>
      <ContentSection title={t('settings_nav_security')} desc={t('settings_security_section_desc')}>
        <SecurityForm />
      </ContentSection>
    </SettingLayout>
  )
}
