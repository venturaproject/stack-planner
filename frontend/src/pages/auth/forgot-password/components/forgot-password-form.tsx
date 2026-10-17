import { HTMLAttributes, useState } from 'react'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
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
import { Input } from '@/components/ui/input'
import { useI18n } from '@/i18n/context'
import { getAuthAdapter } from '@/lib/auth-adapter'

type ForgotFormProps = HTMLAttributes<HTMLDivElement>

function makeFormSchema(t: (key: string) => string) {
  return z.object({
    email: z
      .string()
      .min(1, { message: t('auth_email_required_error') })
      .email({ message: t('auth_email_invalid_error') }),
  })
}

export function ForgotForm({ className, ...props }: ForgotFormProps) {
  const { t } = useI18n()
  const [isLoading, setIsLoading] = useState(false)
  // The backend responds identically whether or not the email exists (see
  // RequestPasswordResetHandler) — this stays true once submitted regardless of
  // outcome, on purpose: showing anything else would leak which emails are
  // registered.
  const [submitted, setSubmitted] = useState(false)
  const formSchema = makeFormSchema(t)

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: { email: '' },
  })

  async function onSubmit(data: z.infer<typeof formSchema>) {
    setIsLoading(true)
    try {
      await getAuthAdapter().forgotPassword(data.email)
    } finally {
      setIsLoading(false)
      setSubmitted(true)
    }
  }

  if (submitted) {
    return (
      <div className={cn('grid gap-4', className)} {...props}>
        <p className="text-sm text-muted-foreground">{t('auth_forgot_password_success')}</p>
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
              name="email"
              render={({ field }) => (
                <FormItem className="space-y-1">
                  <FormLabel>{t('user_field_email')}</FormLabel>
                  <FormControl>
                    <Input placeholder={t('user_field_email_placeholder')} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <Button className="mt-2" disabled={isLoading}>
              {t('auth_continue_button')}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}
