import { useEffect, useState } from 'react'
import QRCode from 'qrcode'
import { toast } from 'sonner'
import { useAuthStore } from '@/lib/auth'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { PasswordInput } from '@/components/forms/password-input'
import InputError from '@/components/forms/input-error'
import { useI18n } from '@/i18n/context'
import { authApi } from '@/services/auth-api'

type SetupStep = 'idle' | 'scan' | 'backup-codes'

function TwoFactorSelfService() {
  const { t } = useI18n()
  const user = useAuthStore((s) => s.user)
  const rehydrate = useAuthStore((s) => s.rehydrate)

  const [enabled, setEnabled] = useState(false)
  const [checking, setChecking] = useState(true)
  const [step, setStep] = useState<SetupStep>('idle')
  const [secret, setSecret] = useState('')
  const [qrDataUrl, setQrDataUrl] = useState('')
  const [code, setCode] = useState('')
  const [backupCodes, setBackupCodes] = useState<string[]>([])
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [disabling, setDisabling] = useState(false)

  useEffect(() => {
    setEnabled(Boolean(user?.totpEnabled))
    setChecking(false)
  }, [user])

  const startSetup = async () => {
    setError(null)
    try {
      const setup = await authApi.setupTotp()
      setSecret(setup.secret)
      setQrDataUrl(await QRCode.toDataURL(setup.provisioningUri))
      setStep('scan')
    } catch {
      toast.error(t('settings_something_wrong_error'))
    }
  }

  const confirmSetup = async (e: React.FormEvent) => {
    e.preventDefault()
    setSubmitting(true)
    setError(null)
    try {
      const confirmation = await authApi.confirmTotp(code)
      setBackupCodes(confirmation.backupCodes)
      setStep('backup-codes')
    } catch {
      setError(t('auth_totp_invalid_code_error'))
    } finally {
      setSubmitting(false)
    }
  }

  const finishSetup = async () => {
    setEnabled(true)
    setStep('idle')
    setCode('')
    await rehydrate()
    toast.success(t('settings_security_totp_enabled_toast'))
  }

  const disableTotp = async (e: React.FormEvent) => {
    e.preventDefault()
    setDisabling(true)
    setError(null)
    try {
      await authApi.disableTotp(password)
      setEnabled(false)
      setPassword('')
      await rehydrate()
      toast.success(t('settings_security_totp_disabled_toast'))
    } catch (err: unknown) {
      const e2 = err as { response?: { data?: { message?: string } } }
      setError(e2.response?.data?.message ?? t('settings_something_wrong_error'))
    } finally {
      setDisabling(false)
    }
  }

  if (checking) return null

  if (step === 'scan') {
    return (
      <form onSubmit={confirmSetup} className="grid max-w-xs gap-4">
        {qrDataUrl && <img src={qrDataUrl} alt="" className="mx-auto h-40 w-40" />}
        <p className="break-all text-center font-mono text-xs text-muted-foreground">{secret}</p>
        <div className="space-y-1">
          <Label htmlFor="setupCode">{t('auth_totp_code_label')}</Label>
          <Input
            id="setupCode"
            value={code}
            onChange={(e) => setCode(e.target.value)}
            inputMode="numeric"
            autoFocus
          />
          <InputError message={error ?? undefined} className="mt-2" />
        </div>
        <div className="flex gap-2">
          <Button type="submit" disabled={submitting || !code}>
            {submitting ? t('common_saving') : t('auth_verify_button')}
          </Button>
          <Button type="button" variant="outline" onClick={() => setStep('idle')}>
            {t('common_cancel')}
          </Button>
        </div>
      </form>
    )
  }

  if (step === 'backup-codes') {
    return (
      <div className="grid max-w-xs gap-4">
        <div className="grid grid-cols-2 gap-2 rounded-md border bg-muted/40 p-4 font-mono text-sm">
          {backupCodes.map((c) => (
            <span key={c}>{c}</span>
          ))}
        </div>
        <p className="text-xs text-muted-foreground">{t('auth_totp_backup_codes_warning')}</p>
        <Button onClick={finishSetup}>{t('auth_totp_backup_codes_continue')}</Button>
      </div>
    )
  }

  return (
    <div className="max-w-sm space-y-4">
      <div className="flex items-center justify-between rounded-md border px-4 py-3">
        <div>
          <p className="text-sm font-medium">{t('settings_security_totp_label')}</p>
          <p className="text-xs text-muted-foreground">
            {enabled
              ? t('settings_security_totp_status_enabled')
              : t('settings_security_totp_status_disabled')}
          </p>
        </div>
        <Badge variant={enabled ? 'default' : 'secondary'}>
          {enabled ? t('common_enabled') : t('common_disabled')}
        </Badge>
      </div>

      {!enabled && (
        <Button onClick={startSetup}>{t('settings_security_enable_totp_button')}</Button>
      )}

      {enabled && (
        <form onSubmit={disableTotp} className="space-y-2">
          <Label htmlFor="disablePassword">
            {t('settings_security_disable_totp_password_label')}
          </Label>
          <PasswordInput
            id="disablePassword"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
          <InputError message={error ?? undefined} />
          <Button type="submit" variant="destructive" disabled={disabling || !password}>
            {disabling ? t('common_saving') : t('settings_security_disable_totp_button')}
          </Button>
        </form>
      )}
    </div>
  )
}

export function SecurityForm() {
  const { t } = useI18n()

  return (
    <div className="space-y-4">
      <h4 className="text-sm font-medium">{t('settings_security_totp_section_title')}</h4>
      <TwoFactorSelfService />
    </div>
  )
}
