import {
  IconBrowserCheck,
  IconHelp,
  IconLayoutDashboard,
  IconNotification,
  IconPalette,
  IconSettings,
  IconUserCog,
  IconUsers,
  IconShield,
  IconChecklist,
  IconLayoutKanban,
} from '@tabler/icons-react'
import { type SidebarData } from '../types'
import BrandLogo from '../brand-logo'

type TFunc = (key: string, params?: Record<string, string | number>) => string

export const DynamicSidebarData = (t: TFunc): SidebarData => ({
  teams: [
    {
      name: 'Planner',
      logo: BrandLogo,
      plan: 'Gestión de proyectos',
    },
  ],
  navGroups: [
    {
      title: 'General',
      items: [
        {
          title: 'Dashboard',
          url: '/admin',
          icon: IconLayoutDashboard,
        },
        {
          title: 'Proyectos',
          url: '/admin/projects',
          icon: IconLayoutKanban,
        },
        {
          title: 'Issues',
          url: '/admin/issues',
          icon: IconChecklist,
        },
        {
          title: 'Mi trabajo',
          url: '/admin/my-work',
          icon: IconChecklist,
        },
      ],
    },
    {
      title: 'Control de acceso',
      items: [
        {
          title: t('users_title'),
          url: '/admin/users',
          icon: IconUsers,
          permission: 'users.view',
        },
        {
          title: 'Roles',
          url: '/admin/roles',
          icon: IconShield,
        },
        {
          title: 'Permisos',
          url: '/admin/permissions',
          icon: IconShield,
        },
      ],
    },
    {
      title: 'Otros',
      items: [
        {
          title: t('settings_title'),
          icon: IconSettings,
          items: [
            {
              title: t('settings_nav_profile'),
              url: '/admin/settings',
              icon: IconUserCog,
            },
            {
              title: t('settings_nav_appearance'),
              url: '/admin/settings/appearance',
              icon: IconPalette,
            },
            {
              title: t('settings_nav_notifications'),
              url: '/admin/settings/notifications',
              icon: IconNotification,
            },
            {
              title: t('settings_nav_display'),
              url: '/admin/settings/display',
              icon: IconBrowserCheck,
            },
          ],
        },
        {
          title: t('nav_help_center'),
          url: '/admin/help-center',
          icon: IconHelp,
        },
      ],
    },
  ],
})
