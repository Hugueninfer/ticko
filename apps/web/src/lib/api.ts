import { createTickoClient, ensureCsrfCookie, type TickoClient } from '@ticko/api-client';

/**
 * The browser client: same origin, session cookie, CSRF header. No token is
 * ever read from or written to browser storage.
 */
export const api: TickoClient = createTickoClient();

export { ensureCsrfCookie };
