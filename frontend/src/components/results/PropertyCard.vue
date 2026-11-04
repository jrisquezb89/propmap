<script setup lang="ts">
import type { Property } from '@/api/types'
import { formatPrice, imageAt, listedAgo, specsLine, typeLabel } from '@/lib/format'

defineProps<{
  property: Property
  active?: boolean
  /** Eager-load images for the first cards (above the fold). */
  eager?: boolean
  /** Horizontal layout with a thumbnail: the mobile map preview. */
  compact?: boolean
}>()

defineEmits<{ hover: [id: number | null] }>()
</script>

<template>
  <article
    :id="`property-${property.id}`"
    class="group relative flex overflow-hidden rounded-xl bg-surface shadow-sm ring-1 transition-shadow focus-within:ring-2 focus-within:ring-accent-ring hover:shadow-md"
    :class="[active ? 'shadow-md ring-2 ring-accent-ring' : 'ring-line', compact ? 'flex-row' : 'flex-col']"
    @mouseenter="$emit('hover', property.id)"
    @mouseleave="$emit('hover', null)"
    @focusin="$emit('hover', property.id)"
    @focusout="$emit('hover', null)"
  >
    <div class="relative shrink-0 overflow-hidden bg-muted" :class="compact ? 'w-28' : 'aspect-[16/10]'">
      <img
        :src="imageAt(property.image_url, 640)"
        :srcset="`${imageAt(property.image_url, 480)} 480w, ${imageAt(property.image_url, 800)} 800w`"
        sizes="(min-width: 1280px) 20vw, (min-width: 768px) 40vw, 100vw"
        :alt="''"
        width="640"
        height="400"
        :loading="eager ? 'eager' : 'lazy'"
        decoding="async"
        class="h-full w-full object-cover transition-transform duration-500 motion-safe:group-hover:scale-[1.03]"
      />
      <span v-if="!compact" class="absolute left-3 top-3 rounded-full bg-surface/95 px-2.5 py-0.5 text-xs font-semibold text-fg-soft shadow-sm">
        {{ typeLabel(property.type) }}
      </span>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1" :class="compact ? 'py-3 pl-3 pr-10' : 'p-4'">
      <p class="text-lg font-bold tracking-tight text-fg">{{ formatPrice(property.price) }}</p>
      <p class="text-sm text-fg-soft">{{ specsLine(property) }}</p>
      <h3 class="mt-1 text-sm font-medium text-fg" :class="compact ? 'line-clamp-1' : 'line-clamp-2'">
        <!-- Stretched link: the whole card is clickable, but the accessible name is just the title. -->
        <RouterLink
          :to="{ name: 'property', params: { id: property.id } }"
          class="outline-none after:absolute after:inset-0 after:content-['']"
        >
          {{ property.title }}
        </RouterLink>
      </h3>
      <p class="line-clamp-1 text-sm text-fg-muted">{{ property.address }}</p>
      <p v-if="!compact" class="mt-auto pt-2 text-xs text-fg-muted">{{ property.neighborhood }} · {{ listedAgo(property.listed_at) }}</p>
    </div>
  </article>
</template>
