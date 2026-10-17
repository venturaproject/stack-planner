import { act, renderHook } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { useBulkSelection } from './use-bulk-selection'

describe('useBulkSelection', () => {
  it('selects and deselects an individual row', () => {
    const { result } = renderHook(() => useBulkSelection([1, 2], 2))

    act(() => result.current.toggleSelectRow(1, true))
    expect(result.current.selectedIds).toEqual(new Set([1]))

    act(() => result.current.toggleSelectRow(1, false))
    expect(result.current.selectedIds).toEqual(new Set())
  })

  it('selects and deselects all rows on the current page', () => {
    const { result } = renderHook(() => useBulkSelection([1, 2], 3))

    act(() => result.current.toggleSelectAll(true))
    expect(result.current.selectedIds).toEqual(new Set([1, 2]))
    expect(result.current.allPageSelected).toBe(true)

    act(() => result.current.toggleSelectAll(false))
    expect(result.current.selectedIds).toEqual(new Set())
    expect(result.current.allPageSelected).toBe(false)
  })
})
