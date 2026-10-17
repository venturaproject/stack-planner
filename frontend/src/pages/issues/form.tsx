import { useEffect, useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { IconArrowLeft, IconCheck, IconUserCircle } from '@tabler/icons-react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { RoutePending } from '@/components/route-pending'
import { useI18n } from '@/i18n/context'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { DatePicker } from '@/components/ui/date-picker'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { RichTextEditor } from '@/components/ui/rich-text-editor'
import { ScrollArea } from '@/components/ui/scroll-area'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  issuesApi,
  type CreatePlannerIssue,
  type PlannerIssue,
  type PlannerIssueDetail,
} from '@/services/issues-api'
import { planningApi } from '@/services/planning-api'
import { projectsApi, type PlannerProject } from '@/services/projects-api'

export interface IssueFormValues {
  title: string
  type: 'story' | 'task' | 'bug' | 'subtask'
  priority: 'critical' | 'high' | 'medium' | 'low' | 'urgent'
  description: string
  epicId: number | null
  sprintId: number | null
  assigneeNames: string[]
  storyPoints: number | null
  dueAt: string | null
}

const emptyValues: IssueFormValues = {
  title: '',
  type: 'task',
  priority: 'medium',
  description: '',
  epicId: null,
  sprintId: null,
  assigneeNames: [],
  storyPoints: null,
  dueAt: null,
}

