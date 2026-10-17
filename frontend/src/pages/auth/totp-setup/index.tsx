import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import QRCode from 'qrcode'
import { toast } from 'sonner'
import { DocumentTitle } from '@/components/document-title'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/forms/input-error'
import { authApi } from '@/services/auth-api'
import { useAuthStore } from '@/lib/auth'
import { useI18n } from '@/i18n/context'

type Step = 'loading' | 'scan' | 'backup-codes'

export default function TotpSetupPage() {
  const { t } = useI18n()
  const navigate = useNavigate()
  const rehydrate = useAuthStore((s) => s.rehydrate)

  const [step, setStep] = useState<Step>('loading')
  const [secret, setSecret] = useState('')
  const [qrDataUrl, setQrDataUrl] = useState('')
  const [code, setCode] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [backupCodes, setBackupCodes] = useState<string[]>([])

  // POST /totp/setup overwrites any pending secret server-side, so it must fire
  // exactly once per mount — a ref guard (rather than the usual `cancelled` flag)
  // stops StrictMode's double-invoke from issuing two real, non-idempotent requests.
  const requested = useRef(false)
  useEffect(() => {
    if (requested.current) return
    requested.current = true
    ;(async () => {
      try {
        const setup = await authApi.setupTotp()
        setSecret(setup.secret)
        setQrDataUrl(await QRCode.toDataURL(setup.provisioningUri))
        setStep('scan')
      } catch {
        toast.error(t('settings_something_wrong_error'))
      }
    })()
  }, [t])

  const onConfirm = async (e: React.FormEvent) => {
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

  const onContinue = async () => {
    await rehydrate()
    navigate('/admin')
  }

  return (
    <>
      <DocumentTitle title={t('auth_totp_setup_page_title')} />
      <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10">
        <div className="w-full max-w-sm">
          <Card>
            <CardHeader className="text-center">
              <CardTitle className="text-xl">{t('auth_totp_setup_page_title')}</CardTitle>
              <CardDescription>
                {step === 'backup-codes'
                  ? t('auth_totp_backup_codes_description')
                  : t('auth_totp_setup_description')}
              </CardDescription>
            </CardHeader>
            <CardContent>
              {step === 'loading' && (
                <p className="text-center text-sm text-muted-foreground">{t('common_loading')}</p>
              )}

              {step === 'scan' && (
                <form onSubmit={onConfirm} className="grid gap-4">
                  {qrDataUrl && (
                    <img
                      src={qrDataUrl}
                      alt={t('auth_totp_setup_page_title')}
                      className="mx-auto h-48 w-48"
                    />
                  )}
                  <div className="space-y-1 text-center">
                    <p className="text-xs text-muted-foreground">
                      {t('auth_totp_manual_entry_label')}
                    </p>
                    <p className="break-all font-mono text-xs">{secret}</p>
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="code">{t('auth_totp_code_label')}</Label>
                    <Input
                      id="code"
                      type="text"
                      inputMode="numeric"
                      autoComplete="one-time-code"
                      autoFocus
                      value={code}
                      onChange={(e) => setCode(e.target.value)}
                    />
                    <InputError message={error ?? undefined} className="mt-2" />
                  </div>
                  <Button disabled={submitting || !code}>
                    {submitting ? t('common_saving') : t('auth_verify_button')}
                  </Button>
                </form>
              )}

              {step === 'backup-codes' && (
                <div className="grid gap-4">
                  <div className="grid grid-cols-2 gap-2 rounded-md border bg-muted/40 p-4 font-mono text-sm">
                    {backupCodes.map((c) => (
                      <span key={c}>{c}</span>
                    ))}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {t('auth_totp_backup_codes_warning')}
                  </p>
                  <Button onClick={onContinue}>{t('auth_totp_backup_codes_continue')}</Button>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </>
  )
}
