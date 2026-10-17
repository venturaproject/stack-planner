import { Button } from '@/components/ui/button'
import { DocumentTitle } from '@/components/document-title'
import { useI18n } from '@/i18n/context'

export default function UnauthorisedError() {
  const { t } = useI18n()
  return (
    <>
      <DocumentTitle title={t('error_401_title')} />
      <div className="h-svh">
        <div className="m-auto flex h-full w-full flex-col items-center justify-center gap-2">
          <h1 className="text-[7rem] font-bold leading-tight">401</h1>
          <span className="font-medium">{t('error_401_title')}</span>
          <p className="text-center text-muted-foreground">
            {t('error_401_description_line1')} <br /> {t('error_401_description_line2')}
          </p>
          <div className="mt-6 flex gap-4">
            <Button variant="outline" onClick={() => window.history.back()}>
              {t('error_go_back_button')}
            </Button>
            <Button onClick={() => window.location.assign('/')}>
              {t('error_back_to_home_button')}
            </Button>
          </div>
        </div>
      </div>
    </>
  )
}
