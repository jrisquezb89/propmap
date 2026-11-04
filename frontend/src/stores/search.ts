import { defineStore } from 'pinia'
import { computed, ref, shallowRef } from 'vue'
import type { LocationQuery } from 'vue-router'
import { isAbortError, NetworkError } from '@/api/client'
import { getProperty, listProperties } from '@/api/properties'
import type { Bounds, Filters, Property } from '@/api/types'
import { useServerStore } from './server'

export type SearchStatus = 'idle' | 'loading' | 'success' | 'error'

/**
 * Search results and the list <-> map interaction state.
 *
 * Every new search aborts the previous request, so a slow response for an old
 * viewport can never overwrite results for the current one.
 */
export const useSearchStore = defineStore('search', () => {
  const server = useServerStore()

  // Results are replaced wholesale, never mutated in place: shallowRef avoids deep proxies on 100 objects.
  const properties = shallowRef<Property[]>([])
  const total = ref(0)
  const page = ref(1)
  const lastPage = ref(1)
  const status = ref<SearchStatus>('idle')
  const loadingMore = ref(false)
  const error = ref<string | null>(null)
  /** A failed "Load more" keeps the results already shown and reports inline. */
  const loadMoreError = ref<string | null>(null)

  const hoveredId = ref<number | null>(null)
  const selectedId = ref<number | null>(null)

  /** Query of the last search page, so "Back to results" restores it. */
  const lastSearchQuery = shallowRef<LocationQuery>({})

  /** Properties seen in any response, so the detail page can render instantly. */
  const cache = new Map<number, Property>()

  let current: { filters: Filters; bounds: Bounds | null } | null = null
  let controller: AbortController | null = null

  const canLoadMore = computed(() => status.value === 'success' && page.value < lastPage.value)
  const selected = computed(() => properties.value.find((p) => p.id === selectedId.value) ?? null)

  function remember(items: Property[]) {
    for (const p of items) cache.set(p.id, p)
  }

  function startRequest(): AbortSignal {
    controller?.abort()
    controller = new AbortController()
    return controller.signal
  }

  function describe(e: unknown): string {
    if (e instanceof NetworkError) {
      server.markUnreachable()
      return "We couldn't reach the server. Check your connection and try again."
    }
    return 'Something went wrong while loading homes. Please try again.'
  }

  async function search(filters: Filters, bounds: Bounds | null) {
    current = { filters, bounds }
    const signal = startRequest()
    status.value = 'loading'
    error.value = null
    loadMoreError.value = null
    loadingMore.value = false

    try {
      const res = await listProperties({ filters, bounds }, signal)
      remember(res.data)
      properties.value = res.data
      total.value = res.meta.total
      page.value = res.meta.current_page
      lastPage.value = res.meta.last_page
      status.value = 'success'
      if (selectedId.value !== null && !res.data.some((p) => p.id === selectedId.value)) selectedId.value = null
    } catch (e) {
      if (isAbortError(e)) return // superseded by a newer search
      error.value = describe(e)
      status.value = 'error'
    }
  }

  async function loadMore() {
    if (!current || !canLoadMore.value || loadingMore.value) return
    const signal = startRequest()
    loadingMore.value = true
    loadMoreError.value = null

    try {
      const res = await listProperties({ ...current, page: page.value + 1 }, signal)
      remember(res.data)
      const seen = new Set(properties.value.map((p) => p.id))
      properties.value = [...properties.value, ...res.data.filter((p) => !seen.has(p.id))]
      page.value = res.meta.current_page
      lastPage.value = res.meta.last_page
      total.value = res.meta.total
    } catch (e) {
      if (!isAbortError(e)) loadMoreError.value = describe(e)
    } finally {
      // Only the request that is still current owns the flag.
      if (!signal.aborted) loadingMore.value = false
    }
  }

  function retry() {
    if (current) return search(current.filters, current.bounds)
  }

  function cached(id: number): Property | undefined {
    return cache.get(id)
  }

  async function fetchProperty(id: number, signal?: AbortSignal): Promise<Property> {
    const property = await getProperty(id, signal)
    cache.set(id, property)
    return property
  }

  return {
    properties,
    total,
    status,
    loadingMore,
    error,
    loadMoreError,
    hoveredId,
    selectedId,
    selected,
    lastSearchQuery,
    canLoadMore,
    search,
    loadMore,
    retry,
    cached,
    fetchProperty,
  }
})
