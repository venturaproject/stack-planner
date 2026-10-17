import { ListFilter, X } from 'lucide-react'
import type { DateRange } from 'react-day-picker'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { DataTableDateFilterSection } from '@/components/data-table'
import { useI18n } from '@/i18n/context'

interface CustomerFiltersProps {
  dateRange: DateRange | undefined
  hasActiveFilters: boolean
  onDateChange: (range: DateRange | undefined) => void
  onClearAll: () => void
}

export function CustomerFilters({
  dateRange,
  hasActiveFilters,
  onDateChange,
  onClearAll,
}: CustomerFiltersProps) {
  const { t } = useI18n()

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="outline" size="sm" className="h-9 gap-1">
          <ListFilter className="h-3.5 w-3.5" />
          <span className="sr-only sm:not-sr-only sm:whitespace-nowrap">{t('filter')}</span>
          {hasActiveFilters && <span className="ml-0.5 h-1.5 w-1.5 rounded-full bg-primary" />}
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" className="w-auto p-0">
        <div className="p-3">
          <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">
            {t('filter_by')}
          </p>
        </div>

        <Separator />

        <DataTableDateFilterSection
          label={t('label_created_at')}
          range={dateRange}
          onSelect={onDateChange}
        />

        {hasActiveFilters && (
          <>
            <Separator />
            <div className="p-3">
              <Button
                variant="ghost"
                size="sm"
                className="w-full text-xs text-muted-foreground"
                onClick={onClearAll}
              >
                <X className="mr-1 h-3 w-3" />
                {t('clear_filters')}
              </Button>
            </div>
          </>
        )}
      </PopoverContent>
    </Popover>
  )
}
