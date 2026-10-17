import React from 'react'
import { useTheme } from '@/context/theme-context'

const BrandLogo = ({ className = '', isCollapsed = false }) => {
  const { theme } = useTheme()

  // Function to determine logo source based on theme and collapse state
  const getLogoSrc = (currentTheme: string, currentIsCollapsed: boolean) => {
    let effectiveTheme = currentTheme

    // If theme is 'system', determine based on system preference
    if (currentTheme === 'system') {
      effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    // When theme is dark, use the dark logo (has light colors that show on dark background)
    // When theme is light, use the light logo (has dark colors that show on light background)
    const isDarkTheme = effectiveTheme === 'dark'

    // Choose compact or full logo based on collapse state
    if (currentIsCollapsed) {
      return isDarkTheme ? '/logo-planner-compact-dark.svg' : '/logo-planner-compact-light.svg'
    } else {
      return isDarkTheme ? '/logo-planner-dark.svg' : '/logo-planner-light.svg'
    }
  }

  const logoSrc = getLogoSrc(theme, isCollapsed)

  return (
    <img
      src={logoSrc}
      alt="Planner"
      className={`${isCollapsed ? 'h-12 w-auto max-w-[150px]' : 'h-10 w-auto max-w-full'} ${className}`}
    />
  )
}

export default BrandLogo
