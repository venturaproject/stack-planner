import type { ReactNode } from 'react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { ChevronLeft, Pencil, ShoppingCart } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { pathFor } from '@/lib/app-routes'
import { formatDate } from '@/lib/formatters'
import { useI18n } from '@/i18n/context'
import type { Customer } from './types'

interface ShowCustomerPageProps {
  customer: Customer
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="flex items-center justify-between py-3">
      <dt className="text-sm text-muted-foreground">{label}</dt>
      <dd className="text-sm font-medium">{value || '—'}</dd>
    </div>
  )
}

function getInitials(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((p) => p[0] ?? '')
    .join('')
    .toUpperCase()
}

export default function ShowCustomer({ customer }: ShowCustomerPageProps) {
  const { t } = useI18n()
  const navigate = useNavigate()

  const hasAddress = Boolean(customer.addressStreet)
  const address = [
    customer.addressStreet,
    customer.addressCity,
    customer.addressPostalCode,
    customer.addressCountry,
  ]
    .filter(Boolean)
    .join(', ')

  return (
    <AuthenticatedLayout title={customer.name}>
      <Main>
        <div className="grid flex-1 items-start gap-6 md:gap-8 max-w-3xl mx-auto">
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="icon"
              onClick={() => navigate(pathFor('admin.customers.index'))}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="flex-1">
              <h2 className="text-2xl font-bold tracking-tight">{customer.name}</h2>
              <p className="text-muted-foreground text-sm">{customer.email}</p>
            </div>
            <Button
              variant="outline"
              onClick={() => navigate(pathFor('admin.orders.create', { customerId: customer.id }))}
            >
              <ShoppingCart className="mr-2 h-4 w-4" />
              {t('customers_new_order_tooltip')}
            </Button>
            <Button onClick={() => navigate(pathFor('admin.customers.edit', customer.id))}>
              <Pencil className="mr-2 h-4 w-4" />
              {t('common_edit')}
            </Button>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>{t('customers_show_profile_title')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-start gap-6">
                <Avatar className="h-16 w-16 shrink-0">
                  <AvatarFallback className="bg-primary/10 text-primary text-xl font-bold">
                    {getInitials(customer.name)}
                  </AvatarFallback>
                </Avatar>
                <dl className="flex-1 space-y-1">
                  <InfoRow label={t('customer_field_name')} value={customer.name} />
                  <Separator />
                  <InfoRow label={t('customer_field_email')} value={customer.email} />
                  <Separator />
                  <div className="flex items-center justify-between py-3">
                    <dt className="text-sm text-muted-foreground">
                      {t('customers_table_address')}
                    </dt>
                    <dd>
                      {hasAddress ? (
                        <Badge variant="secondary">{address}</Badge>
                      ) : (
                        <span className="text-sm text-muted-foreground">
                          {t('customers_show_no_address')}
                        </span>
                      )}
                    </dd>
                  </div>
                  <Separator />
                  <InfoRow label={t('common_created_at')} value={formatDate(customer.createdAt)} />
                </dl>
              </div>
            </CardContent>
          </Card>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
