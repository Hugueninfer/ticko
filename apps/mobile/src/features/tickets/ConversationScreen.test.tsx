import { screen, userEvent, waitFor } from '@testing-library/react-native';
import { router, useLocalSearchParams } from 'expo-router';

import { mockApi, problem, respond } from '@/test/api-mock';
import { asTicket, makeAttachment, makeMessage, makeTicket, makeUser, page, TICKET_ID } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { ConversationScreen } from './ConversationScreen';

const BASE = `/api/v1/tickets/${TICKET_ID}`;

function routes(
  ticket = makeTicket({
    status: 'pending',
    sla: {
      first_response_at: '2026-10-15T14:09:00+00:00',
      first_response_due_at: null,
      resolution_due_at: null,
      is_overdue: false,
    },
  }),
  extra: Record<string, unknown> = {},
) {
  return mockApi({
    'GET /api/v1/auth/me': { data: makeUser() },
    [`GET ${BASE}`]: { data: asTicket(ticket) },
    [`GET ${BASE}/messages`]: page([
      makeMessage({
        id: 'm1',
        body: 'Vou viajar na sexta.',
        author: { id: 'user-customer', name: 'Mariana Costa', role: 'customer', email: null },
        created_at: '2026-10-15T14:00:00+00:00',
      }),
      makeMessage({ id: 'm2' }),
    ]),
    [`GET ${BASE}/attachments`]: { data: [makeAttachment()] },
    [`POST ${BASE}/messages`]: respond(201, { data: makeMessage({ id: 'm3' }) }),
    ...extra,
  });
}

describe('ConversationScreen', () => {
  beforeEach(() => jest.mocked(useLocalSearchParams).mockReturnValue({ id: TICKET_ID }));

  it('shows the conversation, the photos and where the ticket stands', async () => {
    routes();
    await renderApp(<ConversationScreen />, { user: makeUser() });

    expect(await screen.findByText('Oi! Já abrimos uma investigação com a transportadora.')).toBeOnTheScreen();
    expect(screen.getByText('Vou viajar na sexta.')).toBeOnTheScreen();
    expect(screen.getByText('O rastreio está parado desde o dia 06/10.')).toBeOnTheScreen();
    expect(screen.getByText(/Ticket #0TK001 · Ana, da equipe/)).toBeOnTheScreen();
    expect(screen.getByLabelText('Abrir foto rastreio.jpg')).toBeOnTheScreen();
    expect(screen.getByRole('progressbar', { name: 'Em atendimento' })).toBeOnTheScreen();
  });

  it('sends a reply and clears the box', async () => {
    const mock = routes();
    const user = userEvent.setup();
    await renderApp(<ConversationScreen />, { user: makeUser() });

    const input = await screen.findByLabelText('Mensagem');
    await user.type(input, 'Pode ser por Sedex, obrigada!');
    await user.press(screen.getByRole('button', { name: 'Enviar mensagem' }));

    await waitFor(() => expect(mock.calls(`POST ${BASE}/messages`)).toHaveLength(1));
    expect(mock.calls(`POST ${BASE}/messages`)[0]?.body).toEqual({ body: 'Pode ser por Sedex, obrigada!' });
    await waitFor(() => expect(input).toHaveDisplayValue(''));
  });

  it('keeps the draft when sending fails', async () => {
    routes(undefined, { [`POST ${BASE}/messages`]: problem(500) });
    const user = userEvent.setup();
    await renderApp(<ConversationScreen />, { user: makeUser() });

    await user.type(await screen.findByLabelText('Mensagem'), 'Olá?');
    await user.press(screen.getByRole('button', { name: 'Enviar mensagem' }));

    expect(await screen.findByText('A mensagem não foi enviada. Tente de novo.')).toBeOnTheScreen();
    expect(screen.getByLabelText('Mensagem')).toHaveDisplayValue('Olá?');
  });

  it('lets the customer reopen a closed ticket', async () => {
    const mock = routes(makeTicket({ status: 'closed' }), {
      [`PATCH ${BASE}/status`]: { data: asTicket(makeTicket({ status: 'open' })) },
    });
    const user = userEvent.setup();
    await renderApp(<ConversationScreen />, { user: makeUser() });

    expect(await screen.findByText('Este ticket foi fechado.')).toBeOnTheScreen();
    expect(screen.queryByLabelText('Mensagem')).toBeNull();
    expect(screen.getByRole('progressbar', { name: 'Resolvido' })).toBeOnTheScreen();

    await user.press(screen.getByRole('button', { name: 'Reabrir ticket' }));

    await waitFor(() => expect(mock.calls(`PATCH ${BASE}/status`)[0]?.body).toEqual({ status: 'open' }));
  });

  it('treats malformed deep links and foreign tickets as not found', async () => {
    const mock = routes();
    jest.mocked(useLocalSearchParams).mockReturnValue({ id: '../auth/me' });
    const first = await renderApp(<ConversationScreen />, { user: makeUser() });

    expect(await screen.findAllByText('Ticket não encontrado')).not.toHaveLength(0);
    expect(mock.calls(`GET ${BASE}`)).toHaveLength(0);
    await first.unmount();

    routes(undefined, { [`GET ${BASE}`]: problem(404, 'Not Found') });
    jest.mocked(useLocalSearchParams).mockReturnValue({ id: TICKET_ID });
    const user = userEvent.setup();
    await renderApp(<ConversationScreen />, { user: makeUser() });

    expect(await screen.findByText('Ele pode ter sido removido ou não pertence à sua conta.')).toBeOnTheScreen();
    await user.press(screen.getByRole('button', { name: 'Voltar' }));
    expect(router.back).toHaveBeenCalled();
  });

  it('offers a retry when the ticket cannot load', async () => {
    routes(undefined, { [`GET ${BASE}`]: problem(500) });
    await renderApp(<ConversationScreen />, { user: makeUser() });

    expect(await screen.findByRole('button', { name: 'Tentar de novo' })).toBeOnTheScreen();
  });
});
