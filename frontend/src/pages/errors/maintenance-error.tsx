import { Button } from '@/components/ui/button'
import { DocumentTitle } from '@/components/document-title'
import { useI18n } from '@/i18n/context'

export default function MaintenanceError() {
  const { t } = useI18n()
  return (
    <>
      <DocumentTitle title={t('error_503_title')} />
      <div className="h-svh">
        <div className="m-auto flex h-full w-full flex-col items-center justify-center gap-2">
          <h1 className="text-[7rem] font-bold leading-tight">503</h1>
          <span className="font-medium">{t('error_503_heading')}</span>
          <p className="text-center text-muted-foreground">
            {t('error_503_description_line1')} <br />
            {t('error_503_description_line2')}
          </p>
          <div className="mt-6 flex gap-4">
            <Button variant="outline">{t('error_learn_more_button')}</Button>
          </div>
        </div>
      </div>
    </>
  )
}
