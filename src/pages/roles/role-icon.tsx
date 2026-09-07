import {
  Shield,
  ShieldAlert,
  ShieldCheck,
  User,
  Users,
  Eye,
  Pencil,
  Settings,
  type LucideIcon,
} from 'lucide-react'

const ROLE_ICON_MAP: Record<string, LucideIcon> = {
  'super admin': ShieldAlert,
  superadmin: ShieldAlert,
  admin: ShieldCheck,
  user: User,
  editor: Pencil,
  writer: Pencil,
  viewer: Eye,
  reader: Eye,
  manager: Users,
  gestor: Users,
  settings: Settings,
  config: Settings,
}

function resolveIcon(name: string): LucideIcon {
  const lower = name.toLowerCase()
  for (const [key, icon] of Object.entries(ROLE_ICON_MAP)) {
    if (lower.includes(key)) return icon
  }
  return Shield
}

interface RoleIconProps {
  name: string
  className?: string
}

export function RoleIcon({ name, className = 'h-4 w-4 text-muted-foreground' }: RoleIconProps) {
  // resolveIcon only ever returns one of the module-level icon components
  // above — it selects, never creates one — but the linter can't see inside
  // the function to tell the two apart from this call site.
  const Icon = resolveIcon(name)
  // oxlint-disable-next-line react/static-components
  return <Icon className={className} />
}
