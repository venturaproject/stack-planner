import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { toast } from 'sonner'
import { IconPlus } from '@tabler/icons-react'
import { RoutePending } from '@/components/route-pending'
import { useI18n } from '@/i18n/context'
import { projectsApi, type CreatePlannerProject } from '@/services/projects-api'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Button } from '@/components/ui/button'
import { ProjectCard } from '@/components/project-card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

export default function ProjectsPage() {
  const { t } = useI18n()
  const projects = useQuery({ queryKey: ['planner', 'projects'], queryFn: projectsApi.list })
  const queryClient = useQueryClient()
  const [createOpen, setCreateOpen] = useState(false)
  const createProject = useMutation({
    mutationFn: (payload: CreatePlannerProject) => projectsApi.create(payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['planner', 'projects'] })
      setCreateOpen(false)
      toast.success(t('planner_projects_created'))
    },
    onError: () => toast.error(t('planner_projects_create_error')),
  })

  if (projects.isPending) return <RoutePending />
  if (projects.isError)
    return (
      <section className="p-8 text-sm text-destructive">{t('planner_projects_load_error')}</section>
    )

  return (
    <AuthenticatedLayout title={t('planner_projects_title')}>
      <Main className="space-y-6 p-6 md:p-8">
        <header className="flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="text-sm text-muted-foreground">{t('planner_projects_heading')}</p>
            <h1 className="mt-1 text-3xl font-semibold tracking-tight">
              {t('planner_projects_title')}
            </h1>
          </div>
          <div className="flex items-center gap-3">
            <div className="rounded-lg border bg-card px-4 py-2 text-sm text-muted-foreground">
              {t('planner_projects_active_count', { count: projects.data.meta.total })}
            </div>
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
              <DialogTrigger asChild>
                <Button>
                  <IconPlus className="mr-2 size-4" />
                  {t('planner_projects_new')}
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>{t('planner_projects_create')}</DialogTitle>
                  <DialogDescription>{t('planner_projects_create_description')}</DialogDescription>
                </DialogHeader>
                <form
                  className="space-y-4"
                  onSubmit={(event) => {
                    event.preventDefault()
                    const form = new FormData(event.currentTarget)
                    createProject.mutate({
                      name: String(form.get('name')).trim(),
                      key: String(form.get('key')).trim().toUpperCase(),
                      description: String(form.get('description')).trim(),
                    })
                  }}
                >
                  <div className="space-y-2">
                    <Label htmlFor="project-name">{t('planner_projects_name')}</Label>
                    <Input
                      id="project-name"
                      name="name"
                      required
                      maxLength={160}
                      placeholder={t('planner_projects_name_placeholder')}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="project-key">{t('planner_projects_key')}</Label>
                    <Input
                      id="project-key"
                      name="key"
                      required
                      minLength={2}
                      maxLength={8}
                      pattern="[A-Za-z][A-Za-z0-9]{1,7}"
                      placeholder={t('planner_projects_key_placeholder')}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="project-description">{t('planner_projects_description')}</Label>
                    <Textarea
                      id="project-description"
                      name="description"
                      rows={3}
                      placeholder={t('planner_projects_description_placeholder')}
                    />
                  </div>
                  <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => setCreateOpen(false)}>
                      {t('planner_projects_cancel')}
                    </Button>
                    <Button type="submit" disabled={createProject.isPending}>
                      {createProject.isPending
                        ? t('planner_projects_creating')
                        : t('planner_projects_create')}
                    </Button>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          </div>
        </header>

        <section className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {projects.data.data.map((project) => (
            <ProjectCard key={project.id} project={project} />
          ))}
        </section>
      </Main>
    </AuthenticatedLayout>
  )
}
