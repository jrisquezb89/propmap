import type { Property } from '@/api/types'
import { formatPriceShort } from '@/lib/format'

/**
 * Marker visuals are plain DOM nodes (Advanced Markers take any element as
 * content). State is a CSS class toggle, so highlighting one pin never
 * re-renders the others.
 */
export function createPricePin(property: Property): HTMLElement {
  const el = document.createElement('div')
  el.className = 'price-pin'
  el.textContent = formatPriceShort(property.price)
  return el
}

export function createClusterPin(count: number): HTMLElement {
  const el = document.createElement('div')
  el.className = 'cluster-pin'
  // Grow with the cluster size, within sensible limits.
  const size = Math.min(56, 32 + Math.log2(count) * 4)
  el.style.width = el.style.height = `${size}px`
  el.textContent = String(count)
  return el
}
