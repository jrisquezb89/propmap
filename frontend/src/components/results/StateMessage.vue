<script setup lang="ts">
defineProps<{
  title: string
  tone?: 'neutral' | 'error'
}>()
</script>

<template>
  <div class="mx-auto max-w-sm py-12 text-center" :role="tone === 'error' ? 'alert' : undefined">
    <div
      class="mx-auto grid size-12 place-items-center rounded-full"
      :class="tone === 'error' ? 'bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400' : 'bg-accent-soft text-accent'"
      aria-hidden="true"
    >
      <svg v-if="tone === 'error'" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
      </svg>
      <svg v-else class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
      </svg>
    </div>
    <h2 class="mt-4 text-base font-semibold text-fg">{{ title }}</h2>
    <p class="mt-1 text-sm text-fg-muted"><slot /></p>
    <div v-if="$slots.actions" class="mt-5 flex justify-center gap-3"><slot name="actions" /></div>
  </div>
</template>
