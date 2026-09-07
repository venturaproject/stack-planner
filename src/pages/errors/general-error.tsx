import { cn } from '@/lib/utils'
import { GoBack } from './components/go-back'
import { DocumentTitle } from '@/components/document-title'
import { useI18n } from '@/i18n/context'

interface GeneralErrorProps extends React.HTMLAttributes<HTMLDivElement> {
  minimal?: boolean
}

export default function GeneralError({ className, minimal = false }: GeneralErrorProps) {
  const { t } = useI18n()
  return (
    <>
      <DocumentTitle title={t('error_500_title')} />
      <div className={cn('h-svh w-full', className)}>
        <div className="m-auto flex h-full w-full flex-col items-center justify-center gap-2">
          {!minimal && <h1 className="text-[7rem] font-bold leading-tight">500</h1>}
          <span className="font-medium">
            {t('error_500_title')} {`:')`}
          </span>
          <p className="text-center text-muted-foreground">
            {t('error_500_description_line1')} <br /> {t('error_500_description_line2')}
          </p>
          {!minimal && <GoBack />}
        </div>
      </div>
    </>
  )
}
