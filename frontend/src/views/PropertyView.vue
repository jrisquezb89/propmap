<script setup lang="ts">
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ApiError, isAbortError } from '@/api/client'
import type { Property } from '@/api/types'
import MiniMap from '@/components/map/MiniMap.vue'
import StateMessage from '@/components/results/StateMessage.vue'
import { formatListedAt, formatNumber, formatPrice, imageAt, typeLabel, bedroomsLabel } from '@/lib/format'
import { isDark } from '@/composables/useTheme'
import { useSearchStore } from '@/stores/search'
import { useServerStore } from '@/stores/server'

const route = useRoute()
const server = useServerStore()
const search = useSearchStore()

const id = computed(() => Number(route.params.id))
const property = shallowRef<Property | null>(null)
const state = ref<'loading' | 'ready' | 'notfound' | 'error'>('loading')
let controller: AbortController | null = null

/** Back to the exact search (filters + map position) the user came from. */
const backTo = computed(() => ({ name: 'search', query: search.lastSearchQuery }))

async function load() {
  controller?.abort()
  controller = new AbortController()
  const { signal } = controller

  // Coming from the list: render instantly from cache, then refresh in the background.
  const cached = search.cached(id.value)
  property.value = cached ?? null
  state.value = cached ? 'ready' : 'loading'

  if (!(await server.ensureAwake())) {
    if (!cached) state.value = 'error'
    return
  }

  try {
    property.value = await search.fetchProperty(id.value, signal)
    state.value = 'ready'
  } catch (e) {
    if (isAbortError(e)) return
    if (e instanceof ApiError && e.status === 404) state.value = 'notfound'
    else if (!cached) state.value = 'error'
  }
}

watch(id, load, { immediate: true })
onBeforeUnmount(() => controller?.abort())

watch(property, (p) => {
  if (p) document.title = `${p.title} · ${formatPrice(p.price)} · PropMap`
})

const facts = computed(() => {
  const p = property.value
  if (!p) return []
  return [
    { label: 'Bedrooms', value: p.bedrooms === 0 ? 'Studio' : String(p.bedrooms) },
    { label: 'Bathrooms', value: String(p.bathrooms) },
    { label: 'Living area', value: `${formatNumber(p.sqft)} sqft` },
    { label: 'Price / sqft', value: formatPrice(Math.round(p.price / p.sqft)) },
    { label: 'Type', value: typeLabel(p.type) },
    { label: 'Listed', value: formatListedAt(p.listed_at) },
  ]
})
</script>

<template>
  <div class="mx-auto w-full max-w-6xl px-4 py-6 md:px-6 md:py-8">
    <RouterLink :to="backTo" class="inline-flex items-center gap-1 rounded text-sm font-medium text-accent hover:text-accent-strong">
      <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
      </svg>
      Back to results
    </RouterLink>

    <!-- Loading skeleton -->
    <div v-if="state === 'loading'" class="mt-6 grid gap-8 lg:grid-cols-[1fr_22rem]" aria-busy="true">
      <h1 tabindex="-1" class="sr-only">Loading property</h1>
      <div class="space-y-4" aria-hidden="true">
        <div class="aspect-[16/10] rounded-2xl bg-skeleton motion-safe:animate-pulse" />
        <div class="h-7 w-2/3 rounded bg-skeleton motion-safe:animate-pulse" />
        <div class="h-4 w-1/2 rounded bg-skeleton motion-safe:animate-pulse" />
      </div>
      <div class="h-64 rounded-2xl bg-skeleton motion-safe:animate-pulse" aria-hidden="true" />
    </div>

    <StateMessage v-else-if="state === 'notfound'" title="This home isn't listed anymore">
      It may have sold or been removed. There's plenty more on the map.
      <template #actions>
        <RouterLink :to="backTo" class="btn-primary">Browse homes</RouterLink>
      </template>
    </StateMessage>

    <StateMessage v-else-if="state === 'error'" title="Couldn't load this home" tone="error">
      Check your connection and try again.
      <template #actions>
        <button type="button" class="btn-primary" @click="load()">Try again</button>
      </template>
    </StateMessage>

    <article v-else-if="property" class="mt-6 grid gap-8 lg:grid-cols-[1fr_22rem]">
      <div class="min-w-0">
        <img
          :src="imageAt(property.image_url, 1200)"
          :srcset="`${imageAt(property.image_url, 800)} 800w, ${imageAt(property.image_url, 1600)} 1600w`"
          sizes="(min-width: 1024px) 720px, 100vw"
          alt=""
          width="1200"
          height="750"
          class="aspect-[16/10] w-full rounded-2xl bg-muted object-cover"
        />

        <div class="mt-6">
          <p class="text-sm font-medium text-accent">{{ typeLabel(property.type) }} · {{ property.neighborhood }}</p>
          <h1 tabindex="-1" class="mt-1 text-2xl font-bold tracking-tight text-fg outline-none md:text-3xl">
            {{ property.title }}
          </h1>
          <p class="mt-1 text-fg-muted">{{ property.address }}</p>
        </div>

        <section class="mt-8" aria-labelledby="about-heading">
          <h2 id="about-heading" class="text-lg font-semibold text-fg">About this home</h2>
          <p class="mt-2 leading-relaxed text-fg-soft">{{ property.description }}</p>
        </section>

        <section class="mt-8" aria-labelledby="facts-heading">
          <h2 id="facts-heading" class="text-lg font-semibold text-fg">Facts</h2>
          <dl class="mt-3 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-line ring-1 ring-line sm:grid-cols-3">
            <div v-for="f in facts" :key="f.label" class="bg-surface p-4">
              <dt class="text-xs font-medium uppercase tracking-wide text-fg-muted">{{ f.label }}</dt>
              <dd class="mt-1 font-semibold text-fg">{{ f.value }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <aside class="lg:sticky lg:top-6 lg:self-start" aria-label="Price and location">
        <div class="rounded-2xl bg-surface p-5 shadow-sm ring-1 ring-line">
          <p class="text-3xl font-bold tracking-tight text-fg">{{ formatPrice(property.price) }}</p>
          <p class="mt-1 text-sm text-fg-muted">
            {{ bedroomsLabel(property.bedrooms) }} · {{ property.bathrooms }} ba · {{ formatNumber(property.sqft) }} sqft
          </p>
          <div class="mt-5">
            <MiniMap :key="isDark ? 'dark' : 'light'" :property="property" />
          </div>
          <p class="mt-3 text-sm text-fg-muted">{{ property.address }}</p>
        </div>
      </aside>
    </article>
  </div>
</template>
