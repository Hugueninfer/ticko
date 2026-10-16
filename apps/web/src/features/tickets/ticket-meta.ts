import type { Sentiment, Ticket, TicketPriority, TicketStatus } from '@ticko/api-client';

import { minutesUntil } from '@/lib/format';

export const PRIORITIES: TicketPriority[] = ['urgent', 'high', 'normal', 'low'];
export const STATUSES: TicketStatus[] = ['open', 'pending', 'resolved', 'closed'];
export const SENTIMENTS: Sentiment[] = ['negative', 'neutral', 'positive'];

export type SlaState =
  | { kind: 'done' }
  | { kind: 'none' }
  | { kind: 'running'; label: 'firstResponseIn' | 'resolutionIn'; minutes: number; tone: 'ok' | 'warn' }
  | { kind: 'overdue'; minutes: number };

/** The next pending deadline of a ticket: first response, then resolution. */
export function slaState(ticket: Pick<Ticket, 'status' | 'sla'>, now: Date = new Date()): SlaState {
  if (ticket.status === 'resolved' || ticket.status === 'closed') {
    return { kind: 'done' };
  }

  const { first_response_at, first_response_due_at, resolution_due_at } = ticket.sla;
  const due = first_response_at === null && first_response_due_at ? first_response_due_at : resolution_due_at;

  if (!due) {
    return { kind: 'none' };
  }

  const minutes = minutesUntil(due, now);

  if (minutes < 0) {
    return { kind: 'overdue', minutes: -minutes };
  }

  return {
    kind: 'running',
    label: due === first_response_due_at && first_response_at === null ? 'firstResponseIn' : 'resolutionIn',
    minutes,
    tone: minutes <= 60 ? 'warn' : 'ok',
  };
}
