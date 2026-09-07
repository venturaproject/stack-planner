import { AuthenticatedLayout } from '@/layouts'
import { ChevronLeft, Loader2, MapPin, Eye } from 'lucide-react'
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
import { formatDate } from '@/lib/formatters'
import { useQueryClient } from '@tanstack/react-query'
import { useI18n } from '@/i18n/context'
import { getErrorMessage } from '@/lib/errors'
import { customersApi } from '@/services/customers-api'
import { editCustomerAddressSchema, EditCustomerAddressFormValues } from '@/schemas/customer.schema'
import type { Customer } from './types'

interface EditCustomerPageProps {
  customer: Customer
}

export default function EditCustomer({ customer }: EditCustomerPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<EditCustomerAddressFormValues>({
    resolver: zodResolver(editCustomerAddressSchema),
    defaultValues: {
      street: customer.addressStreet ?? '',
      city: customer.addressCity ?? '',
      postalCode: customer.addressPostalCode ?? '',
      country: customer.addressCountry ?? '',
    },
  })

  const onSubmit = async (values: EditCustomerAddressFormValues) => {
    try {
      await customersApi.updateAddress(customer.id, values)
      toast.success(t('customer_edit_success'))
      queryClient.invalidateQueries({ queryKey: ['customers'] })
      navigate(pathFor('admin.customers.show', customer.id))
    } catch (err: unknown) {
      toast.error(getErrorMessage(err, t('customer_edit_error')))
    }
  }

  return (
    <AuthenticatedLayout title={t('customer_edit_title')}>
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
              <div className="flex-1">
                <h1 className="whitespace-nowrap text-xl font-semibold tracking-tight">
                  {t('customer_edit_title')}
                </h1>
                <p className="text-sm text-muted-foreground">{customer.name}</p>
              </div>
              <div className="hidden items-center gap-2 md:flex">
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.customers.show', customer.id))}
                  disabled={isSubmitting}
                >
                  <Eye className="mr-2 h-4 w-4" />
                  {t('common_view')}
                </Button>
                <Button
                  variant="outline"
                  onClick={() => navigate(pathFor('admin.customers.index'))}
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

            <div className="grid gap-4 md:grid-cols-2 lg:gap-6">
              {/* Left: contact info (read-only) */}
              <Card className="h-full">
                <CardHeader>
                  <CardTitle>{t('customer_edit_contact_title')}</CardTitle>
                  <CardDescription>{t('customer_edit_contact_description')}</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4">
                  <div className="grid gap-2">
                    <Label>{t('customer_field_name')}</Label>
                    <Input value={customer.name} disabled />
                  </div>
                  <div className="grid gap-2">
                    <Label>{t('customer_field_email')}</Label>
                    <Input value={customer.email} disabled />
                  </div>
                  <div className="border-t pt-4 grid gap-3 text-sm">
                    <div>
                      <p className="text-muted-foreground text-xs">{t('common_created_at')}</p>
                      <p className="font-medium mt-0.5">{formatDate(customer.createdAt)}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Right: address */}
              <Card className="h-full">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <MapPin className="h-5 w-5" />
                    {t('customer_edit_address_title')}
                  </CardTitle>
                  <CardDescription>{t('customer_edit_card_description')}</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4">
                  <div className="grid gap-2">
                    <Label htmlFor="street">{t('customer_field_street')}</Label>
                    <Input id="street" {...register('street')} />
                    {errors.street && (
                      <p className="text-sm text-destructive">{errors.street.message}</p>
                    )}
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                      <Label htmlFor="city">{t('customer_field_city')}</Label>
                      <Input id="city" {...register('city')} />
                      {errors.city && (
                        <p className="text-sm text-destructive">{errors.city.message}</p>
                      )}
                    </div>
                    <div className="grid gap-2">
                      <Label htmlFor="postalCode">{t('customer_field_postal_code')}</Label>
                      <Input id="postalCode" {...register('postalCode')} />
                      {errors.postalCode && (
                        <p className="text-sm text-destructive">{errors.postalCode.message}</p>
                      )}
                    </div>
                  </div>
                  <div className="grid gap-2">
                    <Label htmlFor="country">{t('customer_field_country')}</Label>
                    <Input id="country" {...register('country')} />
                    {errors.country && (
                      <p className="text-sm text-destructive">{errors.country.message}</p>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Mobile buttons */}
            <div className="flex items-center gap-2 md:hidden">
              <Button
                variant="outline"
                className="flex-1"
                onClick={() => navigate(pathFor('admin.customers.show', customer.id))}
                disabled={isSubmitting}
              >
                <Eye className="mr-2 h-4 w-4" />
                {t('common_view')}
              </Button>
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
                {isSubmitting ? t('common_saving') : t('common_save_changes')}
              </Button>
            </div>
          </div>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
