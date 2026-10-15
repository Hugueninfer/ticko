import { screen, userEvent, waitFor } from '@testing-library/react-native';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';

import { mockApi, problem, respond } from '@/test/api-mock';
import { makeTicket, makeUser, TICKET_ID } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { NewTicketScreen } from './NewTicketScreen';

const categories = [
  { id: 'cat-entrega', name: 'Entrega', slug: 'entrega', description: null },
  { id: 'cat-troca', name: 'Troca e devolução', slug: 'troca', description: null },
];

function routes(extra: Record<string, unknown> = {}) {
  return mockApi({
    'GET /api/v1/auth/me': { data: makeUser() },
    'GET /api/v1/categories': { data: categories },
    'POST /api/v1/tickets': respond(201, { data: makeTicket() }),
    [`POST /api/v1/tickets/${TICKET_ID}/attachments`]: respond(201, { data: { id: 'att-1' } }),
    ...extra,
  });
}

const DESCRIPTION = 'Comprei um fone no dia 05/10\ne um dos lados não funciona.';

describe('NewTicketScreen', () => {
  it('asks for a real description', async () => {
    const mock = routes();
    const user = userEvent.setup();
    await renderApp(<NewTicketScreen />, { user: makeUser() });

    await user.type(await screen.findByLabelText('O que aconteceu?'), 'oi');
    await user.press(screen.getByRole('button', { name: 'Enviar' }));

    expect(await screen.findByText('Conte um pouco mais (pelo menos 10 caracteres).')).toBeOnTheScreen();
    expect(mock.calls('POST /api/v1/tickets')).toHaveLength(0);
  });

  it('opens the ticket with a subject from the first line, a category and a photo', async () => {
    jest.mocked(ImagePicker.launchCameraAsync).mockResolvedValueOnce({
      canceled: false,
      assets: [{ uri: 'file:///raw.jpg', width: 100, height: 100 }],
    });
    const mock = routes();
    const user = userEvent.setup();
    await renderApp(<NewTicketScreen />, { user: makeUser() });

    await user.type(await screen.findByLabelText('O que aconteceu?'), DESCRIPTION);
    await user.press(await screen.findByRole('radio', { name: 'Troca e devolução' }));
    await user.press(screen.getByRole('button', { name: 'Câmera' }));
    expect(await screen.findByRole('button', { name: 'Remover foto' })).toBeOnTheScreen();
    await user.press(screen.getByRole('button', { name: 'Enviar' }));

    await waitFor(() => expect(router.replace).toHaveBeenCalledWith(`/tickets/${TICKET_ID}`));
    expect(mock.calls('POST /api/v1/tickets')[0]?.body).toEqual({
      subject: 'Comprei um fone no dia 05/10',
      description: DESCRIPTION,
      category_id: 'cat-troca',
    });
    expect(mock.calls(`POST /api/v1/tickets/${TICKET_ID}/attachments`)).toHaveLength(1);
  });

  it('lets the customer remove a photo and unselect a category', async () => {
    jest.mocked(ImagePicker.launchImageLibraryAsync).mockResolvedValueOnce({
      canceled: false,
      assets: [{ uri: 'file:///raw.jpg', width: 100, height: 100 }],
    });
    const mock = routes();
    const user = userEvent.setup();
    await renderApp(<NewTicketScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('button', { name: 'Galeria' }));
    await user.press(await screen.findByRole('button', { name: 'Remover foto' }));
    await user.press(await screen.findByRole('radio', { name: 'Entrega' }));
    await user.press(screen.getByRole('radio', { name: 'Entrega' }));
    await user.type(screen.getByLabelText('O que aconteceu?'), DESCRIPTION);
    await user.press(screen.getByRole('button', { name: 'Enviar' }));

    await waitFor(() => expect(mock.calls('POST /api/v1/tickets')).toHaveLength(1));
    expect((mock.calls('POST /api/v1/tickets')[0]?.body as { category_id: unknown }).category_id).toBeNull();
    expect(mock.calls(`POST /api/v1/tickets/${TICKET_ID}/attachments`)).toHaveLength(0);
  });

  it('explains a refused camera permission', async () => {
    jest.mocked(ImagePicker.requestCameraPermissionsAsync).mockResolvedValueOnce({ granted: false } as never);
    routes();
    const user = userEvent.setup();
    await renderApp(<NewTicketScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('button', { name: 'Câmera' }));

    expect(await screen.findByText('Permita o acesso nos ajustes do celular para anexar fotos.')).toBeOnTheScreen();
  });

  it('reports a failure to open the ticket', async () => {
    routes({ 'POST /api/v1/tickets': problem(500) });
    const user = userEvent.setup();
    await renderApp(<NewTicketScreen />, { user: makeUser() });

    await user.type(await screen.findByLabelText('O que aconteceu?'), DESCRIPTION);
    await user.press(screen.getByRole('button', { name: 'Enviar' }));

    expect(await screen.findByText('Algo deu errado. Tente de novo.')).toBeOnTheScreen();
    expect(router.replace).not.toHaveBeenCalled();
  });

  it('goes back on cancel', async () => {
    routes();
    const user = userEvent.setup();
    await renderApp(<NewTicketScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('button', { name: 'Cancelar' }));
    expect(router.back).toHaveBeenCalled();
  });
});
