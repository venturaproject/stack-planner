import { useQuery } from '@tanstack/react-query'
import { IconCalendar, IconCircleCheck, IconCircleDashed, IconProgress } from '@tabler/icons-react'
import { RoutePending } from '@/components/route-pending'
import { issuesApi } from '@/services/issues-api'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Link, useSearchParams } from 'react-router-dom'
import { projectsApi } from '@/services/projects-api'
import { useI18n } from '@/i18n/context'

const columnDefinitions = [
  { id: 'todo', labelKey: 'planner_status_todo', icon: IconCircleDashed },
  { id: 'in_progress', labelKey: 'planner_status_in_progress', icon: IconProgress },
  { id: 'in_review', labelKey: 'planner_status_in_review', icon: IconProgress },
  { id: 'done', labelKey: 'planner_status_done', icon: IconCircleCheck },
] as const

const priorityStyles = {
  critical: 'text-rose-700 bg-rose-500/10',
  urgent: 'text-rose-700 bg-rose-500/10',
  high: 'text-orange-700 bg-orange-500/10',
  medium: 'text-blue-700 bg-blue-500/10',
  low: 'text-zinc-600 bg-zinc-500/10',
}

export default function IssuesPage() {
  const { t, currentLang } = useI18n()
  const [searchParams] = useSearchParams()
  const projectIdValue = searchParams.get('projectId')
  const projectId = projectIdValue ? Number(projectIdValue) : undefined
  const query = useQuery({
    queryKey: ['planner', 'issues', projectId],
    queryFn: () => issuesApi.list(projectId),
  })
  const projectQuery = useQuery({
    queryKey: ['planner', 'projects'],
    queryFn: projectsApi.list,
    enabled: projectId !== undefined,
  })
  const columns = columnDefinitions.map((column) => ({ ...column, label: t(column.labelKey) }))
  if (query.isPending) return <RoutePending />
  if (query.isError)
    return (
      <section className="p-8 text-sm text-destructive">{t('planner_issues_load_error')}</section>
    )

  return (
    <AuthenticatedLayout title={t('planner_issues_title')}>
      <Main className="space-y-6 p-6 md:p-8">
        <header>
          <p className="text-sm text-muted-foreground">{t('planner_planning')}</p>
          <h1 className="mt-1 text-3xl font-semibold tracking-tight">
            {projectId
              ? (projectQuery.data?.data.find((project) => project.id === projectId)?.name ??
                t('planner_project_board_title'))
              : t('planner_issues_title')}
          </h1>
          <p className="mt-2 text-sm text-muted-foreground">
            {projectId ? t('planner_project_board_title') : t('planner_demo_board_title')} ·{' '}
            {t('planner_issues_count', { count: query.data.data.length })}
          </p>
        </header>
        <section className="grid gap-5 xl:grid-cols-4">
          {columns.map((column) => {
            const items = query.data.data.filter((issue) => issue.status === column.id)
            const Icon = column.icon
            return (
              <div key={column.id} className="min-h-64 rounded-xl bg-muted/50 p-3">
                <header className="flex items-center gap-2 px-2 py-2 text-sm font-medium">
                  <Icon size={17} />
                  <span>{column.label}</span>
                  <span className="ml-auto rounded-full bg-background px-2 py-0.5 text-xs text-muted-foreground">
                    {items.length}
                  </span>
                </header>
                <div className="mt-2 space-y-3">
                  {items.map((issue) => (
                    <Link
                      to={`/admin/issues/${issue.id}`}
                      key={issue.id}
                      className="block rounded-lg border bg-card p-4 shadow-sm transition hover:shadow-md"
                    >
                      <p className="text-xs font-medium text-muted-foreground">{issue.key}</p>
                      <h2 className="mt-2 text-sm font-medium leading-5">{issue.title}</h2>
                      <footer className="mt-4 flex items-center justify-between">
                        <span
                          className={`rounded px-2 py-1 text-[11px] font-semibold uppercase ${priorityStyles[issue.priority]}`}
                        >
                          {t(`planner_priority_${issue.priority}`)}
                        </span>
                        {issue.dueAt && (
                          <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                            <IconCalendar size={14} />
                            {new Date(issue.dueAt).toLocaleDateString(
                              currentLang === 'es' ? 'es-ES' : 'en-US',
                            )}
                          </span>
                        )}
                      </footer>
                    </Link>
                  ))}
                </div>
              </div>
            )
          })}
        </section>
      </Main>
    </AuthenticatedLayout>
  )
}
