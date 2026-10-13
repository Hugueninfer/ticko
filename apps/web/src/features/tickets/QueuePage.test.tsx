import { screen, waitFor, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { agent, makeCategory, makeTicket, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

function setup(handler?: Parameters<typeof mockApi>[0][string]) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/categories': { data: [makeCategory()] },
    'GET /api/v1/tickets':
      handler ?? ((request) => (request.query.get('per_page') === '1' ? page([], 7) : page([makeTicket()], 30))),
  });
}

describe('QueuePage', () => {
  it('lists my tickets by default with counts per tab', async () => {
    const api = setup();
    renderApp('/tickets');

    const row = (await screen.findByRole('link', { name: 'Pedido não chegou' })).closest('tr')!;
    expect(within(row).getByText('Mariana Costa')).toBeInTheDocument();
    expect(within(row).getByText('Entrega')).toBeInTheDocument();
    expect(within(row).getByText('Alta')).toBeInTheDocument();
    expect(within(row).getByText('Aberto')).toBeInTheDocument();
    expect(within(row).getByText('Sem responsável')).toBeInTheDocument();
    expect(within(row).getByText(/1ª resposta em/)).toBeInTheDocument();
    expect(screen.getByText(/Mostrando 1 de 30/)).toBeInTheDocument();

    const list = api.find('GET', '/api/v1/tickets').find((request) => request.query.get('per_page') === '25');
    expect(list?.query.get('assignee_id')).toBe(agent.id);
    expect(list?.query.get('sort')).toBe('-created_at');
    expect(await screen.findAllByText('7')).toHaveLength(4);
  });

  it('switches tabs, filters, searches and pages through the URL', async () => {
    const api = setup();
    const { user, router } = renderApp('/tickets');
    await screen.findByRole('link', { name: 'Pedido não chegou' });

    await user.click(screen.getByRole('tab', { name: /Atrasados/ }));
    await user.selectOptions(screen.getByLabelText('Prioridade'), 'urgent');
    await user.selectOptions(screen.getByLabelText('Status'), 'open');
    await user.selectOptions(screen.getByLabelText('Categoria'), 'cat-delivery');
    await user.selectOptions(screen.getByLabelText('Ordenar'), 'first_response_due_at');
    await user.type(screen.getByLabelText('Buscar'), 'rastreio');

    await waitFor(() => expect(router.state.location.search).toContain('q=rastreio'));
    await waitFor(() => {
      const last = api
        .find('GET', '/api/v1/tickets')
        .filter((request) => request.query.get('per_page') === '25')
        .at(-1);
      expect(Object.fromEntries(last!.query)).toMatchObject({
        overdue: '1',
        priority: 'urgent',
        status: 'open',
        category_id: 'cat-delivery',
        sort: 'first_response_due_at',
        q: 'rastreio',
        page: '1',
      });
    });

    await user.click(screen.getByRole('button', { name: 'Próxima' }));
    await waitFor(() => expect(router.state.location.search).toContain('page=2'));

    await user.click(screen.getByRole('button', { name: 'Limpar filtros' }));
    await waitFor(() => expect(router.state.location.search).toBe('?tab=overdue&sort=first_response_due_at'));
  });

  it('shows the empty and error states', async () => {
    setup(page([]));
    renderApp('/tickets?tab=all');
    expect(await screen.findByText('Fila zerada')).toBeInTheDocument();
  });

  it('offers a retry when the list fails', async () => {
    setup(problem(500, 'boom'));
    renderApp('/tickets?tab=unassigned');
    expect(await screen.findByRole('button', { name: 'Tentar de novo' })).toBeInTheDocument();
  });
});
