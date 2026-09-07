import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, Contact, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Main } from '@/components/layout'
import { toast } from 'sonner'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { useQueryClient } from '@tanstack/react-query'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'
import { customersApi } from '@/services/customers-api'
import { createCustomerSchema, CreateCustomerFormValues } from '@/schemas/customer.schema'

export default function CreateCustomer() {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<CreateCustomerFormValues>({
    resolver: zodResolver(createCustomerSchema),
    defaultValues: { name: '', email: '', street: '', city: '', postalCode: '', country: '' },
  })

  const onSubmit = async (values: CreateCustomerFormValues) => {
    try {
      await customersApi.create({
        name: values.name,
        email: values.email,
        addressStreet: values.street || null,
        addressCity: values.city || null,
        addressPostalCode: values.postalCode || null,
        addressCountry: values.country || null,
      })
      toast.success(t('customer_create_success'))
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      navigate(pathFor('admin.customers.index'))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('customer_create_error')))
    }
  }

  return (
    <AuthenticatedLayout title={t('customer_create_title')}>
      <Main>
        <div className="mx-auto grid w-full max-w-2xl flex-1 items-start gap-4 md:gap-6">
          {/* Header */}
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              className="h-7 w-7"
              onClick={() => navigate(pathFor('admin.customers.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
              <span className="sr-only">{t('common_back')}</span>
            </Button>
            <h1 className="whitespace-nowrap text-xl font-semibold tracking-tight">
              {t('customer_create_title')}
            </h1>
            <div className="hidden items-center gap-2 md:ml-auto md:flex">
              <Button
                variant="outline"
                onClick={() => navigate(pathFor('admin.customers.index'))}
                disabled={isSubmitting}
              >
                {t('common_cancel')}
              </Button>
              <Button size="sm" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isSubmitting ? t('customer_create_submitting') : t('customer_create_submit')}
              </Button>
            </div>
          </div>

          <Card>
            <CardHeader className="items-center text-center">
              <div className="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Contact className="h-6 w-6" />
              </div>
              <CardTitle>{t('customer_create_card_title')}</CardTitle>
              <CardDescription>{t('customer_create_card_description')}</CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="name">{t('customer_field_name')}</Label>
                  <Input id="name" {...register('name')} placeholder="Ada Lovelace" />
                  {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="email">{t('customer_field_email')}</Label>
                  <Input
                    id="email"
                    type="email"
                    {...register('email')}
                    placeholder="ada@example.com"
                  />
                  {errors.email && (
                    <p className="text-sm text-destructive">{errors.email.message}</p>
                  )}
                </div>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="street">{t('customer_field_street')}</Label>
                <Input
                  id="street"
                  {...register('street')}
                  placeholder={t('customer_field_street_placeholder')}
                />
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="city">{t('customer_field_city')}</Label>
                  <Input id="city" {...register('city')} />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="postalCode">{t('customer_field_postal_code')}</Label>
                  <Input id="postalCode" {...register('postalCode')} />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="country">{t('customer_field_country')}</Label>
                  <Input id="country" {...register('country')} />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Mobile buttons */}
          <div className="flex items-center gap-2 md:hidden">
            <Button
              variant="outline"
              className="flex-1"
              onClick={() => navigate(pathFor('admin.customers.index'))}
              disabled={isSubmitting}
            >
              {t('common_cancel')}
            </Button>
            <Button className="flex-1" onClick={handleSubmit(onSubmit)} disabled={isSubmitting}>
              {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isSubmitting ? t('customer_create_submitting') : t('customer_create_submit')}
            </Button>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
