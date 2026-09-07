import { HTMLAttributes, useState } from 'react'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link, useSearchParams } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { PasswordInput } from '@/components/forms/password-input'
import { useI18n } from '@/i18n/context'
import { getAuthAdapter } from '@/lib/auth-adapter'

type ResetPasswordFormProps = HTMLAttributes<HTMLDivElement>

function makeFormSchema(t: (key: string) => string) {
  return z
    .object({
      password: z.string().min(8, { message: t('auth_password_min_length_error') }),
      passwordConfirmation: z.string(),
    })
    .refine((data) => data.password === data.passwordConfirmation, {
      message: t('auth_passwords_dont_match_error'),
      path: ['passwordConfirmation'],
    })
}

export function ResetPasswordForm({ className, ...props }: ResetPasswordFormProps) {
  const { t } = useI18n()
  const [searchParams] = useSearchParams()
  const [isLoading, setIsLoading] = useState(false)
  const [succeeded, setSucceeded] = useState(false)
  const [serverError, setServerError] = useState<string | null>(null)
  const formSchema = makeFormSchema(t)

  const user = searchParams.get('user')
  const version = searchParams.get('version')
  const expires = searchParams.get('expires')
  const signature = searchParams.get('signature')
  // All four are required for the link to even be attemptable — a missing one
  // means this wasn't opened from a genuine emailed link, so skip straight to
  // the invalid-link state rather than letting the form submit and 400.
  const linkIsWellFormed = Boolean(user && version && expires && signature)

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: { password: '', passwordConfirmation: '' },
  })

  async function onSubmit(data: z.infer<typeof formSchema>) {
    if (!user || !version || !expires || !signature) return
    setIsLoading(true)
    setServerError(null)
    try {
      await getAuthAdapter().resetPassword({
        user,
        version: Number(version),
        expires: Number(expires),
        signature,
        password: data.password,
      })
      setSucceeded(true)
    } catch {
      setServerError(t('auth_reset_password_invalid_link'))
    } finally {
      setIsLoading(false)
    }
  }

  if (!linkIsWellFormed || serverError) {
    return (
      <div className={cn('grid gap-4', className)} {...props}>
        <p className="text-sm text-destructive">{serverError ?? t('auth_reset_password_invalid_link')}</p>
        <Link to="/forgot-password" className="text-sm underline underline-offset-4 hover:text-primary">
          {t('auth_forgot_password_title')}
        </Link>
      </div>
    )
  }

  if (succeeded) {
    return (
      <div className={cn('grid gap-4', className)} {...props}>
        <p className="text-sm text-muted-foreground">{t('auth_reset_password_success')}</p>
        <Link to="/login" className="text-sm underline underline-offset-4 hover:text-primary">
          {t('auth_go_to_login')}
        </Link>
      </div>
    )
  }

  return (
    <div className={cn('grid gap-6', className)} {...props}>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)}>
          <div className="grid gap-2">
            <FormField
              control={form.control}
              name="password"
              render={({ field }) => (
                <FormItem className="space-y-1">
                  <FormLabel>{t('auth_new_password_label')}</FormLabel>
                  <FormControl>
                    <PasswordInput autoComplete="new-password" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="passwordConfirmation"
              render={({ field }) => (
                <FormItem className="space-y-1">
                  <FormLabel>{t('auth_confirm_password_label')}</FormLabel>
                  <FormControl>
                    <PasswordInput autoComplete="new-password" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <Button className="mt-2" disabled={isLoading}>
              {t('auth_reset_password_button')}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}
