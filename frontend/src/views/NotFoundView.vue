<script setup lang="ts">
import StateMessage from '@/components/results/StateMessage.vue'
</script>

<template>
  <div class="px-4 py-16">
    <h1 tabindex="-1" class="sr-only">Page not found</h1>
    <StateMessage title="Page not found">
      That link doesn't go anywhere.
      <template #actions>
        <RouterLink to="/" class="btn-primary">Search homes</RouterLink>
      </template>
    </StateMessage>
  </div>
</template>
