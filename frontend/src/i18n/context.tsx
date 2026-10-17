import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'

export type Language = 'en' | 'es'

interface Translations {
  [key: string]: string
}

interface I18nContextType {
  t: (key: string, params?: Record<string, string | number>) => string
  currentLang: Language
  changeLanguage: (lang: Language) => void
  isLoading: boolean
}

const I18nContext = createContext<I18nContextType | undefined>(undefined)

const localeModules: Record<Language, () => Promise<{ default: Translations }>> = {
  es: () => import('./locales/es.json'),
  en: () => import('./locales/en.json'),
}

function getInitialLang(): Language {
  const saved = window.localStorage.getItem('lang') as Language
  return saved === 'en' || saved === 'es' ? saved : 'es'
}

function applyTranslation(
  translations: Translations,
  fallback: Translations,
  key: string,
  params?: Record<string, string | number>,
): string {
  let resolvedKey = key
  if (params && typeof params['count'] === 'number') {
    const variant = params['count'] === 1 ? `${key}_one` : `${key}_other`
    if (translations[variant] !== undefined) {
      resolvedKey = variant
    } else if (fallback[variant] !== undefined) {
      resolvedKey = variant
    }
  }

  let translation = translations[resolvedKey] ?? fallback[resolvedKey] ?? key

  if (params) {
    for (const [param, value] of Object.entries(params)) {
      translation = translation.replaceAll(`{${param}}`, String(value))
    }
  }

  return translation
}

export function I18nProvider({ children }: { children: ReactNode }) {
  const [currentLang, setCurrentLang] = useState<Language>(getInitialLang)
  const [translations, setTranslations] = useState<Translations>({})
  const [fallbackTranslations, setFallbackTranslations] = useState<Translations>({})
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    setIsLoading(true)
    const loadActive = localeModules[currentLang]()
    const loadFallback =
      currentLang === 'en'
        ? Promise.resolve({ default: {} as Translations })
        : localeModules['en']()

    Promise.all([loadActive, loadFallback])
      .then(([active, fb]) => {
        setTranslations(active.default)
        setFallbackTranslations(fb.default)
      })
      .finally(() => setIsLoading(false))
  }, [currentLang])

  useEffect(() => {
    window.localStorage.setItem('lang', currentLang)
  }, [currentLang])

  const changeLanguage = (lang: Language) => setCurrentLang(lang)

  const t = useCallback(
    (key: string, params?: Record<string, string | number>): string =>
      applyTranslation(translations, fallbackTranslations, key, params),
    [translations, fallbackTranslations],
  )

  return (
    <I18nContext.Provider value={{ t, currentLang, changeLanguage, isLoading }}>
      {children}
    </I18nContext.Provider>
  )
}

export function useI18n(): I18nContextType {
  const context = useContext(I18nContext)
  if (!context) {
    throw new Error('useI18n must be used within an I18nProvider')
  }
  return context
}
