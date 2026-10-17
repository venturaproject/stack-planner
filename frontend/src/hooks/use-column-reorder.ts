import { useEffect, useRef, useState } from 'react'

function getStorageKey(storageKey: string) {
  return `column-order:${storageKey}`
}

function getVisibilityKey(storageKey: string) {
  return `column-visibility:${storageKey}`
}

function readStorage<T>(key: string, fallback: T): T {
  try {
    const raw = localStorage.getItem(key)
    return raw ? (JSON.parse(raw) as T) : fallback
  } catch {
    return fallback
  }
}

/**
 * Reconciles a (possibly stale) stored order against the current column list.
 * The first and last ids (by convention: the select checkbox and the row
 * actions menu) are always pinned to their edge positions and excluded from
 * drag-and-drop, so they can never end up stranded by a stale/partial order.
 * Any id present in `initialIds` but missing from `stored` (e.g. a column
 * added after the order was last saved) is inserted at its natural position
 * instead of being appended at the end.
 */
function reconcileOrder(stored: string[], initialIds: string[]): string[] {
  if (initialIds.length === 0) return []

  const pinnedFirst = initialIds[0]
  const pinnedLast = initialIds[initialIds.length - 1]
  const middleIds = initialIds.slice(1, -1)
  const known = new Set(middleIds)

  const validStoredMiddle = stored.filter((id) => known.has(id))
  const missingMiddle = middleIds.filter((id) => !validStoredMiddle.includes(id))

  const merged = [...validStoredMiddle]
  for (const id of missingMiddle) {
    const naturalIndex = middleIds.indexOf(id)
    let insertAt = merged.length
    for (let i = 0; i < merged.length; i++) {
      if (middleIds.indexOf(merged[i]) > naturalIndex) {
        insertAt = i
        break
      }
    }
    merged.splice(insertAt, 0, id)
  }

  return [pinnedFirst, ...merged, pinnedLast]
}

/**
 * Manages column order and visibility with localStorage persistence.
 *
 * The storage key is derived automatically from the current route, so each
 * page keeps its own saved state without extra configuration.
 *
 * @param initialIds - Initial column order (used if nothing is in localStorage)
 */
export function useColumnReorder(initialIds: string[]) {
  const storageKey = window.location.pathname
  const pinnedFirst = initialIds[0]
  const pinnedLast = initialIds[initialIds.length - 1]

  const [columnOrder, setColumnOrder] = useState<string[]>(() =>
    reconcileOrder(readStorage(getStorageKey(storageKey), initialIds), initialIds),
  )
  const [columnVisibility, setColumnVisibility] = useState<Record<string, boolean>>(() =>
    readStorage(getVisibilityKey(storageKey), {}),
  )
  const dragColumnId = useRef<string | null>(null)

  // Persist order when it changes
  useEffect(() => {
    try {
      localStorage.setItem(getStorageKey(storageKey), JSON.stringify(columnOrder))
    } catch {
      // localStorage may be full or blocked in incognito mode
    }
  }, [columnOrder, storageKey])

  // Persist visibility when it changes
  useEffect(() => {
    try {
      localStorage.setItem(getVisibilityKey(storageKey), JSON.stringify(columnVisibility))
    } catch {
      // localStorage may be full or blocked in incognito mode
    }
  }, [columnVisibility, storageKey])

  const handleDragStart = (id: string) => {
    if (id === pinnedFirst || id === pinnedLast) return
    dragColumnId.current = id
  }

  const handleDrop = (targetId: string) => {
    if (!dragColumnId.current || dragColumnId.current === targetId) return
    if (targetId === pinnedFirst || targetId === pinnedLast) return
    setColumnOrder((prev) => {
      const next = [...prev]
      const from = next.indexOf(dragColumnId.current!)
      const to = next.indexOf(targetId)
      next.splice(from, 1)
      next.splice(to, 0, dragColumnId.current!)
      return next
    })
    dragColumnId.current = null
  }

  return {
    columnOrder,
    columnVisibility,
    setColumnVisibility,
    handleDragStart,
    handleDrop,
  }
}
