/**
 * Deep links and notification payloads are untrusted input: only a
 * well-formed ticket id is accepted, and the screen then loads the ticket
 * through the API, which checks that it belongs to the signed-in user.
 */

const ULID = /^[0-9a-hjkmnp-tv-z]{26}$/i;

export function isTicketId(value: unknown): value is string {
  return typeof value === 'string' && ULID.test(value);
}

/** Ticket id from a push notification's data, if it holds a valid one. */
export function ticketIdFromNotification(data: unknown): string | null {
  if (typeof data !== 'object' || data === null) {
    return null;
  }

  const id = (data as Record<string, unknown>).ticket_id;

  return isTicketId(id) ? id.toLowerCase() : null;
}

/** In-app route for a ticket id, or null. */
export function ticketPath(id: unknown): `/tickets/${string}` | null {
  return isTicketId(id) ? `/tickets/${id.toLowerCase()}` : null;
}
