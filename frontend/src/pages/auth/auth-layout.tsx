import { DocumentTitle } from '@/components/document-title'
import BrandLogo from '@/components/layout/brand-logo'
import { useI18n } from '@/i18n/context'
interface Props {
  children: React.ReactNode
  title?: string
}

export default function AuthLayout({ children, title }: Props) {
  const { t } = useI18n()
  return (
    <>
      <DocumentTitle title={title ?? t('auth_layout_default_title')} />
      <div className="container grid h-svh flex-col items-center justify-center bg-primary-foreground lg:max-w-none lg:px-0">
        <div className="mx-auto flex w-full flex-col justify-center space-y-2 sm:w-[480px] lg:p-8">
          <div className="mb-4 flex items-center justify-center">
            <BrandLogo className="mr-2" />
          </div>
          {children}
        </div>
      </div>
    </>
  )
}
