import { useState } from 'react'
import { UserCog } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import { useAuthStore } from '@/lib/auth'
import { useI18n } from '@/i18n/context'

/** Shown app-wide (see AuthenticatedLayout) whenever the current session is an admin impersonating another user. */
export function ImpersonationBanner() {
  const { t } = useI18n()
  const impersonatorEmail = useAuthStore((s) => s.impersonatorEmail)
  const targetEmail = useAuthStore((s) => s.user?.email)
  const stopImpersonating = useAuthStore((s) => s.stopImpersonating)
  const [isStopping, setIsStopping] = useState(false)

  if (!impersonatorEmail) return null

  const handleStop = async () => {
    setIsStopping(true)
    try {
      await stopImpersonating()
    } catch {
      toast.error(t('impersonation_stop_error'))
    } finally {
      setIsStopping(false)
    }
  }

  return (
    <div className="flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-sm font-medium text-amber-950">
      <UserCog className="h-4 w-4 shrink-0" />
      <span>{t('impersonation_banner_text', { email: targetEmail ?? '' })}</span>
      <Button
        size="sm"
        variant="outline"
        className="h-7 border-amber-950/30 bg-amber-500 text-amber-950 hover:bg-amber-400"
        onClick={handleStop}
        disabled={isStopping}
      >
        {t('impersonation_stop_button')}
      </Button>
    </div>
  )
}
