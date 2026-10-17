import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { notificationsApi, type Notification } from '@/services/notifications-api'

export type { Notification } from '@/services/notifications-api'

const QUERY_KEY = ['notifications']

async function fetchNotifications(): Promise<Notification[]> {
  return notificationsApi.list()
}

export function useNotifications(enabled = true) {
  const qc = useQueryClient()

  const { data: notifications = [], isLoading } = useQuery({
    queryKey: QUERY_KEY,
    queryFn: fetchNotifications,
    staleTime: 30_000,
    enabled,
  })

  const invalidate = () => qc.invalidateQueries({ queryKey: QUERY_KEY })

  const markRead = useMutation({
    mutationFn: (id: string) => notificationsApi.markRead(id),
    onMutate: async (id) => {
      await qc.cancelQueries({ queryKey: QUERY_KEY })
      const prev = qc.getQueryData<Notification[]>(QUERY_KEY)
      qc.setQueryData<Notification[]>(QUERY_KEY, (old = []) =>
        old.map((n) => (n.id === id ? { ...n, readAt: new Date().toISOString() } : n)),
      )
      return { prev }
    },
    onError: (_e, _id, ctx) => {
      if (ctx?.prev) qc.setQueryData(QUERY_KEY, ctx.prev)
    },
  })

  const markAllRead = useMutation({
    mutationFn: () => notificationsApi.markAllRead(),
    onMutate: async () => {
      await qc.cancelQueries({ queryKey: QUERY_KEY })
      const prev = qc.getQueryData<Notification[]>(QUERY_KEY)
      qc.setQueryData<Notification[]>(QUERY_KEY, (old = []) =>
        old.map((n) => ({ ...n, readAt: n.readAt ?? new Date().toISOString() })),
      )
      return { prev }
    },
    onError: (_e, _v, ctx) => {
      if (ctx?.prev) qc.setQueryData(QUERY_KEY, ctx.prev)
    },
  })

  const deleteOne = useMutation({
    mutationFn: (id: string) => notificationsApi.delete(id),
    onMutate: async (id) => {
      await qc.cancelQueries({ queryKey: QUERY_KEY })
      const prev = qc.getQueryData<Notification[]>(QUERY_KEY)
      qc.setQueryData<Notification[]>(QUERY_KEY, (old = []) => old.filter((n) => n.id !== id))
      return { prev }
    },
    onError: (_e, _id, ctx) => {
      if (ctx?.prev) qc.setQueryData(QUERY_KEY, ctx.prev)
    },
  })

  const deleteAll = useMutation({
    mutationFn: () => notificationsApi.deleteAll(),
    onMutate: async () => {
      await qc.cancelQueries({ queryKey: QUERY_KEY })
      const prev = qc.getQueryData<Notification[]>(QUERY_KEY)
      qc.setQueryData<Notification[]>(QUERY_KEY, [])
      return { prev }
    },
    onError: (_e, _v, ctx) => {
      if (ctx?.prev) qc.setQueryData(QUERY_KEY, ctx.prev)
    },
  })

  const unreadCount = notifications.filter((n) => n.readAt === null).length

  return {
    notifications,
    isLoading,
    unreadCount,
    markRead: (id: string) => markRead.mutate(id),
    markAllRead: () => markAllRead.mutate(),
    deleteOne: (id: string) => deleteOne.mutate(id),
    deleteAll: () => deleteAll.mutate(),
    invalidate,
  }
}
