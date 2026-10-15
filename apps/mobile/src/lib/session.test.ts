import * as SecureStore from 'expo-secure-store';

import { clearSession, currentToken, loadSession, rememberUser, saveSession } from './session';

describe('session', () => {
  it('keeps the token in secure storage only, scoped to this device', async () => {
    await saveSession('secret-token', 60, 1_000);

    expect(SecureStore.setItemAsync).toHaveBeenCalledWith(
      'ticko.session.v1',
      JSON.stringify({ token: 'secret-token', expiresAt: 61_000 }),
      { keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY },
    );
    expect(currentToken(2_000)).toBe('secret-token');
    expect(currentToken(61_000)).toBeNull();
  });

  it('restores a valid session and drops expired or corrupt ones', async () => {
    await saveSession('t1', 60, 0);
    await expect(loadSession(1_000)).resolves.toMatchObject({ token: 't1' });

    await expect(loadSession(60_001)).resolves.toBeNull();
    expect(SecureStore.deleteItemAsync).toHaveBeenCalled();

    await SecureStore.setItemAsync('ticko.session.v1', '{not json');
    await expect(loadSession()).resolves.toBeNull();

    await SecureStore.setItemAsync('ticko.session.v1', JSON.stringify({ token: 1 }));
    await expect(loadSession()).resolves.toBeNull();
  });

  it('remembers the profile alongside the token', async () => {
    await rememberUser({ id: 'ignored' });
    await saveSession('t1', 60);
    await rememberUser({ id: 'u1' });

    await expect(loadSession()).resolves.toMatchObject({ token: 't1', user: { id: 'u1' } });
  });

  it('forgets everything on sign-out', async () => {
    await saveSession('t1', 60);
    await clearSession();

    expect(currentToken()).toBeNull();
    await expect(loadSession()).resolves.toBeNull();
  });
});
