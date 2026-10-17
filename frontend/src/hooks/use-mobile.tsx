import * as React from 'react'

const MOBILE_BREAKPOINT = 768

function subscribe(onChange: () => void) {
  const mql = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`)
  mql.addEventListener('change', onChange)
  return () => mql.removeEventListener('change', onChange)
}

function getSnapshot() {
  return window.innerWidth < MOBILE_BREAKPOINT
}

// useSyncExternalStore is React's own pattern for subscribing to a value that
// lives outside React (matchMedia here) — it reads the real current value on
// first render instead of an undefined placeholder, and re-subscribing never
// needs a setState-in-effect to stay in sync.
export function useIsMobile() {
  return React.useSyncExternalStore(subscribe, getSnapshot)
}
