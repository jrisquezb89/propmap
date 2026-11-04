<script setup lang="ts">
import { computed, ref } from 'vue'
import { PROPERTY_TYPES, type PropertyType } from '@/api/types'
import { useUrlFilters } from '@/composables/useUrlFilters'
import { BEDROOM_OPTIONS, PRICE_STEPS } from '@/lib/filters'
import { formatPriceShort, typeLabel } from '@/lib/format'

/*
 * Every control writes straight to the URL; the displayed values are read back
 * from it. Price uses two native selects with preset steps instead of a
 * dual-thumb slider: accessible by default and easy on touch screens.
 */
const { filters, isFiltered, setFilters, clearFilters } = useUrlFilters()

const minOptions = computed(() => PRICE_STEPS.filter((p) => filters.value.maxPrice === undefined || p <= filters.value.maxPrice))
const maxOptions = computed(() => PRICE_STEPS.filter((p) => filters.value.minPrice === undefined || p >= filters.value.minPrice))

/** Mobile only: the filters collapse behind a button so results get the screen. */
const open = ref(false)
const activeCount = computed(() => Object.values(filters.value).filter((v) => v !== undefined).length)

function toNumber(value: string): number | undefined {
  return value === '' ? undefined : Number(value)
}

function onType(value: string) {
  setFilters({ type: value === '' ? undefined : (value as PropertyType) })
}
</script>

<template>
  <div class="border-b border-line bg-surface px-4 py-2.5 md:px-5 md:py-3">
    <div class="flex items-center justify-between gap-3 md:hidden">
      <button
        type="button"
        class="btn-secondary gap-2"
        :aria-expanded="open"
        aria-controls="filters-form"
        @click="open = !open"
      >
        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path d="M10 3.75a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM17.25 4.5a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM5 3.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 1 .75.75ZM4.25 17a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM17.25 17a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM9 10a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1 0-1.5h5.5A.75.75 0 0 1 9 10ZM17.25 10.75a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM14 10a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM10 16.25a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" />
        </svg>
        Filters
        <span v-if="activeCount" class="grid size-5 place-items-center rounded-full bg-teal-700 text-xs text-white">
          {{ activeCount }}<span class="sr-only"> active</span>
        </span>
      </button>
      <button v-if="isFiltered" type="button" class="btn-ghost" @click="clearFilters()">Clear filters</button>
    </div>

  <form
    id="filters-form"
    class="flex-wrap items-end gap-x-4 gap-y-3 pt-3 md:flex md:pt-0"
    :class="open ? 'flex' : 'hidden'"
    aria-label="Search filters"
    @submit.prevent
  >
    <div class="flex items-end gap-2">
      <label class="field">
        <span class="field-label">Min price</span>
        <select class="control" :value="filters.minPrice ?? ''" @change="setFilters({ minPrice: toNumber(($event.target as HTMLSelectElement).value) })">
          <option value="">No min</option>
          <option v-for="p in minOptions" :key="p" :value="p">{{ formatPriceShort(p) }}</option>
        </select>
      </label>
      <span class="pb-2.5 text-fg-subtle" aria-hidden="true">–</span>
      <label class="field">
        <span class="field-label">Max price</span>
        <select class="control" :value="filters.maxPrice ?? ''" @change="setFilters({ maxPrice: toNumber(($event.target as HTMLSelectElement).value) })">
          <option value="">No max</option>
          <option v-for="p in maxOptions" :key="p" :value="p">{{ formatPriceShort(p) }}</option>
        </select>
      </label>
    </div>

    <fieldset class="field">
      <legend class="field-label mb-1">Bedrooms</legend>
      <div class="flex rounded-lg bg-muted p-0.5">
        <label v-for="opt in [undefined, ...BEDROOM_OPTIONS]" :key="opt ?? 'any'" class="segment">
          <input
            type="radio"
            name="bedrooms"
            class="peer sr-only"
            :value="opt ?? ''"
            :checked="filters.bedrooms === opt"
            @change="setFilters({ bedrooms: opt })"
          />
          <span class="segment-label">{{ opt === undefined ? 'Any' : `${opt}+` }}</span>
        </label>
      </div>
    </fieldset>

    <label class="field">
      <span class="field-label">Type</span>
      <select class="control" :value="filters.type ?? ''" @change="onType(($event.target as HTMLSelectElement).value)">
        <option value="">Any type</option>
        <option v-for="t in PROPERTY_TYPES" :key="t" :value="t">{{ typeLabel(t) }}</option>
      </select>
    </label>

    <button v-if="isFiltered" type="button" class="btn-ghost mb-0.5 hidden md:inline-flex" @click="clearFilters()">
      Clear filters
    </button>
  </form>
  </div>
</template>
