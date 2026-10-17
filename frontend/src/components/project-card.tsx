import { Link } from 'react-router-dom'
import {
  IconAlertCircle,
  IconCalendar,
  IconLayout,
  IconSettings,
  IconStack,
  IconTimeline,
  IconUsers,
} from '@tabler/icons-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import type { PlannerProject } from '@/services/projects-api'
import { useI18n } from '@/i18n/context'

export function ProjectCard({ project }: { project: PlannerProject }) {
  const { t, currentLang } = useI18n()
  const progress = project.issueCount
    ? Math.round((project.completedIssueCount / project.issueCount) * 100)
    : 0

  return (
    <Card className="group flex h-full flex-col transition-shadow hover:shadow-md">
      <CardHeader className="pb-2">
        <div className="flex items-start gap-3">
          <Link to={`/admin/projects/${project.id}`} className="shrink-0">
            <div
              className="grid size-12 place-items-center rounded-xl text-sm font-bold text-white shadow-sm"
              style={{ backgroundColor: project.color }}
            >
              {project.key}
            </div>
          </Link>
          <div className="min-w-0 flex-1">
            <div className="flex items-start justify-between gap-2">
              <Link
                to={`/admin/projects/${project.id}`}
                className="line-clamp-1 font-semibold leading-tight hover:underline"
              >
                {project.name}
              </Link>
              <Badge className="shrink-0 text-[10px]" variant="secondary">
                {t('planner_project_active')}
              </Badge>
            </div>
            <p className="mt-1 line-clamp-2 text-xs leading-relaxed text-muted-foreground">
              {project.description}
            </p>
          </div>
        </div>
      </CardHeader>
      <CardContent className="flex flex-1 flex-col gap-4 pt-0">
        <div>
          <div className="mb-1.5 flex items-center justify-between text-xs">
            <span className="text-muted-foreground">{t('planner_project_issues')}</span>
            <span className="font-medium tabular-nums">
              {project.completedIssueCount}/{project.issueCount}{' '}
              <span className="ml-1 text-muted-foreground">
                ({t('planner_percent_complete', { percent: progress })})
              </span>
            </span>
          </div>
          <Progress value={progress} className="h-1.5" />
        </div>
        {project.activeSprintName && (
          <div className="flex items-center gap-1.5 text-xs">
            <span className="rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">
              {project.activeSprintName}
            </span>
          </div>
        )}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3 text-xs text-muted-foreground">
            <span className="flex items-center gap-1">
              <IconAlertCircle size={14} />
              {t('planner_project_open_count', { count: project.openIssueCount })}
            </span>
            {project.members.length > 0 && (
              <span className="flex items-center gap-1">
                <IconUsers size={14} />
                {project.members.length}
              </span>
            )}
          </div>
          {project.members.length > 0 && (
            <div className="flex -space-x-2">
              {project.members.slice(0, 4).map((member) => (
                <div
                  key={member.name}
                  title={member.name}
                  className="grid size-6 place-items-center overflow-hidden rounded-full border-2 border-background bg-muted text-[10px] font-semibold"
                >
                  {member.avatar ? (
                    <img src={member.avatar} alt={member.name} className="size-full object-cover" />
                  ) : (
                    member.name
                      .split(' ')
                      .map((part) => part[0])
                      .join('')
                      .slice(0, 2)
                      .toUpperCase()
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
        {(project.startDate || project.endDate) && (
          <div className="flex items-center gap-1 text-xs text-muted-foreground">
            <IconCalendar size={13} />
            {project.startDate
              ? new Date(project.startDate).toLocaleDateString(
                  currentLang === 'es' ? 'es-ES' : 'en-US',
                  {
                    day: '2-digit',
                    month: 'short',
                  },
                )
              : '?'}{' '}
            –{' '}
            {project.endDate
              ? new Date(project.endDate).toLocaleDateString(
                  currentLang === 'es' ? 'es-ES' : 'en-US',
                  {
                    day: '2-digit',
                    month: 'short',
                  },
                )
              : '?'}
          </div>
        )}
        <div className="mt-auto flex items-center gap-3 border-t pt-2">
          <Link
            to={`/admin/projects/${project.id}/board`}
            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
          >
            <IconLayout size={14} />
            {t('planner_project_board')}
          </Link>
          <Link
            to={`/admin/projects/${project.id}/backlog`}
            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
          >
            <IconStack size={14} />
            {t('planner_project_backlog')}
          </Link>
          <Link
            to={`/admin/projects/${project.id}/gantt`}
            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
          >
            <IconTimeline size={14} />
            {t('planner_project_timeline')}
          </Link>
          <Link
            to={`/admin/projects/${project.id}/settings`}
            className="ml-auto text-xs text-muted-foreground hover:text-foreground"
            aria-label={t('planner_project_settings_aria', { name: project.name })}
          >
            <IconSettings size={14} />
          </Link>
        </div>
      </CardContent>
    </Card>
  )
}
