import { describe, expect, it } from 'vitest'
import { buildCreateUserPayload, buildUpdateUserPayload } from './user-payload'
import type { CreateUserFormValues, EditUserFormValues } from '@/schemas/user.schema'

// Regression coverage for the bug that shipped this session: both forms sent
// snake_case fields (first_name, role_ids...) but CreateUserCommand/UpdateUserCommand
// are camelCase with no name-converter, so saves silently did nothing.
describe('buildCreateUserPayload', () => {
  it('splits the full name and sends camelCase fields', () => {
    const values: CreateUserFormValues = {
      name: 'Ada Lovelace',
      email: 'ada@example.com',
      password: 'password123',
      roles: ['ADMIN'],
      permissions: [],
      role_ids: ['1'],
    }

    expect(buildCreateUserPayload(values)).toEqual({
      email: 'ada@example.com',
      password: 'password123',
      firstName: 'Ada',
      lastName: 'Lovelace',
      roleIds: ['1'],
    })
  })

  it('handles a single-word name with an empty last name', () => {
    const values: CreateUserFormValues = {
      name: 'Cher',
      email: 'cher@example.com',
      password: 'password123',
      roles: [],
      permissions: [],
      role_ids: [],
    }

    expect(buildCreateUserPayload(values)).toMatchObject({ firstName: 'Cher', lastName: '' })
  })
})

describe('buildUpdateUserPayload', () => {
  it('sends camelCase fields including permissionIds', () => {
    const values: EditUserFormValues = {
      name: 'Grace Hopper',
      email: 'grace@example.com',
      roles: ['ADMIN'],
      permissions: ['5', '6'],
      role_ids: ['2'],
    }

    expect(buildUpdateUserPayload(values)).toEqual({
      firstName: 'Grace',
      lastName: 'Hopper',
      email: 'grace@example.com',
      roleIds: ['2'],
      permissionIds: ['5', '6'],
    })
  })

  it('omits the password field entirely when left blank', () => {
    const values: EditUserFormValues = {
      name: 'Grace Hopper',
      email: 'grace@example.com',
      password: '',
      roles: [],
      permissions: [],
      role_ids: [],
    }

    expect(buildUpdateUserPayload(values)).not.toHaveProperty('password')
  })

  it('includes the password field when set', () => {
    const values: EditUserFormValues = {
      name: 'Grace Hopper',
      email: 'grace@example.com',
      password: 'new-password123',
      roles: [],
      permissions: [],
      role_ids: [],
    }

    expect(buildUpdateUserPayload(values)).toMatchObject({ password: 'new-password123' })
  })
})
