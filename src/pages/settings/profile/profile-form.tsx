import { useRef, useState } from 'react'
import { toast } from 'sonner'
import { useAuthStore } from '@/lib/auth'
import { axios } from '@/lib/axios'
import { endpoints } from '@/lib/endpoints'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { IconCamera, IconMail, IconShield } from '@tabler/icons-react'
import { useI18n } from '@/i18n/context'

function splitName(fullName: string): { firstName: string; lastName: string } {
  const parts = fullName.trim().split(/\s+/)
  if (parts.length === 1) return { firstName: parts[0], lastName: '' }
  return { firstName: parts[0], lastName: parts.slice(1).join(' ') }
}

export default function ProfileForm() {
  const { t } = useI18n()
  const user = useAuthStore((s) => s.user)
  const permissions = useAuthStore((s) => s.permissions)
  const roles = useAuthStore((s) => s.roles)
  const setAuth = useAuthStore((s) => s.setAuth)

  const fileInputRef = useRef<HTMLInputElement>(null)
  const [preview, setPreview] = useState<string | null>(user?.avatar ?? null)
  const [uploading, setUploading] = useState(false)

  const initial = splitName(user?.name ?? '')
  const [firstName, setFirstName] = useState(initial.firstName)
  const [lastName, setLastName] = useState(initial.lastName)
  const [saving, setSaving] = useState(false)

  const initials = (user?.name ?? 'User')
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)

  async function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (!file) return

    setPreview(URL.createObjectURL(file))
    setUploading(true)

    const formData = new FormData()
    formData.append('avatar', file)

    try {
      const response = await axios.post(endpoints.auth.avatar, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      const updated = response.data
      if (updated) {
        const avatarUrl = updated.avatar_url ?? updated.avatarUrl ?? updated.avatar ?? null
        const avatarWithVersion = avatarUrl
          ? `${avatarUrl}${avatarUrl.includes('?') ? '&' : '?'}v=${Date.now()}`
          : null
        setAuth({ ...user!, avatar: avatarWithVersion }, permissions, roles)
        setPreview(avatarWithVersion)
      }
      toast.success(t('settings_profile_picture_updated'))
    } catch {
      setPreview(user?.avatar ?? null)
      toast.error(t('settings_something_wrong_error'))
    } finally {
      setUploading(false)
      if (fileInputRef.current) fileInputRef.current.value = ''
    }
  }

  async function handleSaveProfile(e: React.FormEvent) {
    e.preventDefault()
    if (!firstName.trim()) return

    setSaving(true)
    try {
      const { data } = await axios.patch(endpoints.auth.me, {
        firstName: firstName.trim(),
        lastName: lastName.trim(),
      })
      const newName = `${data.firstName ?? firstName} ${data.lastName ?? lastName}`.trim()
      setAuth({ ...user!, name: newName }, permissions, roles)
      toast.success(t('settings_profile_updated_success'))
    } catch {
      toast.error(t('settings_something_wrong_error'))
    } finally {
      setSaving(false)
    }
  }

  if (!user) return <div>{t('common_loading')}</div>

  return (
    <div className="space-y-8">
      {/* Avatar */}
      <div className="flex items-center gap-6">
        <div className="relative">
          <Avatar className="h-20 w-20">
            <AvatarImage src={preview ?? undefined} alt={user.name ?? 'User'} />
            <AvatarFallback className="text-lg">{initials}</AvatarFallback>
          </Avatar>
          <button
            type="button"
            onClick={() => fileInputRef.current?.click()}
            disabled={uploading}
            className="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-50"
          >
            <IconCamera size={14} />
          </button>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={handleFileChange}
          />
        </div>
        <div>
          <p className="font-medium">{user.name}</p>
          <p className="text-sm text-muted-foreground">{t('settings_profile_avatar_hint')}</p>
        </div>
      </div>

      {/* Editable name fields */}
      <form onSubmit={handleSaveProfile} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="firstName">{t('settings_profile_first_name_label')}</Label>
            <Input
              id="firstName"
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              disabled={saving}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="lastName">{t('settings_profile_last_name_label')}</Label>
            <Input
              id="lastName"
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              disabled={saving}
            />
          </div>
        </div>
        <Button type="submit" disabled={saving || !firstName.trim()}>
          {saving ? t('common_saving') : t('common_save_changes')}
        </Button>
      </form>

      {/* Read-only fields */}
      <div className="space-y-4">
        <div className="flex items-center gap-3 rounded-md border bg-muted/40 px-4 py-3">
          <IconMail size={16} className="shrink-0 text-muted-foreground" />
          <div>
            <p className="text-xs text-muted-foreground">{t('user_field_email')}</p>
            <p className="text-sm font-medium">{user.email}</p>
          </div>
        </div>

        <div className="flex items-center gap-3 rounded-md border bg-muted/40 px-4 py-3">
          <IconShield size={16} className="shrink-0 text-muted-foreground" />
          <div>
            <p className="text-xs text-muted-foreground">{t('common_roles')}</p>
            <div className="mt-1 flex flex-wrap gap-1">
              {(user.roles ?? []).map((role) => (
                <Badge key={role} variant="secondary">
                  {role}
                </Badge>
              ))}
            </div>
          </div>
        </div>
      </div>

      <p className="text-xs text-muted-foreground">{t('settings_profile_managed_by_admin_note')}</p>
    </div>
  )
}
