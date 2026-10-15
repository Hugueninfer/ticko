import type { Attachment, CurrentUser, Ticket, TicketMessage } from '@ticko/api-client';

import type { TicketListItem } from '@/features/tickets/api';

export const TICKET_ID = '01k7a0000000000000000tk001';

export function makeUser(overrides: Partial<CurrentUser> = {}): CurrentUser {
  return {
    id: 'user-customer',
    name: 'Mariana Costa',
    email: 'mariana@email.com',
    role: 'customer',
    is_active: true,
    notification_preferences: { push_replies: true, push_status_changes: true },
    tenant: { id: 'tenant-1', name: 'Loja Aurora', slug: 'acme' },
    created_at: '2026-10-01T10:00:00+00:00',
    ...overrides,
  };
}

export function makeTicket(overrides: Partial<TicketListItem> = {}): TicketListItem {
  return {
    id: TICKET_ID,
    subject: 'Pedido #88213 não chegou',
    description: 'O rastreio está parado desde o dia 06/10.',
    status: 'open',
    priority: 'normal',
    category: null,
    requester: { id: 'user-customer', name: 'Mariana Costa', role: 'customer', email: null },
    assignee: { id: 'user-agent', name: 'Ana Souza', role: 'agent', email: null },
    last_message: null,
    resolved_at: null,
    closed_at: null,
    sla: { first_response_at: null, first_response_due_at: null, resolution_due_at: null, is_overdue: false },
    created_at: '2026-10-15T12:00:00+00:00',
    updated_at: '2026-10-15T12:00:00+00:00',
    ...overrides,
  };
}

export function asTicket(item: TicketListItem): Ticket {
  return item;
}

export function makeMessage(overrides: Partial<TicketMessage> = {}): TicketMessage {
  return {
    id: 'msg-1',
    ticket_id: TICKET_ID,
    body: 'Oi! Já abrimos uma investigação com a transportadora.',
    is_internal: false,
    author: { id: 'user-agent', name: 'Ana Souza', role: 'agent', email: null },
    created_at: '2026-10-15T14:09:00+00:00',
    ...overrides,
  };
}

export function makeAttachment(overrides: Partial<Attachment> = {}): Attachment {
  return {
    id: 'att-1',
    ticket_id: TICKET_ID,
    uploader_id: 'user-customer',
    original_name: 'rastreio.jpg',
    mime_type: 'image/jpeg',
    size: 1000,
    width: 400,
    height: 300,
    url: 'http://192.168.0.10:8080/api/v1/attachments/att-1/content?signature=x',
    url_expires_in: 300,
    created_at: '2026-10-15T14:02:00+00:00',
    ...overrides,
  };
}

export function page<T>(data: T[]) {
  return {
    data,
    links: { first: '', last: '', prev: null, next: null },
    meta: {
      current_page: 1,
      from: data.length ? 1 : null,
      last_page: 1,
      links: [],
      path: '',
      per_page: 50,
      to: data.length,
      total: data.length,
    },
  };
}
