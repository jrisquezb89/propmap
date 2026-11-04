import { importLibrary, setOptions } from '@googlemaps/js-api-loader'
import { ref } from 'vue'

const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY ?? ''
export const MAP_ID = import.meta.env.VITE_GOOGLE_MAPS_MAP_ID ?? ''

/** False when the key or Map ID is missing: the app still works, just without maps. */
export const mapsConfigured = Boolean(apiKey && MAP_ID)

/** Set when Google rejects the key (wrong key, referrer not allowed, billing off). */
export const mapsAuthFailed = ref(false)

export interface MapsLibraries {
  Map: typeof google.maps.Map
  AdvancedMarkerElement: typeof google.maps.marker.AdvancedMarkerElement
}

let loading: Promise<MapsLibraries> | null = null

/**
 * Loads the Maps JavaScript API once per page, no matter how many maps mount.
 * Rejects if maps aren't configured or the script fails to load.
 */
export function loadGoogleMaps(): Promise<MapsLibraries> {
  if (!mapsConfigured) return Promise.reject(new Error('Google Maps is not configured.'))

  loading ??= (async () => {
    // Google calls this global when the key is rejected (the script itself loads fine).
    ;(window as unknown as { gm_authFailure?: () => void }).gm_authFailure = () => {
      mapsAuthFailed.value = true
    }
    setOptions({ key: apiKey, v: 'weekly', mapIds: [MAP_ID] })
    const [{ Map }, { AdvancedMarkerElement }] = await Promise.all([importLibrary('maps'), importLibrary('marker')])
    return { Map, AdvancedMarkerElement }
  })().catch((error: unknown) => {
    loading = null // allow a retry on the next mount
    throw error
  })

  return loading
}
