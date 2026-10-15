import { screen, userEvent } from '@testing-library/react-native';
import { Pressable, Text } from 'react-native';

import { saveSession, rememberUser, currentToken } from '@/lib/session';
import { mockApi, problem, respond } from '@/test/api-mock';
import { makeUser } from '@/test/fixtures';
import { renderApp } from '@/test/render';

import { useAuth } from './AuthProvider';

function Probe() {
  const { state, signOut } = useAuth();

  return (
    <>
      <Text>
        {state.status === 'signedIn'
          ? `in:${state.user.name}`
          : `${state.status}:${state.status === 'signedOut' ? (state.notice ?? '-') : ''}`}
      </Text>
      <Pressable accessibilityRole="button" onPress={() => void signOut()}>
        <Text>out</Text>
      </Pressable>
    </>
  );
}

describe('AuthProvider', () => {
  it('starts signed out without a saved session', async () => {
    mockApi({});
    await renderApp(<Probe />);

    expect(await screen.findByText('signedOut:-')).toBeOnTheScreen();
  });

  it('restores the saved session and signs out by revoking the token', async () => {
    await saveSession('saved', 60);
    const mock = mockApi({ 'GET /api/v1/auth/me': { data: makeUser() }, 'DELETE /api/v1/auth/tokens/current': respond(204) });
    const user = userEvent.setup();
    await renderApp(<Probe />);

    expect(await screen.findByText('in:Mariana Costa')).toBeOnTheScreen();

    await user.press(screen.getByRole('button', { name: 'out' }));

    expect(await screen.findByText('signedOut:-')).toBeOnTheScreen();
    expect(mock.calls('DELETE /api/v1/auth/tokens/current')[0]?.headers.get('Authorization')).toBe('Bearer saved');
    expect(currentToken()).toBeNull();
  });

  it('says so when the saved token was revoked or expired', async () => {
    await saveSession('revoked', 60);
    mockApi({ 'GET /api/v1/auth/me': problem(401, 'Unauthenticated.') });
    await renderApp(<Probe />);

    expect(await screen.findByText('signedOut:expired')).toBeOnTheScreen();
  });

  it('opens offline with the last known profile', async () => {
    await saveSession('saved', 60);
    await rememberUser(makeUser({ name: 'Offline Mariana' }));
    globalThis.fetch = jest.fn(() => Promise.reject(new TypeError('Network request failed')));
    await renderApp(<Probe />);

    expect(await screen.findByText('in:Offline Mariana')).toBeOnTheScreen();
  });

  it('gives up when offline without a known profile', async () => {
    await saveSession('saved', 60);
    globalThis.fetch = jest.fn(() => Promise.reject(new TypeError('Network request failed')));
    await renderApp(<Probe />);

    expect(await screen.findByText('signedOut:-')).toBeOnTheScreen();
  });
});
