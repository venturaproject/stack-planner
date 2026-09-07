import { Button } from '@/components/ui/button'
import { useI18n } from '@/i18n/context'

export function GoBack() {
  const { t } = useI18n()
  return (
    <div className="mt-6 flex gap-4">
      <Button variant="outline" onClick={() => window.history.back()}>
        {t('error_go_back_button')}
      </Button>
      <Button onClick={() => window.location.assign('/')}>{t('error_back_to_home_button')}</Button>
    </div>
  )
}
