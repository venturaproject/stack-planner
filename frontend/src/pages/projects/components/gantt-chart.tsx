import { useMemo, useState } from 'react'
import {
  addDays,
  addMonths,
  differenceInDays,
  eachMonthOfInterval,
  eachWeekOfInterval,
  endOfMonth,
  format,
  max,
  min,
  parseISO,
  startOfMonth,
} from 'date-fns'
import { enUS, es } from 'date-fns/locale'
import { IconInfoCircle } from '@tabler/icons-react'
import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import type { PlannerIssue } from '@/services/issues-api'
import type { PlannerEpic, PlannerSprint } from '@/services/planning-api'
import { useI18n } from '@/i18n/context'

type ZoomLevel = 'weeks' | 'months'
type GanttRow = {
  id: string
  label: string
  start: Date
  end: Date
  type: 'epic' | 'issue'
  color: string
  issue?: PlannerIssue
  epic?: PlannerEpic
}

const priorityColor: Record<string, string> = {
  critical: '#ef4444',
  high: '#f97316',
  medium: '#eab308',
  low: '#3b82f6',
  urgent: '#ef4444',
}

export function GanttChart({
  issues,
  epics,
  sprints,
}: {
  issues: PlannerIssue[]
  epics: PlannerEpic[]
  sprints: PlannerSprint[]
}) {
  const { t, currentLang } = useI18n()
  const dateLocale = currentLang === 'es' ? es : enUS
  const [zoom, setZoom] = useState<ZoomLevel>('weeks')
  const rows = useMemo<GanttRow[]>(() => {
    const result: GanttRow[] = []
    for (const epic of epics) {
      const epicIssues = issues.filter((issue) => issue.epicId === epic.id)
      if (epicIssues.length) {
        const dates = epicIssues.flatMap((issue) => [
          issue.startAt ? new Date(issue.startAt) : new Date(issue.createdAt),
          issue.dueAt ? new Date(issue.dueAt) : new Date(issue.createdAt),
        ])
        result.push({
          id: `epic-${epic.id}`,
          label: epic.name,
          start: min(dates),
          end: max(dates),
          type: 'epic',
          color: '#8b5cf6',
          epic,
        })
      }
      for (const issue of epicIssues) {
        result.push({
          id: `issue-${issue.id}`,
          label: issue.title,
          start: issue.startAt ? new Date(issue.startAt) : new Date(issue.createdAt),
          end: issue.dueAt ? new Date(issue.dueAt) : addDays(new Date(issue.createdAt), 1),
          type: 'issue',
          color: priorityColor[issue.priority] ?? '#64748b',
          issue,
        })
      }
    }
    for (const issue of issues.filter((item) => !item.epicId)) {
      result.push({
        id: `issue-${issue.id}`,
        label: issue.title,
        start: issue.startAt ? new Date(issue.startAt) : new Date(issue.createdAt),
        end: issue.dueAt ? new Date(issue.dueAt) : addDays(new Date(issue.createdAt), 1),
        type: 'issue',
        color: priorityColor[issue.priority] ?? '#64748b',
        issue,
      })
    }
    return result
  }, [epics, issues])

  const { timelineStart, timelineEnd, columns } = useMemo(() => {
    if (!rows.length) {
      const start = startOfMonth(new Date())
      const end = endOfMonth(addMonths(start, 3))
      return {
        timelineStart: start,
        timelineEnd: end,
        columns: [] as { key: string; label: string }[],
      }
    }
    const dates = rows.flatMap((row) => [row.start, row.end])
    const start = addDays(startOfMonth(min(dates)), -7)
    const end = addDays(endOfMonth(max(dates)), 7)
    const cols =
      zoom === 'weeks'
        ? eachWeekOfInterval({ start, end }, { locale: dateLocale, weekStartsOn: 1 }).map(
            (date) => ({
              key: format(date, 'yyyy-ww'),
              label: format(date, 'dd MMM', { locale: dateLocale }),
            }),
          )
        : eachMonthOfInterval({ start, end }).map((date) => ({
            key: format(date, 'yyyy-MM'),
            label: format(date, 'MMM yyyy', { locale: dateLocale }),
          }))
    return { timelineStart: start, timelineEnd: end, columns: cols }
  }, [dateLocale, rows, zoom])

  const totalDays = Math.max(1, differenceInDays(timelineEnd, timelineStart) + 1)
  const columnWidth = zoom === 'weeks' ? 80 : 120
  const timelineWidth = columns.length * columnWidth
  const todayPosition = (differenceInDays(new Date(), timelineStart) / totalDays) * 100
  const barStyle = (start: Date, end: Date) => ({
    left: `${(differenceInDays(start, timelineStart) / totalDays) * 100}%`,
    width: `max(${((differenceInDays(end, start) + 1) / totalDays) * 100}%, 1%)`,
  })

  if (!rows.length)
    return (
      <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">
        {t('planner_gantt_empty')}
      </div>
    )

  return (
    <TooltipProvider delayDuration={200}>
      <div className="flex h-[520px] flex-col overflow-hidden rounded-xl border bg-card">
        <div className="flex items-center gap-2 border-b bg-background p-3">
          <Tooltip>
            <TooltipTrigger asChild>
              <span className="mr-2 flex cursor-help items-center gap-1 text-sm font-medium">
                {t('planner_gantt_zoom')}
                <IconInfoCircle className="size-3.5 text-muted-foreground" />
              </span>
            </TooltipTrigger>
            <TooltipContent side="bottom" className="max-w-64">
              {t('planner_gantt_zoom_description')}
            </TooltipContent>
          </Tooltip>
          <Button
            size="sm"
            variant={zoom === 'weeks' ? 'default' : 'outline'}
            onClick={() => setZoom('weeks')}
          >
            {t('planner_gantt_weeks')}
          </Button>
          <Button
            size="sm"
            variant={zoom === 'months' ? 'default' : 'outline'}
            onClick={() => setZoom('months')}
          >
            {t('planner_gantt_months')}
          </Button>
        </div>
        <div className="flex min-h-0 flex-1 overflow-auto">
          <div
            className="sticky left-0 z-10 shrink-0 border-r bg-background"
            style={{ width: 260 }}
          >
            <div className="h-10 border-b px-3 py-2 text-xs font-medium text-muted-foreground">
              {t('planner_gantt_epics_issues')}
            </div>
            {rows.map((row) => (
              <div
                key={row.id}
                className={`flex items-center gap-2 truncate border-b px-3 text-sm ${row.type === 'epic' ? 'bg-muted/30 font-semibold' : 'pl-7'}`}
                style={{ height: 44 }}
              >
                {row.type === 'epic' ? (
                  <span
                    className="size-2.5 shrink-0 rounded-sm"
                    style={{ backgroundColor: row.color }}
                  />
                ) : (
                  <span className="shrink-0 font-mono text-[10px] text-muted-foreground">
                    {row.issue?.key}
                  </span>
                )}
                <span className="truncate">{row.label}</span>
              </div>
            ))}
          </div>
          <div className="min-w-0 flex-1 overflow-auto">
            <div className="sticky top-0 z-10 flex h-10 border-b bg-background">
              {columns.map((column) => (
                <div
                  key={column.key}
                  className="flex shrink-0 items-center justify-center border-r text-xs text-muted-foreground"
                  style={{ width: columnWidth }}
                >
                  {column.label}
                </div>
              ))}
            </div>
            <div className="relative" style={{ width: timelineWidth }}>
              {sprints
                .filter((sprint) => sprint.startAt && sprint.endAt)
                .map((sprint) => {
                  const start = parseISO(sprint.startAt)
                  const end = parseISO(sprint.endAt)
                  return (
                    <div
                      key={sprint.id}
                      className="pointer-events-none absolute top-0 z-0 border-x border-dashed border-primary/30 bg-primary/[0.04]"
                      style={{ ...barStyle(start, end), height: rows.length * 44 }}
                    >
                      <span className="absolute left-1 top-0.5 truncate text-[10px] text-muted-foreground">
                        {sprint.name}
                      </span>
                    </div>
                  )
                })}
              {todayPosition >= 0 && todayPosition <= 100 && (
                <div
                  className="pointer-events-none absolute bottom-0 top-0 z-20 w-0.5 bg-red-500/60"
                  style={{ left: `${todayPosition}%` }}
                />
              )}
              {rows.map((row) => (
                <div
                  key={row.id}
                  className={`relative border-b ${row.type === 'epic' ? 'bg-muted/20' : ''}`}
                  style={{ height: 44 }}
                >
                  {columns.map((column, index) => (
                    <div
                      key={column.key}
                      className="absolute bottom-0 top-0 border-r border-muted/40"
                      style={{ left: index * columnWidth, width: columnWidth }}
                    />
                  ))}
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Link
                        to={
                          row.issue
                            ? `/admin/issues/${row.issue.id}`
                            : `/admin/projects/${row.epic?.projectId}/epics`
                        }
                        className="absolute bottom-2 top-2 z-10 flex items-center truncate rounded px-2 text-xs font-medium text-white hover:opacity-90"
                        style={{ ...barStyle(row.start, row.end), backgroundColor: row.color }}
                      >
                        {row.issue?.key ?? row.label}
                      </Link>
                    </TooltipTrigger>
                    <TooltipContent side="top" className="max-w-72">
                      <div className="space-y-1">
                        <p className="font-semibold">
                          {row.issue?.key ? `${row.issue.key} · ` : ''}
                          {row.label}
                        </p>
                        <p className="text-[11px] opacity-80">
                          {format(row.start, 'dd MMM yyyy', { locale: dateLocale })} →{' '}
                          {format(row.end, 'dd MMM yyyy', { locale: dateLocale })}
                        </p>
                        {row.issue && (
                          <>
                            <p className="text-[11px] opacity-80">
                              {t('planner_gantt_status', {
                                status: t(`planner_status_${row.issue.status}`),
                              })}
                            </p>
                            <p className="text-[11px] opacity-80">
                              {t('planner_gantt_priority', {
                                priority: t(`planner_priority_${row.issue.priority}`),
                              })}
                            </p>
                            {null !== row.issue.storyPoints && (
                              <p className="text-[11px] opacity-80">
                                {t('planner_gantt_points', { count: row.issue.storyPoints })}
                              </p>
                            )}
                            {row.issue.assigneeNames.length > 0 && (
                              <p className="text-[11px] opacity-80">
                                {row.issue.assigneeNames.join(', ')}
                              </p>
                            )}
                          </>
                        )}
                      </div>
                    </TooltipContent>
                  </Tooltip>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </TooltipProvider>
  )
}
