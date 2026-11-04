import type { Property, PropertyType } from '@/api/types'

const usd = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 })
const integer = new Intl.NumberFormat('en-US')

/** $1,250,000 */
export function formatPrice(price: number): string {
  return usd.format(price)
}

/** $530K, $1.25M: for map pins and select options. */
export function formatPriceShort(price: number): string {
  if (price >= 1_000_000) {
    const m = price / 1_000_000
    return `$${Number(m.toFixed(m >= 10 ? 1 : 2))}M`
  }
  if (price >= 1_000) return `$${Math.round(price / 1_000)}K`
  return `$${price}`
}

export function formatNumber(n: number): string {
  return integer.format(n)
}

export function bedroomsLabel(bedrooms: number): string {
  return bedrooms === 0 ? 'Studio' : `${bedrooms} bd`
}

/** "3 bd · 2.5 ba · 1,400 sqft" */
export function specsLine(p: Pick<Property, 'bedrooms' | 'bathrooms' | 'sqft'>): string {
  return [bedroomsLabel(p.bedrooms), `${p.bathrooms} ba`, `${formatNumber(p.sqft)} sqft`].join(' · ')
}

const TYPE_LABELS: Record<PropertyType, string> = { house: 'House', condo: 'Condo', townhouse: 'Townhouse' }

export function typeLabel(type: PropertyType): string {
  return TYPE_LABELS[type]
}

/** Screen-reader label for a map pin. */
export function markerLabel(p: Property): string {
  return `${typeLabel(p.type)}, ${formatPrice(p.price)}, ${bedroomsLabel(p.bedrooms)}, ${p.neighborhood}`
}

const listedFormat = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

export function formatListedAt(iso: string): string {
  return listedFormat.format(new Date(iso))
}

/** "Listed 3 days ago" */
export function listedAgo(iso: string, now = new Date()): string {
  const days = Math.max(0, Math.floor((now.getTime() - new Date(iso).getTime()) / 86_400_000))
  if (days === 0) return 'Listed today'
  if (days === 1) return 'Listed yesterday'
  return `Listed ${days} days ago`
}

/** Resize an Unsplash image URL (keeps other params). */
export function imageAt(url: string, width: number): string {
  try {
    const u = new URL(url)
    if (u.hostname === 'images.unsplash.com') u.searchParams.set('w', String(width))
    return u.toString()
  } catch {
    return url
  }
}
