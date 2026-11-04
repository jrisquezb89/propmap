<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted } from 'vue'
import { START_LOCATION, useRoute, useRouter } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import WakingBanner from '@/components/WakingBanner.vue'
import { useServerStore } from '@/stores/server'

const route = useRoute()
const router = useRouter()
const server = useServerStore()

// Start waking the backend immediately, in parallel with rendering.
onMounted(() => server.ensureAwake())

/*
 * SPA navigation doesn't move focus, so screen-reader users wouldn't know the
 * page changed. Move focus to the new page's heading (path changes only, not
 * query changes from filters or map panning).
 */
const removeHook = router.afterEach(async (to, from) => {
  if (from === START_LOCATION || to.path === from.path) return
  await nextTick()
  document.querySelector<HTMLElement>('#main h1')?.focus({ preventScroll: true })
})
onBeforeUnmount(removeHook)
</script>

<template>
  <div class="flex flex-col bg-canvas" :class="route.meta.fullHeight ? 'h-dvh overflow-hidden' : 'min-h-dvh'">
    <a href="#main" class="skip-link">Skip to content</a>
    <AppHeader />
    <WakingBanner />
    <main id="main" class="flex min-h-0 flex-1 flex-col">
      <RouterView />
    </main>
  </div>
</template>
