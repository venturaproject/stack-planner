import {
  IconBrowserCheck,
  IconHelp,
  IconLayoutDashboard,
  IconNotification,
  IconPalette,
  IconSettings,
  IconTool,
  IconUserCog,
  IconUsers,
  IconPackage,
  IconAddressBook,
  IconShoppingCart,
} from '@tabler/icons-react'
import { type SidebarData } from '../types'
import TradivelLogo from '../tradivel-logo'

type TFunc = (key: string, params?: Record<string, string | number>) => string

export const sidebarData = (t: TFunc): SidebarData => ({
  teams: [
    {
      name: 'Tradivel',
      logo: TradivelLogo,
      plan: 'E-commerce Platform',
    },
  ],
  navGroups: [
    {
      title: t('nav_group_general'),
      items: [
        {
          title: t('dashboard_title'),
          url: '/admin',
          icon: IconLayoutDashboard,
        },
      ],
    },
    {
      title: t('nav_group_catalog'),
      items: [
        {
          title: t('products_title'),
          url: '/admin/products',
          icon: IconPackage,
        },
        {
          title: t('customers_title'),
          url: '/admin/customers',
          icon: IconAddressBook,
        },
        {
          title: t('orders_title'),
          url: '/admin/orders',
          icon: IconShoppingCart,
        },
      ],
    },
    {
      title: t('nav_group_access_control'),
      items: [
        {
          title: t('users_title'),
          url: '/admin/users',
          icon: IconUsers,
          permission: 'users.view',
        },
      ],
    },
    {
      title: t('nav_group_other'),
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
              title: t('settings_account_page_title'),
              url: '/admin/settings/account',
              icon: IconTool,
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
