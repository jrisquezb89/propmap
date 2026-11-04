import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { Filters, MapView } from '@/api/types'
import {
  clearFiltersQuery,
  filtersKey,
  filtersToQuery,
  hasActiveFilters,
  mergeQuery,
  parseFilters,
  parseView,
  viewToQuery,
} from '@/lib/filters'

/**
 * Read and write search state through the URL. Components never hold their own
 * copy of the filters: they call setFilters(), the URL changes, and everything
 * derives from route.query. One direction, no sync loops.
 */
export function useUrlFilters() {
  const route = useRoute()
  const router = useRouter()

  const filters = computed(() => parseFilters(route.query))
  /** Changes only when the filters change, not when the map moves. */
  const key = computed(() => filtersKey(filters.value))
  const view = computed(() => parseView(route.query))
  const isFiltered = computed(() => hasActiveFilters(filters.value))

  /** push: each filter change is a history entry, so Back undoes it. */
  function setFilters(patch: Partial<Filters>) {
    const next = { ...filters.value, ...patch }
    return router.push({ query: mergeQuery(route.query, filtersToQuery(next)) })
  }

  function clearFilters() {
    return router.push({ query: clearFiltersQuery(route.query) })
  }

  /** replace: panning the map shouldn't fill the history with entries. */
  function setView(next: MapView) {
    return router.replace({ query: mergeQuery(route.query, viewToQuery(next)) })
  }

  return { filters, key, view, isFiltered, setFilters, clearFilters, setView }
}
