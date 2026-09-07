import type { CreateUserFormValues, EditUserFormValues } from '@/schemas/user.schema'

function splitName(name: string): { firstName: string; lastName: string } {
  const nameParts = name.trim().split(/\s+/)
  return {
    firstName: nameParts[0] ?? name,
    lastName: nameParts.slice(1).join(' ') || '',
  }
}

// The backend's CreateUserCommand/UpdateUserCommand are camelCase with no
// name-converter — sending snake_case (first_name, role_ids...) here silently
// fails to update anything. See users-api.test.ts for the matching read-side bug.
export function buildCreateUserPayload(values: CreateUserFormValues) {
  const { firstName, lastName } = splitName(values.name)

  return {
    email: values.email,
    password: values.password,
    firstName,
    lastName,
    roleIds: values.role_ids,
  }
}

export function buildUpdateUserPayload(values: EditUserFormValues) {
  const { firstName, lastName } = splitName(values.name)

  return {
    firstName,
    lastName,
    email: values.email,
    roleIds: values.role_ids,
    permissionIds: values.permissions,
    ...(values.password ? { password: values.password } : {}),
  }
}
