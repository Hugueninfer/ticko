import { createTickoClient, ensureCsrfCookie as fetchCsrfCookie, type TickoClient } from '@ticko/api-client';

/** fetch is looked up per call so tests can replace it. */
const fetchImpl: typeof globalThis.fetch = (input, init) => globalThis.fetch(input, init);

/**
 * The browser client: the page's own origin, session cookie, CSRF header.
 * No token is ever read from or written to browser storage.
 */
export const api: TickoClient = createTickoClient({ baseUrl: window.location.origin, fetch: fetchImpl });

export function ensureCsrfCookie(): Promise<void> {
  return fetchCsrfCookie({ baseUrl: window.location.origin, fetch: fetchImpl });
}
