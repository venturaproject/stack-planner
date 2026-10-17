import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { format } from 'date-fns'
import { enUS } from 'date-fns/locale'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { DataTablePagination } from '@/components/data-table'
import { useI18n } from '@/i18n/context'
import { usersApi } from '@/services/users-api'

/** Shared by the user show and edit pages — one audit trail table for both. */
export function AuditLogTab({ userId }: { userId: string }) {
  const { t } = useI18n()
  const [page, setPage] = useState(1)
  const size = 20

  const ACTION_LABELS: Record<string, string> = {
    'user.login': t('user_audit_action_login'),
    'user.created': t('user_audit_action_created'),
    'user.updated': t('user_audit_action_updated'),
    'user.deactivated': t('user_audit_action_deactivated'),
  }

  const { data, isLoading } = useQuery({
    queryKey: ['audit-log', userId, page],
    queryFn: () => usersApi.auditLog(userId, { page, size }),
  })

  if (isLoading) {
    return <p className="text-sm text-muted-foreground py-8 text-center">{t('common_loading')}</p>
  }

  if (!data?.data.length) {
    return (
      <p className="text-sm text-muted-foreground py-8 text-center">{t('users_audit_log_empty')}</p>
    )
  }

  return (
    <div className="space-y-4">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>{t('common_date')}</TableHead>
            <TableHead>{t('users_audit_action_header')}</TableHead>
            <TableHead>{t('users_audit_resource_header')}</TableHead>
            <TableHead>{t('users_audit_ip_header')}</TableHead>
            <TableHead>{t('users_audit_hash_header')}</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {data.data.map((row) => (
            <TableRow key={row.id}>
              <TableCell className="text-xs text-muted-foreground whitespace-nowrap">
                {format(new Date(row.createdAt), 'MM/dd/yyyy HH:mm:ss', { locale: enUS })}
              </TableCell>
              <TableCell className="text-sm font-medium">
                {ACTION_LABELS[row.action] ?? row.action}
              </TableCell>
              <TableCell className="text-sm">
                {row.resourceType}
                {row.resourceId && (
                  <span className="ml-1 text-xs text-muted-foreground font-mono">
                    #{row.resourceId.slice(0, 8)}
                  </span>
                )}
              </TableCell>
              <TableCell className="text-xs font-mono text-muted-foreground">
                {row.ipAddress ?? '—'}
              </TableCell>
              <TableCell className="text-xs font-mono text-muted-foreground" title={row.hash}>
                {row.hash.slice(0, 8)}…{row.hash.slice(-8)}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>

      <DataTablePagination
        currentPage={data.page}
        lastPage={data.lastPage}
        perPage={data.perPage}
        total={data.total}
        selectedCount={0}
        onPageChange={setPage}
        onPerPageChange={() => {}}
      />
    </div>
  )
}
