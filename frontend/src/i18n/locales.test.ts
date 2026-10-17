import { describe, expect, it } from 'vitest'
import en from './locales/en.json'
import es from './locales/es.json'

describe('translation catalogs', () => {
  it('provide the same keys for English and Spanish', () => {
    expect(Object.keys(es).sort()).toEqual(Object.keys(en).sort())
  })
})