export default function IssueFormPage({
  projectId,
  issueId,
}: {
  projectId?: number
  issueId?: number
}) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [values, setValues] = useState<IssueFormValues>(emptyValues)
  const [assigneesOpen, setAssigneesOpen] = useState(false)
  const [editorRevision, setEditorRevision] = useState(0)
  const editing = undefined !== issueId
  const exampleTemplates = [
    {
      id: 'bug-with-reproduction',
      name: t('planner_issue_template_bug'),
      values: {
        title: t('planner_issue_template_bug_title'),
        type: 'bug' as const,
        priority: 'high' as const,
        description: `<p><strong>${t('planner_issue_template_problem')}</strong></p><p>${t('planner_issue_template_problem_description')}</p><p><strong>${t('planner_issue_template_steps')}</strong></p><ol><li>${t('planner_issue_template_step_one')}</li><li>${t('planner_issue_template_step_two')}</li><li>${t('planner_issue_template_step_three')}</li></ol><p><strong>${t('planner_issue_template_expected')}</strong></p><p>${t('planner_issue_template_expected_description')}</p><p><strong>${t('planner_issue_template_sample_image')}</strong></p><p><img src="/demo/attachments/DEMO-4/lKs5G8TylIB9d9THDVWENHddoKEKXLEoOhj2mbWh.png" alt="${t('planner_issue_template_image_alt')}" /></p>`,
      },
    },
    {
      id: 'user-story',
      name: t('planner_issue_template_story'),
      values: {
        title: t('planner_issue_template_story_title'),
        type: 'story' as const,
        priority: 'medium' as const,
        description: `<p><strong>${t('planner_issue_template_as_a')}</strong> ${t('planner_issue_template_user')}</p><p><strong>${t('planner_issue_template_i_want')}</strong> ${t('planner_issue_template_complete')}</p><p><strong>${t('planner_issue_template_so_that')}</strong> ${t('planner_issue_template_improve')}</p><p><strong>${t('planner_issue_template_acceptance')}</strong></p><ul><li>${t('planner_issue_template_acceptance_one')}</li><li>${t('planner_issue_template_acceptance_two')}</li></ul>`,
      },
    },
  ]

  const issue = useQuery({
    queryKey: ['planner', 'issues', 'detail', issueId],
    queryFn: () => issuesApi.detail(issueId!),
    enabled: editing,
  })
  const resolvedProjectId = issue.data?.projectId ?? projectId
  const project = useQuery({
    queryKey: ['planner', 'projects', resolvedProjectId],
    queryFn: () => projectsApi.detail(resolvedProjectId!),
    enabled: undefined !== resolvedProjectId,
  })
  const epics = useQuery({
    queryKey: ['planner', 'epics', resolvedProjectId],
    queryFn: () => planningApi.epics(resolvedProjectId!),
    enabled: undefined !== resolvedProjectId,
  })
  const sprints = useQuery({
    queryKey: ['planner', 'sprints', resolvedProjectId],
    queryFn: () => planningApi.sprints(resolvedProjectId!),
    enabled: undefined !== resolvedProjectId,
  })

  useEffect(() => {
    if (!issue.data) return
    setValues({
      title: issue.data.title,
      type: issue.data.type,
      priority: issue.data.priority,
      description: issue.data.description ?? '',
      epicId: issue.data.epicId,
      sprintId: issue.data.sprintId,
      assigneeNames: issue.data.assigneeNames,
      storyPoints: issue.data.storyPoints,
      dueAt: issue.data.dueAt?.slice(0, 10) ?? null,
    })
  }, [issue.data])

  const save = useMutation<PlannerIssue | PlannerIssueDetail, Error, IssueFormValues>({
    mutationFn: async (payload: IssueFormValues) =>
      editing
        ? issuesApi.update(issueId!, payload)
        : issuesApi.create({ projectId: projectId!, ...payload } satisfies CreatePlannerIssue),
    onSuccess: async (saved) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['planner', 'issues'] }),
        queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] }),
      ])
      toast.success(editing ? t('planner_issue_updated') : t('planner_issue_created'))
      navigate(`/admin/issues/${saved.id}`)
    },
    onError: () =>
      toast.error(editing ? t('planner_issue_update_error') : t('planner_issue_create_error')),
  })

  if ((editing && issue.isPending) || project.isPending || epics.isPending || sprints.isPending)
    return <RoutePending />
  if (
    issue.isError ||
    project.isError ||
    epics.isError ||
    sprints.isError ||
    !project.data ||
    !epics.data ||
    !sprints.data
  ) {
    return (
      <AuthenticatedLayout title={t('planner_issues_title')}>
        <Main className="p-6">
          <p className="text-sm text-destructive">{t('planner_issue_detail_load_error')}</p>
        </Main>
      </AuthenticatedLayout>
    )
  }

  const data = issue.data
  const projectData: PlannerProject = project.data
  const cancelPath = data ? `/admin/issues/${data.id}` : `/admin/projects/${projectData.id}/board`
  const update = <K extends keyof IssueFormValues>(key: K, value: IssueFormValues[K]) =>
    setValues((previous) => ({ ...previous, [key]: value }))
  const applyTemplate = (templateId: string) => {
    const template = exampleTemplates.find((item) => item.id === templateId)
    if (!template) return
    setValues((previous) => ({ ...previous, ...template.values }))
    setEditorRevision((revision) => revision + 1)
  }

  const submit = (event: FormEvent) => {
    event.preventDefault()
    if (!values.title.trim()) return
    save.mutate({ ...values, title: values.title.trim() })
  }

  return (
    <AuthenticatedLayout
      title={
        editing
          ? `${t('planner_issue_form_edit')} ${data?.key ?? ''}`
          : t('planner_issue_form_create')
      }
    >
      <Main className="space-y-5 p-6 md:p-8">
        <Link
          to={cancelPath}
          className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
          <IconArrowLeft size={16} />
          {t('planner_issue_form_back_to', {
            name: editing ? (data?.key ?? t('planner_issue_form_edit')) : projectData.name,
          })}
        </Link>
        <header className="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="text-sm text-muted-foreground">
              {projectData.name}
              {data ? ` / ${data.key}` : ''}
            </p>
            <h1 className="mt-1 text-3xl font-semibold tracking-tight">
              {editing ? t('planner_issue_form_edit') : t('planner_issue_form_new_heading')}
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
              {editing
                ? t('planner_issue_form_edit_description')
                : t('planner_issue_form_create_description')}
            </p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link to={cancelPath}>{t('planner_issue_cancel')}</Link>
            </Button>
            <Button type="submit" form="issue-form" disabled={save.isPending}>
              {save.isPending
                ? t('planner_issue_form_saving')
                : editing
                  ? t('planner_issue_form_save')
                  : t('planner_issue_form_create')}
            </Button>
          </div>
        </header>

        <form
          id="issue-form"
          onSubmit={submit}
          className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_340px]"
        >
          <Card>
            <CardHeader>
              <CardTitle>{t('planner_issue_form_title')}</CardTitle>
              <CardDescription>{t('planner_issue_form_description')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {!editing && (
                <div className="space-y-2">
                  <Label>{t('planner_issue_template')}</Label>
                  <Select onValueChange={applyTemplate}>
                    <SelectTrigger>
                      <SelectValue placeholder={t('planner_issue_no_template')} />
                    </SelectTrigger>
                    <SelectContent>
                      {exampleTemplates.map((template) => (
                        <SelectItem key={template.id} value={template.id}>
                          {template.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
              <div className="space-y-2">
                <Label htmlFor="issue-title">{t('planner_issue_title')} *</Label>
                <Input
                  id="issue-title"
                  value={values.title}
                  onChange={(event) => update('title', event.target.value)}
                  placeholder={t('planner_issue_title_placeholder')}
                  required
                  autoFocus
                />
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>{t('planner_issue_type')}</Label>
                  <Select
                    value={values.type}
                    onValueChange={(value) => update('type', value as IssueFormValues['type'])}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="story">{t('planner_type_story')}</SelectItem>
                      <SelectItem value="task">{t('planner_type_task')}</SelectItem>
                      <SelectItem value="bug">{t('planner_type_bug')}</SelectItem>
                      <SelectItem value="subtask">{t('planner_type_subtask')}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>{t('planner_issue_priority')}</Label>
                  <Select
                    value={values.priority}
                    onValueChange={(value) =>
                      update('priority', value as IssueFormValues['priority'])
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="critical">{t('planner_priority_critical')}</SelectItem>
                      <SelectItem value="urgent">{t('planner_priority_urgent')}</SelectItem>
                      <SelectItem value="high">{t('planner_priority_high')}</SelectItem>
                      <SelectItem value="medium">{t('planner_priority_medium')}</SelectItem>
                      <SelectItem value="low">{t('planner_priority_low')}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="space-y-2">
                <Label>{t('planner_issue_description')}</Label>
                <RichTextEditor
                  key={`${data?.id ?? 'new'}-${editorRevision}`}
                  content={values.description}
                  onChange={(html) => update('description', html)}
                  placeholder={t('planner_issue_description_placeholder')}
                  minHeight="min-h-[280px]"
                  issueId={data?.id}
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>{t('planner_issue_properties')}</CardTitle>
              <CardDescription>{t('planner_issue_planning_people')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-5">
              <div className="space-y-2">
                <Label>{t('planner_issue_epic')}</Label>
                <Select
                  value={values.epicId?.toString() ?? 'none'}
                  onValueChange={(value) =>
                    update('epicId', value === 'none' ? null : Number(value))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('planner_issue_no_epic')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">{t('planner_issue_no_epic')}</SelectItem>
                    {epics.data.map((epic) => (
                      <SelectItem key={epic.id} value={String(epic.id)}>
                        {epic.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>{t('planner_issue_sprint')}</Label>
                <Select
                  value={values.sprintId?.toString() ?? 'none'}
                  onValueChange={(value) =>
                    update('sprintId', value === 'none' ? null : Number(value))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('planner_issue_no_sprint')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">{t('planner_issue_no_sprint')}</SelectItem>
                    {sprints.data.map((sprint) => (
                      <SelectItem key={sprint.id} value={String(sprint.id)}>
                        {sprint.name}
                        {sprint.status === 'active' ? ' ⚡' : ''}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>{t('planner_issue_assignees')}</Label>
                <Popover open={assigneesOpen} onOpenChange={setAssigneesOpen}>
                  <PopoverTrigger asChild>
                    <Button type="button" variant="outline" className="w-full justify-start gap-2">
                      <IconUserCircle size={16} />
                      {values.assigneeNames.length
                        ? values.assigneeNames.join(', ')
                        : t('planner_issue_unassigned')}
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent
                    className="w-[var(--radix-popover-trigger-width)] p-0"
                    align="start"
                  >
                    <ScrollArea className="max-h-56 p-1">
                      {projectData.members.map((member) => {
                        const selected = values.assigneeNames.includes(member.name)
                        return (
                          <button
                            key={member.name}
                            type="button"
                            onClick={() =>
                              update(
                                'assigneeNames',
                                selected
                                  ? values.assigneeNames.filter((name) => name !== member.name)
                                  : [...values.assigneeNames, member.name],
                              )
                            }
                            className="flex w-full items-center gap-2 rounded px-2 py-2 text-left text-sm hover:bg-muted"
                          >
                            <Avatar className="size-6">
                              <AvatarImage src={member.avatar ?? undefined} alt={member.name} />
                              <AvatarFallback className="text-[9px]">
                                {member.name
                                  .split(' ')
                                  .map((part) => part[0])
                                  .join('')
                                  .slice(0, 2)}
                              </AvatarFallback>
                            </Avatar>
                            <span className="flex-1">{member.name}</span>
                            {selected && <IconCheck size={15} />}
                          </button>
                        )
                      })}
                    </ScrollArea>
                  </PopoverContent>
                </Popover>
              </div>
              <div className="space-y-2">
                <Label>{t('planner_issue_story_points')}</Label>
                <Select
                  value={values.storyPoints?.toString() ?? 'none'}
                  onValueChange={(value) =>
                    update('storyPoints', value === 'none' ? null : Number(value))
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('planner_issue_unestimated')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">{t('planner_issue_unestimated')}</SelectItem>
                    {[1, 2, 3, 5, 8, 13].map((points) => (
                      <SelectItem key={points} value={String(points)}>
                        {points}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>{t('planner_issue_due_date')}</Label>
                <DatePicker
                  value={values.dueAt}
                  onChange={(value) => update('dueAt', value ?? null)}
                />
              </div>
            </CardContent>
          </Card>
        </form>
      </Main>
    </AuthenticatedLayout>
  )
}
