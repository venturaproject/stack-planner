import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
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
import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { toast } from 'sonner'
import { useI18n } from '@/i18n/context'

function makeDisplayFormSchema(t: (key: string) => string) {
  return z.object({
    items: z.array(z.string()).refine((value) => value.some((item) => item), {
      message: t('settings_display_min_item_error'),
    }),
  })
}

type DisplayFormValues = z.infer<ReturnType<typeof makeDisplayFormSchema>>

export function DisplayForm() {
  const { t } = useI18n()

  const items = [
    { id: 'recents', label: t('settings_display_item_recents') },
    { id: 'home', label: t('settings_display_item_home') },
    { id: 'applications', label: t('settings_display_item_applications') },
    { id: 'desktop', label: t('settings_display_item_desktop') },
    { id: 'downloads', label: t('settings_display_item_downloads') },
    { id: 'documents', label: t('settings_display_item_documents') },
  ] as const

  const form = useForm<DisplayFormValues>({
    resolver: zodResolver(makeDisplayFormSchema(t)),
    defaultValues: {
      items: ['recents', 'home'],
    },
  })

  async function onSubmit(data: DisplayFormValues) {
    try {
      await axios.put(endpoints.settings.display, data)
      toast.success(t('settings_display_saved_success'))
    } catch {
      toast.error(t('settings_something_wrong_error'))
    }
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
        <FormField
          control={form.control}
          name="items"
          render={() => (
            <FormItem>
              <div className="mb-4">
                <FormLabel className="text-base">{t('settings_display_sidebar_label')}</FormLabel>
                <FormDescription>{t('settings_display_sidebar_description')}</FormDescription>
              </div>
              {items.map((item) => (
                <FormField
                  key={item.id}
                  control={form.control}
                  name="items"
                  render={({ field }) => (
                    <FormItem
                      key={item.id}
                      className="flex flex-row items-start space-x-3 space-y-0"
                    >
                      <FormControl>
                        <Checkbox
                          checked={field.value?.includes(item.id)}
                          onCheckedChange={(checked) => {
                            return checked
                              ? field.onChange([...field.value, item.id])
                              : field.onChange(field.value?.filter((value) => value !== item.id))
                          }}
                        />
                      </FormControl>
                      <FormLabel className="font-normal">{item.label}</FormLabel>
                    </FormItem>
                  )}
                />
              ))}
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type="submit">{t('settings_display_update_button')}</Button>
      </form>
    </Form>
  )
}
