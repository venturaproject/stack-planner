import { axios } from '@/lib/axios'

export interface SearchResults {
  users: Array<{ id: string; name: string; email: string }>
  roles: Array<{ id: string; name: string }>
  permissions: Array<{ id: string; name: string; guard_name: string }>
  categories: Array<{ id: string; name: string }>
  tags: Array<{ id: string; name: string }>
}

export const searchApi = {
  async search(term: string, signal?: AbortSignal) {
    const { data } = await axios.get<SearchResults>('/api/search', {
      params: { q: term },
      signal,
    })

    return data
  },
}
