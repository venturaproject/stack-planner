import { Link } from 'react-router-dom'
import { Card } from '@/components/ui/card'
import AuthLayout from '../auth-layout'
import { ForgotForm } from './components/forgot-password-form'
import { useI18n } from '@/i18n/context'

export default function ForgotPassword() {
  const { t } = useI18n()
  return (
    <AuthLayout title={t('auth_forgot_password_title')}>
      <Card className="p-6">
        <div className="mb-2 flex flex-col space-y-2 text-left">
          <h1 className="text-md font-semibold tracking-tight">
            {t('auth_forgot_password_title')}
          </h1>
          <p className="text-sm text-muted-foreground">
            {t('auth_forgot_password_description_line1')} <br />{' '}
            {t('auth_forgot_password_description_line2')}
          </p>
        </div>
        <ForgotForm />
        <p className="mt-4 px-8 text-center text-sm text-muted-foreground">
          {t('auth_no_account_question')}{' '}
          <Link to="/login" className="underline underline-offset-4 hover:text-primary">
            {t('auth_back_to_login')}
          </Link>
          .
        </p>
      </Card>
    </AuthLayout>
  )
}
