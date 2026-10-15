import { act, fireEvent, screen, userEvent, waitFor } from '@testing-library/react-native';
import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import i18next from 'i18next';

import { mockApi, type RecordedRequest, problem, respond } from '@/test/api-mock';
import { makeUser } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { ProfileScreen } from './ProfileScreen';

const constants = Constants as { executionEnvironment: string };

describe('ProfileScreen', () => {
  afterEach(async () => {
    constants.executionEnvironment = 'bare';
    await act(() => i18next.changeLanguage('pt-BR'));
  });

  it('shows the customer and saves notification preferences', async () => {
    const mock = mockApi({
      'GET /api/v1/auth/me': { data: makeUser() },
      'PUT /api/v1/auth/notification-preferences': (request: RecordedRequest) => ({
        body: { data: makeUser({ notification_preferences: request.body as never }) },
      }),
    });
    await renderApp(<ProfileScreen />, { user: makeUser() });

    expect(await screen.findByText('Mariana Costa')).toBeOnTheScreen();
    expect(screen.getByText('MC')).toBeOnTheScreen();

    await fireEvent(screen.getByLabelText('O status mudar'), 'valueChange', false);

    await waitFor(() =>
      expect(mock.calls('PUT /api/v1/auth/notification-preferences')[0]?.body).toEqual({
        push_replies: true,
        push_status_changes: false,
      }),
    );
  });

  it('rolls back a preference that could not be saved', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser() }, 'PUT /api/v1/auth/notification-preferences': problem(500) });
    await renderApp(<ProfileScreen />, { user: makeUser() });

    await fireEvent(await screen.findByLabelText('Chegar uma resposta'), 'valueChange', false);

    expect(await screen.findByText('Não foi possível salvar a preferência.')).toBeOnTheScreen();
    expect(screen.getByLabelText('Chegar uma resposta')).toHaveProp('value', true);
  });

  it('asks to turn notifications on when they are off', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser() } });
    jest.mocked(Notifications.requestPermissionsAsync).mockResolvedValueOnce({ granted: true } as never);
    jest.mocked(Notifications.getPermissionsAsync).mockResolvedValueOnce({ granted: false } as never);
    const user = userEvent.setup();
    await renderApp(<ProfileScreen />, { user: makeUser() });

    expect(await screen.findByText('Notificações desativadas')).toBeOnTheScreen();
    await user.press(screen.getByRole('button', { name: 'Ativar notificações' }));

    await waitFor(() => expect(Notifications.requestPermissionsAsync).toHaveBeenCalled());
  });

  it('explains that Expo Go cannot receive pushes', async () => {
    constants.executionEnvironment = 'storeClient';
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser() } });
    await renderApp(<ProfileScreen />, { user: makeUser() });

    expect(await screen.findByText(/precisam de um development build/)).toBeOnTheScreen();
    expect(screen.queryByText('Notificações desativadas')).toBeNull();
  });

  it('switches the language', async () => {
    mockApi({ 'GET /api/v1/auth/me': { data: makeUser() } });
    const user = userEvent.setup();
    await renderApp(<ProfileScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('radio', { name: 'English' }));

    expect(await screen.findByText('Profile')).toBeOnTheScreen();
  });

  it('signs out, revoking the token on the server', async () => {
    const mock = mockApi({ 'GET /api/v1/auth/me': { data: makeUser() }, 'DELETE /api/v1/auth/tokens/current': respond(204) });
    const user = userEvent.setup();
    await renderApp(<ProfileScreen />, { user: makeUser() });

    await user.press(await screen.findByRole('button', { name: 'Sair' }));

    await waitFor(() => expect(mock.calls('DELETE /api/v1/auth/tokens/current')).toHaveLength(1));
    expect(await screen.findByText('auth:signedOut')).toBeOnTheScreen();
  });
});
