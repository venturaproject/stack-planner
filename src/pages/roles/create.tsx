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
import { rolesApi } from '@/services/roles-api'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'

interface Permission {
  id: number
  name: string
  guard_name: string
}

interface CreateRolePageProps extends PageProps {
  permissions: Permission[]
  groupedPermissions: GroupedPermissions
}

export default function CreateRole({
  permissions: _permissions,
  groupedPermissions,
}: CreateRolePageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [selectedPermissions, setSelectedPermissions] = useState<string[]>([])
  const [name, setName] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handlePermissionChange = (permissionName: string, checked: boolean) => {
    if (checked) {
      setSelectedPermissions([...selectedPermissions, permissionName])
    } else {
      setSelectedPermissions(selectedPermissions.filter((p) => p !== permissionName))
    }
  }

  const handleSelectAllInGroup = (resource: string, checked: boolean) => {
    const groupPerms = groupedPermissions[resource]?.permissions.map((p) => p.name) || []

    if (checked) {
      const newPerms = [...new Set([...selectedPermissions, ...groupPerms])]
      setSelectedPermissions(newPerms)
    } else {
      setSelectedPermissions(selectedPermissions.filter((p) => !groupPerms.includes(p)))
    }
  }

  const isGroupFullySelected = (resource: string) => {
    const groupPerms = groupedPermissions[resource]?.permissions.map((p) => p.name) || []
    return groupPerms.every((p) => selectedPermissions.includes(p))
  }

  const isGroupPartiallySelected = (resource: string) => {
    const groupPerms = groupedPermissions[resource]?.permissions.map((p) => p.name) || []
    const selectedCount = groupPerms.filter((p) => selectedPermissions.includes(p)).length
    return selectedCount > 0 && selectedCount < groupPerms.length
  }

  const onSubmit = async () => {
    if (!name.trim()) {
      toast.error(t('role_name_required_error'))
      return
    }

    setIsSubmitting(true)
    try {
      await rolesApi.create({
        name: name.trim(),
        permissions: selectedPermissions,
      })
      toast.success(t('role_create_success'))
      queryClient.invalidateQueries({ queryKey: ['roles'] })
      navigate(pathFor('admin.roles.index'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('role_create_error')))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthenticatedLayout title={t('role_create_page_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="flex items-center gap-4">
            <Button variant="outline" size="icon" onClick={() => navigate(-1)}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <h1 className="text-xl font-semibold">{t('role_create_heading')}</h1>
          </div>

          <div className="grid gap-4 md:grid-cols-[1fr_2fr]">
            {/* Role Name Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Shield className="h-5 w-5" />
                  {t('role_details_card_title')}
                </CardTitle>
                <CardDescription>{t('role_create_card_description')}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">{t('role_field_name')}</Label>
                  <Input
                    id="name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder={t('role_field_name_placeholder')}
                  />
                </div>

                <div className="pt-4">
                  <p className="text-sm text-muted-foreground">
                    {t('role_selected_permissions_label')}{' '}
                    <strong>{selectedPermissions.length}</strong>
                  </p>
                </div>

                <Button onClick={onSubmit} disabled={isSubmitting} className="w-full">
                  {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                  {isSubmitting ? t('role_create_submitting') : t('role_create_submit')}
                </Button>
              </CardContent>
            </Card>

            {/* Permissions Card */}
            <Card>
              <CardHeader>
                <CardTitle>{t('common_permissions')}</CardTitle>
                <CardDescription>{t('role_select_permissions_description')}</CardDescription>
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
                            <div key={permission.name} className="flex items-center space-x-2">
                              <Checkbox
                                id={permission.name}
                                checked={selectedPermissions.includes(permission.name)}
                                onCheckedChange={(checked) => {
                                  handlePermissionChange(permission.name, checked as boolean)
                                }}
                              />
                              <Label
                                htmlFor={permission.name}
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
