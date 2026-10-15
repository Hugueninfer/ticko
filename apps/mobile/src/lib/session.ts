import * as SecureStore from 'expo-secure-store';

/**
 * The API token lives only in the platform's secure storage (Keychain /
 * Android Keystore) and in memory, never in AsyncStorage or logs.
 */

const KEY = 'ticko.session.v1';

const OPTIONS: SecureStore.SecureStoreOptions = {
  // Not restored to another device from a backup; readable once unlocked.
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
};

export interface Session {
  token: string;
  /** Epoch milliseconds. */
  expiresAt: number;
  /** Last known profile, so the app opens offline; refreshed from the API. */
  user?: unknown;
}

let current: Session | null = null;

function isSession(value: unknown): value is Session {
  return (
    typeof value === 'object' &&
    value !== null &&
    typeof (value as Session).token === 'string' &&
    typeof (value as Session).expiresAt === 'number'
  );
}

export async function loadSession(now: number = Date.now()): Promise<Session | null> {
  const raw = await SecureStore.getItemAsync(KEY, OPTIONS);
  let session: unknown = null;

  try {
    session = raw ? JSON.parse(raw) : null;
  } catch {
    session = null;
  }

  if (!isSession(session) || session.expiresAt <= now) {
    await clearSession();

    return null;
  }

  current = session;

  return session;
}

export async function saveSession(token: string, expiresInSeconds: number, now: number = Date.now()): Promise<Session> {
  const session = { token, expiresAt: now + expiresInSeconds * 1000 };
  await SecureStore.setItemAsync(KEY, JSON.stringify(session), OPTIONS);
  current = session;

  return session;
}

export async function rememberUser(user: unknown): Promise<void> {
  if (!current) {
    return;
  }

  current = { ...current, user };
  await SecureStore.setItemAsync(KEY, JSON.stringify(current), OPTIONS);
}

export async function clearSession(): Promise<void> {
  current = null;
  await SecureStore.deleteItemAsync(KEY, OPTIONS);
}

/** Token for the next request, or null when signed out or expired. */
export function currentToken(now: number = Date.now()): string | null {
  return current && current.expiresAt > now ? current.token : null;
}
