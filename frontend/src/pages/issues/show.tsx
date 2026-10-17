import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Link } from 'react-router-dom'
import {
  IconArrowLeft,
  IconCalendar,
  IconCheck,
  IconClock,
  IconGitBranch,
  IconMessageCircle,
  IconPaperclip,
  IconUsers,
  IconPencil,
} from '@tabler/icons-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { RoutePending } from '@/components/route-pending'
import { issuesApi } from '@/services/issues-api'
import { projectsApi } from '@/services/projects-api'
import { AssigneeAvatarStack } from '@/components/assignee-avatar-stack'
import type { IssueFormValues } from './form'
import { RichTextEditor } from '@/components/ui/rich-text-editor'
import { useI18n } from '@/i18n/context'

const priorityClasses: Record<string, string> = {
  critical: 'border-rose-200 bg-rose-50 text-rose-700',
  high: 'border-orange-200 bg-orange-50 text-orange-700',
  medium: 'border-blue-200 bg-blue-50 text-blue-700',
  low: 'border-zinc-200 bg-zinc-50 text-zinc-600',
}

export default function IssueDetailPage({ issueId }: { issueId: number }) {
  const { t, currentLang } = useI18n()
  const dateLocale = currentLang === 'es' ? 'es-ES' : 'en-US'
  const queryClient = useQueryClient()
  const issue = useQuery({
    queryKey: ['planner', 'issues', 'detail', issueId],
    queryFn: () => issuesApi.detail(issueId),
  })
  const project = useQuery({
    queryKey: ['planner', 'projects', issue.data?.projectId],
    queryFn: () => projectsApi.detail(issue.data!.projectId),
    enabled: !!issue.data,
  })
  const updateStatus = useMutation({
    mutationFn: (status: string) =>
      issuesApi.updateStatus(issueId, status as NonNullable<typeof issue.data>['status']),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['planner', 'issues'] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'issues', 'detail', issueId] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] }),
      ])
      toast.success(t('planner_issue_status_updated'))
    },
    onError: () => toast.error(t('planner_issue_status_update_error')),
  })
  const updateIssue = useMutation({
    mutationFn: (payload: IssueFormValues) => issuesApi.update(issueId, payload),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['planner', 'issues'] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'issues', 'detail', issueId] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] }),
      ])
    },
  })

  if (issue.isPending || (issue.data && project.isPending)) return <RoutePending />
  if (issue.isError || project.isError || !issue.data)
    return (
      <AuthenticatedLayout title={t('planner_issues_title')}>
        <Main>
          <p className="text-sm text-destructive">{t('planner_issue_detail_load_error')}</p>
        </Main>
      </AuthenticatedLayout>
    )

  const data = issue.data
  const projectName = project.data?.name ?? t('planner_projects_title')
  const statusLabel = (status: string) => t(`planner_status_${status}`)
  const valueLabel = (field: string, value: string | null) => {
    if (null === value) return '—'
    if (field === 'status') return statusLabel(value)
    if (field === 'priority') return t(`planner_priority_${value}`)
    if (field === 'type') return t(`planner_type_${value}`)

    return value
  }

  return (
    <AuthenticatedLayout title={data.key}>
      <Main className="space-y-5 p-6 md:p-8">
        <Link
          to={`/admin/projects/${data.projectId}`}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <IconArrowLeft size={16} />
          {t('planner_issue_back_to', { name: projectName })}
        </Link>
        <header>
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p className="text-sm font-medium text-muted-foreground">
                {projectName} / {data.key}
              </p>
              <h1 className="mt-1 text-3xl font-semibold tracking-tight">{data.title}</h1>
              <div className="mt-3 flex flex-wrap items-center gap-2">
                <Select value={data.status} onValueChange={(value) => updateStatus.mutate(value)}>
                  <SelectTrigger className="h-8 w-40">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="todo">{t('planner_status_todo')}</SelectItem>
                    <SelectItem value="in_progress">{t('planner_status_in_progress')}</SelectItem>
                    <SelectItem value="in_review">{t('planner_status_in_review')}</SelectItem>
                    <SelectItem value="done">{t('planner_status_done')}</SelectItem>
                  </SelectContent>
                </Select>
                <Badge variant="secondary">{t(`planner_type_${data.type}`)}</Badge>
                <Badge variant="outline" className={priorityClasses[data.priority]}>
                  {t(`planner_priority_${data.priority}`)}
                </Badge>
                {null !== data.storyPoints && (
                  <Badge variant="outline">
                    {t('planner_issue_points_label', { count: data.storyPoints })}
                  </Badge>
                )}
                {data.dueAt && (
                  <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                    <IconCalendar size={14} />
                    {new Date(data.dueAt).toLocaleDateString(dateLocale)}
                  </span>
                )}
              </div>
              <div className="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                <span>{t('planner_issue_reported_by', { name: data.reporterName })}</span>
                <AssigneeAvatarStack names={data.assigneeNames} />
              </div>
            </div>
            <Button variant="outline" size="sm" asChild>
              <Link to={`/admin/issues/${data.id}/edit`}>
                <IconPencil className="mr-2 size-4" />
                {t('planner_issue_edit')}
              </Link>
            </Button>
          </div>
        </header>

        <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
          <div className="space-y-5">
            <Card>
              <CardHeader>
                <CardTitle>{t('planner_issue_description_section')}</CardTitle>
              </CardHeader>
              <CardContent>
                <RichTextEditor
                  content={data.description ?? ''}
                  issueId={data.id}
                  onBlur={(html) => {
                    if (html !== (data.description ?? ''))
                      updateIssue.mutate({
                        title: data.title,
                        type: data.type,
                        priority: data.priority,
                        description: html,
                        dueAt: data.dueAt,
                        epicId: data.epicId,
                        sprintId: data.sprintId,
                        storyPoints: data.storyPoints,
                        assigneeNames: data.assigneeNames,
                      })
                  }}
                />
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <IconMessageCircle size={18} />
                  {t('planner_issue_comments_activity')}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {data.comments.map((comment) => (
                  <article key={comment.id} className="rounded-lg border p-4">
                    <div className="flex items-center justify-between gap-3">
                      <span className="text-sm font-semibold">{comment.authorName}</span>
                      <time className="text-xs text-muted-foreground">
                        {new Date(comment.createdAt).toLocaleDateString(dateLocale)}
                      </time>
                    </div>
                    <p className="mt-2 text-sm leading-6">{comment.content}</p>
                  </article>
                ))}
                {data.activities.map((activity) => (
                  <div key={`activity-${activity.id}`} className="flex items-start gap-3 text-sm">
                    <span className="mt-1 grid size-7 shrink-0 place-items-center rounded-full bg-muted">
                      <IconGitBranch size={14} />
                    </span>
                    <p className="text-muted-foreground">
                      <span className="font-medium text-foreground">{activity.actorName}</span>{' '}
                      {t('planner_issue_activity_changed', {
                        field: t(`planner_issue_field_${activity.field}`),
                        oldValue: valueLabel(activity.field, activity.oldValue),
                        newValue: valueLabel(activity.field, activity.newValue),
                      })}
                      <span className="ml-2 text-xs">
                        {new Date(activity.createdAt).toLocaleDateString(dateLocale)}
                      </span>
                    </p>
                  </div>
                ))}
                {data.comments.length === 0 && data.activities.length === 0 && (
                  <p className="text-sm text-muted-foreground">{t('planner_issue_no_activity')}</p>
                )}
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <IconCheck size={18} />
                  {t('planner_issue_checklist')}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {data.checklist.map((item) => (
                  <div key={item.id} className="flex items-center gap-3">
                    <span
                      className={`grid size-5 place-items-center rounded border ${item.isDone ? 'border-primary bg-primary text-primary-foreground' : 'border-input'}`}
                    >
                      {item.isDone && <IconCheck size={13} />}
                    </span>
                    <span
                      className={`text-sm ${item.isDone ? 'text-muted-foreground line-through' : ''}`}
                    >
                      {item.text}
                    </span>
                  </div>
                ))}
                {data.checklist.length === 0 && (
                  <p className="text-sm text-muted-foreground">{t('planner_issue_no_checklist')}</p>
                )}
              </CardContent>
            </Card>
          </div>

          <aside className="space-y-5">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <IconClock size={18} />
                  {t('planner_issue_worklog')}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {data.worklogs.map((entry) => (
                  <div key={entry.id} className="border-b pb-3 last:border-0 last:pb-0">
                    <p className="text-sm font-medium">
                      {t('planner_issue_worklog_duration', {
                        author: entry.authorName,
                        hours: Math.floor(entry.minutes / 60),
                        minutes: entry.minutes % 60,
                      })}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">{entry.description}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {new Date(entry.loggedAt).toLocaleDateString(dateLocale)}
                    </p>
                  </div>
                ))}
                {data.worklogs.length === 0 && (
                  <p className="text-sm text-muted-foreground">{t('planner_issue_no_worklog')}</p>
                )}
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>{t('planner_issue_related')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {data.links.map((link) => (
                  <Link
                    key={link.id}
                    to={`/admin/issues/${link.targetId}`}
                    className="block rounded-lg border p-3 hover:bg-muted/50"
                  >
                    <p className="text-xs text-muted-foreground">
                      {t(`planner_issue_link_${link.type}`)} · {link.targetKey}
                    </p>
                    <p className="mt-1 text-sm font-medium">{link.targetTitle}</p>
                  </Link>
                ))}
                {data.links.length === 0 && (
                  <p className="text-sm text-muted-foreground">{t('planner_issue_no_related')}</p>
                )}
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <IconPaperclip size={18} />
                  {t('planner_issue_attachments')}
                </CardTitle>
              </CardHeader>
              <CardContent>
                {data.attachments.length ? (
                  <div className="grid gap-3 sm:grid-cols-2">
                    {data.attachments.map((attachment) => (
                      <a
                        key={attachment.id}
                        href={attachment.url}
                        target="_blank"
                        rel="noreferrer"
                        className="overflow-hidden rounded-lg border transition hover:shadow-sm"
                      >
                        <img
                          src={attachment.url}
                          alt={attachment.filename}
                          className="aspect-video w-full object-cover"
                        />
                        <div className="p-3">
                          <p className="truncate text-sm font-medium">{attachment.filename}</p>
                          <p className="mt-1 text-xs text-muted-foreground">
                            {Math.ceil(attachment.sizeBytes / 1024)} KB
                          </p>
                        </div>
                      </a>
                    ))}
                  </div>
                ) : (
                  <div className="rounded-lg border border-dashed p-5 text-center text-sm text-muted-foreground">
                    {t('planner_issue_no_attachments')}
                  </div>
                )}
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <IconUsers size={18} />
                  {t('planner_issue_followers')}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">{t('planner_issue_following')}</p>
              </CardContent>
            </Card>
          </aside>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
