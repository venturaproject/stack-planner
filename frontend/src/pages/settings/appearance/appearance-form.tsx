import { useState } from 'react'
import { ChevronDownIcon } from '@radix-ui/react-icons'
import { cn } from '@/lib/utils'
import { Button, buttonVariants } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { useTheme } from '@/context/theme-context'
import { toast } from 'sonner'
import { useI18n, type Language } from '@/i18n/context'
import { useAuthStore } from '@/lib/auth'
import { authApi } from '@/services/auth-api'
import { settingsApi } from '@/services/settings-api'

function splitName(fullName: string): { firstName: string; lastName: string } {
  const parts = fullName.trim().split(/\s+/)
  if (parts.length === 1) return { firstName: parts[0] ?? '', lastName: '' }
  return { firstName: parts[0], lastName: parts.slice(1).join(' ') }
}

export function AppearanceForm() {
  const { t, currentLang, changeLanguage } = useI18n()
  const { theme, setTheme } = useTheme()
  const user = useAuthStore((s) => s.user)
  const setAuth = useAuthStore((s) => s.setAuth)
  const permissions = useAuthStore((s) => s.permissions)
  const roles = useAuthStore((s) => s.roles)

  const [font, setFont] = useState<string>(() => localStorage.getItem('font') ?? 'inter')
  const [saving, setSaving] = useState(false)
  const [savingLanguage, setSavingLanguage] = useState(false)

  function handleThemeChange(value: 'light' | 'dark' | 'system') {
    setTheme(value)
  }

  async function handleLanguageChange(value: Language) {
    changeLanguage(value)
    if (!user) return

    setSavingLanguage(true)
    try {
      const { firstName, lastName } = splitName(user.name)
      await authApi.updateProfile({ firstName, lastName, preferredLanguage: value })
      setAuth({ ...user, preferredLanguage: value }, permissions, roles)
      toast.success(t('settings_language_saved_success'))
    } catch {
      toast.error(t('settings_something_wrong_error'))
    } finally {
      setSavingLanguage(false)
    }
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    localStorage.setItem('font', font)
    try {
      await settingsApi.updateAppearance({ theme, font })
      toast.success(t('settings_appearance_saved_success'))
    } catch {
      toast.error(t('settings_something_wrong_error'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-8">
      {/* Language */}
      <div className="space-y-2">
        <Label>{t('settings_language_label')}</Label>
        <p className="text-sm text-muted-foreground">{t('settings_language_description')}</p>
        <RadioGroup
          value={currentLang}
          onValueChange={handleLanguageChange}
          className="grid max-w-xs grid-cols-2 gap-4 pt-2"
        >
          <Label className="[&:has([data-state=checked])]:border-primary flex cursor-pointer items-center justify-center rounded-md border-2 border-muted p-3 hover:border-accent">
            <RadioGroupItem value="es" className="sr-only" disabled={savingLanguage} />
            {t('settings_language_spanish')}
          </Label>
          <Label className="[&:has([data-state=checked])]:border-primary flex cursor-pointer items-center justify-center rounded-md border-2 border-muted p-3 hover:border-accent">
            <RadioGroupItem value="en" className="sr-only" disabled={savingLanguage} />
            {t('settings_language_english')}
          </Label>
        </RadioGroup>
      </div>

      {/* Font */}
      <div className="space-y-2">
        <Label>{t('settings_appearance_font_label')}</Label>
        <div className="relative w-max">
          <select
            value={font}
            onChange={(e) => setFont(e.target.value)}
            className={cn(
              buttonVariants({ variant: 'outline' }),
              'w-[200px] appearance-none font-normal',
            )}
          >
            <option value="inter">Inter</option>
            <option value="manrope">Manrope</option>
            <option value="system">{t('settings_theme_system')}</option>
          </select>
          <ChevronDownIcon className="absolute right-3 top-2.5 h-4 w-4 opacity-50" />
        </div>
        <p className="text-sm text-muted-foreground">{t('settings_appearance_font_description')}</p>
      </div>

      {/* Theme */}
      <div className="space-y-2">
        <Label>{t('settings_appearance_theme_label')}</Label>
        <p className="text-sm text-muted-foreground">
          {t('settings_appearance_theme_description')}
        </p>
        <RadioGroup
          value={theme}
          onValueChange={handleThemeChange}
          className="grid max-w-lg grid-cols-3 gap-4 pt-2"
        >
          {/* Light */}
          <Label className="[&:has([data-state=checked])>div]:border-primary cursor-pointer">
            <RadioGroupItem value="light" className="sr-only" />
            <div className="items-center rounded-md border-2 border-muted p-1 hover:border-accent">
              <div className="space-y-2 rounded-sm bg-[#ecedef] p-2">
                <div className="space-y-2 rounded-md bg-white p-2 shadow-sm">
                  <div className="h-2 w-[80px] rounded-lg bg-[#ecedef]" />
                  <div className="h-2 w-[100px] rounded-lg bg-[#ecedef]" />
                </div>
                <div className="flex items-center space-x-2 rounded-md bg-white p-2 shadow-sm">
                  <div className="h-4 w-4 rounded-full bg-[#ecedef]" />
                  <div className="h-2 w-[100px] rounded-lg bg-[#ecedef]" />
                </div>
              </div>
            </div>
            <span className="block w-full p-2 text-center text-sm font-normal">
              {t('settings_theme_light')}
            </span>
          </Label>

          {/* Dark */}
          <Label className="[&:has([data-state=checked])>div]:border-primary cursor-pointer">
            <RadioGroupItem value="dark" className="sr-only" />
            <div className="items-center rounded-md border-2 border-muted bg-popover p-1 hover:bg-accent hover:text-accent-foreground">
              <div className="space-y-2 rounded-sm bg-slate-950 p-2">
                <div className="space-y-2 rounded-md bg-slate-800 p-2 shadow-sm">
                  <div className="h-2 w-[80px] rounded-lg bg-slate-400" />
                  <div className="h-2 w-[100px] rounded-lg bg-slate-400" />
                </div>
                <div className="flex items-center space-x-2 rounded-md bg-slate-800 p-2 shadow-sm">
                  <div className="h-4 w-4 rounded-full bg-slate-400" />
                  <div className="h-2 w-[100px] rounded-lg bg-slate-400" />
                </div>
              </div>
            </div>
            <span className="block w-full p-2 text-center text-sm font-normal">
              {t('settings_theme_dark')}
            </span>
          </Label>

          {/* System */}
          <Label className="[&:has([data-state=checked])>div]:border-primary cursor-pointer">
            <RadioGroupItem value="system" className="sr-only" />
            <div className="items-center rounded-md border-2 border-muted p-1 hover:border-accent">
              <div className="space-y-2 rounded-sm bg-[#ecedef] p-2">
                <div className="space-y-2 rounded-md bg-gradient-to-r from-white to-slate-800 p-2 shadow-sm">
                  <div className="h-2 w-[80px] rounded-lg bg-slate-400" />
                  <div className="h-2 w-[100px] rounded-lg bg-slate-400" />
                </div>
                <div className="flex items-center space-x-2 rounded-md bg-gradient-to-r from-white to-slate-800 p-2 shadow-sm">
                  <div className="h-4 w-4 rounded-full bg-slate-400" />
                  <div className="h-2 w-[100px] rounded-lg bg-slate-400" />
                </div>
              </div>
            </div>
            <span className="block w-full p-2 text-center text-sm font-normal">
              {t('settings_theme_system')}
            </span>
          </Label>
        </RadioGroup>
      </div>

      <Button type="submit" disabled={saving}>
        {saving ? t('common_saving') : t('settings_appearance_update_button')}
      </Button>
    </form>
  )
}
