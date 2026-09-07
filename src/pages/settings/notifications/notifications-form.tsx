import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Switch } from '@/components/ui/switch'
import { toast } from 'sonner'
import { useState } from 'react'
import { useI18n } from '@/i18n/context'

const schema = z.object({
  type: z.enum(['all', 'mentions', 'none']),
  communication_emails: z.boolean(),
  security_emails: z.boolean(),
  mobile_notifications: z.boolean(),
})

type FormValues = z.infer<typeof schema>

export function NotificationsForm() {
  const { t } = useI18n()
  const [saving, setSaving] = useState(false)

  const form = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      type: 'all',
      communication_emails: false,
      security_emails: true,
      mobile_notifications: false,
    },
  })

  async function onSubmit(data: FormValues) {
    setSaving(true)
    try {
      await axios.put(endpoints.settings.notifications, data)
      toast.success(t('settings_notifications_saved_success'))
    } catch {
      toast.error(t('settings_something_wrong_error'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
        {/* Notification type */}
        <FormField
          control={form.control}
          name="type"
          render={({ field }) => (
            <FormItem className="space-y-3">
              <FormLabel>{t('settings_notifications_type_label')}</FormLabel>
              <FormControl>
                <RadioGroup
                  onValueChange={field.onChange}
                  value={field.value}
                  className="flex flex-col space-y-1"
                >
                  <FormItem className="flex items-center space-x-3 space-y-0">
                    <FormControl>
                      <RadioGroupItem value="all" />
                    </FormControl>
                    <FormLabel className="font-normal">
                      {t('settings_notifications_type_all')}
                    </FormLabel>
                  </FormItem>
                  <FormItem className="flex items-center space-x-3 space-y-0">
                    <FormControl>
                      <RadioGroupItem value="mentions" />
                    </FormControl>
                    <FormLabel className="font-normal">
                      {t('settings_notifications_type_mentions')}
                    </FormLabel>
                  </FormItem>
                  <FormItem className="flex items-center space-x-3 space-y-0">
                    <FormControl>
                      <RadioGroupItem value="none" />
                    </FormControl>
                    <FormLabel className="font-normal">
                      {t('settings_notifications_type_none')}
                    </FormLabel>
                  </FormItem>
                </RadioGroup>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {/* Email notifications */}
        <div>
          <h3 className="mb-4 text-lg font-medium">
            {t('settings_notifications_email_section_title')}
          </h3>
          <div className="space-y-4">
            <FormField
              control={form.control}
              name="communication_emails"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">
                      {t('settings_notifications_communication_emails_label')}
                    </FormLabel>
                    <FormDescription>
                      {t('settings_notifications_communication_emails_desc')}
                    </FormDescription>
                  </div>
                  <FormControl>
                    <Switch checked={field.value} onCheckedChange={field.onChange} />
                  </FormControl>
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="security_emails"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center justify-between rounded-lg border p-4">
                  <div className="space-y-0.5">
                    <FormLabel className="text-base">
                      {t('settings_notifications_security_emails_label')}
                    </FormLabel>
                    <FormDescription>
                      {t('settings_notifications_security_emails_desc')}
                    </FormDescription>
                  </div>
                  <FormControl>
                    <Switch checked={field.value} onCheckedChange={field.onChange} />
                  </FormControl>
                </FormItem>
              )}
            />
          </div>
        </div>

        {/* Mobile notifications */}
        <FormField
          control={form.control}
          name="mobile_notifications"
          render={({ field }) => (
            <FormItem className="flex flex-row items-start space-x-3 space-y-0">
              <FormControl>
                <Checkbox checked={field.value} onCheckedChange={field.onChange} />
              </FormControl>
              <div className="space-y-1 leading-none">
                <FormLabel>{t('settings_notifications_mobile_label')}</FormLabel>
                <FormDescription>{t('settings_notifications_mobile_desc')}</FormDescription>
              </div>
            </FormItem>
          )}
        />

        <Button type="submit" disabled={saving}>
          {saving ? t('common_saving') : t('settings_notifications_update_button')}
        </Button>
      </form>
    </Form>
  )
}
