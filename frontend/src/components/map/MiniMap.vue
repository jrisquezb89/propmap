<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'
import type { Property } from '@/api/types'
import { loadGoogleMaps, MAP_ID, mapsAuthFailed, mapsConfigured } from '@/composables/useGoogleMaps'
import { isDark } from '@/composables/useTheme'
import { markerLabel } from '@/lib/format'
import MapUnavailable from './MapUnavailable.vue'
import { createPricePin } from './pins'

const props = defineProps<{ property: Property }>()

const container = useTemplateRef<HTMLDivElement>('container')
const unavailable = ref(!mapsConfigured)

let map: google.maps.Map | null = null
let marker: google.maps.marker.AdvancedMarkerElement | null = null

onMounted(async () => {
  if (!mapsConfigured) return
  try {
    const { Map, AdvancedMarkerElement } = await loadGoogleMaps()
    if (!container.value) return
    const position = { lat: props.property.lat, lng: props.property.lng }
    map = new Map(container.value, {
      mapId: MAP_ID,
      colorScheme: isDark.value ? 'DARK' : 'LIGHT',
      center: position,
      zoom: 15,
      disableDefaultUI: true,
      zoomControl: true,
      clickableIcons: false,
      // The page scrolls; one-finger / wheel scrolling shouldn't get trapped by the map.
      gestureHandling: 'cooperative',
    })
    const pin = createPricePin(props.property)
    pin.classList.add('is-selected')
    marker = new AdvancedMarkerElement({ map, position, content: pin, title: markerLabel(props.property) })
  } catch {
    unavailable.value = true
  }
})

watch(mapsAuthFailed, (failed) => failed && (unavailable.value = true))

watch(
  () => props.property,
  (p) => {
    const position = { lat: p.lat, lng: p.lng }
    map?.setCenter(position)
    if (marker) marker.position = position
  },
)

onBeforeUnmount(() => {
  if (marker) marker.map = null
})
</script>

<template>
  <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-muted ring-1 ring-line">
    <div v-show="!unavailable" ref="container" class="h-full w-full" role="region" :aria-label="`Map showing ${property.address}`" />
    <MapUnavailable v-if="unavailable" compact />
  </div>
</template>
