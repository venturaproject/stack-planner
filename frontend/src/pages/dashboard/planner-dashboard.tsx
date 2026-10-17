import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { formatDistanceToNow, format, startOfMonth, subMonths } from 'date-fns'
import { enUS, es } from 'date-fns/locale'
import {
  IconArrowUpRight,
  IconBookmark,
  IconBug,
  IconCircleDot,
  IconListCheck,
} from '@tabler/icons-react'
import { CheckCircle2, Clock as IconClock, GitPullRequest, Layers } from 'lucide-react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { RoutePending } from '@/components/route-pending'
import { issuesApi } from '@/services/issues-api'
import { projectsApi } from '@/services/projects-api'
import { ProjectCard } from '@/components/project-card'
import { MetricStatCard } from '@/components/metric-stat-card'
import { IssueActivityChart } from './components/issue-activity-chart'
import { IssuesByStatusChart } from './components/issues-by-status-chart'
import { IssuesByPriorityChart } from './components/issues-by-priority-chart'
import { IssuesByTypeChart } from './components/issues-by-type-chart'
import { ProjectsProgressChart } from './components/projects-progress-chart'
import NotificationList from '@/components/notification/notification-list'
import { useI18n } from '@/i18n/context'

const typeColors: Record<string, string> = {
  story: '#6366f1',
  task: '#3b82f6',
  bug: '#ef4444',
  subtask: '#94a3b8',
}
const priorityColors: Record<string, string> = {
  critical: '#ef4444',
  high: '#f97316',
  medium: '#eab308',
  low: '#3b82f6',
}
const typeIcon: Record<string, typeof IconCircleDot> = {
  bug: IconBug,
  story: IconBookmark,
  task: IconListCheck,
  subtask: IconCircleDot,
}

