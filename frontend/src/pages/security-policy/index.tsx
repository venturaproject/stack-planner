import { useEffect, useState } from 'react'
import { toast } from 'sonner'
import { AuthenticatedLayout } from '@/layouts'
import { AccessControlTabs } from '@/pages/users/access-control-tabs'
import { Main } from '@/components/layout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Switch } from '@/components/ui/switch'
import { ShieldCheck } from 'lucide-react'
import { settingsApi } from '@/services/settings-api'
import { usePermission } from '@/hooks/use-permission'
import ForbiddenError from '@/pages/errors/forbidden'
import { useI18n } from '@/i18n/context'

function RequireTwoFactorToggle() {
  const { t } = useI18n()
  const [require2fa, setRequire2fa] = useState(false)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    settingsApi
      .security()
      .then((settings) => setRequire2fa(settings.require2fa))
      .catch(() => toast.error(t('settings_something_wrong_error')))
      .finally(() => setLoading(false))
  }, [t])

  const toggle = async (checked: boolean) => {
    setSaving(true)
    setRequire2fa(checked)
    try {
      await settingsApi.updateSecurity({ require2fa: checked })
      toast.success(t('settings_security_policy_updated_toast'))
    } catch {
      setRequire2fa(!checked)
      toast.error(t('settings_something_wrong_error'))
    } finally {
      setSaving(false)
    }
  }

  if (loading) return null

  return (
    <div className="flex max-w-xl items-center justify-between rounded-md border px-4 py-3">
      <div>
        <p className="text-sm font-medium">{t('settings_security_require_2fa_label')}</p>
        <p className="text-xs text-muted-foreground">
          {t('settings_security_require_2fa_description')}
        </p>
      </div>
      <Switch checked={require2fa} disabled={saving} onCheckedChange={toggle} />
    </div>
  )
}

export default function SecurityPolicyPage() {
  const { t } = useI18n()
  const { hasFullAccess } = usePermission()

  if (!hasFullAccess()) {
    return <ForbiddenError />
  }

  return (
    <AuthenticatedLayout title={t('security_policy_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <AccessControlTabs />
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <ShieldCheck className="h-5 w-5" />
                {t('security_policy_title')}
              </CardTitle>
              <CardDescription>{t('security_policy_page_description')}</CardDescription>
            </CardHeader>
            <CardContent>
              <RequireTwoFactorToggle />
            </CardContent>
          </Card>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
