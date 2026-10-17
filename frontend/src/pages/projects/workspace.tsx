import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { toast } from 'sonner'
import { Link } from 'react-router-dom'
import {
  DndContext,
  PointerSensor,
  KeyboardSensor,
  closestCorners,
  useDroppable,
  useSensor,
  useSensors,
  type DragEndEvent,
} from '@dnd-kit/core'
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import {
  IconArrowLeft,
  IconCalendar,
  IconChartBar,
  IconLayoutKanban,
  IconList,
  IconTargetArrow,
  IconRocket,
  IconPlus,
  IconSettings,
  IconUsers,
} from '@tabler/icons-react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { useI18n } from '@/i18n/context'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { RoutePending } from '@/components/route-pending'
import type { PlannerProject } from '@/services/projects-api'
import { planningApi } from '@/services/planning-api'
import { issuesApi, type PlannerIssue } from '@/services/issues-api'
import { Button } from '@/components/ui/button'
import { AssigneeAvatarStack } from '@/components/assignee-avatar-stack'
import { GanttChart } from './components/gantt-chart'
import { useAuthStore } from '@/lib/auth'
import { projectsApi } from '@/services/projects-api'
import { Input } from '@/components/ui/input'

const statusDefinitions = [
  { key: 'todo', labelKey: 'planner_status_todo' },
  { key: 'in_progress', labelKey: 'planner_status_in_progress' },
  { key: 'in_review', labelKey: 'planner_status_in_review' },
  { key: 'done', labelKey: 'planner_status_done' },
] as const

interface BoardStatus {
  key: PlannerIssue['status']
  label: string
}

const priorityClass: Record<string, string> = {
  critical: 'border-rose-200 bg-rose-50 text-rose-700',
  urgent: 'border-rose-200 bg-rose-50 text-rose-700',
  high: 'border-orange-200 bg-orange-50 text-orange-700',
  medium: 'border-blue-200 bg-blue-50 text-blue-700',
  low: 'border-zinc-200 bg-zinc-50 text-zinc-600',
}

function SortableIssueCard({ issue }: { issue: PlannerIssue }) {
  const { t, currentLang } = useI18n()
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: issue.id,
    data: { status: issue.status },
  })

  return (
    <Link
      ref={setNodeRef}
      to={`/admin/issues/${issue.id}`}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      {...attributes}
      {...listeners}
      className={`block rounded-lg border bg-card p-4 shadow-sm transition hover:shadow-md ${isDragging ? 'z-10 opacity-50' : ''}`}
    >
      <p className="text-xs font-medium text-muted-foreground">{issue.key}</p>
      <h2 className="mt-2 text-sm font-medium leading-5">{issue.title}</h2>
      <footer className="mt-4 flex items-center justify-between">
        <Badge variant="outline" className={priorityClass[issue.priority]}>
          {t(`planner_priority_${issue.priority}`)}
        </Badge>
        <span className="flex items-center gap-2">
          {null !== issue.storyPoints && (
            <span className="rounded bg-muted px-1.5 py-0.5 text-xs font-medium">
              {t('planner_issue_points_label', { count: issue.storyPoints })}
            </span>
          )}
          <AssigneeAvatarStack names={issue.assigneeNames} />
        </span>
        {issue.dueAt && (
          <span className="text-xs text-muted-foreground">
            {new Date(issue.dueAt).toLocaleDateString(currentLang === 'es' ? 'es-ES' : 'en-US')}
          </span>
        )}
      </footer>
    </Link>
  )
}

