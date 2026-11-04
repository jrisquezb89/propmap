<script setup lang="ts">
import { computed, nextTick, watch } from 'vue'
import type { Property } from '@/api/types'
import { prefersReducedMotion } from '@/composables/useMediaQuery'
import { formatNumber } from '@/lib/format'
import PropertyCard from './PropertyCard.vue'
import PropertyCardSkeleton from './PropertyCardSkeleton.vue'
import StateMessage from './StateMessage.vue'

export type ResultsPhase = 'loading' | 'error' | 'down' | 'results'

const props = defineProps<{
  phase: ResultsPhase
  properties: Property[]
  total: number
  error: string | null
  loadMoreError: string | null
  canLoadMore: boolean
  loadingMore: boolean
  hoveredId: number | null
  selectedId: number | null
  isFiltered: boolean
  waking: boolean
  /** False when the map is unavailable and results cover all of Miami. */
  hasViewport: boolean
}>()

const emit = defineEmits<{
  hover: [id: number | null]
  loadMore: []
  retry: []
  clearFilters: []
}>()

const summary = computed(() => {
  if (props.phase === 'loading') return props.waking ? 'Waking up the server…' : 'Loading homes…'
  if (props.phase !== 'results') return ''
  const n = props.total
  return `${formatNumber(n)} ${n === 1 ? 'home' : 'homes'} ${props.hasViewport ? 'in this area' : 'in Miami'}`
})

const shown = computed(() => props.properties.length)

/** A marker was clicked: bring its card into view and move focus to it. */
watch(
  () => props.selectedId,
  async (id) => {
    if (id === null) return
    await nextTick()
    const card = document.getElementById(`property-${id}`)
    if (!card) return
    card.scrollIntoView({ block: 'nearest', behavior: prefersReducedMotion() ? 'auto' : 'smooth' })
    card.querySelector<HTMLAnchorElement>('a')?.focus({ preventScroll: true })
  },
)
</script>

<template>
  <div class="px-4 pb-24 pt-4 md:px-5 md:pb-8">
    <div class="mb-4 flex items-baseline justify-between gap-3">
      <h1 id="results-heading" tabindex="-1" class="text-xl font-bold tracking-tight text-fg outline-none">
        Homes for sale in Miami
      </h1>
    </div>
    <!-- Announced to screen readers whenever the results change. -->
    <p class="-mt-3 mb-4 min-h-5 text-sm text-fg-muted" aria-live="polite" aria-atomic="true">{{ summary }}</p>

    <div v-if="phase === 'loading'" class="grid gap-4 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2" aria-busy="true">
      <PropertyCardSkeleton v-for="n in 6" :key="n" />
    </div>

    <StateMessage v-else-if="phase === 'down'" title="The server is taking a nap" tone="error">
      It's hosted on a free tier that sleeps when idle, and it didn't wake up in time.
      <template #actions>
        <button type="button" class="btn-primary" @click="emit('retry')">Try again</button>
      </template>
    </StateMessage>

    <StateMessage v-else-if="phase === 'error'" title="Couldn't load homes" tone="error">
      {{ error }}
      <template #actions>
        <button type="button" class="btn-primary" @click="emit('retry')">Try again</button>
      </template>
    </StateMessage>

    <StateMessage v-else-if="properties.length === 0" :title="hasViewport ? 'No homes in this area' : 'No homes match'">
      <template v-if="hasViewport">Try zooming out or moving the map{{ isFiltered ? ', or loosen your filters' : '' }}.</template>
      <template v-else>Try loosening your filters.</template>
      <template v-if="isFiltered" #actions>
        <button type="button" class="btn-secondary" @click="emit('clearFilters')">Clear filters</button>
      </template>
    </StateMessage>

    <template v-else>
      <p v-if="total > shown" class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 px-3 py-2 text-sm text-amber-900 dark:text-amber-200 ring-1 ring-amber-200 dark:ring-amber-800/60">
        Showing {{ formatNumber(shown) }} of {{ formatNumber(total) }}.{{ hasViewport ? ' Zoom in to narrow down, or load more below.' : ' Load more below.' }}
      </p>
      <ul class="grid gap-4 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2" aria-labelledby="results-heading">
        <li v-for="(property, i) in properties" :key="property.id">
          <PropertyCard
            :property="property"
            :active="property.id === hoveredId || property.id === selectedId"
            :eager="i < 4"
            @hover="emit('hover', $event)"
          />
        </li>
      </ul>

      <div v-if="canLoadMore || loadMoreError" class="mt-6 text-center">
        <p v-if="loadMoreError" class="mb-3 text-sm text-red-700 dark:text-red-400" role="alert">{{ loadMoreError }}</p>
        <button type="button" class="btn-secondary" :disabled="loadingMore" @click="emit('loadMore')">
          {{ loadingMore ? 'Loading…' : loadMoreError ? 'Try again' : 'Load more homes' }}
        </button>
      </div>
    </template>
  </div>
</template>
