import { useState } from 'react'
import { ListFilter, X } from 'lucide-react'
import type { DateRange } from 'react-day-picker'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Separator } from '@/components/ui/separator'
import { Slider } from '@/components/ui/slider'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { DataTableDateFilterSection } from '@/components/data-table'
import { useI18n } from '@/i18n/context'
import { formatMoney } from '@/lib/formatters'

const PRICE_SLIDER_MAX = 500_00 // minor units — the top handle means "no upper limit"

interface ProductFiltersProps {
  stockStatus?: string
  priceMin?: number
  priceMax?: number
  dateRange: DateRange | undefined
  hasActiveFilters: boolean
  onStockStatusChange: (value: string | undefined) => void
  onPriceChange: (min: number | undefined, max: number | undefined) => void
  onDateChange: (range: DateRange | undefined) => void
  onClearAll: () => void
}

export function ProductFilters({
  stockStatus,
  priceMin,
  priceMax,
  dateRange,
  hasActiveFilters,
  onStockStatusChange,
  onPriceChange,
  onDateChange,
  onClearAll,
}: ProductFiltersProps) {
  const { t } = useI18n()
  const [range, setRange] = useState<[number, number]>([
    priceMin ?? 0,
    priceMax ?? PRICE_SLIDER_MAX,
  ])

  // Reset the slider when priceMin/priceMax change externally (e.g. filters
  // cleared elsewhere), adjusted during render instead of in an effect.
  const [prevPriceMin, setPrevPriceMin] = useState(priceMin)
  const [prevPriceMax, setPrevPriceMax] = useState(priceMax)
  if (priceMin !== prevPriceMin || priceMax !== prevPriceMax) {
    setPrevPriceMin(priceMin)
    setPrevPriceMax(priceMax)
    setRange([priceMin ?? 0, priceMax ?? PRICE_SLIDER_MAX])
  }

  const commitPrice = (value: number[]) => {
    const [min, max] = value
    onPriceChange(min > 0 ? min : undefined, max < PRICE_SLIDER_MAX ? max : undefined)
  }

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

        <div className="p-3">
          <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
            {t('products_filter_stock_status')}
          </p>
          <div className="space-y-1.5">
            <label className="flex items-center gap-2 text-sm cursor-pointer select-none">
              <Checkbox
                checked={!stockStatus}
                onCheckedChange={(checked) => {
                  if (checked) onStockStatusChange(undefined)
                }}
              />
              {t('products_tab_all')}
            </label>
            <label className="flex items-center gap-2 text-sm cursor-pointer select-none">
              <Checkbox
                checked={stockStatus === 'positive'}
                onCheckedChange={(checked) => onStockStatusChange(checked ? 'positive' : undefined)}
              />
              {t('products_filter_stock_positive')}
            </label>
            <label className="flex items-center gap-2 text-sm cursor-pointer select-none">
              <Checkbox
                checked={stockStatus === 'zero'}
                onCheckedChange={(checked) => onStockStatusChange(checked ? 'zero' : undefined)}
              />
              {t('products_filter_stock_zero')}
            </label>
            <label className="flex items-center gap-2 text-sm cursor-pointer select-none">
              <Checkbox
                checked={stockStatus === 'negative'}
                onCheckedChange={(checked) => onStockStatusChange(checked ? 'negative' : undefined)}
              />
              {t('products_filter_stock_negative')}
            </label>
          </div>
        </div>

        <Separator />

        <div className="p-3 w-64">
          <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-2">
            {t('products_filter_price')}
          </p>
          <Slider
            min={0}
            max={PRICE_SLIDER_MAX}
            step={100}
            value={range}
            onValueChange={(value) => setRange(value as [number, number])}
            onValueCommit={commitPrice}
            className="my-4"
          />
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <span>{formatMoney(range[0], 'USD')}</span>
            <span>
              {range[1] >= PRICE_SLIDER_MAX
                ? `${formatMoney(PRICE_SLIDER_MAX, 'USD')}+`
                : formatMoney(range[1], 'USD')}
            </span>
          </div>
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
