import { Link } from 'react-router-dom'
import { useI18n } from '@/i18n/context'

export function TermPrivacyLink({ termLink = '', privacyLink = '' }) {
  const { t } = useI18n()
  return (
    <>
      <p className="mt-4 px-8 text-center text-sm text-muted-foreground">
        {t('auth_terms_agreement_prefix')}{' '}
        <Link to={termLink} className="underline underline-offset-4 hover:text-primary">
          {t('auth_terms_of_service')}
        </Link>{' '}
        {t('common_and')}{' '}
        <Link to={privacyLink} className="underline underline-offset-4 hover:text-primary">
          {t('auth_privacy_policy')}
        </Link>
        .
      </p>
    </>
  )
}
