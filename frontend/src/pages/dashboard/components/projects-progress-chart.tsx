'use client'

import * as React from 'react'
import { Bar, BarChart, Cell, XAxis, YAxis } from 'recharts'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  ChartConfig,
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from '@/components/ui/chart'
import { useI18n } from '@/i18n/context'

interface ProjectProgressDatum {
  name: string
  key: string
  color: string
  percent: number
  total: number
}

interface ProjectsProgressChartProps {
  data: ProjectProgressDatum[]
}

export function ProjectsProgressChart({ data }: ProjectsProgressChartProps) {
  const { t } = useI18n()
  const chartConfig = React.useMemo(() => {
    const config: ChartConfig = { percent: { label: t('planner_percent_complete_label') } }
    for (const d of data) {
      config[d.key] = { label: d.name, color: d.color }
    }
    return config
  }, [data, t])

  const chartData = data.map((d) => ({ ...d, fill: d.color }))

  return (
    <Card>
      <CardHeader>
        <CardTitle>{t('planner_project_progress_by_project')}</CardTitle>
        <CardDescription>{t('planner_project_tasks_completed_percentage')}</CardDescription>
      </CardHeader>
      <CardContent>
        {data.length === 0 ? (
          <div className="flex h-[220px] items-center justify-center text-sm text-muted-foreground">
            {t('planner_project_progress_empty')}
          </div>
        ) : (
          <ChartContainer
            config={chartConfig}
            className="w-full"
            style={{ maxHeight: Math.max(120, data.length * 40) }}
          >
            <BarChart accessibilityLayer data={chartData} layout="vertical" margin={{ left: -10 }}>
              <XAxis type="number" dataKey="percent" domain={[0, 100]} hide />
              <YAxis
                dataKey="key"
                type="category"
                tickLine={false}
                tickMargin={10}
                axisLine={false}
                width={70}
              />
              <ChartTooltip
                cursor={false}
                content={<ChartTooltipContent hideLabel formatter={(value) => `${value}%`} />}
              />
              <Bar dataKey="percent" radius={5}>
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
