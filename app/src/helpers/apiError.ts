/**
 * Message for a failed API call: the first validation error, else the `message` of the answer,
 * else the fallback. `httpClient` rejects with `error.response.data`.
 */
export function apiErrorMessage(error: unknown, fallback: string): string {
  const e = error as any
  const data = e?.response?.data ?? e?.data
  const errors = data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors)[0]
    const message = Array.isArray(first) ? first[0] : first
    if (typeof message === 'string' && message) return message
  }
  if (typeof data?.message === 'string' && data.message) return data.message
  return fallback
}
