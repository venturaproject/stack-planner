import { SettingLayout } from '@/layouts'
import ContentSection from '../components/content-section'
import { AccountForm } from './account-form'
import { useI18n } from '@/i18n/context'

export default function SettingsAccount() {
  const { t } = useI18n()
  return (
    <>
      <SettingLayout title={t('settings_account_page_title')}>
        <ContentSection
          title={t('settings_account_page_title')}
          desc={t('settings_account_section_desc')}
        >
          <AccountForm />
        </ContentSection>
      </SettingLayout>
    </>
  )
}
