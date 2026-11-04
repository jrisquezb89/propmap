<script setup lang="ts">
import { mapsConfigured } from '@/composables/useGoogleMaps'

defineProps<{ compact?: boolean }>()

const showSetupHint = import.meta.env.DEV && !mapsConfigured
</script>

<template>
  <div class="absolute inset-0 grid place-items-center bg-muted p-6 text-center">
    <div class="max-w-xs">
      <svg class="mx-auto size-8 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
      </svg>
      <p class="mt-2 text-sm font-medium text-fg-soft">Map unavailable</p>
      <p v-if="!compact" class="mt-1 text-sm text-fg-muted">
        <template v-if="showSetupHint">
          Add <code class="rounded bg-surface px-1">VITE_GOOGLE_MAPS_API_KEY</code> and
          <code class="rounded bg-surface px-1">VITE_GOOGLE_MAPS_MAP_ID</code> to <code class="rounded bg-surface px-1">frontend/.env</code>.
        </template>
        <template v-else>The map couldn't load, but you can still browse every home in the list.</template>
      </p>
    </div>
  </div>
</template>
