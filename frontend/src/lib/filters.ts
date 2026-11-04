import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import { PROPERTY_TYPES, type Filters, type MapView, type PropertyType } from '@/api/types'

/**
 * The URL is the single source of truth for search state. These pure functions
 * translate between `route.query` and typed values. Anything invalid
 * (?bedrooms=abc, ?type=castle) is dropped instead of breaking the page.
 */

export const PRICE_STEPS = [250_000, 500_000, 750_000, 1_000_000, 1_500_000, 2_000_000, 3_000_000, 5_000_000]
export const BEDROOM_OPTIONS = [1, 2, 3, 4, 5]

/** Default camera: central Miami, Coral Gables to Miami Beach. */
export const DEFAULT_VIEW: MapView = { lat: 25.775, lng: -80.215, zoom: 12 }

const FILTER_KEYS = ['min_price', 'max_price', 'bedrooms', 'type'] as const

type QueryValue = LocationQuery[string] | undefined

function first(value: QueryValue): string | undefined {
  const v = Array.isArray(value) ? value[0] : value
  return v ?? undefined
}

function nonNegativeInt(value: QueryValue, max = Number.MAX_SAFE_INTEGER): number | undefined {
  const raw = first(value)
  if (raw === undefined || !/^\d+$/.test(raw)) return undefined
  const n = Number(raw)
  return n <= max ? n : undefined
}

function finite(value: QueryValue, min: number, max: number): number | undefined {
  const raw = first(value)
  if (raw === undefined || raw.trim() === '') return undefined
  const n = Number(raw)
  return Number.isFinite(n) && n >= min && n <= max ? n : undefined
}

export function parseFilters(query: LocationQuery): Filters {
  const filters: Filters = {}

  const minPrice = nonNegativeInt(query.min_price)
  const maxPrice = nonNegativeInt(query.max_price)
  if (minPrice !== undefined) filters.minPrice = minPrice
  // An inverted range can't match anything: keep the min, drop the max.
  if (maxPrice !== undefined && (minPrice === undefined || maxPrice >= minPrice)) filters.maxPrice = maxPrice

  const bedrooms = nonNegativeInt(query.bedrooms, 20)
  if (bedrooms !== undefined && bedrooms > 0) filters.bedrooms = bedrooms

  const type = first(query.type)
  if (type && (PROPERTY_TYPES as readonly string[]).includes(type)) filters.type = type as PropertyType

  return filters
}

export function filtersToQuery(filters: Filters): LocationQueryRaw {
  return {
    min_price: filters.minPrice?.toString(),
    max_price: filters.maxPrice?.toString(),
    bedrooms: filters.bedrooms?.toString(),
    type: filters.type,
  }
}

export function parseView(query: LocationQuery): MapView | null {
  const lat = finite(query.lat, -90, 90)
  const lng = finite(query.lng, -180, 180)
  const zoom = finite(query.zoom, 3, 21)
  return lat !== undefined && lng !== undefined && zoom !== undefined ? { lat, lng, zoom: Math.round(zoom) } : null
}

export function viewToQuery(view: MapView): LocationQueryRaw {
  // 5 decimals is ~1 m: precise enough, and keeps shared URLs short.
  return { lat: view.lat.toFixed(5), lng: view.lng.toFixed(5), zoom: String(Math.round(view.zoom)) }
}

/** Merge a partial update into the current query, removing keys that became empty. */
export function mergeQuery(current: LocationQuery, patch: LocationQueryRaw): LocationQueryRaw {
  const next: LocationQueryRaw = { ...current, ...patch }
  for (const key of Object.keys(next)) {
    if (next[key] === undefined || next[key] === null || next[key] === '') delete next[key]
  }
  return next
}

export function clearFiltersQuery(current: LocationQuery): LocationQueryRaw {
  return mergeQuery(current, Object.fromEntries(FILTER_KEYS.map((k) => [k, undefined])))
}

export function hasActiveFilters(filters: Filters): boolean {
  return Object.values(filters).some((v) => v !== undefined)
}

/** Stable key for "did the filters actually change?" (ignores map-only query changes). */
export function filtersKey(filters: Filters): string {
  return FILTER_KEYS.map((k) => filtersToQuery(filters)[k] ?? '').join('|')
}
