<script setup lang="ts">
import { computed, ref, shallowRef, watch } from 'vue'
import { useRoute } from 'vue-router'
import type { Bounds, MapView } from '@/api/types'
import FiltersBar from '@/components/filters/FiltersBar.vue'
import PropertyMap from '@/components/map/PropertyMap.vue'
import MobileViewToggle, { type MobileView } from '@/components/MobileViewToggle.vue'
import PropertyCard from '@/components/results/PropertyCard.vue'
import ResultsList, { type ResultsPhase } from '@/components/results/ResultsList.vue'
import { mapsConfigured } from '@/composables/useGoogleMaps'
import { useMediaQuery } from '@/composables/useMediaQuery'
import { isDark } from '@/composables/useTheme'
import { useUrlFilters } from '@/composables/useUrlFilters'
import { DEFAULT_VIEW } from '@/lib/filters'
import { useSearchStore } from '@/stores/search'
import { useServerStore } from '@/stores/server'

const route = useRoute()
const server = useServerStore()
const search = useSearchStore()
const { filters, key: filtersKey, view, isFiltered, setView, clearFilters } = useUrlFilters()

const isDesktop = useMediaQuery('(min-width: 768px)')
const mobileView = ref<MobileView>('list')

/**
 * Only read when the map mounts: after that the map owns the camera and writes
 * it back to the URL. A theme change remounts the map (colorScheme is init-only)
 * and it reopens exactly where it was, because the camera lives in the URL.
 */
const initialView = computed(() => view.value ?? DEFAULT_VIEW)

const bounds = shallowRef<Bounds | null>(null)
const mapUnavailable = ref(!mapsConfigured)

// Rounded, so sub-pixel camera jitter doesn't trigger a new request.
const boundsKey = computed(() =>
  bounds.value ? Object.values(bounds.value).map((n: number) => n.toFixed(5)).join(',') : '',
)

function onMapIdle(next: Bounds, nextView: MapView) {
  bounds.value = next
  setView(nextView)
}

/** Without a map there's no viewport: fall back to searching all of Miami. */
function onMapUnavailable() {
  mapUnavailable.value = true
}

// Search when the filters or the viewport change, once the server is awake.
watch(
  [filtersKey, boundsKey, () => server.isReady, mapUnavailable],
  () => {
    if (!server.isReady) return
    if (mapUnavailable.value) return search.search(filters.value, null)
    if (bounds.value) return search.search(filters.value, bounds.value)
  },
  { immediate: true },
)

// Remember this search so the detail page can link back to it.
watch(
  () => route.query,
  (q) => (search.lastSearchQuery = q),
  { immediate: true },
)

const phase = computed<ResultsPhase>(() => {
  if (server.status === 'down') return 'down'
  if (!server.isReady || search.status === 'idle' || search.status === 'loading') return 'loading'
  if (search.status === 'error') return 'error'
  return 'results'
})

function onSelect(id: number) {
  search.selectedId = id
}

function retry() {
  if (server.status === 'down') return server.ensureAwake()
  return search.retry()
}

const showListOnMobile = computed(() => mobileView.value === 'list')
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <FiltersBar />

    <!--
      Mobile: list and map share one grid cell and the inactive one is made
      invisible + inert. Not display:none: a hidden map has zero size, which
      would report a degenerate viewport and cost a fresh map load on toggle.
    -->
    <div class="grid min-h-0 flex-1 grid-cols-1 grid-rows-1 md:grid-cols-[minmax(22rem,44%)_1fr] xl:grid-cols-[minmax(22rem,40%)_1fr]">
      <section
        id="results-panel"
        class="col-start-1 row-start-1 overflow-y-auto overscroll-contain bg-canvas"
        :class="{ invisible: !isDesktop && !showListOnMobile }"
        :inert="!isDesktop && !showListOnMobile"
        aria-labelledby="results-heading"
      >
        <ResultsList
          :phase="phase"
          :properties="search.properties"
          :total="search.total"
          :error="search.error"
          :load-more-error="search.loadMoreError"
          :can-load-more="search.canLoadMore"
          :loading-more="search.loadingMore"
          :hovered-id="search.hoveredId"
          :selected-id="isDesktop ? search.selectedId : null"
          :is-filtered="isFiltered"
          :waking="server.status === 'waking'"
          :has-viewport="!mapUnavailable"
          @hover="search.hoveredId = $event"
          @load-more="search.loadMore()"
          @retry="retry()"
          @clear-filters="clearFilters()"
        />
      </section>

      <section
        id="map-panel"
        class="relative col-start-1 row-start-1 md:col-start-2"
        :class="{ invisible: !isDesktop && showListOnMobile }"
        :inert="!isDesktop && showListOnMobile"
        aria-label="Map"
      >
        <PropertyMap
          :key="isDark ? 'dark' : 'light'"
          :properties="search.properties"
          :hovered-id="search.hoveredId"
          :selected-id="search.selectedId"
          :initial-view="initialView"
          @idle="onMapIdle"
          @select="onSelect"
          @hover="search.hoveredId = $event"
          @unavailable="onMapUnavailable"
        />

        <!-- Mobile map view: tapping a pin shows a preview card instead of jumping to the list. -->
        <div v-if="!isDesktop && search.selected" class="absolute inset-x-3 bottom-[5.5rem] z-10">
          <div class="relative">
            <PropertyCard :property="search.selected" active compact />
            <button
              type="button"
              class="absolute right-2 top-2 z-10 grid size-8 place-items-center rounded-full bg-surface/95 text-fg-soft shadow"
              aria-label="Close preview"
              @click="search.selectedId = null"
            >
              <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
            </button>
          </div>
        </div>
      </section>
    </div>

    <MobileViewToggle v-if="!mapUnavailable" v-model="mobileView" />
  </div>
</template>
