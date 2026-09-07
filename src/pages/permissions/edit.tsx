import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, Lock, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Main } from '@/components/layout'
import { toast } from 'sonner'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { useQueryClient } from '@tanstack/react-query'
import { PageProps } from '@/types'
import { permissionsApi } from '@/services/permissions-api'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'

interface Permission {
  id: number
  name: string
  guard_name: string
}

interface EditPermissionPageProps extends PageProps {
  permission: Permission
}

export default function EditPermission({ permission }: EditPermissionPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [name, setName] = useState(permission.name)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const onSubmit = async () => {
    if (!name.trim()) {
      toast.error(t('permission_name_required_error'))
      return
    }

    if (!/^[a-z0-9.]+$/.test(name)) {
      toast.error(t('permission_name_format_error'))
      return
    }

    setIsSubmitting(true)
    try {
      await permissionsApi.update(permission.id, {
        name: name.trim(),
      })
      toast.success(t('permission_update_success'))
      queryClient.invalidateQueries({ queryKey: ['permissions'] })
      navigate(pathFor('admin.permissions.index'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('permission_update_error')))
    } finally {
      setIsSubmitting(false)
    }
  }

  const group = name.split('.')[0]
  const action = name.split('.')[1] || ''

  return (
    <AuthenticatedLayout title={t('permission_edit_page_title', { name: permission.name })}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="flex items-center gap-4">
            <Button variant="outline" size="icon" onClick={() => navigate(-1)}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <h1 className="text-xl font-semibold">{t('permission_edit_heading')}</h1>
          </div>

          <div className="grid gap-4 md:grid-cols-[1fr_2fr]">
            {/* Permission Details Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Lock className="h-5 w-5" />
                  {t('permission_details_card_title')}
                </CardTitle>
                <CardDescription>{t('permission_edit_card_description')}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">{t('permission_field_current')}</Label>
                  <Input
                    id="name"
                    value={name}
                    onChange={(e) => setName(e.target.value.toLowerCase())}
                    placeholder={t('permission_field_current_placeholder')}
                  />
                </div>

                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p className="text-muted-foreground">{t('permission_original_name_label')}</p>
                    <p className="font-medium">{permission.name}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">{t('roles_table_guard')}</p>
                    <p className="font-medium">{permission.guard_name}</p>
                  </div>
                </div>

                <div className="text-xs text-muted-foreground space-y-1 bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                  <p className="font-semibold text-yellow-800">{t('permission_warning_label')}</p>
                  <p>{t('permission_warning_text')}</p>
                </div>

                <Button
                  onClick={onSubmit}
                  disabled={isSubmitting || !name.trim()}
                  className="w-full"
                >
                  {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                  {isSubmitting ? t('common_saving') : t('common_save_changes')}
                </Button>
              </CardContent>
            </Card>

            {/* Info Card */}
            <Card>
              <CardHeader>
                <CardTitle>{t('users_summary_title')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p className="text-muted-foreground">{t('permission_field_group')}</p>
                    <p className="font-medium">{group || '-'}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground">{t('permission_field_action')}</p>
                    <p className="font-medium">{action || '-'}</p>
                  </div>
                </div>
                <div className="p-4 bg-muted rounded-lg">
                  <p className="text-sm text-muted-foreground">
                    {t('permission_edit_preview_label')}
                  </p>
                  <p className="font-mono text-xl font-semibold mt-1">{name}</p>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
