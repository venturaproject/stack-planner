import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, KeyRound, Loader2, Shield, ClipboardList, Eye } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Main } from '@/components/layout'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { editUserSchema, EditUserFormValues } from '@/schemas/user.schema'
import { useNavigate } from 'react-router-dom'
import { generatePassword } from '@/lib/generate-password'
import { toast } from 'sonner'
import { pathFor } from '@/lib/app-routes'
import { PageProps } from '@/types'
import { usersApi } from '@/services/users-api'
import { AuditLogTab } from './components/audit-log-tab'
import { buildUpdateUserPayload } from './user-payload'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'

interface Permission {
  id: number
  name: string
  description?: string | null
}

interface Role {
  id: number
  name: string
  description?: string | null
  permissions?: Permission[]
}

interface UserRole {
  id: string | number
  name: string
}

interface UserData {
  id: string | number
  name?: string
  firstName?: string
  lastName?: string
  first_name?: string
  last_name?: string
  username?: string | null
  email: string
  status?: string
  createdAt?: string | null
  created_at?: string | null
  roles: UserRole[]
  permissions?: Array<{ id: number; name: string }>
}

interface EditUserPageProps extends PageProps {
  user: UserData
  roles: Role[]
  permissions: Permission[]
}

export default function EditUser({ user, roles = [], permissions = [] }: EditUserPageProps) {
  const { t, currentLang } = useI18n()
  const navigate = useNavigate()

  const availableRoles = Array.isArray(roles) ? roles : []
  const allPermissions = Array.isArray(permissions) ? permissions : []
  const fullName =
    user.name ||
    `${user.firstName ?? user.first_name ?? ''} ${user.lastName ?? user.last_name ?? ''}`.trim()
  const displayName = fullName || user.email

  // Current role of the user (single-role)
  const currentRoleId = user.roles?.[0] ? String(user.roles[0].id) : ''

  // Initial permissions: direct saved permissions if present, otherwise the assigned role's permissions
  const savedPermissionIds = (user.permissions ?? []).map((p) => String(p.id))
  const rolePermissionIds =
    availableRoles
      .find((r) => String(r.id) === currentRoleId)
      ?.permissions?.map((p) => String(p.id)) ?? []
  const defaultPermissionIds =
    savedPermissionIds.length > 0 ? savedPermissionIds : rolePermissionIds

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<EditUserFormValues>({
    resolver: zodResolver(editUserSchema),
    defaultValues: {
      name: fullName,
      username: user.username ?? '',
      email: user.email,
      password: '',
      roles: user.roles?.map((r) => r.name) || [],
      permissions: defaultPermissionIds,
      role_ids: currentRoleId ? [currentRoleId] : [],
    },
  })

  const role_ids = watch('role_ids') || []
  const selectedPermissions = watch('permissions') || []
  const selectedRoleId = role_ids[0] ?? ''
  const selectedRole = availableRoles.find((r) => String(r.id) === selectedRoleId)

  const handleRoleChange = (roleId: string) => {
    const role = availableRoles.find((r) => String(r.id) === roleId)
    setValue('role_ids', [roleId])
    setValue('roles', role ? [role.name] : [])
    // When the role changes, pre-load that role's permissions
    const rolePermIds = (role?.permissions ?? []).map((p) => String(p.id))
    setValue('permissions', rolePermIds)
  }

  const handlePermissionToggle = (permId: string) => {
    const current = selectedPermissions
    setValue(
      'permissions',
      current.includes(permId) ? current.filter((id) => id !== permId) : [...current, permId],
    )
  }

  const handleGeneratePassword = () => {
    setValue('password', generatePassword())
  }

  const onSubmit = async (values: EditUserFormValues) => {
    try {
      await usersApi.update(user.id, buildUpdateUserPayload(values))
      toast.success(t('user_update_success'))
    } catch (err: unknown) {
      const e = err as { response?: { data?: Record<string, string | string[]> } }
      if (e.response?.data) {
        Object.entries(e.response.data).forEach(([key, message]) => {
          setError(key as Parameters<typeof setError>[0], {
            message: Array.isArray(message) ? message[0] : String(message),
          })
        })
      }
      toast.error(getErrorMessage(err, t('user_update_error')))
    }
  }

  const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString(currentLang === 'es' ? 'es-ES' : 'en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })

  return (
    <AuthenticatedLayout title={t('users_edit_page_title', { name: displayName })}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="grid flex-1 auto-rows-max gap-4">
            {/* Header */}
            <div className="flex items-center gap-4">
              <Button
                variant="outline"
                size="icon"
                className="h-7 w-7"
                onClick={() => navigate(-1)}
              >
                <ChevronLeft className="h-4 w-4" />
                <span className="sr-only">{t('common_back')}</span>
              </Button>
              <h1 className="flex-1 shrink-0 whitespace-nowrap text-xl font-semibold tracking-tight sm:grow-0">
                {t('users_edit_heading')}
              </h1>
              <div className="hidden items-center gap-2 md:ml-auto md:flex">
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.users.show', user.id))}
                  disabled={isSubmitting}
                >
                  <Eye className="mr-2 h-4 w-4" />
                  {t('common_view')}
                </Button>
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.users.index'))}
                  disabled={isSubmitting}
                >
                  {t('common_cancel')}
                </Button>
                <Button size="sm" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                  {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  {isSubmitting ? t('common_saving') : t('common_save_changes')}
                </Button>
              </div>
            </div>

            {/* ── Top row: two equal columns ── */}
            <div className="grid gap-4 md:grid-cols-2 lg:gap-6">
              {/* Left: user data */}
              <Card className="h-full">
                <CardHeader>
                  <CardTitle>{t('users_edit_details_title')}</CardTitle>
                  <CardDescription>{t('users_edit_details_description')}</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4">
                  <div className="grid gap-2">
                    <Label htmlFor="name">{t('user_field_name')}</Label>
                    <Input id="name" type="text" {...register('name')} />
                    {errors.name && (
                      <p className="text-sm text-destructive">{errors.name.message}</p>
                    )}
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="username">{t('user_field_username')}</Label>
                    <Input id="username" type="text" {...register('username')} />
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="email">{t('user_field_email')}</Label>
                    <Input id="email" type="email" {...register('email')} />
                    {errors.email && (
                      <p className="text-sm text-destructive">{errors.email.message}</p>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* Right: role + password + info in a single card */}
              <Card className="h-full">
                <CardContent className="pt-6 grid gap-5">
                  {/* Role */}
                  <div className="grid gap-2">
                    <Label>{t('user_field_role')}</Label>
                    <Select value={selectedRoleId} onValueChange={handleRoleChange}>
                      <SelectTrigger>
                        <SelectValue placeholder={t('user_field_role_placeholder')} />
                      </SelectTrigger>
                      <SelectContent>
                        {availableRoles.map((role) => (
                          <SelectItem key={role.id} value={String(role.id)}>
                            {role.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  {/* Password */}
                  <div className="grid gap-2">
                    <Label htmlFor="password">{t('user_field_new_password')}</Label>
                    <div className="flex gap-2">
                      <Input
                        id="password"
                        type="password"
                        className="font-mono"
                        placeholder={t('user_field_new_password_placeholder')}
                        {...register('password')}
                      />
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        onClick={handleGeneratePassword}
                      >
                        <KeyRound className="h-4 w-4" />
                      </Button>
                    </div>
                    {errors.password && (
                      <p className="text-sm text-destructive">{errors.password.message}</p>
                    )}
                  </div>

                  {/* Info */}
                  <div className="border-t pt-4 grid gap-3 text-sm">
                    <div>
                      <p className="text-muted-foreground text-xs">
                        {t('users_edit_user_id_label')}
                      </p>
                      <p className="font-mono text-xs break-all mt-0.5">{user.id}</p>
                    </div>
                    {(user.createdAt ?? user.created_at) && (
                      <div>
                        <p className="text-muted-foreground text-xs">{t('common_created_at')}</p>
                        <p className="font-medium mt-0.5">
                          {formatDate((user.createdAt ?? user.created_at)!)}
                        </p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* ── Bottom row: permissions | audit log ── */}
            <Tabs defaultValue="permissions">
              <TabsList>
                <TabsTrigger value="permissions" className="gap-2">
                  <Shield className="h-4 w-4" />
                  {t('users_edit_permissions_title')}
                </TabsTrigger>
                <TabsTrigger value="audit" className="gap-2">
                  <ClipboardList className="h-4 w-4" />
                  {t('users_tab_audit_log')}
                </TabsTrigger>
              </TabsList>

              <TabsContent value="permissions" className="mt-4">
                <Card>
                  <CardHeader>
                    <CardTitle>{t('users_edit_permissions_title')}</CardTitle>
                    <CardDescription>
                      {selectedRole
                        ? t('users_edit_permissions_from_role', { role: selectedRole.name })
                        : t('users_edit_permissions_select_role')}
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    {allPermissions.length === 0 ? (
                      <p className="text-sm text-muted-foreground">
                        {t('users_no_permissions_available')}
                      </p>
                    ) : (
                      (() => {
                        const grouped = allPermissions.reduce<Record<string, Permission[]>>(
                          (acc, p) => {
                            const group = p.name.includes(':') ? p.name.split(':')[0] : 'other'
                            acc[group] = [...(acc[group] ?? []), p]
                            return acc
                          },
                          {},
                        )
                        return (
                          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {Object.entries(grouped).map(([group, perms]) => (
                              <div key={group}>
                                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2 pb-1 border-b">
                                  {group}
                                </p>
                                <div className="space-y-0.5">
                                  {perms.map((perm) => {
                                    const checked = selectedPermissions.includes(String(perm.id))
                                    const fromRole =
                                      selectedRole?.permissions?.some(
                                        (p) => String(p.id) === String(perm.id),
                                      ) ?? false
                                    return (
                                      <label
                                        key={perm.id}
                                        htmlFor={`perm-${perm.id}`}
                                        className="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 transition-colors hover:bg-muted/40"
                                      >
                                        <Checkbox
                                          id={`perm-${perm.id}`}
                                          checked={checked}
                                          onCheckedChange={() =>
                                            handlePermissionToggle(String(perm.id))
                                          }
                                        />
                                        <span className="flex-1 text-sm font-mono">
                                          {perm.name.includes(':')
                                            ? perm.name.split(':')[1]
                                            : perm.name}
                                        </span>
                                        {fromRole && (
                                          <Badge
                                            variant="outline"
                                            className="text-[10px] px-1.5 py-0 h-4 shrink-0"
                                          >
                                            {t('users_permission_from_role_badge')}
                                          </Badge>
                                        )}
                                      </label>
                                    )
                                  })}
                                </div>
                              </div>
                            ))}
                          </div>
                        )
                      })()
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="audit" className="mt-4">
                <Card>
                  <CardContent className="pt-6">
                    <AuditLogTab userId={String(user.id)} />
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>

            {/* Mobile buttons */}
            <div className="flex items-center gap-2 md:hidden">
              <Button
                variant="outline"
                className="flex-1"
                onClick={() => navigate(pathFor('admin.users.show', user.id))}
                disabled={isSubmitting}
              >
                <Eye className="mr-2 h-4 w-4" />
                {t('common_view')}
              </Button>
              <Button
                variant="outline"
                className="flex-1"
                onClick={() => navigate(pathFor('admin.users.index'))}
                disabled={isSubmitting}
              >
                {t('common_cancel')}
              </Button>
              <Button className="flex-1" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isSubmitting ? t('common_saving') : t('common_save_changes')}
              </Button>
            </div>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
