'use client'

import * as React from 'react'
import { Bar, BarChart, CartesianGrid, Cell, XAxis, YAxis } from 'recharts'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from '@/components/ui/chart'
import { useI18n } from '@/i18n/context'

interface PriorityDatum {
  key: string
  label: string
  color: string
  count: number
}

interface IssuesByPriorityChartProps {
  data: PriorityDatum[]
}

export function IssuesByPriorityChart({ data }: IssuesByPriorityChartProps) {
  const { t } = useI18n()
  const chartConfig = React.useMemo(() => {
    const config: ChartConfig = { count: { label: t('planner_chart_tasks') } }
    for (const d of data) {
      config[d.key] = { label: d.label, color: d.color }
    }
    return config
  }, [data, t])

  const chartData = data.map((d) => ({ ...d, fill: d.color }))

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('planner_chart_tasks_by_priority')}</CardTitle>
        <CardDescription>{t('planner_chart_priority_distribution')}</CardDescription>
      </CardHeader>
      <CardContent>
        {data.length === 0 ? (
          <div className="flex h-[200px] items-center justify-center text-sm text-muted-foreground">
            {t('planner_chart_empty')}
          </div>
        ) : (
          <ChartContainer config={chartConfig} className="max-h-[250px] w-full">
            <BarChart accessibilityLayer data={chartData}>
              <CartesianGrid vertical={false} />
              <XAxis dataKey="label" tickLine={false} tickMargin={10} axisLine={false} />
              <YAxis tickLine={false} axisLine={false} tickMargin={8} allowDecimals={false} />
              <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
              <Bar dataKey="count" radius={[4, 4, 0, 0]}>
                {chartData.map((entry) => (
                  <Cell key={entry.key} fill={entry.fill} />
                ))}
              </Bar>
            </BarChart>
          </ChartContainer>
        )}
      </CardContent>
    </Card>
  )
}
