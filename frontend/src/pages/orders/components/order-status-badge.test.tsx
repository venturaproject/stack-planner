import { render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it } from 'vitest'
import { I18nProvider } from '@/i18n/context'
import { OrderStatusBadge } from './order-status-badge'
import type { OrderStatus } from '@/pages/orders/types'

// This is the single shared component that replaced four separate, differently-styled
// status badge implementations (orders list, orders detail, dashboard overview/reports).
// The regression this guards against: any OrderStatus value that isn't in STATUS_STYLES
// throws at render time instead of silently rendering nothing.
describe('OrderStatusBadge', () => {
  beforeEach(() => {
    localStorage.removeItem('lang')
  })

  it.each<[OrderStatus, string]>([
    ['draft', 'Borrador'],
    ['placed', 'Confirmado'],
    ['cancelled', 'Cancelado'],
  ])('renders the Spanish label for status "%s"', async (status, expectedLabel) => {
    render(
      <I18nProvider>
        <OrderStatusBadge status={status} />
      </I18nProvider>,
    )

    expect(await screen.findByText(expectedLabel)).toBeInTheDocument()
  })
})
