import {
  format,
  subDays,
  startOfWeek,
  endOfWeek,
  startOfMonth,
  endOfMonth,
  subWeeks,
  subMonths,
} from 'date-fns'
import { es, enUS } from 'date-fns/locale'
import type { DateRange } from 'react-day-picker'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { useI18n } from '@/i18n/context'

interface DataTableDateFilterSectionProps {
  label: string
  range: DateRange | undefined
  onSelect: (range: DateRange | undefined) => void
}

export function DataTableDateFilterSection({
  label,
  range,
  onSelect,
}: DataTableDateFilterSectionProps) {
  const { t, currentLang } = useI18n()
  const dfnsLocale = currentLang === 'en' ? enUS : es

  const presets = [
    { label: t('date_preset_today'), from: new Date(), to: new Date() },
    { label: t('date_preset_yesterday'), from: subDays(new Date(), 1), to: subDays(new Date(), 1) },
    { label: t('date_preset_last_7'), from: subDays(new Date(), 6), to: new Date() },
    { label: t('date_preset_last_30'), from: subDays(new Date(), 29), to: new Date() },
    {
      label: t('date_preset_this_week'),
      from: startOfWeek(new Date(), { locale: dfnsLocale }),
      to: endOfWeek(new Date(), { locale: dfnsLocale }),
    },
    {
      label: t('date_preset_last_week'),
      from: startOfWeek(subWeeks(new Date(), 1), { locale: dfnsLocale }),
      to: endOfWeek(subWeeks(new Date(), 1), { locale: dfnsLocale }),
    },
    {
      label: t('date_preset_this_month'),
      from: startOfMonth(new Date()),
      to: endOfMonth(new Date()),
    },
    {
      label: t('date_preset_last_month'),
      from: startOfMonth(subMonths(new Date(), 1)),
      to: endOfMonth(subMonths(new Date(), 1)),
    },
  ]

  return (
    <div className="p-3">
      <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
        {label}
      </p>
      <div className="flex flex-wrap gap-1 mb-2">
        {presets.map(({ label: presetLabel, from, to }) => (
          <Button
            key={presetLabel}
            variant="outline"
            size="sm"
            className="h-6 px-2 text-xs"
            onClick={() => onSelect({ from, to })}
          >
            {presetLabel}
          </Button>
        ))}
      </div>
      <Calendar
        mode="range"
        selected={range}
        onSelect={onSelect}
        locale={dfnsLocale}
        numberOfMonths={2}
        className="rounded-md border p-0"
      />
      {range?.from && (
        <div className="flex items-center justify-between mt-2">
          <span className="text-xs text-muted-foreground">
            {range.to
              ? `${format(range.from, 'dd/MM/yy')} – ${format(range.to, 'dd/MM/yy')}`
              : format(range.from, 'dd/MM/yy')}
          </span>
          <Button
            variant="ghost"
            size="sm"
            className="h-6 px-2 text-xs"
            onClick={() => onSelect(undefined)}
          >
            <X className="mr-1 h-3 w-3" />
            {t('clear_date')}
          </Button>
        </div>
      )}
    </div>
  )
}
