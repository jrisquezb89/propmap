<script setup lang="ts">
export type MobileView = 'list' | 'map'

const view = defineModel<MobileView>({ required: true })
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 bottom-5 z-20 flex justify-center md:hidden">
    <div class="pointer-events-auto flex rounded-full bg-slate-900 p-1 shadow-lg dark:bg-slate-800 dark:ring-1 dark:ring-white/10" role="group" aria-label="Show results as">
      <button
        v-for="opt in (['list', 'map'] as const)"
        :key="opt"
        type="button"
        class="flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
        :class="view === opt ? 'bg-white text-slate-900' : 'text-white hover:bg-white/10'"
        :aria-pressed="view === opt"
        aria-controls="results-panel map-panel"
        @click="view = opt"
      >
        <svg v-if="opt === 'list'" class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M8.157 2.176a1.5 1.5 0 0 0-1.147 0l-4.084 1.69A1.5 1.5 0 0 0 2 5.25v10.877a1.5 1.5 0 0 0 2.074 1.386l3.51-1.452 4.26 1.762a1.5 1.5 0 0 0 1.146 0l4.083-1.69A1.5 1.5 0 0 0 18 14.75V3.872a1.5 1.5 0 0 0-2.073-1.386l-3.51 1.452-4.26-1.762ZM7.58 5a.75.75 0 0 1 .75.75v6.5a.75.75 0 0 1-1.5 0v-6.5A.75.75 0 0 1 7.58 5Zm5.59 2.75a.75.75 0 0 0-1.5 0v6.5a.75.75 0 0 0 1.5 0v-6.5Z" clip-rule="evenodd" />
        </svg>
        {{ opt === 'list' ? 'List' : 'Map' }}
      </button>
    </div>
  </div>
</template>
