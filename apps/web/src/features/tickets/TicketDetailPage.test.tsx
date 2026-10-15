import { fireEvent, screen, waitFor } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { mockApi, problem } from '@/test/api-mock';
import { agent, makeMember, makeMessage, makeTicket, page } from '@/test/fixtures';
import { renderApp } from '@/test/render';

const ticket = makeTicket();
const url = `/tickets/${ticket.id}`;

function setup(overrides: Parameters<typeof mockApi>[0] = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: agent },
    'GET /api/v1/tickets/:id': { data: ticket },
    'GET /api/v1/tickets/:id/messages': {
      data: [makeMessage(), makeMessage({ id: 'msg-2', body: '<b>nota</b> só para a equipe', is_internal: true })],
    },
    'GET /api/v1/tickets/:id/attachments': {
      data: [
        {
          id: 'att-1',
          ticket_id: ticket.id,
          original_name: 'rastreio.jpg',
          mime_type: 'image/jpeg',
          size: 214 * 1024,
          width: 800,
          height: 600,
          url: 'http://localhost:3000/api/v1/attachments/att-1/content?signature=x',
          url_expires_in: 300,
          created_at: ticket.created_at,
        },
      ],
    },
    'GET /api/v1/tickets': page([makeTicket({ id: 'other', subject: 'Troca de tamanho', status: 'resolved' }), ticket]),
    'GET /api/v1/team': (request) =>
      page(request.query.get('role') === 'agent' ? [makeMember(), { ...makeMember(), id: agent.id, name: agent.name }] : []),
    ...overrides,
  });
}

describe('TicketDetailPage', () => {
  it('shows the customer, the conversation as plain text, attachments and the AI placeholder', async () => {
    setup();
    renderApp(url);

    expect(await screen.findByRole('heading', { name: 'Pedido não chegou' })).toBeInTheDocument();
    expect(screen.getAllByText('Mariana Costa').length).toBeGreaterThan(0);
    expect(screen.getByText('mariana@example.com')).toBeInTheDocument();
    expect(screen.getByText('O rastreio parou há 6 dias.')).toBeInTheDocument();
    expect(await screen.findByText('Estamos verificando com a transportadora.')).toBeInTheDocument();
    // Markup in user content stays text.
    expect(screen.getByText('<b>nota</b> só para a equipe')).toBeInTheDocument();
    expect(document.querySelector('b')).toBeNull();
    expect(screen.getByText('Nota interna', { selector: 'span' })).toBeInTheDocument();
    expect(await screen.findByRole('link', { name: 'Abrir imagem rastreio.jpg' })).toHaveAttribute('rel', 'noopener noreferrer');
    expect(screen.getByText('Sugestões de resposta em breve')).toBeInTheDocument();
    expect(await screen.findByRole('link', { name: 'Troca de tamanho' })).toBeInTheDocument();
  });

  it('sends replies and internal notes with the CSRF header', async () => {
    document.cookie = 'XSRF-TOKEN=abc';
    const api = setup({
      'POST /api/v1/tickets/:id/messages': (request) => ({
        data: makeMessage({ body: (request.body as { body: string }).body }),
      }),
    });
    const { user } = renderApp(url);

    const box = await screen.findByRole('textbox', { name: 'Responder' });
    await user.type(box, 'Já reenviamos o pedido.');
    await user.click(screen.getByRole('button', { name: 'Enviar' }));
    await waitFor(() => expect(api.find('POST', `/api/v1/tickets/${ticket.id}/messages`)).toHaveLength(1));
    expect(api.find('POST', `/api/v1/tickets/${ticket.id}/messages`)[0]?.body).toEqual({
      body: 'Já reenviamos o pedido.',
      is_internal: false,
    });
    expect(api.find('POST', `/api/v1/tickets/${ticket.id}/messages`)[0]?.headers.get('X-XSRF-TOKEN')).toBe('abc');
    await waitFor(() => expect(box).toHaveValue(''));

    await user.click(screen.getByRole('tab', { name: 'Nota interna' }));
    const note = screen.getByRole('textbox', { name: 'Nota interna' });
    await user.type(note, 'Cliente VIP');
    fireEvent.keyDown(note, { key: 'Enter', ctrlKey: true });
    await waitFor(() => expect(api.find('POST', `/api/v1/tickets/${ticket.id}/messages`)).toHaveLength(2));
    expect(api.find('POST', `/api/v1/tickets/${ticket.id}/messages`)[1]?.body).toEqual({
      body: 'Cliente VIP',
      is_internal: true,
    });
  });

  it('shows validation errors from the API', async () => {
    setup({ 'POST /api/v1/tickets/:id/messages': problem(422, 'invalid', { body: ['The body field is too long.'] }) });
    const { user } = renderApp(url);

    await user.type(await screen.findByRole('textbox', { name: 'Responder' }), 'x');
    await user.click(screen.getByRole('button', { name: 'Enviar' }));
    expect(await screen.findByText('The body field is too long.')).toBeInTheDocument();
  });

  it('changes status, assigns and resolves', async () => {
    const api = setup({
      'PATCH /api/v1/tickets/:id/status': (request) => ({
        data: { ...ticket, status: (request.body as { status: string }).status },
      }),
      'PATCH /api/v1/tickets/:id/assignee': { data: ticket },
    });
    const { user } = renderApp(url);

    await user.selectOptions(await screen.findByLabelText('Status'), 'pending');
    await user.click(screen.getByRole('button', { name: 'Atribuir a mim' }));
    await user.click(screen.getByRole('button', { name: 'Resolver' }));

    await waitFor(() => expect(api.find('PATCH', `/api/v1/tickets/${ticket.id}/status`)).toHaveLength(2));
    expect(api.find('PATCH', `/api/v1/tickets/${ticket.id}/status`).map((request) => request.body)).toEqual([
      { status: 'pending' },
      { status: 'resolved' },
    ]);
    expect(api.find('PATCH', `/api/v1/tickets/${ticket.id}/assignee`)[0]?.body).toEqual({ assignee_id: agent.id });
    expect(await screen.findByText('Responsável atualizado.')).toBeInTheDocument();
  });

  it('uploads images as multipart form data', async () => {
    const api = setup({ 'POST /api/v1/tickets/:id/attachments': { status: 201, body: { data: {} } } });
    renderApp(url);

    const input = await screen.findByLabelText('Anexar imagem', { selector: 'input' });
    fireEvent.change(input, { target: { files: [new File(['png'], 'print.png', { type: 'image/png' })] } });

    await waitFor(() =>
      expect(api.requests.map((r) => r.method + ' ' + r.path)).toContain('POST /api/v1/tickets/' + ticket.id + '/attachments'),
    );
    expect(api.find('POST', `/api/v1/tickets/${ticket.id}/attachments`)[0]?.headers.get('Content-Type')).toMatch(
      /^multipart\/form-data/,
    );
    expect(await screen.findByText('Imagem anexada.')).toBeInTheDocument();
  });

  it('blocks replies on closed tickets and shows not found', async () => {
    setup({ 'GET /api/v1/tickets/:id': { data: makeTicket({ status: 'closed' }) } });
    renderApp(url);
    expect(await screen.findByText('Ticket fechado. Reabra para responder.')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Reabrir' })).toBeInTheDocument();
  });

  it('says when the ticket does not exist', async () => {
    setup({ 'GET /api/v1/tickets/:id': problem(404, 'missing') });
    renderApp(url);
    expect(await screen.findByText('Ticket não encontrado.')).toBeInTheDocument();
  });
});
