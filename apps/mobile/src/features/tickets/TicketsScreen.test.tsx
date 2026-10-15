import { screen, userEvent } from '@testing-library/react-native';
import { router } from 'expo-router';

import { mockApi, type RecordedRequest, problem } from '@/test/api-mock';
import { makeTicket, makeUser, page, TICKET_ID } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { TicketsScreen } from './TicketsScreen';

const active = [
  makeTicket({
    status: 'pending',
    last_message: {
      id: 'm1',
      excerpt: 'Posso reenviar agora por Sedex.',
      author_name: 'Ana Souza',
      author_role: 'agent',
      created_at: '2026-10-15T14:09:00+00:00',
    },
  }),
  makeTicket({ id: '01k7a0000000000000000tk002', subject: 'Cupom não aplica no carrinho', status: 'open' }),
];

describe('TicketsScreen', () => {
  it('lists open tickets with their status and latest message', async () => {
    const mock = mockApi({
      'GET /api/v1/auth/me': { data: makeUser() },
      'GET /api/v1/tickets': (request: RecordedRequest) => ({ body: page(request.query.get('state') === 'done' ? [] : active) }),
    });
    await renderApp(<TicketsScreen />, { user: makeUser() });

    expect(await screen.findByText('Pedido #88213 não chegou')).toBeOnTheScreen();
    expect(screen.getByText('Ana: Posso reenviar agora por Sedex.')).toBeOnTheScreen();
    expect(screen.getByText('Aguardando você')).toBeOnTheScreen();
    expect(screen.getByText('Em análise')).toBeOnTheScreen();
    expect(screen.getByText('Aguardando a primeira resposta.')).toBeOnTheScreen();
    expect(await screen.findByText('Abertos · 2')).toBeOnTheScreen();

    const [request] = mock.calls('GET /api/v1/tickets');
    expect(request?.query.get('state')).toBe('active');
    expect(request?.query.get('sort')).toBe('-updated_at');
  });

  it('opens a ticket and the new ticket form', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser() }, 'GET /api/v1/tickets': page(active) });
    const user = userEvent.setup();
    await renderApp(<TicketsScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('button', { name: /Pedido #88213/ }));
    expect(router.push).toHaveBeenCalledWith(`/tickets/${TICKET_ID}`);

    await user.press(screen.getByRole('button', { name: 'Novo ticket' }));
    expect(router.push).toHaveBeenCalledWith('/tickets/new');
  });

  it('shows the resolved tab and its empty state', async () => {
    mockApi({
      'GET /api/v1/auth/me': { data: makeUser() },
      'GET /api/v1/tickets': (request: RecordedRequest) => ({ body: page(request.query.get('state') === 'done' ? [] : active) }),
    });
    const user = userEvent.setup();
    await renderApp(<TicketsScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('tab', { name: 'Resolvidos' }));

    expect(await screen.findByText('Nada resolvido ainda')).toBeOnTheScreen();
  });

  it('shows the empty state for customers without tickets', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser() }, 'GET /api/v1/tickets': page([]) });
    await renderApp(<TicketsScreen />, { user: makeUser() });

    expect(await screen.findByText('Nenhum ticket aberto')).toBeOnTheScreen();
  });

  it('offers to retry when the list fails', async () => {
    const mock = mockApi({ 'GET /api/v1/auth/me': { data: makeUser() }, 'GET /api/v1/tickets': problem(500, 'Server Error') });
    const user = userEvent.setup();
    await renderApp(<TicketsScreen />, { user: makeUser() });

    expect(await screen.findByText('Não foi possível carregar')).toBeOnTheScreen();

    mock.set('GET /api/v1/tickets', page(active));
    await user.press(screen.getByRole('button', { name: 'Tentar de novo' }));

    expect(await screen.findByText('Pedido #88213 não chegou')).toBeOnTheScreen();
  });
});
