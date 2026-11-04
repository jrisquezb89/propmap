import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { checkHealth } from '@/api/properties'

/**
 * Cold-start handling for the free-tier backend (Render sleeps after 15 min idle).
 *
 *   checking ──(answers < SLOW_AFTER_MS)──────────────▶ ready
 *      │                                                ▲
 *      └──(still waiting at SLOW_AFTER_MS, or fails)──▶ waking ──(retries)──┘
 *                                                       │
 *                                          (gives up at GIVE_UP_AFTER_MS)──▶ down
 *
 * While Render boots it usually holds the request open instead of failing,
 * so each attempt gets a generous timeout, and failed attempts back off.
 */
export type ServerStatus = 'idle' | 'checking' | 'waking' | 'ready' | 'down'

export const SLOW_AFTER_MS = 2_000
export const ATTEMPT_TIMEOUT_MS = 30_000
export const GIVE_UP_AFTER_MS = 90_000
const MAX_BACKOFF_MS = 5_000

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

export const useServerStore = defineStore('server', () => {
  const status = ref<ServerStatus>('idle')
  let inFlight: Promise<boolean> | null = null

  const isReady = computed(() => status.value === 'ready')

  /** Resolves true once the API answers. Concurrent callers share one wake-up. */
  function ensureAwake(): Promise<boolean> {
    if (status.value === 'ready') return Promise.resolve(true)
    inFlight ??= wake().finally(() => {
      inFlight = null
    })
    return inFlight
  }

  async function wake(): Promise<boolean> {
    status.value = 'checking'
    const startedAt = Date.now()
    const slowTimer = setTimeout(() => {
      if (status.value === 'checking') status.value = 'waking'
    }, SLOW_AFTER_MS)

    try {
      let backoff = 1_000
      for (;;) {
        const remaining = GIVE_UP_AFTER_MS - (Date.now() - startedAt)
        try {
          await checkHealth(Math.min(ATTEMPT_TIMEOUT_MS, Math.max(remaining, 1)))
          status.value = 'ready'
          return true
        } catch {
          if (Date.now() - startedAt + backoff >= GIVE_UP_AFTER_MS) {
            status.value = 'down'
            return false
          }
          status.value = 'waking'
          await sleep(backoff)
          backoff = Math.min(backoff * 2, MAX_BACKOFF_MS)
        }
      }
    } finally {
      clearTimeout(slowTimer)
    }
  }

  /** A request failed with a network error after we thought we were awake. */
  function markUnreachable() {
    if (status.value === 'ready') status.value = 'idle'
  }

  return { status, isReady, ensureAwake, markUnreachable }
})
