<script setup lang="ts">
import { MarkerClusterer, SuperClusterAlgorithm } from '@googlemaps/markerclusterer'
import { onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'
import type { Bounds, MapView, Property } from '@/api/types'
import { loadGoogleMaps, MAP_ID, mapsAuthFailed, mapsConfigured } from '@/composables/useGoogleMaps'
import { isDark } from '@/composables/useTheme'
import { markerLabel } from '@/lib/format'
import MapUnavailable from './MapUnavailable.vue'
import { createClusterPin, createPricePin } from './pins'

const props = defineProps<{
  properties: Property[]
  hoveredId: number | null
  selectedId: number | null
  initialView: MapView
}>()

const emit = defineEmits<{
  idle: [bounds: Bounds, view: MapView]
  select: [id: number]
  hover: [id: number | null]
  unavailable: []
}>()

const IDLE_DEBOUNCE_MS = 300

/** Keep the camera near the data, while still letting people pan around a bit. */
const SOUTH_FLORIDA = { north: 26.4, south: 25.2, east: -79.8, west: -80.9 }

const container = useTemplateRef<HTMLDivElement>('container')
const state = ref<'loading' | 'ready' | 'unavailable'>(mapsConfigured ? 'loading' : 'unavailable')

/*
 * Google Maps objects are deliberately NOT reactive. Vue's deep proxies would
 * wrap Google's internals (slow, and can break identity checks). The template
 * never reads them, so plain variables are all we need.
 */
let map: google.maps.Map | null = null
let clusterer: MarkerClusterer | null = null
let Marker: typeof google.maps.marker.AdvancedMarkerElement | null = null
let idleTimer: ReturnType<typeof setTimeout> | undefined
const markers = new Map<number, { marker: google.maps.marker.AdvancedMarkerElement; pin: HTMLElement }>()

function fail() {
  if (state.value === 'unavailable') return
  state.value = 'unavailable'
  emit('unavailable')
}

onMounted(async () => {
  if (!mapsConfigured) return emit('unavailable')

  try {
    const libs = await loadGoogleMaps()
    if (!container.value) return // unmounted while loading
    Marker = libs.AdvancedMarkerElement

    map = new libs.Map(container.value, {
      mapId: MAP_ID,
      // Init-only option: the parent remounts this component when the theme changes.
      colorScheme: isDark.value ? 'DARK' : 'LIGHT',
      center: { lat: props.initialView.lat, lng: props.initialView.lng },
      zoom: props.initialView.zoom,
      minZoom: 10,
      restriction: { latLngBounds: SOUTH_FLORIDA, strictBounds: false },
      gestureHandling: 'greedy',
      clickableIcons: false,
      disableDefaultUI: true,
      zoomControl: true,
      // Top-right keeps it clear of the mobile preview card and toggle.
      zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_TOP },
      fullscreenControl: false,
    })

    clusterer = new MarkerClusterer({
      map,
      algorithm: new SuperClusterAlgorithm({ radius: 70, maxZoom: 15 }),
      renderer: {
        render: ({ count, position }) =>
          new libs.AdvancedMarkerElement({
            position,
            content: createClusterPin(count),
            title: `${count} homes. Zoom in to see them.`,
            zIndex: 1_000 + count,
          }),
      },
    })

    map.addListener('idle', () => {
      clearTimeout(idleTimer)
      idleTimer = setTimeout(emitIdle, IDLE_DEBOUNCE_MS)
    })

    syncMarkers(props.properties)
    state.value = 'ready'
  } catch {
    fail()
  }
})

watch(mapsAuthFailed, (failed) => failed && fail())

function emitIdle() {
  const bounds = map?.getBounds()?.toJSON()
  const center = map?.getCenter()
  const zoom = map?.getZoom()
  if (!bounds || !center || zoom === undefined) return
  emit('idle', bounds, { lat: center.lat(), lng: center.lng(), zoom })
}

/** Diff the new results against the markers on the map: only add/remove what changed. */
function syncMarkers(list: Property[]) {
  if (!clusterer || !Marker) return
  const nextIds = new Set(list.map((p) => p.id))

  const removed: google.maps.marker.AdvancedMarkerElement[] = []
  for (const [id, { marker }] of markers) {
    if (!nextIds.has(id)) {
      removed.push(marker)
      markers.delete(id)
    }
  }

  const added: google.maps.marker.AdvancedMarkerElement[] = []
  for (const property of list) {
    if (markers.has(property.id)) continue
    const pin = createPricePin(property)
    const marker = new Marker({
      position: { lat: property.lat, lng: property.lng },
      content: pin,
      title: markerLabel(property),
      gmpClickable: true,
    })
    marker.addEventListener('gmp-click', () => emit('select', property.id))
    pin.addEventListener('mouseenter', () => emit('hover', property.id))
    pin.addEventListener('mouseleave', () => emit('hover', null))
    markers.set(property.id, { marker, pin })
    added.push(marker)
    applyState(property.id)
  }

  clusterer.removeMarkers(removed, true)
  clusterer.addMarkers(added, true)
  clusterer.render()
}

function applyState(id: number | null) {
  if (id === null) return
  const entry = markers.get(id)
  if (!entry) return
  const hovered = id === props.hoveredId
  const selected = id === props.selectedId
  entry.pin.classList.toggle('is-hovered', hovered)
  entry.pin.classList.toggle('is-selected', selected)
  // Bring the active pin above its neighbours.
  entry.marker.zIndex = hovered || selected ? 999 : null
}

watch(
  () => props.properties,
  (list) => syncMarkers(list),
)

watch([() => props.hoveredId, () => props.selectedId], ([h, s], [prevH, prevS]) => {
  for (const id of new Set([h, s, prevH, prevS])) applyState(id)
})

onBeforeUnmount(() => {
  clearTimeout(idleTimer)
  clusterer?.clearMarkers(true)
  clusterer?.setMap(null)
  if (map) google.maps.event.clearInstanceListeners(map)
  markers.clear()
})
</script>

<template>
  <div class="relative h-full w-full bg-muted">
    <div
      v-show="state !== 'unavailable'"
      ref="container"
      class="h-full w-full"
      role="region"
      aria-label="Map of search results"
    />
    <div
      v-if="state === 'loading'"
      class="absolute inset-0 grid place-items-center text-sm text-fg-muted"
      aria-hidden="true"
    >
      <span class="motion-safe:animate-pulse">Loading map…</span>
    </div>
    <MapUnavailable v-if="state === 'unavailable'" />
  </div>
</template>