function BoardColumn({ status, group }: { status: BoardStatus; group: PlannerIssue[] }) {
  const { setNodeRef, isOver } = useDroppable({ id: status.key, data: { status: status.key } })
  return (
    <section
      ref={setNodeRef}
      className={`min-h-56 rounded-xl bg-muted/50 p-3 transition-colors ${isOver ? 'bg-primary/10' : ''}`}
    >
      <header className="flex items-center gap-2 px-2 py-2 text-sm font-medium">
        <span>{status.label}</span>
        <Badge variant="secondary" className="ml-auto">
          {group.length}
        </Badge>
      </header>
      <SortableContext
        items={group.map((issue) => issue.id)}
        strategy={verticalListSortingStrategy}
      >
        <div className="mt-2 min-h-32 space-y-3">
          {group.map((issue) => (
            <SortableIssueCard key={issue.id} issue={issue} />
          ))}
        </div>
      </SortableContext>
    </section>
  )
}

export default function ProjectWorkspace({
  project,
  initialTab = 'board',
}: {
  project: PlannerProject
  initialTab?: string
}) {
  const { t, currentLang } = useI18n()
  const queryClient = useQueryClient()
  const isAdmin = useAuthStore((state) => state.roles.includes('ADMIN'))
  const [memberEmail, setMemberEmail] = useState('')
  const statuses = statusDefinitions.map((status) => ({
    key: status.key,
    label: t(status.labelKey),
  }))
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  )
  const issues = useQuery({
    queryKey: ['planner', 'issues', project.id],
    queryFn: () => issuesApi.list(project.id),
  })
  const epics = useQuery({
    queryKey: ['planner', 'epics', project.id],
    queryFn: () => planningApi.epics(project.id),
  })
  const sprints = useQuery({
    queryKey: ['planner', 'sprints', project.id],
    queryFn: () => planningApi.sprints(project.id),
  })
  const members = useQuery({
    queryKey: ['planner', 'project-members', project.id],
    queryFn: () => projectsApi.members(project.id),
    enabled: isAdmin,
  })
  const addMember = useMutation({
    mutationFn: () => projectsApi.addMember(project.id, memberEmail.trim()),
    onSuccess: async () => {
      setMemberEmail('')
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['planner', 'project-members', project.id] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] }),
      ])
      toast.success(t('planner_project_member_added'))
    },
    onError: () => toast.error(t('planner_project_member_error')),
  })
  const removeMember = useMutation({
    mutationFn: (userId: string) => projectsApi.removeMember(project.id, userId),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['planner', 'project-members', project.id] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] }),
      ])
    },
    onError: () => toast.error(t('planner_project_member_error')),
  })
  const updateStatus = useMutation({
    mutationFn: ({ id, status }: { id: number; status: PlannerIssue['status'] }) =>
      issuesApi.updateStatus(id, status),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['planner', 'issues'] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] }),
      ])
    },
    onError: () => toast.error(t('planner_issue_status_update_error')),
  })

  if (issues.isPending || epics.isPending || sprints.isPending) return <RoutePending />
  if (issues.isError || epics.isError || sprints.isError)
    return (
      <AuthenticatedLayout title={project.name}>
        <Main>
          <p className="text-sm text-destructive">{t('planner_projects_load_error')}</p>
        </Main>
      </AuthenticatedLayout>
    )

  const items = issues.data.data
  const done = items.filter((issue) => issue.status === 'done').length
  const progress = items.length ? Math.round((done / items.length) * 100) : 0
  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    if (!over) return
    const issue = items.find((item) => item.id === active.id)
    const target = items.find((item) => item.id === over.id)
    const nextStatus = (over.data.current?.status ??
      target?.status ??
      String(over.id)) as PlannerIssue['status']
    if (
      issue &&
      statuses.some((status) => status.key === nextStatus) &&
      issue.status !== nextStatus
    ) {
      updateStatus.mutate({ id: issue.id, status: nextStatus })
    }
  }

  return (
    <AuthenticatedLayout title={project.name}>
      <Main className="space-y-5 p-6 md:p-8">
        <Link
          to="/admin/projects"
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <IconArrowLeft size={16} /> {t('planner_projects_title')}
        </Link>

        <section className="rounded-xl border bg-card shadow-sm">
          <div className="flex flex-wrap items-center gap-4 p-5">
            <div
              className="grid size-14 shrink-0 place-items-center rounded-xl text-base font-bold text-white shadow-sm"
              style={{ backgroundColor: project.color }}
            >
              {project.key}
            </div>
            <div className="min-w-56 flex-1">
              <div className="flex flex-wrap items-center gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">{project.name}</h1>
                <Badge variant="secondary">{t('planner_project_active')}</Badge>
              </div>
              <p className="mt-1 text-sm text-muted-foreground">{project.description}</p>
            </div>
          </div>
          <div className="grid gap-4 border-t p-5 sm:grid-cols-3">
            <div>
              <p className="text-xs text-muted-foreground">{t('planner_project_issues')}</p>
              <p className="mt-1 text-2xl font-semibold">{items.length}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">{t('planner_project_completed')}</p>
              <p className="mt-1 text-2xl font-semibold">{progress}%</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">{t('planner_project_status')}</p>
              <p className="mt-1 text-2xl font-semibold">{t('planner_project_active')}</p>
            </div>
          </div>
        </section>

        <Tabs key={initialTab} defaultValue={initialTab} className="space-y-5">
          <TabsList className="h-auto flex-wrap justify-start">
            <TabsTrigger value="board" className="gap-2">
              <IconLayoutKanban size={16} />
              {t('planner_project_board')}
            </TabsTrigger>
            <TabsTrigger value="backlog" className="gap-2">
              <IconList size={16} />
              {t('planner_project_backlog')}
            </TabsTrigger>
            <TabsTrigger value="gantt" className="gap-2">
              <IconCalendar size={16} />
              {t('planner_project_timeline')}
            </TabsTrigger>
            <TabsTrigger value="epics" className="gap-2">
              <IconTargetArrow size={16} />
              {t('planner_project_epics')}
            </TabsTrigger>
            <TabsTrigger value="sprints" className="gap-2">
              <IconRocket size={16} />
              {t('planner_project_sprints')}
            </TabsTrigger>
            <TabsTrigger value="reports" className="gap-2">
              <IconChartBar size={16} />
              {t('planner_project_reports')}
            </TabsTrigger>
            <TabsTrigger value="settings" className="gap-2">
              <IconSettings size={16} />
              {t('planner_project_settings')}
            </TabsTrigger>
          </TabsList>

          <TabsContent value="board" className="space-y-4">
            <div className="flex justify-end">
              <Button asChild>
                <Link to={`/admin/projects/${project.id}/issues/new`}>
                  <IconPlus className="mr-2 size-4" />
                  {t('planner_issue_form_create')}
                </Link>
              </Button>
            </div>
            <DndContext
              sensors={sensors}
              collisionDetection={closestCorners}
              onDragEnd={handleDragEnd}
            >
              <div className="grid gap-4 xl:grid-cols-4">
                {statuses.map((status) => {
                  const group = items.filter((issue) => issue.status === status.key)
                  return <BoardColumn key={status.key} status={status} group={group} />
                })}
              </div>
            </DndContext>
          </TabsContent>

          <TabsContent value="backlog">
            <Card>
              <CardHeader>
                <CardTitle>{t('planner_project_backlog_title')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {items
                  .filter((issue) => issue.status !== 'done')
                  .map((issue) => (
                    <div
                      key={issue.id}
                      className="flex flex-wrap items-center gap-3 rounded-lg border p-3"
                    >
                      <span className="w-24 text-xs font-medium text-muted-foreground">
                        {issue.key}
                      </span>
                      <span className="min-w-48 flex-1 text-sm font-medium">{issue.title}</span>
                      <Badge variant="outline" className={priorityClass[issue.priority]}>
                        {t(`planner_priority_${issue.priority}`)}
                      </Badge>
                      <Badge variant="secondary">
                        {statuses.find((status) => status.key === issue.status)?.label}
                      </Badge>
                    </div>
                  ))}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="gantt">
            <GanttChart issues={items} epics={epics.data} sprints={sprints.data} />
          </TabsContent>

          <TabsContent value="epics">
            <Card>
              <CardHeader>
                <CardTitle>{t('planner_project_epics_title')}</CardTitle>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                {epics.data.map((epic) => (
                  <article key={epic.id} className="rounded-xl border p-4">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="text-xs font-medium text-muted-foreground">{epic.key}</p>
                        <h3 className="mt-1 font-semibold">{epic.name}</h3>
                      </div>
                      <Badge variant="secondary">{t(`planner_epic_${epic.status}`)}</Badge>
                    </div>
                    <p className="mt-3 text-sm text-muted-foreground">{epic.description}</p>
                    <div className="mt-4 flex items-center gap-3">
                      <div className="h-2 flex-1 rounded-full bg-muted">
                        <div
                          className="h-full rounded-full bg-primary"
                          style={{ width: `${epic.progress}%` }}
                        />
                      </div>
                      <span className="text-xs font-medium">{epic.progress}%</span>
                    </div>
                  </article>
                ))}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="sprints" className="space-y-4">
            {sprints.data.map((sprint) => (
              <Card key={sprint.id}>
                <CardContent className="flex flex-wrap items-center gap-4 p-5">
                  <div
                    className={`grid size-11 place-items-center rounded-lg ${sprint.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}
                  >
                    <IconRocket size={20} />
                  </div>
                  <div className="min-w-48 flex-1">
                    <div className="flex items-center gap-2">
                      <h3 className="font-semibold">{sprint.name}</h3>
                      <Badge variant={sprint.status === 'active' ? 'default' : 'secondary'}>
                        {sprint.status === 'active'
                          ? t('planner_sprint_active')
                          : t('planner_sprint_planned')}
                      </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">{sprint.goal}</p>
                  </div>
                  <div className="text-sm text-muted-foreground">
                    {new Date(sprint.startAt).toLocaleDateString(
                      currentLang === 'es' ? 'es-ES' : 'en-US',
                    )}{' '}
                    —{' '}
                    {new Date(sprint.endAt).toLocaleDateString(
                      currentLang === 'es' ? 'es-ES' : 'en-US',
                    )}
                  </div>
                </CardContent>
              </Card>
            ))}
          </TabsContent>

          <TabsContent value="reports" className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>{t('planner_project_progress_title')}</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-4xl font-semibold">{progress}%</div>
                <div className="mt-4 h-2 rounded-full bg-muted">
                  <div
                    className="h-full rounded-full bg-primary"
                    style={{ width: `${progress}%` }}
                  />
                </div>
                <p className="mt-2 text-sm text-muted-foreground">
                  {t('planner_project_completed_issue_count', {
                    completed: done,
                    total: items.length,
                  })}
                </p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>{t('planner_project_issues_by_status')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {statuses.map((status) => {
                  const count = items.filter((issue) => issue.status === status.key).length
                  return (
                    <div key={status.key} className="flex items-center justify-between text-sm">
                      <span>{status.label}</span>
                      <span className="font-semibold">{count}</span>
                    </div>
                  )
                })}
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="settings" className="space-y-4">
            <div className="grid gap-4 lg:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_general_settings')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  <div className="flex justify-between gap-3">
                    <span className="text-muted-foreground">{t('planner_projects_name')}</span>
                    <span className="font-medium">{project.name}</span>
                  </div>
                  <div className="flex justify-between gap-3">
                    <span className="text-muted-foreground">{t('planner_projects_key')}</span>
                    <span className="font-medium">{project.key}</span>
                  </div>
                  <div className="flex justify-between gap-3">
                    <span className="text-muted-foreground">{t('planner_project_status')}</span>
                    <Badge variant="secondary">{t('planner_project_active')}</Badge>
                  </div>
                  <div className="flex justify-between gap-3">
                    <span className="text-muted-foreground">{t('planner_project_members')}</span>
                    <span className="inline-flex items-center gap-1 font-medium">
                      <IconUsers size={15} />
                      {project.members.length}
                    </span>
                  </div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_workflow')}</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                  {statuses.map((status, index) => (
                    <div key={status.key} className="flex items-center gap-2">
                      <Badge variant="outline">{status.label}</Badge>
                      {index < statuses.length - 1 && (
                        <span className="text-muted-foreground">→</span>
                      )}
                    </div>
                  ))}
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_types_priorities')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                      {t('planner_project_issue_types')}
                    </p>
                    <div className="flex flex-wrap gap-2">
                      <Badge variant="secondary">{t('planner_type_story')}</Badge>
                      <Badge variant="secondary">{t('planner_type_task')}</Badge>
                      <Badge variant="secondary">{t('planner_type_bug')}</Badge>
                    </div>
                  </div>
                  <div>
                    <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                      {t('planner_project_priorities')}
                    </p>
                    <div className="flex flex-wrap gap-2">
                      <Badge variant="outline" className={priorityClass.critical}>
                        {t('planner_priority_critical')}
                      </Badge>
                      <Badge variant="outline" className={priorityClass.high}>
                        {t('planner_priority_high')}
                      </Badge>
                      <Badge variant="outline" className={priorityClass.medium}>
                        {t('planner_priority_medium')}
                      </Badge>
                      <Badge variant="outline" className={priorityClass.low}>
                        {t('planner_priority_low')}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_estimation_templates')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                      {t('planner_project_story_point_scale')}
                    </p>
                    <div className="flex gap-2">
                      {[1, 2, 3, 5, 8, 13].map((point) => (
                        <Badge key={point} variant="secondary">
                          {point}
                        </Badge>
                      ))}
                    </div>
                  </div>
                  <p className="text-sm text-muted-foreground">
                    {t('planner_project_templates_description')}
                  </p>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_custom_fields')}</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground">
                    {t('planner_project_custom_fields_description')}
                  </p>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_labels_components')}</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground">
                    {t('planner_project_labels_components_description')}
                  </p>
                </CardContent>
              </Card>
            </div>
            {isAdmin && (
              <Card>
                <CardHeader>
                  <CardTitle>{t('planner_project_members')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <form
                    className="flex flex-wrap gap-2"
                    onSubmit={(event) => {
                      event.preventDefault()
                      if (memberEmail.trim()) addMember.mutate()
                    }}
                  >
                    <Input
                      type="email"
                      value={memberEmail}
                      onChange={(event) => setMemberEmail(event.target.value)}
                      placeholder={t('planner_project_member_email')}
                      aria-label={t('planner_project_member_email')}
                      required
                      className="min-w-56 flex-1"
                    />
                    <Button type="submit" disabled={addMember.isPending || !memberEmail.trim()}>
                      {t('planner_project_member_add')}
                    </Button>
                  </form>
                  {members.isError ? (
                    <p className="text-sm text-destructive">{t('planner_project_member_error')}</p>
                  ) : (
                    <ul className="divide-y">
                      {(members.data ?? []).map((member) => (
                        <li
                          key={member.userId}
                          className="flex items-center justify-between gap-3 py-2"
                        >
                          <div className="min-w-0">
                            <p className="truncate text-sm font-medium">{member.name}</p>
                            <p className="text-xs text-muted-foreground">{member.role}</p>
                          </div>
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            disabled={removeMember.isPending}
                            onClick={() => removeMember.mutate(member.userId)}
                          >
                            {t('planner_project_member_remove')}
                          </Button>
                        </li>
                      ))}
                    </ul>
                  )}
                </CardContent>
              </Card>
            )}
          </TabsContent>
        </Tabs>
      </Main>
    </AuthenticatedLayout>
  )
}
