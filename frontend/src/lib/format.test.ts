import { describe, expect, it } from 'vitest'
import { formatPrice, formatPriceShort, imageAt, listedAgo, specsLine } from './format'

describe('format', () => {
  it('formats full prices', () => {
    expect(formatPrice(1_250_000)).toBe('$1,250,000')
  })

  it.each([
    [530_000, '$530K'],
    [999_000, '$999K'],
    [1_000_000, '$1M'],
    [1_250_000, '$1.25M'],
    [12_400_000, '$12.4M'],
    [800, '$800'],
  ])('formats short prices: %i -> %s', (price, expected) => {
    expect(formatPriceShort(price)).toBe(expected)
  })

  it('builds the specs line, with studios', () => {
    expect(specsLine({ bedrooms: 3, bathrooms: 2.5, sqft: 1400 })).toBe('3 bd · 2.5 ba · 1,400 sqft')
    expect(specsLine({ bedrooms: 0, bathrooms: 1, sqft: 520 })).toBe('Studio · 1 ba · 520 sqft')
  })

  it('describes listing age', () => {
    const now = new Date('2026-11-04T12:00:00Z')
    expect(listedAgo('2026-11-04T08:00:00Z', now)).toBe('Listed today')
    expect(listedAgo('2026-11-03T08:00:00Z', now)).toBe('Listed yesterday')
    expect(listedAgo('2026-10-25T12:00:00Z', now)).toBe('Listed 10 days ago')
  })

  it('resizes Unsplash images and leaves other URLs alone', () => {
    expect(imageAt('https://images.unsplash.com/photo-1?auto=format&w=1200', 480)).toBe(
      'https://images.unsplash.com/photo-1?auto=format&w=480',
    )
    expect(imageAt('https://example.com/a.jpg', 480)).toBe('https://example.com/a.jpg')
  })
})
