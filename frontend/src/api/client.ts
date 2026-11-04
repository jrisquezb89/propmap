const BASE_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api').replace(/\/+$/, '')

/** The server answered with a non-2xx status. */
export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

/** The request never got an answer (offline, DNS, CORS, timeout). */
export class NetworkError extends Error {
  constructor(message = 'Could not reach the server.') {
    super(message)
    this.name = 'NetworkError'
  }
}

export function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

type QueryValue = string | number | undefined | null

interface RequestOptions {
  signal?: AbortSignal
  /** Abort the request after this many ms (raises NetworkError). */
  timeoutMs?: number
}

export async function apiGet<T>(
  path: string,
  params: Record<string, QueryValue> = {},
  { signal, timeoutMs }: RequestOptions = {},
): Promise<T> {
  const url = new URL(BASE_URL + path)
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, String(value))
  }

  const timeout = timeoutMs ? AbortSignal.timeout(timeoutMs) : undefined
  const signals = [signal, timeout].filter((s): s is AbortSignal => s !== undefined)

  let response: Response
  try {
    response = await fetch(url, {
      headers: { Accept: 'application/json' },
      signal: signals.length ? AbortSignal.any(signals) : undefined,
    })
  } catch (error) {
    // The caller cancelled: let it propagate so callers can ignore it.
    if (signal?.aborted) throw error
    throw new NetworkError()
  }

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as { message?: string; errors?: Record<string, string[]> }
    throw new ApiError(body.message ?? `Request failed (${response.status})`, response.status, body.errors)
  }

  return (await response.json()) as T
}
