import { useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import ProjectsPage from '@/pages/projects'
import ProjectWorkspace from '@/pages/projects/workspace'
import { RoutePending } from '@/components/route-pending'
import { projectsApi } from '@/services/projects-api'

export default function ProjectsRoute() {
  const { id, section } = useParams()
  const projectId = id ? Number(id) : null
  const project = useQuery({
    queryKey: ['planner', 'projects', projectId],
    queryFn: () => projectsApi.detail(projectId!),
    enabled: null !== projectId && Number.isInteger(projectId),
  })

  if (null === projectId) return <ProjectsPage />
  if (project.isPending) return <RoutePending />
  if (project.isError)
    return <div className="p-8 text-sm text-destructive">No se pudo encontrar el proyecto.</div>

  return <ProjectWorkspace project={project.data} initialTab={section} />
}
