import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { NetworkError } from '@/api/client'
import { checkHealth } from '@/api/properties'
import { GIVE_UP_AFTER_MS, SLOW_AFTER_MS, useServerStore } from './server'

vi.mock('@/api/properties', () => ({ checkHealth: vi.fn() }))
const health = vi.mocked(checkHealth)

/** A health check that answers after `ms`. */
function answersAfter(ms: number) {
  return () => new Promise<{ status: string }>((resolve) => setTimeout(() => resolve({ status: 'ok' }), ms))
}

describe('server store (cold start)', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    setActivePinia(createPinia())
    health.mockReset()
  })
  afterEach(() => vi.useRealTimers())

  it('goes straight to ready when the server answers quickly (no waking banner)', async () => {
    health.mockImplementation(answersAfter(300))
    const server = useServerStore()

    const done = server.ensureAwake()
    expect(server.status).toBe('checking')
    await vi.advanceTimersByTimeAsync(300)

    await expect(done).resolves.toBe(true)
    expect(server.status).toBe('ready')
  })

  it('shows "waking" after 2 seconds, then becomes ready', async () => {
    health.mockImplementation(answersAfter(25_000)) // Render cold start
    const server = useServerStore()

    const done = server.ensureAwake()
    await vi.advanceTimersByTimeAsync(SLOW_AFTER_MS - 1)
    expect(server.status).toBe('checking')
    await vi.advanceTimersByTimeAsync(1)
    expect(server.status).toBe('waking')

    await vi.advanceTimersByTimeAsync(25_000)
    await expect(done).resolves.toBe(true)
    expect(server.status).toBe('ready')
  })

  it('retries failed checks with backoff', async () => {
    health
      .mockRejectedValueOnce(new NetworkError()) // e.g. 502 while the instance boots
      .mockRejectedValueOnce(new NetworkError())
      .mockResolvedValue({ status: 'ok' })
    const server = useServerStore()

    const done = server.ensureAwake()
    await vi.advanceTimersByTimeAsync(0)
    expect(server.status).toBe('waking')
    await vi.advanceTimersByTimeAsync(1_000 + 2_000)

    await expect(done).resolves.toBe(true)
    expect(health).toHaveBeenCalledTimes(3)
  })

  it('gives up and reports "down" after the deadline', async () => {
    health.mockRejectedValue(new NetworkError())
    const server = useServerStore()

    const done = server.ensureAwake()
    await vi.advanceTimersByTimeAsync(GIVE_UP_AFTER_MS)

    await expect(done).resolves.toBe(false)
    expect(server.status).toBe('down')
  })

  it('shares one wake-up between concurrent callers', async () => {
    health.mockImplementation(answersAfter(100))
    const server = useServerStore()

    const a = server.ensureAwake()
    const b = server.ensureAwake()
    await vi.advanceTimersByTimeAsync(100)

    await expect(Promise.all([a, b])).resolves.toEqual([true, true])
    expect(health).toHaveBeenCalledTimes(1)
  })
})
