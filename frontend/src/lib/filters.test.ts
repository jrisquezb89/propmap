import { describe, expect, it } from 'vitest'
import {
  clearFiltersQuery,
  filtersKey,
  filtersToQuery,
  hasActiveFilters,
  mergeQuery,
  parseFilters,
  parseView,
  viewToQuery,
} from './filters'

describe('parseFilters', () => {
  it('reads valid filters from the query', () => {
    expect(parseFilters({ min_price: '500000', max_price: '1000000', bedrooms: '3', type: 'condo' })).toEqual({
      minPrice: 500_000,
      maxPrice: 1_000_000,
      bedrooms: 3,
      type: 'condo',
    })
  })

  it('returns no filters for an empty query', () => {
    expect(parseFilters({})).toEqual({})
  })

  it.each([
    [{ min_price: 'cheap' }],
    [{ min_price: '-5' }],
    [{ min_price: '1.5' }],
    [{ bedrooms: 'abc' }],
    [{ bedrooms: '0' }], // "Any" is represented by absence
    [{ bedrooms: '99' }],
    [{ type: 'castle' }],
    [{ type: '' }],
  ])('drops invalid values: %o', (query) => {
    expect(parseFilters(query)).toEqual({})
  })

  it('keeps the min and drops the max when the range is inverted', () => {
    expect(parseFilters({ min_price: '900000', max_price: '100000' })).toEqual({ minPrice: 900_000 })
  })

  it('takes the first value of repeated params', () => {
    expect(parseFilters({ type: ['house', 'condo'] })).toEqual({ type: 'house' })
  })
})

describe('filtersToQuery', () => {
  it('round-trips through parseFilters', () => {
    const filters = { minPrice: 250_000, bedrooms: 2, type: 'townhouse' as const }
    expect(parseFilters(mergeQuery({}, filtersToQuery(filters)) as never)).toEqual(filters)
  })
})

describe('parseView / viewToQuery', () => {
  it('parses a valid camera and rounds zoom', () => {
    expect(parseView({ lat: '25.77', lng: '-80.19', zoom: '13.4' })).toEqual({ lat: 25.77, lng: -80.19, zoom: 13 })
  })

  it.each([
    [{ lat: '25.77', lng: '-80.19' }],
    [{ lat: 'x', lng: '-80.19', zoom: '12' }],
    [{ lat: '95', lng: '-80.19', zoom: '12' }],
    [{ lat: '25.77', lng: '-80.19', zoom: '40' }],
  ])('rejects incomplete or out-of-range cameras: %o', (query) => {
    expect(parseView(query)).toBeNull()
  })

  it('writes 5 decimals to keep URLs short', () => {
    expect(viewToQuery({ lat: 25.7712345678, lng: -80.1912345678, zoom: 12.6 })).toEqual({
      lat: '25.77123',
      lng: '-80.19123',
      zoom: '13',
    })
  })
})

describe('query helpers', () => {
  it('mergeQuery removes keys that became empty and keeps the rest', () => {
    expect(mergeQuery({ type: 'condo', lat: '25.7' }, { type: undefined, bedrooms: '2' })).toEqual({ lat: '25.7', bedrooms: '2' })
  })

  it('clearFiltersQuery keeps the map position', () => {
    expect(clearFiltersQuery({ type: 'condo', min_price: '1', lat: '25.7', lng: '-80.2', zoom: '12' })).toEqual({
      lat: '25.7',
      lng: '-80.2',
      zoom: '12',
    })
  })

  it('filtersKey ignores map-only changes', () => {
    expect(filtersKey(parseFilters({ type: 'condo', lat: '1' }))).toBe(filtersKey(parseFilters({ type: 'condo', lat: '2' })))
    expect(filtersKey({ type: 'condo' })).not.toBe(filtersKey({ type: 'house' }))
  })

  it('hasActiveFilters', () => {
    expect(hasActiveFilters({})).toBe(false)
    expect(hasActiveFilters({ bedrooms: 2 })).toBe(true)
  })
})