export default function PlannerDashboard() {
  const { t, currentLang } = useI18n()
  const dateLocale = currentLang === 'es' ? es : enUS
  const projectsQuery = useQuery({ queryKey: ['planner', 'projects'], queryFn: projectsApi.list })
  const issuesQuery = useQuery({ queryKey: ['planner', 'issues'], queryFn: () => issuesApi.list() })
  const statusLabels: Record<string, string> = {
    todo: t('planner_status_todo'),
    in_progress: t('planner_status_in_progress'),
    in_review: t('planner_status_in_review'),
    done: t('planner_status_done'),
  }
  const typeLabels: Record<string, string> = {
    story: t('planner_type_story'),
    task: t('planner_type_task'),
    bug: t('planner_type_bug'),
    subtask: t('planner_type_subtask'),
  }
  const priorityLabels: Record<string, string> = {
    critical: t('planner_priority_critical'),
    urgent: t('planner_priority_urgent'),
    high: t('planner_priority_high'),
    medium: t('planner_priority_medium'),
    low: t('planner_priority_low'),
  }

  if (projectsQuery.isPending || issuesQuery.isPending) return <RoutePending />
  if (projectsQuery.isError || issuesQuery.isError) {
    return (
      <AuthenticatedLayout title={t('planner_dashboard_title')}>
        <Main>
          <p className="text-sm text-destructive">{t('planner_dashboard_load_error')}</p>
        </Main>
      </AuthenticatedLayout>
    )
  }

  const projects = projectsQuery.data.data
  const issues = issuesQuery.data.data
  const completed = issues.filter((issue) => issue.status === 'done').length
  const open = issues.length - completed
  const inProgress = issues.filter((issue) => issue.status === 'in_progress').length
  const monthStarts = Array.from({ length: 6 }, (_, index) =>
    startOfMonth(subMonths(new Date(), 5 - index)),
  )
  const monthlyActivity = monthStarts.map((month) => {
    const key = format(month, 'yyyy-MM')
    const monthIssues = issues.filter(
      (issue) => format(new Date(issue.createdAt), 'yyyy-MM') === key,
    )
    return {
      month: format(month, 'MMM', { locale: dateLocale }),
      created: monthIssues.length,
      completed: monthIssues.filter((issue) => issue.status === 'done').length,
    }
  })
  const issuesByStatus = Object.entries(statusLabels).map(([key, label]) => ({
    key,
    label,
    count: issues.filter((issue) => issue.status === key).length,
  }))
  const issuesByPriority = Object.entries(priorityLabels).map(([key, label]) => ({
    key,
    label,
    color: priorityColors[key],
    count: issues.filter((issue) => issue.priority === key).length,
  }))
  const issuesByType = Object.entries(typeLabels)
    .map(([key, label]) => ({
      key,
      label,
      color: typeColors[key],
      count: issues.filter((issue) => issue.type === key).length,
    }))
    .filter((item) => item.count > 0)
  const projectsProgress = projects.map((project) => ({
    name: project.name,
    key: project.key,
    color: project.color,
    percent: project.issueCount
      ? Math.round((project.completedIssueCount / project.issueCount) * 100)
      : 0,
    total: project.issueCount,
  }))
  const recentIssues = [...issues]
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    .slice(0, 6)
  const completedThisMonth = issues.filter(
    (issue) =>
      issue.status === 'done' && new Date(issue.createdAt).getMonth() === new Date().getMonth(),
  ).length

  return (
    <AuthenticatedLayout title={t('planner_dashboard_title')}>
      <Main>
        <div className="mb-2 flex items-center justify-between space-y-2">
          <h1 className="text-2xl font-bold tracking-tight">{t('planner_dashboard_title')}</h1>
          <Link
            to="/admin/projects"
            className="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
          >
            {t('planner_dashboard_see_projects')} <IconArrowUpRight size={16} />
          </Link>
        </div>

        <Tabs orientation="vertical" defaultValue="overview" className="space-y-4">
          <div className="w-full overflow-x-auto pb-2">
            <TabsList>
              <TabsTrigger value="overview">{t('planner_dashboard_tab_overview')}</TabsTrigger>
              <TabsTrigger value="analytics">{t('planner_dashboard_tab_analytics')}</TabsTrigger>
              <TabsTrigger value="reports">{t('planner_dashboard_tab_reports')}</TabsTrigger>
              <TabsTrigger value="notifications">
                {t('planner_dashboard_tab_notifications')}
              </TabsTrigger>
            </TabsList>
          </div>

          <TabsContent value="overview" className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <MetricStatCard
                title={t('planner_dashboard_active_projects')}
                value={projects.length}
                subtitle={t('planner_dashboard_projects_in_progress')}
                icon={Layers}
                sparklineColor="#10b981"
                sparklineData={projects.map((project) => project.issueCount)}
                tooltip={t('planner_dashboard_active_projects_tooltip')}
              />
              <MetricStatCard
                title={t('planner_dashboard_open_tasks')}
                value={open}
                subtitle={t('planner_dashboard_pending_tasks')}
                icon={GitPullRequest}
                sparklineColor="#6366f1"
                sparklineData={monthlyActivity.map((month) => month.created)}
                tooltip={t('planner_dashboard_open_tasks_tooltip')}
              />
              <MetricStatCard
                title={t('planner_dashboard_completed_month')}
                value={completedThisMonth}
                subtitle={t('planner_dashboard_closed_tasks')}
                icon={CheckCircle2}
                sparklineColor="#f59e0b"
                sparklineData={monthlyActivity.map((month) => month.completed)}
                tooltip={t('planner_dashboard_completed_tooltip')}
              />
              <MetricStatCard
                title={t('planner_dashboard_in_progress')}
                value={inProgress}
                subtitle={t('planner_dashboard_development_now')}
                icon={IconClock}
                sparklineColor="#3b82f4"
                sparklineData={monthlyActivity.map((month) => month.created - month.completed)}
                tooltip={t('planner_dashboard_progress_tooltip')}
              />
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_dashboard_task_activity')}</CardTitle>
                  <CardDescription>
                    {t('planner_dashboard_created_completed_month')}
                  </CardDescription>
                </CardHeader>
                <CardContent className="pl-2">
                  <IssueActivityChart data={monthlyActivity} />
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_dashboard_recent_tasks')}</CardTitle>
                  <CardDescription>
                    {t('planner_dashboard_recent_tasks_description')}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {recentIssues.map((issue) => {
                      const TypeIcon = typeIcon[issue.type] ?? IconCircleDot
                      return (
                        <div key={issue.id} className="flex items-start gap-3">
                          <div className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md border bg-muted">
                            <TypeIcon className="size-4 text-primary" />
                          </div>
                          <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                            <Link
                              to={`/admin/issues/${issue.id}`}
                              className="truncate text-sm font-medium leading-none hover:text-primary"
                            >
                              {issue.title}
                            </Link>
                            <p className="font-mono text-xs text-muted-foreground">{issue.key}</p>
                          </div>
                          <div className="flex shrink-0 flex-col items-end gap-1">
                            <Badge
                              variant={
                                issue.status === 'done'
                                  ? 'default'
                                  : issue.status === 'in_progress'
                                    ? 'secondary'
                                    : 'outline'
                              }
                            >
                              {statusLabels[issue.status] ?? issue.status}
                            </Badge>
                            <span className="text-xs text-muted-foreground">
                              {formatDistanceToNow(new Date(issue.createdAt), {
                                addSuffix: true,
                                locale: dateLocale,
                              })}
                            </span>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                </CardContent>
              </Card>
            </div>
            <div>
              <div className="mb-4 flex items-center justify-between">
                <div>
                  <h2 className="text-xl font-semibold tracking-tight">
                    {t('planner_dashboard_projects')}
                  </h2>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {t('planner_dashboard_workspace_progress')}
                  </p>
                </div>
                <Link
                  to="/admin/projects"
                  className="text-sm font-medium text-primary hover:underline"
                >
                  {t('planner_dashboard_view_all')}
                </Link>
              </div>
              <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                {projects.map((project) => (
                  <ProjectCard key={project.id} project={project} />
                ))}
              </div>
            </div>
          </TabsContent>

          <TabsContent value="analytics" className="space-y-4">
            <div className="grid gap-4 lg:grid-cols-2">
              <IssuesByStatusChart data={issuesByStatus} />
              <IssuesByPriorityChart data={issuesByPriority} />
              <ProjectsProgressChart data={projectsProgress} />
              <IssuesByTypeChart data={issuesByType} />
            </div>
          </TabsContent>
          <TabsContent value="reports" className="space-y-4">
            <div className="grid flex-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
              <IssuesByStatusChart data={issuesByStatus} />
              <IssuesByPriorityChart data={issuesByPriority} />
              <IssuesByTypeChart data={issuesByType} />
              <ProjectsProgressChart data={projectsProgress} />
              <Card className="md:col-span-2">
                <CardHeader>
                  <CardTitle>{t('planner_dashboard_monthly_activity')}</CardTitle>
                  <CardDescription>
                    {t('planner_dashboard_created_completed_six_months')}
                  </CardDescription>
                </CardHeader>
                <CardContent className="pl-2">
                  <IssueActivityChart data={monthlyActivity} />
                </CardContent>
              </Card>
            </div>
          </TabsContent>
          <TabsContent value="notifications" className="space-y-4">
            <div className="max-w-2xl">
              <NotificationList />
            </div>
          </TabsContent>
        </Tabs>
      </Main>
    </AuthenticatedLayout>
  )
}
