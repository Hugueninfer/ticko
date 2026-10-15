import { screen, userEvent, waitFor } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import { currentToken } from '@/lib/session';
import { mockApi, problem, respond } from '@/test/api-mock';
import { makeUser } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { useAuth } from './AuthProvider';
import { SignInScreen } from './SignInScreen';

function Screen() {
  const { state } = useAuth();

  return state.status === 'signedIn' ? null : <SignInScreen />;
}

const TOKEN = { token: '1|ticko_secret', token_type: 'Bearer', expires_in: 2592000 };

describe('SignInScreen', () => {
  it('validates the form before calling the API', async () => {
    const mock = mockApi({});
    const user = userEvent.setup();
    await renderApp(<Screen />);

    await user.press(await screen.findByRole('button', { name: 'Entrar' }));

    expect(await screen.findByText('Informe um e-mail válido.')).toBeOnTheScreen();
    expect(screen.getByText('Informe a senha.')).toBeOnTheScreen();
    expect(mock.calls('POST /api/v1/auth/tokens')).toHaveLength(0);
  });

  it('signs in with the tenant header and keeps the token in secure storage', async () => {
    const mock = mockApi({
      'POST /api/v1/auth/tokens': respond(201, TOKEN),
      'GET /api/v1/auth/me': { data: makeUser() },
    });
    const user = userEvent.setup();
    await renderApp(<Screen />);

    await user.type(await screen.findByLabelText('E-mail'), ' mariana@email.com ');
    await user.type(screen.getByLabelText('Senha'), 'correct horse');
    await user.press(screen.getByRole('button', { name: 'Entrar' }));

    await waitFor(() => expect(currentToken()).toBe('1|ticko_secret'));
    const [request] = mock.calls('POST /api/v1/auth/tokens');
    expect(request?.headers.get('X-Tenant')).toBe('acme');
    expect(request?.body).toEqual({ email: 'mariana@email.com', password: 'correct horse', device_name: 'Pixel Test' });
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith(
      'ticko.session.v1',
      expect.stringContaining('1|ticko_secret'),
      expect.anything(),
    );
  });

  it('shows a generic message for wrong credentials and rate limits', async () => {
    mockApi({ 'POST /api/v1/auth/tokens': problem(422, 'Invalid', { email: ['These credentials do not match our records.'] }) });
    const user = userEvent.setup();
    await renderApp(<Screen />);

    await user.type(await screen.findByLabelText('E-mail'), 'mariana@email.com');
    await user.type(screen.getByLabelText('Senha'), 'wrong');
    await user.press(screen.getByRole('button', { name: 'Entrar' }));
    expect(await screen.findByText('E-mail ou senha incorretos.')).toBeOnTheScreen();
  });

  it('offers the demo customer only when the API enables it', async () => {
    mockApi({});
    const first = await renderApp(<Screen />);
    await screen.findByRole('button', { name: 'Entrar' });
    expect(screen.queryByRole('button', { name: 'Entrar como cliente demo' })).toBeNull();
    await first.unmount();

    const mock = mockApi({
      'GET /api/v1/auth/demo': { data: { tenant: 'acme', roles: ['agent', 'admin'] } },
      'POST /api/v1/auth/demo/tokens': respond(201, TOKEN),
      'GET /api/v1/auth/me': { data: makeUser() },
    });
    const user = userEvent.setup();
    await renderApp(<Screen />);

    await user.press(await screen.findByRole('button', { name: 'Entrar como cliente demo' }));

    await waitFor(() => expect(currentToken()).toBe('1|ticko_secret'));
    expect(mock.calls('POST /api/v1/auth/demo/tokens')[0]?.body).toEqual({ device_name: 'Pixel Test' });
  });

  it('turns staff accounts away and revokes their token', async () => {
    const mock = mockApi({
      'POST /api/v1/auth/tokens': respond(201, TOKEN),
      'GET /api/v1/auth/me': { data: makeUser({ role: 'agent' }) },
      'DELETE /api/v1/auth/tokens/current': respond(204),
    });
    const user = userEvent.setup();
    await renderApp(<Screen />);

    await user.type(await screen.findByLabelText('E-mail'), 'ana@acme.test');
    await user.type(screen.getByLabelText('Senha'), 'secret');
    await user.press(screen.getByRole('button', { name: 'Entrar' }));

    expect(await screen.findByText('Este app é para clientes. Atendentes e admins usam o Ticko na web.')).toBeOnTheScreen();
    expect(mock.calls('DELETE /api/v1/auth/tokens/current')).toHaveLength(1);
    expect(currentToken()).toBeNull();
  });
});
