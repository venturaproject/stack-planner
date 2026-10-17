import { useParams } from 'react-router-dom'
import IssueFormPage from '@/pages/issues/form'

export default function IssueFormRoute() {
  const { projectId, id } = useParams()

  return (
    <IssueFormPage
      projectId={projectId ? Number(projectId) : undefined}
      issueId={id ? Number(id) : undefined}
    />
  )
}
