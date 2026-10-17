import { useParams } from 'react-router-dom'
import IssueDetailPage from '@/pages/issues/show'

export default function IssueDetailRoute() {
  const { id } = useParams()
  return <IssueDetailPage issueId={Number(id)} />
}
