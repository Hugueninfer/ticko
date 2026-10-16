import type {
  AiSuggestion,
  Category,
  CurrentUser,
  KbArticle,
  Metrics,
  TeamMember,
  Ticket,
  TicketMessage,
} from '@ticko/api-client';

const tenant = { id: 'tenant-1', name: 'Loja Aurora', slug: 'acme' };

export function makeUser(overrides: Partial<CurrentUser> = {}): CurrentUser {
  return {
    id: 'user-agent',
    name: 'Ana Souza',
    email: 'ana@acme.test',
    role: 'agent',
    is_active: true,
    notification_preferences: { push_replies: true, push_status_changes: true },
    tenant,
    created_at: '2026-10-01T10:00:00+00:00',
    ...overrides,
  };
}

export const agent = makeUser();
export const admin = makeUser({ id: 'user-admin', name: 'Renata Nogueira', email: 'renata@acme.test', role: 'admin' });

const slaTargets = {
  urgent: { first_response_minutes: 15, resolution_minutes: 240, is_custom: false },
  high: { first_response_minutes: 60, resolution_minutes: 480, is_custom: true },
  normal: { first_response_minutes: 240, resolution_minutes: 1440, is_custom: false },
  low: { first_response_minutes: 480, resolution_minutes: 4320, is_custom: false },
};

export function makeCategory(overrides: Partial<Category> = {}): Category {
  return {
    id: 'cat-delivery',
    name: 'Entrega',
    slug: 'entrega',
    description: 'Atrasos e rastreio',
    sla_targets: slaTargets,
    tickets_count: 3,
    ...overrides,
  };
}

export function makeTicket(overrides: Partial<Ticket> = {}): Ticket {
  return {
    id: '01JTICKET000000000000ABC821',
    subject: 'Pedido não chegou',
    description: 'O rastreio parou há 6 dias.',
    status: 'open',
    priority: 'high',
    category: { id: 'cat-delivery', name: 'Entrega', slug: 'entrega', description: null, sla_targets: slaTargets },
    requester: { id: 'user-customer', name: 'Mariana Costa', role: 'customer', email: 'mariana@example.com' },
    assignee: null,
    resolved_at: null,
    closed_at: null,
    sla: {
      first_response_at: null,
      first_response_due_at: new Date(Date.now() + 42 * 60_000).toISOString(),
      resolution_due_at: new Date(Date.now() + 8 * 3_600_000).toISOString(),
      is_overdue: false,
    },
    created_at: '2026-10-12T14:02:00+00:00',
    updated_at: '2026-10-12T14:02:00+00:00',
    ...overrides,
  };
}

export function makeMessage(overrides: Partial<TicketMessage> = {}): TicketMessage {
  return {
    id: 'msg-1',
    ticket_id: '01JTICKET000000000000ABC821',
    body: 'Estamos verificando com a transportadora.',
    is_internal: false,
    author: { id: 'user-agent', name: 'Ana Souza', role: 'agent', email: 'ana@acme.test' },
    created_at: '2026-10-12T14:05:00+00:00',
    ...overrides,
  };
}

export function makeMember(overrides: Partial<TeamMember> = {}): TeamMember {
  return {
    id: 'user-caio',
    name: 'Caio Freitas',
    email: 'caio@acme.test',
    role: 'agent',
    is_active: true,
    notification_preferences: { push_replies: true, push_status_changes: true },
    open_tickets_count: 4,
    created_at: '2026-10-01T10:00:00+00:00',
    ...overrides,
  };
}

export function page<T>(data: T[], total = data.length, currentPage = 1, perPage = 25) {
  return {
    data,
    links: { first: '', last: '', prev: null, next: null },
    meta: {
      current_page: currentPage,
      from: data.length ? 1 : null,
      last_page: Math.max(1, Math.ceil(total / perPage)),
      links: [],
      path: '',
      per_page: perPage,
      to: data.length,
      total,
    },
  };
}

export function makeMetrics(overrides: Partial<Metrics> = {}): Metrics {
  return {
    period: { from: '2026-09-13', to: '2026-10-12' },
    tickets_received: { value: 1412, previous: 1307 },
    first_response_median_minutes: { value: 14, previous: 20 },
    sla_first_response_percent: { value: 93.4, previous: 91.3 },
    volume_by_day: [
      { date: '2026-10-11', count: 40 },
      { date: '2026-10-12', count: 42 },
    ],
    by_category: [
      { category_id: 'cat-delivery', name: 'Entrega', count: 486 },
      { category_id: null, name: null, count: 66 },
    ],
    ...overrides,
  };
}

export function makeArticle(overrides: Partial<KbArticle> = {}): KbArticle {
  return {
    id: 'kb-1',
    title: 'Política de entregas e prazos',
    markdown: '## Prazos\n\nSul e Sudeste: 3 a 5 dias úteis.',
    visibility: 'customers',
    tags: ['rastreio'],
    category: null,
    status: 'published',
    has_unpublished_changes: false,
    published_at: '2026-10-12T10:00:00+00:00',
    index: { status: 'indexed', progress: 100, error: null, indexed_at: '2026-10-12T10:01:00+00:00', chunks_count: 14 },
    citations_count: 312,
    updated_by: { id: 'user-agent', name: 'Caio Freitas', role: 'agent', email: 'caio@acme.test' },
    created_at: '2026-10-01T10:00:00+00:00',
    updated_at: '2026-10-12T10:00:00+00:00',
    ...overrides,
  };
}

export function makeSuggestion(overrides: Partial<AiSuggestion> = {}): AiSuggestion {
  return {
    id: 'sg-1',
    ticket_id: 'ticket-1',
    status: 'ready',
    body: 'Oi, Mariana! Abrimos uma investigação com a transportadora [1].',
    confidence: 0.86,
    citations: [
      {
        n: 1,
        chunk_id: 'ch-1',
        article_id: 'kb-1',
        title: 'Política de entregas e prazos',
        heading: 'Rastreio sem atualização',
        quote: 'O prazo de resposta é de até 2 dias úteis.',
        score: 0.86,
      },
    ],
    candidates: [{ article_id: 'kb-1', title: 'Política de entregas e prazos', score: 0.86 }],
    error_code: null,
    provider: 'fake',
    model: 'fake-extractive',
    latency_ms: 120,
    edited: false,
    discard_reason: null,
    reviewed_by: null,
    reviewed_at: null,
    message_id: null,
    gap_reported_at: null,
    created_at: '2026-10-12T14:09:00+00:00',
    ...overrides,
  } as AiSuggestion;
}
