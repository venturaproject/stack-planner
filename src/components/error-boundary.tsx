import { Component, type ErrorInfo, type ReactNode } from 'react'
import { Button } from '@/components/ui/button'
import { useI18n } from '@/i18n/context'

interface Props {
  children: ReactNode
  fallback?: ReactNode
}

interface State {
  hasError: boolean
  error: Error | null
}

function DefaultErrorFallback({ error, onRetry }: { error: Error | null; onRetry: () => void }) {
  const { t } = useI18n()
  return (
    <div className="flex min-h-[200px] flex-col items-center justify-center gap-4 rounded-lg border border-destructive/30 bg-destructive/5 p-8 text-center">
      <p className="text-sm font-medium text-destructive">{t('error_boundary_message')}</p>
      {import.meta.env.DEV && error && (
        <pre className="max-w-full overflow-auto rounded bg-muted p-3 text-left text-xs text-muted-foreground">
          {error.message}
        </pre>
      )}
      <Button variant="outline" size="sm" onClick={onRetry}>
        {t('error_boundary_retry')}
      </Button>
    </div>
  )
}

/**
 * Generic ErrorBoundary for catching React render errors.
 * Use it to wrap critical UI sections (complex tables, widgets, etc.)
 *
 * @example
 * <ErrorBoundary>
 *   <ComplexComponent />
 * </ErrorBoundary>
 *
 * @example with a custom fallback
 * <ErrorBoundary fallback={<p>This module is unavailable</p>}>
 *   <SomeComponent />
 * </ErrorBoundary>
 */
export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props)
    this.state = { hasError: false, error: null }
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    // In production, hook this up to Sentry / Bugsnag / etc.
    console.error('[ErrorBoundary] Error caught:', error, info.componentStack)
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null })
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) return this.props.fallback

      return <DefaultErrorFallback error={this.state.error} onRetry={this.handleReset} />
    }

    return this.props.children
  }
}
