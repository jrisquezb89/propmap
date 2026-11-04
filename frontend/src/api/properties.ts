import { apiGet } from './client'
import type { Bounds, Filters, Paginated, Property } from './types'

/** The API's per_page cap. The map shows the same results as the list, so we ask for the max. */
export const PAGE_SIZE = 100

export function listProperties(
  { filters, bounds, page = 1 }: { filters: Filters; bounds?: Bounds | null; page?: number },
  signal?: AbortSignal,
) {
  return apiGet<Paginated<Property>>(
    '/properties',
    {
      min_price: filters.minPrice,
      max_price: filters.maxPrice,
      bedrooms: filters.bedrooms,
      type: filters.type,
      ...(bounds ?? {}),
      page,
      per_page: PAGE_SIZE,
    },
    { signal },
  )
}

export function getProperty(id: number, signal?: AbortSignal) {
  return apiGet<{ data: Property }>(`/properties/${id}`, {}, { signal }).then((r) => r.data)
}

export function checkHealth(timeoutMs: number) {
  return apiGet<{ status: string }>('/health', {}, { timeoutMs })
}
