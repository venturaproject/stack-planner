import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { IconCalendar, IconCircleCheck, IconProgress } from '@tabler/icons-react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { RoutePending } from '@/components/route-pending'
import { issuesApi } from '@/services/issues-api'
import { projectsApi } from '@/services/projects-api'
import { useAuthStore } from '@/lib/auth'
import { useI18n } from '@/i18n/context'

export default function MyWorkPage() {
  const { t, currentLang } = useI18n()
  const currentUserName = useAuthStore((state) => state.user?.name)
  const issues = useQuery({ queryKey: ['planner', 'issues'], queryFn: () => issuesApi.list() })
  const projects = useQuery({ queryKey: ['planner', 'projects'], queryFn: projectsApi.list })
  if (issues.isPending || projects.isPending) return <RoutePending />
  if (issues.isError || projects.isError)
    return (
      <AuthenticatedLayout title={t('planner_my_work_title')}>
        <Main>
          <p className="text-sm text-destructive">{t('planner_my_work_load_error')}</p>
        </Main>
      </AuthenticatedLayout>
    )

  const projectById = new Map(projects.data.data.map((project) => [project.id, project]))
  const assignedIssues = issues.data.data.filter(
    (issue) => !currentUserName || issue.assigneeNames.includes(currentUserName),
  )
  const openIssues = assignedIssues.filter((issue) => issue.status !== 'done')
  const inProgress = openIssues.filter((issue) => issue.status === 'in_progress')
  const toDo = openIssues.filter((issue) => issue.status === 'todo')

  return (
    <AuthenticatedLayout title={t('planner_my_work_title')}>
      <Main className="space-y-6 p-6 md:p-8">
        <header>
          <p className="text-sm text-muted-foreground">{t('planner_my_work_subtitle')}</p>
          <h1 className="mt-1 text-3xl font-semibold tracking-tight">
            {t('planner_my_work_title')}
          </h1>
          <p className="mt-2 text-sm text-muted-foreground">
            {t('planner_my_work_description', {
              name: currentUserName ?? t('planner_my_work_your_account'),
            })}
          </p>
        </header>
        <section className="grid gap-4 md:grid-cols-2">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">
                {t('planner_my_work_in_progress')}
              </CardTitle>
              <IconProgress className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{inProgress.length}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">{t('planner_my_work_to_do')}</CardTitle>
              <IconCircleCheck className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{toDo.length}</div>
            </CardContent>
          </Card>
        </section>
        <Card>
          <CardHeader>
            <CardTitle>{t('planner_my_work_open_issues')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {openIssues.map((issue) => {
              const project = projectById.get(issue.projectId)
              const statusLabel =
                issue.status === 'in_progress'
                  ? t('planner_status_in_progress')
                  : issue.status === 'in_review'
                    ? t('planner_status_in_review')
                    : t('planner_status_todo')
              return (
                <Link
                  key={issue.id}
                  to={`/admin/issues?projectId=${issue.projectId}`}
                  className="flex flex-wrap items-center gap-3 rounded-lg border p-3 transition hover:bg-muted/50"
                >
                  <span className="w-24 text-xs font-medium text-muted-foreground">
                    {issue.key}
                  </span>
                  <span className="min-w-48 flex-1 text-sm font-medium">{issue.title}</span>
                  <span className="text-xs text-muted-foreground">{project?.name}</span>
                  <Badge variant="secondary">{statusLabel}</Badge>
                  {issue.dueAt && (
                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                      <IconCalendar size={14} />
                      {new Date(issue.dueAt).toLocaleDateString(
                        currentLang === 'es' ? 'es-ES' : 'en-US',
                      )}
                    </span>
                  )}
                </Link>
              )
            })}
          </CardContent>
        </Card>
      </Main>
    </AuthenticatedLayout>
  )
}
