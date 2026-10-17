'use client'

import * as React from 'react'
import { Label, Pie, PieChart } from 'recharts'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from '@/components/ui/chart'
import { useI18n } from '@/i18n/context'

interface StatusDatum {
  key: string
  label: string
  count: number
}

interface IssuesByStatusChartProps {
  data: StatusDatum[]
}

const CATEGORY_COLORS: Record<string, string> = {
  todo: '#94a3b8',
  in_progress: '#60a5fa',
  done: '#10b981',
}

export function IssuesByStatusChart({ data }: IssuesByStatusChartProps) {
  const { t } = useI18n()
  const total = React.useMemo(() => data.reduce((sum, d) => sum + d.count, 0), [data])

  const chartConfig = React.useMemo(() => {
    const config: ChartConfig = { count: { label: t('planner_chart_tasks') } }
    for (const d of data) {
      config[d.key] = { label: d.label, color: CATEGORY_COLORS[d.key] ?? '#94a3b8' }
    }
    return config
  }, [data, t])

  const chartData = data.map((d) => ({ ...d, fill: CATEGORY_COLORS[d.key] ?? '#94a3b8' }))

  const renderCenterLabel: React.ComponentProps<typeof Label>['content'] = ({ viewBox }) => {
    if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
      return (
        <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle" dominantBaseline="middle">
          <tspan x={viewBox.cx} y={viewBox.cy} className="fill-foreground text-3xl font-bold">
            {total.toLocaleString()}
          </tspan>
          <tspan x={viewBox.cx} y={(viewBox.cy ?? 0) + 24} className="fill-muted-foreground">
            {t('planner_chart_tasks').toLocaleLowerCase()}
          </tspan>
        </text>
      )
    }
  }

  return (
    <Card className="flex flex-col">
      <CardHeader className="items-center pb-0">
        <CardTitle>{t('planner_chart_tasks_by_status')}</CardTitle>
        <CardDescription>{t('planner_chart_status_distribution')}</CardDescription>
      </CardHeader>
      <CardContent className="flex-1 pb-0">
        {total === 0 ? (
          <div className="flex h-[250px] items-center justify-center text-sm text-muted-foreground">
            {t('planner_chart_empty')}
          </div>
        ) : (
          <ChartContainer config={chartConfig} className="mx-auto aspect-square max-h-[250px]">
            <PieChart>
              <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
              <Pie
                data={chartData}
                dataKey="count"
                nameKey="label"
                innerRadius={60}
                strokeWidth={5}
              >
                <Label content={renderCenterLabel} />
              </Pie>
            </PieChart>
          </ChartContainer>
        )}
      </CardContent>
    </Card>
  )
}
