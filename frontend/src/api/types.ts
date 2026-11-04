/** Mirrors App\Http\Resources\PropertyResource in the Laravel API. */
export interface Property {
  id: number
  title: string
  description: string
  price: number
  bedrooms: number
  bathrooms: number
  sqft: number
  type: PropertyType
  address: string
  neighborhood: string
  lat: number
  lng: number
  image_url: string
  listed_at: string
}

export const PROPERTY_TYPES = ['house', 'condo', 'townhouse'] as const
export type PropertyType = (typeof PROPERTY_TYPES)[number]

/** Laravel's paginated resource collection envelope. */
export interface Paginated<T> {
  data: T[]
  links: { first: string | null; last: string | null; prev: string | null; next: string | null }
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }
}

export interface Bounds {
  north: number
  south: number
  east: number
  west: number
}

/** Search filters as the UI understands them. Undefined means "any". */
export interface Filters {
  minPrice?: number
  maxPrice?: number
  bedrooms?: number
  type?: PropertyType
}

/** Map camera, persisted in the URL so a shared link opens the same view. */
export interface MapView {
  lat: number
  lng: number
  zoom: number
}
