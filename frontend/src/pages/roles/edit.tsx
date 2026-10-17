import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, Shield, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion'
import { Main } from '@/components/layout'
import { toast } from 'sonner'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { useQueryClient } from '@tanstack/react-query'
import { PageProps, GroupedPermissions } from '@/types'
import { Badge } from '@/components/ui/badge'
import { rolesApi } from '@/services/roles-api'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'

interface Permission {
  id: number
  name: string
  guard_name: string
}

interface Role {
  id: number
  name: string
  guard_name: string
  permissions?: Permission[]
  permissions_count?: number
  users_count?: number
}

interface EditRolePageProps extends PageProps {
  role: Role
  permissions: Permission[]
  groupedPermissions: GroupedPermissions
  rolePermissions: number[]
}

export default function EditRole({ role, groupedPermissions, rolePermissions }: EditRolePageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const fullAccessRoles = ['admin']
  const hasFullAccessRole = fullAccessRoles.includes(role.name)

  const [name, setName] = useState(role.name)
  const [selectedPermissions, setSelectedPermissions] = useState<number[]>(rolePermissions)
  const [isSubmitting, setIsSubmitting] = useState(false)

  // Reset selection when rolePermissions changes externally (e.g. navigating
  // to a different role), adjusted during render instead of in an effect.
  const [prevRolePermissions, setPrevRolePermissions] = useState(rolePermissions)
  if (rolePermissions !== prevRolePermissions) {
    setPrevRolePermissions(rolePermissions)
    setSelectedPermissions(rolePermissions)
  }

  const handlePermissionChange = (permissionId: number, checked: boolean) => {
    if (hasFullAccessRole) return

    if (checked) {
      setSelectedPermissions([...selectedPermissions, permissionId])
    } else {
      setSelectedPermissions(selectedPermissions.filter((id) => id !== permissionId))
    }
  }

  const handleSelectAllInGroup = (resource: string, checked: boolean) => {
    if (hasFullAccessRole) return

    const groupPerms = groupedPermissions[resource]?.permissions.map((p) => p.id) || []

    if (checked) {
      const newPerms = [...new Set([...selectedPermissions, ...groupPerms])]
      setSelectedPermissions(newPerms)
    } else {
      setSelectedPermissions(selectedPermissions.filter((id) => !groupPerms.includes(id)))
    }
  }

  const isGroupFullySelected = (resource: string) => {
    if (hasFullAccessRole) return true
    const groupPerms = groupedPermissions[resource]?.permissions.map((p) => p.id) || []
    return groupPerms.every((id) => selectedPermissions.includes(id))
  }

  const isGroupPartiallySelected = (resource: string) => {
    if (hasFullAccessRole) return false
    const groupPerms = groupedPermissions[resource]?.permissions.map((p) => p.id) || []
    const selectedCount = groupPerms.filter((id) => selectedPermissions.includes(id)).length
    return selectedCount > 0 && selectedCount < groupPerms.length
  }

  const onSubmit = async () => {
    if (!name.trim()) {
      toast.error(t('role_name_required_error'))
      return
    }

    setIsSubmitting(true)
    try {
      await rolesApi.update(role.id, {
        name: name.trim(),
        permissions: selectedPermissions,
      })
      toast.success(t('role_update_success'))
      queryClient.invalidateQueries({ queryKey: ['roles'] })
      navigate(pathFor('admin.roles.index'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('role_update_error')))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthenticatedLayout title={t('role_edit_page_title', { name: role.name })}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="flex items-center gap-4">
            <Button variant="outline" size="icon" onClick={() => navigate(-1)}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <h1 className="text-xl font-semibold">{t('role_edit_heading')}</h1>
            {hasFullAccessRole && <Badge variant="secondary">{t('role_system_badge')}</Badge>}
          </div>

          <div className="grid gap-4 md:grid-cols-[1fr_2fr]">
            {/* Role Name Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  {t('role_details_card_title')}
                </CardTitle>
                <CardDescription>
                  {hasFullAccessRole
                    ? t('role_edit_full_access_description')
                    : t('role_edit_card_description')}
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">{t('role_field_name')}</Label>
                  <Input
                    id="name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder={t('role_field_name_placeholder')}
                    disabled={hasFullAccessRole}
                  />
                </div>

                <div className="pt-4">
                  <p className="text-sm text-muted-foreground">
                    {hasFullAccessRole
                      ? t('role_all_permissions_bypass_note')
                      : t('role_selected_permissions_count', { count: selectedPermissions.length })}
                  </p>
                </div>

                <Button
                  onClick={onSubmit}
                  disabled={isSubmitting || hasFullAccessRole}
                  className="w-full"
                >
                  {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                  {isSubmitting ? t('common_saving') : t('common_save_changes')}
                </Button>
              </CardContent>
            </Card>

            {/* Permissions Card */}
            <Card>
              <CardHeader>
                <CardTitle>{t('common_permissions')}</CardTitle>
                <CardDescription>
                  {hasFullAccessRole
                    ? t('role_edit_permissions_full_access_description')
                    : t('role_select_permissions_description')}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <Accordion type="multiple" className="w-full">
                  {Object.values(groupedPermissions).map((group) => (
                    <AccordionItem value={group.key} key={group.key}>
                      <AccordionTrigger className="hover:no-underline">
                        <div className="flex items-center gap-2">
                          <Checkbox
                            checked={isGroupFullySelected(group.key)}
                            ref={(el) => {
                              if (el) {
                                ;(
                                  el as HTMLButtonElement & { indeterminate: boolean }
                                ).indeterminate = isGroupPartiallySelected(group.key)
                              }
                            }}
                            onCheckedChange={(checked) => {
                              handleSelectAllInGroup(group.key, checked as boolean)
                            }}
                            onClick={(e) => e.stopPropagation()}
                            disabled={hasFullAccessRole}
                          />
                          <span className="font-medium">{group.label}</span>
                          <span className="text-muted-foreground text-sm">
                            ({group.permissions.length})
                          </span>
                        </div>
                      </AccordionTrigger>
                      <AccordionContent>
                        <div className="grid gap-2 pl-6 pt-2">
                          {group.permissions.map((permission) => (
                            <div key={permission.id} className="flex items-center space-x-2">
                              <Checkbox
                                id={String(permission.id)}
                                checked={
                                  hasFullAccessRole || selectedPermissions.includes(permission.id)
                                }
                                onCheckedChange={(checked) => {
                                  handlePermissionChange(permission.id, checked as boolean)
                                }}
                                disabled={hasFullAccessRole}
                              />
                              <Label
                                htmlFor={String(permission.id)}
                                className="text-sm font-normal cursor-pointer"
                              >
                                {permission.actionLabel}
                              </Label>
                            </div>
                          ))}
                        </div>
                      </AccordionContent>
                    </AccordionItem>
                  ))}
                </Accordion>
              </CardContent>
            </Card>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
