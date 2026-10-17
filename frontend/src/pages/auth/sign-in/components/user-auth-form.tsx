import { HTMLAttributes, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { PasswordInput } from '@/components/forms/password-input'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import InputError from '@/components/forms/input-error'
import { getAuthAdapter, TotpRequiredError } from '@/lib/auth-adapter'
import { useAuthStore } from '@/lib/auth'
import { useI18n } from '@/i18n/context'

function makeSchema(t: (key: string) => string) {
  return z.object({
    login: z.string().min(1, t('auth_login_required_error')),
    password: z.string().min(1, t('auth_password_required_error')),
    remember: z.boolean(),
  })
}
type FormData = z.infer<ReturnType<typeof makeSchema>>

type UserAuthFormProps = HTMLAttributes<HTMLDivElement> & {
  status?: string
  canResetPassword?: boolean
}

export function UserAuthForm({
  className,
  status,
  canResetPassword = true,
  ...props
}: UserAuthFormProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const rehydrate = useAuthStore((s) => s.rehydrate)

  // Set once the password step succeeds but the account has TOTP enabled — the
  // form then switches to asking for a code/backup code without re-prompting
  // for the password (credentials are re-submitted from `pendingCredentials`).
  const [pendingCredentials, setPendingCredentials] = useState<FormData | null>(null)
  const [totpCode, setTotpCode] = useState('')
  const [useBackupCode, setUseBackupCode] = useState(false)
  const [totpError, setTotpError] = useState<string | null>(null)
  const [totpSubmitting, setTotpSubmitting] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
    watch,
    setValue,
  } = useForm<FormData>({
    resolver: zodResolver(makeSchema(t)),
    defaultValues: { login: '', password: '', remember: false },
  })

  const afterLogin = async () => {
    await rehydrate()
    const { mustSetupTotp } = useAuthStore.getState()
    navigate(mustSetupTotp ? '/totp-setup' : '/admin')
  }

  const onSubmit = async (data: FormData) => {
    try {
      await getAuthAdapter().login(data)
      await afterLogin()
    } catch (err: unknown) {
      if (err instanceof TotpRequiredError) {
        setPendingCredentials(data)
        return
      }
      const e = err as { response?: { data?: { message?: string } } }
      const message = e.response?.data?.message ?? t('auth_incorrect_credentials_error')
      setError('login', { message })
    }
  }

  const onSubmitTotp = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!pendingCredentials) return
    setTotpSubmitting(true)
    setTotpError(null)
    try {
      await getAuthAdapter().login({
        ...pendingCredentials,
        ...(useBackupCode ? { backupCode: totpCode } : { totpCode }),
      })
      await afterLogin()
    } catch {
      setTotpError(t('auth_incorrect_credentials_error'))
    } finally {
      setTotpSubmitting(false)
    }
  }

  if (pendingCredentials) {
    return (
      <div className={cn('grid gap-6', className)} {...props}>
        <form onSubmit={onSubmitTotp} noValidate>
          <div className="grid gap-2">
            <div className="space-y-1">
              <Label htmlFor="totpCode">
                {useBackupCode ? t('auth_totp_backup_code_label') : t('auth_totp_code_label')}
              </Label>
              <Input
                id="totpCode"
                type="text"
                inputMode="numeric"
                autoComplete="one-time-code"
                autoFocus
                value={totpCode}
                onChange={(e) => setTotpCode(e.target.value)}
              />
              <InputError message={totpError ?? undefined} className="mt-2" />
            </div>

            <Button className="mt-4" disabled={totpSubmitting || !totpCode}>
              {totpSubmitting ? t('auth_signing_in') : t('auth_verify_button')}
            </Button>

            <button
              type="button"
              className="text-sm text-muted-foreground underline-offset-4 hover:underline"
              onClick={() => {
                setUseBackupCode((v) => !v)
                setTotpCode('')
                setTotpError(null)
              }}
            >
              {useBackupCode ? t('auth_use_totp_code_instead') : t('auth_use_backup_code_instead')}
            </button>
          </div>
        </form>
      </div>
    )
  }

  return (
    <div className={cn('grid gap-6', className)} {...props}>
      {status && <div className="text-sm font-medium text-green-600">{status}</div>}

      <form onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className="grid gap-2">
          <div className="space-y-1">
            <Label htmlFor="login">{t('auth_login_field_label')}</Label>
            <Input
              id="login"
              type="text"
              placeholder={t('auth_login_field_placeholder')}
              autoComplete="username"
              {...register('login')}
            />
            <InputError message={errors.login?.message} className="mt-2" />
          </div>

          <div className="space-y-1">
            <div className="flex items-center justify-between">
              <Label htmlFor="password">{t('user_field_password')}</Label>
              {canResetPassword && (
                <Link
                  to="/forgot-password"
                  className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >
                  {t('auth_forgot_password_link')}
                </Link>
              )}
            </div>
            <PasswordInput
              id="password"
              placeholder="********"
              autoComplete="current-password"
              {...register('password')}
            />
            <InputError message={errors.password?.message} className="mt-2" />
          </div>

          <div className="flex flex-row items-center space-x-2 space-y-0 mt-2">
            <Checkbox
              id="remember"
              checked={watch('remember')}
              onCheckedChange={(checked) => setValue('remember', !!checked)}
            />
            <label htmlFor="remember" className="text-sm font-normal text-muted-foreground">
              {t('auth_remember_me')}
            </label>
          </div>

          <Button className="mt-4" disabled={isSubmitting}>
            {isSubmitting ? t('auth_signing_in') : t('auth_sign_in_button')}
          </Button>
        </div>
      </form>
    </div>
  )
}
