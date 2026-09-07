import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, KeyRound, Loader2 } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Main } from '@/components/layout'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { createUserSchema, CreateUserFormValues } from '@/schemas/user.schema'
import { useNavigate } from 'react-router-dom'
import { PageProps } from '@/types'
import { toast } from 'sonner'
import { generatePassword } from '@/lib/generate-password'
import { pathFor } from '@/lib/app-routes'
import { usersApi } from '@/services/users-api'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'
import { buildCreateUserPayload } from './user-payload'

interface Role {
  id: number
  name: string
}

interface CreateUserPageProps extends PageProps {
  roles: Role[]
}

const generateUsername = (name: string) => {
  if (!name) return ''
  return name
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, '')
    .trim()
    .replace(/\s+/g, '.')
}

export default function CreateUser({ roles = [] }: CreateUserPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const [usernameEdited, setUsernameEdited] = useState(false)
  const availableRoles = Array.isArray(roles) ? roles : []

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<CreateUserFormValues>({
    resolver: zodResolver(createUserSchema),
    defaultValues: { name: '', username: '', email: '', password: '', roles: [], role_ids: [] },
  })

  const name = watch('name')
  const username = watch('username')
  const role_ids = watch('role_ids') || []

  useEffect(() => {
    if (!usernameEdited && name) {
      setValue('username', generateUsername(name))
    }
  }, [name, usernameEdited, setValue])

  const handleRoleToggle = (role: Role) => {
    const currentIds = role_ids
    const currentNames = watch('roles') || []
    const id = String(role.id)

    if (currentIds.includes(id)) {
      setValue(
        'role_ids',
        currentIds.filter((i) => i !== id),
      )
      setValue(
        'roles',
        currentNames.filter((n) => n !== role.name),
      )
    } else {
      setValue('role_ids', [...currentIds, id])
      setValue('roles', [...currentNames, role.name])
    }
  }

  const handleGeneratePassword = () => {
    setValue('password', generatePassword())
  }

  const onSubmit = async (values: CreateUserFormValues) => {
    try {
      await usersApi.create(buildCreateUserPayload(values))
      toast.success(t('user_create_success'))
      navigate(pathFor('admin.users.index'))
    } catch (err: unknown) {
      const e = err as { response?: { data?: Record<string, string | string[]> } }
      if (e.response?.data) {
        Object.entries(e.response.data).forEach(([key, message]) => {
          setError(key as Parameters<typeof setError>[0], {
            message: Array.isArray(message) ? message[0] : String(message),
          })
        })
      }
      toast.error(getErrorMessage(err, t('user_create_error')))
    }
  }

  return (
    <AuthenticatedLayout title={t('users_create_page_title')}>
      <Main>
        <div className="grid flex-1 items-start gap-4 md:gap-8">
          <div className="grid flex-1 auto-rows-max gap-4">
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
                {t('users_create_heading')}
              </h1>
              <div className="hidden items-center gap-2 md:ml-auto md:flex">
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.users.index'))}
                  disabled={isSubmitting}
                >
                  {t('common_cancel')}
                </Button>
                <Button size="sm" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                  {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                  {isSubmitting ? t('users_create_submitting') : t('users_create_heading')}
                </Button>
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-[1fr_350px] lg:gap-8">
              <div className="grid auto-rows-max items-start gap-4 lg:gap-8">
                <Card>
                  <CardHeader>
                    <CardTitle>{t('users_create_card_title')}</CardTitle>
                    <CardDescription>{t('users_create_card_description')}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="grid gap-6">
                      <div className="grid gap-3">
                        <Label htmlFor="name">{t('user_field_full_name')}</Label>
                        <Input
                          id="name"
                          type="text"
                          className="w-full"
                          placeholder={t('user_field_full_name_placeholder')}
                          {...register('name')}
                        />
                        {errors.name && (
                          <p className="text-sm text-destructive">{errors.name.message}</p>
                        )}
                      </div>

                      <div className="grid gap-3">
                        <Label htmlFor="username">{t('user_field_username')}</Label>
                        <Input
                          id="username"
                          type="text"
                          className="w-full"
                          placeholder={t('user_field_username_placeholder')}
                          {...register('username', {
                            onChange: () => setUsernameEdited(true),
                          })}
                        />
                        <p className="text-xs text-muted-foreground">
                          {t('user_field_username_hint')}
                        </p>
                        {errors.username && (
                          <p className="text-sm text-destructive">{errors.username.message}</p>
                        )}
                      </div>

                      <div className="grid gap-3">
                        <Label htmlFor="email">{t('user_field_email')}</Label>
                        <Input
                          id="email"
                          type="email"
                          className="w-full"
                          placeholder={t('user_field_email_placeholder')}
                          {...register('email')}
                        />
                        {errors.email && (
                          <p className="text-sm text-destructive">{errors.email.message}</p>
                        )}
                      </div>

                      <div className="grid gap-3">
                        <Label htmlFor="password">{t('user_field_password')}</Label>
                        <div className="flex gap-2">
                          <Input
                            id="password"
                            type="password"
                            className="w-full font-mono"
                            placeholder={t('user_field_password_placeholder')}
                            {...register('password')}
                          />
                          <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={handleGeneratePassword}
                            title={t('user_generate_password_tooltip')}
                          >
                            <KeyRound className="h-4 w-4" />
                          </Button>
                        </div>
                        {errors.password && (
                          <p className="text-sm text-destructive">{errors.password.message}</p>
                        )}
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>

              <div className="grid auto-rows-max items-start gap-4 lg:gap-8">
                <Card>
                  <CardHeader>
                    <CardTitle>{t('common_roles')}</CardTitle>
                    <CardDescription>{t('users_roles_section_description')}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {availableRoles.map((role) => (
                        <div key={role.id} className="flex items-center space-x-2">
                          <Checkbox
                            id={`role-${role.id}`}
                            checked={role_ids.includes(String(role.id))}
                            onCheckedChange={() => handleRoleToggle(role)}
                          />
                          <Label
                            htmlFor={`role-${role.id}`}
                            className="text-sm font-normal cursor-pointer"
                          >
                            {role.name}
                          </Label>
                        </div>
                      ))}
                      {availableRoles.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                          {t('users_no_roles_available')}
                        </p>
                      )}
                    </div>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>{t('users_summary_title')}</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div>
                      <p className="text-sm text-muted-foreground">
                        {t('users_summary_name_label')}
                      </p>
                      <p className="font-medium">{name || '-'}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">{t('user_field_username')}</p>
                      <p className="font-medium">{username || generateUsername(name) || '-'}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">
                        {t('users_summary_roles_label')}
                      </p>
                      <p className="font-medium">
                        {(watch('roles') || []).length > 0
                          ? watch('roles')?.join(', ')
                          : t('common_none')}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>

            <div className="flex items-center justify-center gap-2 md:hidden">
              <Button
                variant="outline"
                onClick={() => navigate(pathFor('admin.users.index'))}
                disabled={isSubmitting}
              >
                {t('common_cancel')}
              </Button>
              <Button size="sm" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                {isSubmitting ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                {t('users_create_heading')}
              </Button>
            </div>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
