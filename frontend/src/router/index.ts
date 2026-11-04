import { createRouter, createWebHistory } from 'vue-router'
import SearchView from '@/views/SearchView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    /** The page fills the viewport and scrolls internally (search: list + map). */
    fullHeight?: boolean
  }
}

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'search', component: SearchView, meta: { title: 'Miami homes on a map', fullHeight: true } },
    {
      path: '/properties/:id(\\d+)',
      name: 'property',
      // Code-split: the search page doesn't pay for the detail page.
      component: () => import('@/views/PropertyView.vue'),
      meta: { title: 'Home details' },
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue'), meta: { title: 'Not found' } },
  ],
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.path !== from.path) return { top: 0 }
  },
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · PropMap` : 'PropMap'
})
