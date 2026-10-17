import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const demoAvatarByName: Record<string, string> = {
  'Admin User': '/demo/avatars/1/nqyaBorOubooKHaGuYkaLf3bB8PHZEP1zDxrG0KC.png',
  'Laura Gómez': '/demo/avatars/2/AcO9YLKJJXoBPn96PV7krjodUW2aLdl7gGDVIddo.png',
  'Marcos Ruiz': '/demo/avatars/1/U2zoDg4MmhnEMJjoYVtXAdeg7KKTIVJf50H3qdYl.png',
}

export function AssigneeAvatarStack({ names }: { names: string[] }) {
  if (names.length === 0) return null
  const shown = names.slice(0, 4)
  return (
    <div className="flex -space-x-2" aria-label={`Asignado a ${names.join(', ')}`}>
      {shown.map((name) => (
        <Avatar key={name} className="size-6 border-2 border-background" title={name}>
          <AvatarImage src={demoAvatarByName[name]} alt={name} />
          <AvatarFallback className="text-[9px]">
            {name
              .split(' ')
              .map((part) => part[0])
              .join('')
              .slice(0, 2)
              .toUpperCase()}
          </AvatarFallback>
        </Avatar>
      ))}
      {names.length > shown.length && (
        <span className="grid size-6 place-items-center rounded-full border-2 border-background bg-muted text-[9px] font-semibold">
          +{names.length - shown.length}
        </span>
      )}
    </div>
  )
}
