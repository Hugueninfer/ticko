import type { Attachment, Ticket, TicketMessage } from '@ticko/api-client';

export type TimelineStep = 'open' | 'progress' | 'resolved';

/** Where the ticket is on the customer's three-step timeline. */
export function currentStep(ticket: Pick<Ticket, 'status' | 'sla'>): TimelineStep {
  if (ticket.status === 'resolved' || ticket.status === 'closed') {
    return 'resolved';
  }

  return ticket.status === 'pending' || ticket.sla.first_response_at ? 'progress' : 'open';
}

export type ConversationItem =
  | { kind: 'day'; key: string; at: string }
  | { kind: 'message'; key: string; at: string; message: TicketMessage }
  | { kind: 'attachment'; key: string; at: string; attachment: Attachment };

/** Messages and photos in time order, with a separator at each new day. */
export function buildConversation(messages: TicketMessage[], attachments: Attachment[]): ConversationItem[] {
  const entries: ConversationItem[] = [
    ...messages.map((message) => ({ kind: 'message' as const, key: `m-${message.id}`, at: message.created_at, message })),
    ...attachments.map((attachment) => ({
      kind: 'attachment' as const,
      key: `a-${attachment.id}`,
      at: attachment.created_at,
      attachment,
    })),
  ].sort((a, b) => a.at.localeCompare(b.at) || a.key.localeCompare(b.key));

  const items: ConversationItem[] = [];
  let lastDay = '';

  for (const entry of entries) {
    const date = new Date(entry.at);
    const day = `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;

    if (day !== lastDay) {
      items.push({ kind: 'day', key: `d-${day}`, at: entry.at });
      lastDay = day;
    }

    items.push(entry);
  }

  return items;
}
