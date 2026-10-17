import { lazy, type ComponentType } from 'react'

const RELOAD_FLAG = 'chunk-reload-attempted'

/**
 * Wraps a React.lazy() import factory so a route chunk that fails to load —
 * or never settles at all — recovers with a single automatic reload instead
 * of hanging forever.
 *
 * Why this exists: reloading the SPA in the same instant the backend
 * container restarts can leave some of a route's dynamic imports aborted
 * mid-flight (observed via nginx logging 499 on them), with the underlying
 * fetch neither resolving nor rejecting. React.lazy has no way to retry a
 * promise that never settles, so the nearest <Suspense> fallback is stuck
 * indefinitely — no thrown error, nothing for an error boundary to catch.
 * A stale/renamed chunk after a frontend redeploy would hit the same
 * failure mode (a real rejection this time), so this guards both cases.
 *
 * Guarded by sessionStorage so a genuinely broken chunk reloads once, then
 * surfaces its error normally on the next failure instead of loop-reloading.
 */
export function lazyWithRetry<P extends Record<string, unknown>>(
  factory: () => Promise<{ default: ComponentType<P> }>,
  timeoutMs = 15000,
) {
  return lazy(() =>
    Promise.race([
      factory(),
      new Promise<never>((_, reject) =>
        setTimeout(() => reject(new Error('Chunk load timed out')), timeoutMs),
      ),
    ])
      .then((mod) => {
        sessionStorage.removeItem(RELOAD_FLAG)
        return mod
      })
      .catch((error: unknown) => {
        if (!sessionStorage.getItem(RELOAD_FLAG)) {
          sessionStorage.setItem(RELOAD_FLAG, '1')
          window.location.reload()
          // Reload is already in flight — never settle so nothing else runs.
          return new Promise<{ default: ComponentType<P> }>(() => {})
        }
        throw error instanceof Error ? error : new Error('Failed to load chunk')
      }),
  )
}
