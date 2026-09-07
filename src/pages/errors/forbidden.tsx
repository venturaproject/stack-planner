import { GoBack } from './components/go-back'
import { DocumentTitle } from '@/components/document-title'
import { useI18n } from '@/i18n/context'

export default function ForbiddenError() {
  const { t } = useI18n()
  return (
    <>
      <DocumentTitle title={t('error_403_title')} />
      <div className="h-svh">
        <div className="m-auto flex h-full w-full flex-col items-center justify-center gap-2">
          <h1 className="text-[7rem] font-bold leading-tight">403</h1>
          <span className="font-medium">{t('error_403_title')}</span>
          <p className="text-center text-muted-foreground">
            {t('error_403_description_line1')} <br />
            {t('error_403_description_line2')}
          </p>
          <GoBack />
        </div>
      </div>
    </>
  )
}
