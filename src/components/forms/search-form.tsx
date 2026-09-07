import { Search } from 'lucide-react'

import { Label } from '@/components/ui/label'
import { SidebarGroup, SidebarGroupContent, SidebarInput } from '@/components/ui/sidebar'
import { useI18n } from '@/i18n/context'

export function SearchForm({ ...props }: React.ComponentProps<'form'>) {
  const { t } = useI18n()
  return (
    <form {...props}>
      <SidebarGroup className="py-0">
        <SidebarGroupContent className="relative">
          <Label htmlFor="search" className="sr-only">
            {t('search_form_label')}
          </Label>
          <SidebarInput id="search" placeholder={t('search_form_placeholder')} className="pl-8" />
          <Search className="pointer-events-none absolute left-2 top-1/2 size-4 -translate-y-1/2 select-none opacity-50" />
        </SidebarGroupContent>
      </SidebarGroup>
    </form>
  )
}
