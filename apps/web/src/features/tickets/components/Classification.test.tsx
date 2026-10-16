import type { Ticket } from '@ticko/api-client';
import { screen, waitFor, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi } from '@/test/api-mock';
import { agent, makeCategory, makeTicket, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const classified: Partial<Ticket> = {
  priority: 'urgent',
  sentiment: 'negative',
  sources: { category: 'ai', priority: 'ai', sentiment: 'ai' },
  classification: {
    status: 'classified',
    model: 'bertimbau-lora-v1',
    category: { label: 'entrega', confidence: 0.93 },
    priority: { label: 'urgent', confidence: 0.81 },
    sentiment: { label: 'negative', confidence: 0.88 },
    applied: ['category', 'priority', 'sentiment'],
    error_code: null,
    created_at: '2026-10-12T14:02:05+00:00',
  },
};

function setup(ticket: Ticket, overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/tickets/:id': { data: ticket },
    'GET /api/v1/tickets/:id/messages': { data: [] },
    'GET /api/v1/tickets/:id/attachments': { data: [] },
    'GET /api/v1/tickets/:id/suggestions/latest': { data: null },
    'GET /api/v1/tickets': page([]),
    'GET /api/v1/team': page([]),
    'GET /api/v1/categories': {
      data: [
        makeCategory({ id: 'cat-delivery', name: 'Entrega' }),
        makeCategory({ id: 'cat-payment', name: 'Pagamento', slug: 'pagamento' }),
      ],
    },
    ...overrides,
  });
}

describe('AI classification', () => {
  it('marks the fields the AI set and shows what it suggested', async () => {
    const ticket = makeTicket(classified);
    setup(ticket);
    renderApp(`/tickets/${ticket.id}`);

    const card = await screen.findByRole('region', { name: 'Classificação da IA' });
    expect(screen.getByTestId('ai-badge-category')).toHaveAttribute('title', 'Definido pela IA (93%)');
    expect(screen.getByTestId('ai-badge-priority')).toBeInTheDocument();
    expect(screen.getByTestId('ai-badge-sentiment')).toBeInTheDocument();
    expect(within(card).getByText('Modelo bertimbau-lora-v1')).toBeInTheDocument();
    expect(within(card).getByText('88%')).toBeInTheDocument();
    expect(within(card).queryByText(/não aplicada/)).toBeNull();
  });

  it('says when a suggestion was not applied and when the classifier failed', async () => {
    const lowConfidence = makeTicket({
      ...classified,
      sources: { category: 'requester', priority: 'requester', sentiment: null },
      sentiment: null,
      classification: { ...classified.classification!, applied: [] },
    });
    setup(lowConfidence);
    const { unmount } = renderApp(`/tickets/${lowConfidence.id}`);

    const card = await screen.findByRole('region', { name: 'Classificação da IA' });
    expect(within(card).getAllByText(/não aplicada/)).toHaveLength(3);
    expect(screen.queryByTestId('ai-badge-category')).toBeNull();
    unmount();

    const failed = makeTicket({
      sources: { category: null, priority: null, sentiment: null },
      classification: {
        ...classified.classification!,
        status: 'failed',
        category: null,
        priority: null,
        sentiment: null,
        applied: [],
        error_code: 'TIMEOUT',
      },
    });
    setup(failed);
    renderApp(`/tickets/${failed.id}`);
    expect(await screen.findByText('O classificador não respondeu. Defina os campos manualmente.')).toBeInTheDocument();
  });

  it('sends only the corrected fields', async () => {
    const ticket = makeTicket(classified);
    const api = setup(ticket, {
      'PATCH /api/v1/tickets/:id/classification': {
        data: { ...ticket, priority: 'normal', sources: { category: 'ai', priority: 'staff', sentiment: 'staff' } },
      },
    });
    const { user } = renderApp(`/tickets/${ticket.id}`);

    await user.click(await screen.findByRole('button', { name: 'Corrigir' }));
    await user.selectOptions(screen.getByLabelText('Prioridade'), 'normal');
    await user.selectOptions(screen.getByLabelText('Sentimento'), '');
    await user.click(screen.getByRole('button', { name: 'Salvar correção' }));

    await waitFor(() => expect(api.find('PATCH', `/api/v1/tickets/${ticket.id}/classification`)).toHaveLength(1));
    expect(api.find('PATCH', `/api/v1/tickets/${ticket.id}/classification`)[0]?.body).toEqual({
      priority: 'normal',
      sentiment: null,
    });
    expect(await screen.findByText('Classificação corrigida.')).toBeInTheDocument();
  });

  it('closes the form without a request when nothing changed', async () => {
    const ticket = makeTicket(classified);
    const api = setup(ticket);
    const { user } = renderApp(`/tickets/${ticket.id}`);

    await user.click(await screen.findByRole('button', { name: 'Corrigir' }));
    await user.selectOptions(screen.getByLabelText('Categoria'), 'cat-payment');
    await user.selectOptions(screen.getByLabelText('Categoria'), 'cat-delivery');
    await user.click(screen.getByRole('button', { name: 'Salvar correção' }));

    expect(await screen.findByRole('button', { name: 'Corrigir' })).toBeInTheDocument();
    expect(api.find('PATCH', `/api/v1/tickets/${ticket.id}/classification`)).toHaveLength(0);
  });

  it('renders labels as text, and nothing without the staff-only fields', async () => {
    const ticket = makeTicket({
      ...classified,
      classification: { ...classified.classification!, model: '<img src=x onerror=alert(1)>' },
    });
    setup(ticket);
    const { unmount } = renderApp(`/tickets/${ticket.id}`);
    expect(await screen.findByText('Modelo <img src=x onerror=alert(1)>')).toBeInTheDocument();
    expect(document.querySelector('img[src="x"]')).toBeNull();
    unmount();

    const plain = makeTicket();
    setup(plain);
    renderApp(`/tickets/${plain.id}`);
    expect(await screen.findByRole('heading', { name: 'Pedido não chegou' })).toBeInTheDocument();
    expect(screen.queryByText('Sentimento')).toBeNull();
    expect(screen.queryByRole('region', { name: 'Classificação da IA' })).toBeNull();
  });
});
