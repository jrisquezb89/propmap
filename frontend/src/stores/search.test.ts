import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { NetworkError } from '@/api/client'
import { listProperties } from '@/api/properties'
import type { Paginated, Property } from '@/api/types'
import { useSearchStore } from './search'

vi.mock('@/api/properties', () => ({ listProperties: vi.fn(), getProperty: vi.fn(), PAGE_SIZE: 100 }))
const list = vi.mocked(listProperties)

function property(id: number): Property {
  return {
    id,
    title: `Home ${id}`,
    description: '',
    price: 500_000,
    bedrooms: 2,
    bathrooms: 2,
    sqft: 1000,
    type: 'condo',
    address: '',
    neighborhood: 'Brickell',
    lat: 25.76,
    lng: -80.19,
    image_url: '',
    listed_at: '2026-11-01T00:00:00Z',
  }
}

function page(ids: number[], { current = 1, last = 1, total = ids.length } = {}): Paginated<Property> {
  return {
    data: ids.map(property),
    links: { first: null, last: null, prev: null, next: null },
    meta: { current_page: current, last_page: last, per_page: 100, total, from: null, to: null },
  }
}

/** A request that resolves when we say so, and rejects like fetch does when aborted. */
function deferred(signal: AbortSignal | undefined) {
  let resolve!: (v: Paginated<Property>) => void
  const promise = new Promise<Paginated<Property>>((res, rej) => {
    resolve = res
    signal?.addEventListener('abort', () => rej(new DOMException('Aborted', 'AbortError')))
  })
  return { promise, resolve }
}

const box = { north: 25.8, south: 25.7, east: -80.1, west: -80.3 }

describe('search store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    list.mockReset()
  })

  it('loads results for filters and bounds', async () => {
    list.mockResolvedValue(page([1, 2, 3]))
    const store = useSearchStore()

    await store.search({ type: 'condo' }, box)

    expect(list).toHaveBeenCalledWith({ filters: { type: 'condo' }, bounds: box }, expect.any(AbortSignal))
    expect(store.status).toBe('success')
    expect(store.properties.map((p) => p.id)).toEqual([1, 2, 3])
    expect(store.cached(2)?.title).toBe('Home 2')
  })

  it('a stale response never overwrites a newer search', async () => {
    const requests: ReturnType<typeof deferred>[] = []
    list.mockImplementation((_args, signal) => {
      const d = deferred(signal)
      requests.push(d)
      return d.promise
    })
    const store = useSearchStore()

    const first = store.search({}, box) // user pans...
    const second = store.search({}, { ...box, north: 25.9 }) // ...and pans again before it returns

    requests[1]!.resolve(page([20, 21]))
    await second
    requests[0]!.resolve(page([10])) // the old one arrives late (already aborted)
    await first

    expect(store.properties.map((p) => p.id)).toEqual([20, 21])
    expect(store.status).toBe('success')
  })

  it('load more appends the next page without duplicates', async () => {
    list.mockResolvedValueOnce(page([1, 2], { current: 1, last: 2, total: 3 }))
    list.mockResolvedValueOnce(page([2, 3], { current: 2, last: 2, total: 3 }))
    const store = useSearchStore()

    await store.search({}, box)
    expect(store.canLoadMore).toBe(true)
    await store.loadMore()

    expect(store.properties.map((p) => p.id)).toEqual([1, 2, 3])
    expect(store.canLoadMore).toBe(false)
    expect(list).toHaveBeenLastCalledWith({ filters: {}, bounds: box, page: 2 }, expect.any(AbortSignal))
  })

  it('a failed load more keeps the current results', async () => {
    list.mockResolvedValueOnce(page([1, 2], { current: 1, last: 2, total: 4 }))
    list.mockRejectedValueOnce(new NetworkError())
    const store = useSearchStore()

    await store.search({}, box)
    await store.loadMore()

    expect(store.status).toBe('success')
    expect(store.properties).toHaveLength(2)
    expect(store.loadMoreError).toMatch(/couldn't reach/i)
  })

  it('reports errors and retries the same search', async () => {
    list.mockRejectedValueOnce(new Error('boom'))
    list.mockResolvedValueOnce(page([7]))
    const store = useSearchStore()

    await store.search({ bedrooms: 3 }, box)
    expect(store.status).toBe('error')
    expect(store.error).toBeTruthy()

    await store.retry()
    expect(store.status).toBe('success')
    expect(list).toHaveBeenLastCalledWith({ filters: { bedrooms: 3 }, bounds: box }, expect.any(AbortSignal))
  })

  it('clears the selection when the selected home leaves the viewport', async () => {
    list.mockResolvedValueOnce(page([1, 2]))
    list.mockResolvedValueOnce(page([3]))
    const store = useSearchStore()

    await store.search({}, box)
    store.selectedId = 2
    await store.search({}, box)

    expect(store.selectedId).toBeNull()
  })
})
