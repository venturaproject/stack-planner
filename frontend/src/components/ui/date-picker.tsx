import * as React from 'react'
import { format, parseISO } from 'date-fns'
import { enUS, es } from 'date-fns/locale'
import { CalendarIcon, X } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useI18n } from '@/i18n/context'

interface DatePickerProps {
  value?: string | null
  onChange: (value: string | undefined) => void
  placeholder?: string
  className?: string
  disabled?: boolean
  clearable?: boolean
}

export function DatePicker({
  value,
  onChange,
  placeholder,
  className,
  disabled = false,
  clearable = true,
}: DatePickerProps) {
  const { t, currentLang } = useI18n()
  const dateLocale = currentLang === 'es' ? es : enUS
  const [open, setOpen] = React.useState(false)
  const selected = React.useMemo(() => {
    if (!value) return undefined
    try {
      return parseISO(value)
    } catch {
      return undefined
    }
  }, [value])

  function handleSelect(date: Date | undefined) {
    onChange(date ? format(date, 'yyyy-MM-dd') : undefined)
    setOpen(false)
  }

  function handleClear(event: React.MouseEvent) {
    event.stopPropagation()
    onChange(undefined)
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          type="button"
          variant="outline"
          disabled={disabled}
          className={cn(
            'w-full justify-start text-left font-normal',
            !selected && 'text-muted-foreground',
            className,
          )}
        >
          <CalendarIcon className="mr-2 size-4 shrink-0" />
          <span className="flex-1 truncate">
            {selected
              ? format(selected, 'dd MMM yyyy', { locale: dateLocale })
              : (placeholder ?? t('common_select_date'))}
          </span>
          {clearable && selected && (
            <X
              className="ml-1 size-3.5 shrink-0 opacity-50 hover:opacity-100"
              aria-label={t('common_clear_date')}
              onClick={handleClear}
            />
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align="start">
        <Calendar
          mode="single"
          locale={dateLocale}
          selected={selected}
          onSelect={handleSelect}
          captionLayout="dropdown"
          defaultMonth={selected ?? new Date()}
          initialFocus
        />
      </PopoverContent>
    </Popover>
  )
}
