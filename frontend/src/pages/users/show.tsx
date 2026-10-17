import { useState } from 'react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ConfirmDialog } from '@/components/dialogs/confirm-dialog'
import { ChevronLeft, Pencil, Shield, ClipboardList, UserCog } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { format } from 'date-fns'
import { enUS } from 'date-fns/locale'
import { PageProps } from '@/types'
import { callTypes } from './data/data'
import type { UserStatus } from './data/schema'
import { pathFor } from '@/lib/app-routes'
import { AuditLogTab } from './components/audit-log-tab'
import { useAuthStore } from '@/lib/auth'
import { useI18n } from '@/i18n/context'

interface RawRole {
  id: string | number
  name: string
}

interface UserData {
  id: string
  // normalized
  name?: string
  username?: string | null
  status?: string
  avatar?: string | null
  lastActivity?: string | null
  createdAt?: string | null
  // raw API fields
  email: string
  firstName?: string
  lastName?: string
  first_name?: string
  last_name?: string
  active?: boolean
  avatarUrl?: string | null
  avatar_url?: string | null
  roles?: Array<RawRole | string>
  created_at?: string | null
  updated_at?: string | null
}

interface ShowUserPageProps extends PageProps {
  user: UserData
}

function resolveUser(user: UserData) {
  const displayName =
    user.name ||
    `${user.firstName ?? user.first_name ?? ''} ${user.lastName ?? user.last_name ?? ''}`.trim() ||
    user.email

  const status = user.status ?? (user.active !== false ? 'active' : 'inactive')

  const avatar = user.avatar ?? user.avatarUrl ?? user.avatar_url ?? null

  const lastActivity = user.lastActivity ?? user.updated_at ?? null

  const roles: string[] = (user.roles ?? []).map((r) => (typeof r === 'string' ? r : r.name))

  return { displayName, status, avatar, lastActivity, roles }
}

function getInitials(name?: string | null): string {
  if (!name?.trim()) return 'U'
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((p) => p[0] ?? '')
    .join('')
    .toUpperCase()
}

export default function ShowUser({ user }: ShowUserPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const currentUserId = useAuthStore((s) => s.user?.id)
  const impersonate = useAuthStore((s) => s.impersonate)
  const [impersonateOpen, setImpersonateOpen] = useState(false)
  const [isImpersonating, setIsImpersonating] = useState(false)

  const { displayName, status, avatar, lastActivity, roles } = resolveUser(user)
  const statusCls = callTypes.get(status as UserStatus) ?? ''
  const isSelf = String(currentUserId) === String(user.id)

  const handleImpersonate = async () => {
    setIsImpersonating(true)
    try {
      await impersonate(user.id)
      toast.success(t('user_impersonate_success', { name: displayName }))
      navigate(pathFor('dashboard'))
    } catch {
      toast.error(t('user_impersonate_error'))
    } finally {
      setIsImpersonating(false)
      setImpersonateOpen(false)
    }
  }

  const statusLabels: Record<string, string> = {
    active: t('user_status_active'),
    inactive: t('user_status_inactive'),
    suspended: t('user_status_suspended'),
  }

  return (
    <AuthenticatedLayout title={displayName}>
      <Main>
        <div className="grid flex-1 items-start gap-6 md:gap-8 max-w-5xl mx-auto">
          {/* ── Header ── */}
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              onClick={() => navigate(pathFor('admin.users.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="flex-1">
              <h2 className="text-2xl font-bold tracking-tight">{displayName}</h2>
              <p className="text-muted-foreground text-sm">{user.email}</p>
            </div>
            {!isSelf && status === 'active' && (
              <Button variant="outline" onClick={() => setImpersonateOpen(true)}>
                <UserCog className="mr-2 h-4 w-4" />
                {t('user_impersonate_action')}
              </Button>
            )}
            <Button onClick={() => navigate(pathFor('admin.users.edit', user.id))}>
              <Pencil className="mr-2 h-4 w-4" />
              {t('common_edit')}
            </Button>
          </div>

          <ConfirmDialog
            open={impersonateOpen}
            onOpenChange={setImpersonateOpen}
            title={t('user_impersonate_confirm_title', { name: displayName })}
            desc={t('user_impersonate_confirm_description')}
            confirmText={t('user_impersonate_action')}
            isLoading={isImpersonating}
            handleConfirm={handleImpersonate}
          />

          {/* ── Profile (always visible) ── */}
          <Card>
            <CardHeader>
              <CardTitle>{t('users_show_profile_title')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-start gap-6">
                <Avatar className="h-20 w-20 shrink-0">
                  {avatar && <AvatarImage src={avatar} alt={displayName} />}
                  <AvatarFallback className="bg-primary/10 text-primary text-2xl font-bold">
                    {getInitials(displayName)}
                  </AvatarFallback>
                </Avatar>
                <div className="grid gap-3 flex-1">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p className="text-muted-foreground text-xs uppercase tracking-wide mb-1">
                        {t('users_show_full_name_label')}
                      </p>
                      <p className="font-medium">{displayName || '—'}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground text-xs uppercase tracking-wide mb-1">
                        {t('user_field_email')}
                      </p>
                      <p>{user.email}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground text-xs uppercase tracking-wide mb-1">
                        {t('common_status')}
                      </p>
                      <Badge variant="outline" className={cn('rounded-full capitalize', statusCls)}>
                        {statusLabels[status] ?? status}
                      </Badge>
                    </div>
                    <div>
                      <p className="text-muted-foreground text-xs uppercase tracking-wide mb-1">
                        {t('users_show_start_date_label')}
                      </p>
                      <p className="text-sm">
                        {(() => {
                          const createdAt = user.createdAt ?? user.created_at
                          return createdAt
                            ? format(new Date(createdAt), 'MM/dd/yyyy', { locale: enUS })
                            : '—'
                        })()}
                      </p>
                    </div>
                    <div>
                      <p className="text-muted-foreground text-xs uppercase tracking-wide mb-1">
                        {t('users_show_last_activity_label')}
                      </p>
                      <p className="text-sm">
                        {lastActivity
                          ? format(new Date(lastActivity), 'MM/dd/yyyy HH:mm', { locale: enUS })
                          : '—'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* ── Tabs: Roles | Audit ── */}
          <Tabs defaultValue="roles">
            <TabsList>
              <TabsTrigger value="roles" className="gap-2">
                <Shield className="h-4 w-4" />
                {t('users_tab_roles')}
              </TabsTrigger>
              <TabsTrigger value="audit" className="gap-2">
                <ClipboardList className="h-4 w-4" />
                {t('users_tab_audit_log')}
              </TabsTrigger>
            </TabsList>

            {/* Roles */}
            <TabsContent value="roles" className="mt-4">
              <Card>
                <CardContent className="pt-6">
                  {roles.length ? (
                    <div className="flex flex-wrap gap-2">
                      {roles.map((role) => (
                        <Badge key={role} variant="secondary">
                          {role}
                        </Badge>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-muted-foreground">{t('users_no_roles_assigned')}</p>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            {/* Audit */}
            <TabsContent value="audit" className="mt-4">
              <Card>
                <CardContent className="pt-6">
                  <AuditLogTab userId={user.id} />
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
