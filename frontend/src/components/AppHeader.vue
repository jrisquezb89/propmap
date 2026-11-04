<script setup lang="ts">
import ThemeSwitcher from './ThemeSwitcher.vue'
</script>

<template>
  <header class="flex h-14 shrink-0 items-center justify-between border-b border-line bg-surface px-4 md:px-5">
    <RouterLink to="/" class="flex items-center gap-2 rounded-md font-bold tracking-tight text-fg">
      <svg class="size-7" viewBox="0 0 32 32" aria-hidden="true">
        <rect width="32" height="32" rx="8" fill="#0f766e" />
        <path d="M16 6c-4.4 0-8 3.4-8 7.7C8 19.5 16 26 16 26s8-6.5 8-12.3C24 9.4 20.4 6 16 6Zm0 10.5a2.8 2.8 0 1 1 0-5.6 2.8 2.8 0 0 1 0 5.6Z" fill="#fff" />
      </svg>
      <span class="text-lg">Prop<span class="text-accent">Map</span></span>
    </RouterLink>
    <div class="flex items-center gap-4">
      <p class="hidden text-sm text-fg-muted sm:block">Miami homes, on a map</p>
      <ThemeSwitcher />
    </div>
  </header>
</template>
