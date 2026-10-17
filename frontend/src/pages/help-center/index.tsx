import { IconHelpCircle, IconMail } from '@tabler/icons-react'
import { AuthenticatedLayout } from '@/layouts'
import { Main } from '@/components/layout'
import { Card, CardContent } from '@/components/ui/card'
import { useI18n } from '@/i18n/context'

export default function HelpCenterPage() {
  const { t } = useI18n()

  return (
    <AuthenticatedLayout title={t('help_center_title')}>
      <Main className="space-y-6 p-6 md:p-8">
        <header>
          <p className="text-sm text-muted-foreground">{t('help_center_subtitle')}</p>
          <h1 className="mt-1 text-3xl font-semibold tracking-tight">{t('help_center_title')}</h1>
          <p className="mt-2 text-sm text-muted-foreground">{t('help_center_description')}</p>
        </header>
        <div className="grid gap-4 md:grid-cols-2">
          <Card>
            <CardContent className="flex gap-4 p-5">
              <IconHelpCircle className="mt-1 size-5 text-primary" />
              <div>
                <h2 className="font-semibold">{t('help_center_guides_title')}</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                  {t('help_center_guides_description')}
                </p>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="flex gap-4 p-5">
              <IconMail className="mt-1 size-5 text-primary" />
              <div>
                <h2 className="font-semibold">{t('help_center_support_title')}</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                  {t('help_center_support_description')}
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </Main>
    </AuthenticatedLayout>
  )
}
