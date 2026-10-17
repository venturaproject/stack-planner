import { describe, expect, it } from 'vitest'
import { normalizeUser } from './users-api'

// Regression coverage for the bug that shipped this session: the backend
// returns camelCase (firstName, avatarUrl, createdAt) with no name-converter,
// but normalizeUser originally only understood snake_case, so avatars never
// showed, names fell back to the email, and "last activity" stayed blank.
describe('normalizeUser', () => {
  it('prefers camelCase fields from the API over snake_case fallbacks', () => {
    const result = normalizeUser({
      firstName: 'Ada',
      lastName: 'Lovelace',
      avatarUrl: '/media/ada.png',
      createdAt: '2026-01-01T00:00:00Z',
      updatedAt: '2026-02-01T00:00:00Z',
    })

    expect(result.name).toBe('Ada Lovelace')
    expect(result.avatar).toBe('/media/ada.png')
    expect(result.createdAt).toBe('2026-01-01T00:00:00Z')
    expect(result.lastActivity).toBe('2026-02-01T00:00:00Z')
  })

  it('falls back to snake_case fields when camelCase is absent', () => {
    const result = normalizeUser({
      first_name: 'Grace',
      last_name: 'Hopper',
      avatar_url: '/media/grace.png',
      created_at: '2026-01-01T00:00:00Z',
    })

    expect(result.name).toBe('Grace Hopper')
    expect(result.avatar).toBe('/media/grace.png')
    expect(result.createdAt).toBe('2026-01-01T00:00:00Z')
  })

  it('derives role from the first entry of a roles array of objects', () => {
    const result = normalizeUser({ roles: [{ name: 'ADMIN' }, { name: 'USER' }] })

    expect(result.role).toBe('ADMIN')
  })

  it('derives role from the first entry of a roles array of strings', () => {
    const result = normalizeUser({ roles: ['ADMIN'] })

    expect(result.role).toBe('ADMIN')
  })

  it('defaults status to active unless explicitly inactive', () => {
    expect(normalizeUser({}).status).toBe('active')
    expect(normalizeUser({ active: false }).status).toBe('inactive')
  })
})
