<script setup lang="ts">
import { useServerStore } from '@/stores/server'

const server = useServerStore()
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
    enter-from-class="-translate-y-1 opacity-0"
    leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
    leave-to-class="opacity-0"
  >
    <div
      v-if="server.status === 'waking'"
      class="flex shrink-0 items-center gap-3 border-b border-accent-line bg-accent-soft px-4 py-2.5 text-sm text-accent-strong md:px-5"
      role="status"
    >
      <svg class="size-4 shrink-0 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
      </svg>
      <p>
        <span class="font-semibold">Waking up the server…</span>
        <span class="text-accent-strong"> It runs on a free tier that sleeps when idle, so the first load can take up to a minute.</span>
      </p>
    </div>
  </Transition>
</template>
