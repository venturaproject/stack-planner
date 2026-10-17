import { Card } from '@/components/ui/card'
import AuthLayout from '../auth-layout'
import { ResetPasswordForm } from './components/reset-password-form'
import { useI18n } from '@/i18n/context'

export default function ResetPassword() {
  const { t } = useI18n()
  return (
    <AuthLayout title={t('auth_reset_password_title')}>
      <Card className="p-6">
        <div className="mb-2 flex flex-col space-y-2 text-left">
          <h1 className="text-md font-semibold tracking-tight">{t('auth_reset_password_title')}</h1>
          <p className="text-sm text-muted-foreground">{t('auth_reset_password_description')}</p>
        </div>
        <ResetPasswordForm />
      </Card>
    </AuthLayout>
  )
}
