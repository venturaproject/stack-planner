import React from 'react'
import { useI18n } from '@/i18n/context'

export function NotFound() {
  const { t } = useI18n()
  return (
    <div className="h-screen w-full flex flex-col gap-6 items-center justify-center container overflow-y-auto">
      <svg
        width="135"
        height="87"
        focusable="false"
        fill="currentColor"
        stroke="currentColor"
        className="text-neutral-500"
      >
        <g fill="none" strokeLinecap="round" strokeLinejoin="round" strokeMiterlimit="10">
          <path
            d="M11.821 60.5v23a2.006 2.006 0 002 2h118a2.006 2.006 0 002-2v-80a2.006 2.006 0 00-2-2h-118a2.006 2.006 0 00-2 2v27"
            strokeWidth="3"
          />
          <path strokeWidth="2" d="M133.721 14h-122M29.721 8h-10" />
          <path strokeWidth="3" d="M2.121 55.1l19.3-19.2M21.421 55.1l-19.3-19.2" />
        </g>
      </svg>

      <div className="text-center">
        <h3 className="text-xl font-semibold mb-1">{t('error_404_alt_heading')}</h3>

        <p className="text-sm text-muted-foreground italic">{t('error_404_alt_description')}</p>
      </div>

      <img
        alt="Confused Travolta"
        loading="lazy"
        width="480"
        height="204"
        decoding="async"
        className="rounded-md"
        src="/img/confused-travolta.gif"
      />
    </div>
  )
}
