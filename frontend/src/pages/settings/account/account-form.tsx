import { z } from 'zod'
import { format } from 'date-fns'
import { enUS } from 'date-fns/locale'
import { useForm } from 'react-hook-form'
import { CalendarIcon, CaretSortIcon, CheckIcon } from '@radix-ui/react-icons'
import { zodResolver } from '@hookform/resolvers/zod'
import { cn } from '@/lib/utils'
import { toast } from '@/hooks/use-toast'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useI18n } from '@/i18n/context'

type AccountFormValues = z.infer<ReturnType<typeof makeAccountFormSchema>>

function makeAccountFormSchema(t: (key: string) => string) {
  return z.object({
    name: z
      .string()
      .min(2, {
        message: t('settings_account_name_min_error'),
      })
      .max(30, {
        message: t('settings_account_name_max_error'),
      }),
    dob: z.date({
      required_error: t('settings_account_dob_required_error'),
    }),
    language: z.string({
      required_error: t('settings_account_language_required_error'),
    }),
  })
}

export function AccountForm() {
  const { t } = useI18n()

  const languages = [
    { label: t('settings_account_language_english'), value: 'en' },
    { label: t('settings_account_language_french'), value: 'fr' },
    { label: t('settings_account_language_german'), value: 'de' },
    { label: t('settings_account_language_spanish'), value: 'es' },
    { label: t('settings_account_language_portuguese'), value: 'pt' },
    { label: t('settings_account_language_russian'), value: 'ru' },
    { label: t('settings_account_language_japanese'), value: 'ja' },
    { label: t('settings_account_language_korean'), value: 'ko' },
    { label: t('settings_account_language_chinese'), value: 'zh' },
  ] as const

  // This can come from your database or API.
  const defaultValues: Partial<AccountFormValues> = {
    name: '',
  }

  const form = useForm<AccountFormValues>({
    resolver: zodResolver(makeAccountFormSchema(t)),
    defaultValues,
  })

  function onSubmit(data: AccountFormValues) {
    toast({
      title: t('settings_account_submitted_title'),
      description: (
        <pre className="mt-2 w-[340px] rounded-md bg-slate-950 p-4">
          <code className="text-white">{JSON.stringify(data, null, 2)}</code>
        </pre>
      ),
    })
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>{t('settings_account_name_label')}</FormLabel>
              <FormControl>
                <Input placeholder={t('settings_account_name_placeholder')} {...field} />
              </FormControl>
              <FormDescription>{t('settings_account_name_description')}</FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="dob"
          render={({ field }) => (
            <FormItem className="flex flex-col">
              <FormLabel>{t('settings_account_dob_label')}</FormLabel>
              <Popover>
                <PopoverTrigger asChild>
                  <FormControl>
                    <Button
                      variant={'outline'}
                      className={cn(
                        'w-[240px] pl-3 text-left font-normal',
                        !field.value && 'text-muted-foreground',
                      )}
                    >
                      {field.value ? (
                        format(field.value, 'PPP', { locale: enUS })
                      ) : (
                        <span>{t('settings_account_pick_date')}</span>
                      )}
                      <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                    </Button>
                  </FormControl>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                  <Calendar
                    mode="single"
                    selected={field.value}
                    onSelect={field.onChange}
                    locale={enUS}
                    disabled={(date: Date) => date > new Date() || date < new Date('1900-01-01')}
                  />
                </PopoverContent>
              </Popover>
              <FormDescription>{t('settings_account_dob_description')}</FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="language"
          render={({ field }) => (
            <FormItem className="flex flex-col">
              <FormLabel>{t('settings_account_language_label')}</FormLabel>
              <Popover>
                <PopoverTrigger asChild>
                  <FormControl>
                    <Button
                      variant="outline"
                      role="combobox"
                      className={cn(
                        'w-[200px] justify-between',
                        !field.value && 'text-muted-foreground',
                      )}
                    >
                      {field.value
                        ? languages.find((language) => language.value === field.value)?.label
                        : t('settings_account_select_language_placeholder')}
                      <CaretSortIcon className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                  </FormControl>
                </PopoverTrigger>
                <PopoverContent className="w-[200px] p-0">
                  <Command>
                    <CommandInput placeholder={t('settings_account_search_language_placeholder')} />
                    <CommandEmpty>{t('settings_account_no_language_found')}</CommandEmpty>
                    <CommandGroup>
                      <CommandList>
                        {languages.map((language) => (
                          <CommandItem
                            value={language.label}
                            key={language.value}
                            onSelect={() => {
                              form.setValue('language', language.value)
                            }}
                          >
                            <CheckIcon
                              className={cn(
                                'mr-2 h-4 w-4',
                                language.value === field.value ? 'opacity-100' : 'opacity-0',
                              )}
                            />
                            {language.label}
                          </CommandItem>
                        ))}
                      </CommandList>
                    </CommandGroup>
                  </Command>
                </PopoverContent>
              </Popover>
              <FormDescription>{t('settings_account_language_used_description')}</FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type="submit">{t('settings_account_update_button')}</Button>
      </form>
    </Form>
  )
}
