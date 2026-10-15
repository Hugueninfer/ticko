import { useQueryClient } from '@tanstack/react-query';
import { ApiError, unwrap, type CurrentUser } from '@ticko/api-client';
import * as Device from 'expo-device';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

import { api, setUnauthorizedHandler } from '@/lib/api';
import { requireConfig } from '@/lib/config';
import { queryKeys } from '@/lib/query';
import { disconnectRealtime } from '@/lib/realtime';
import { clearSession, loadSession, rememberUser, saveSession } from '@/lib/session';

export type SignedOutNotice = 'expired' | 'staff';

export type AuthState =
  { status: 'loading' } | { status: 'signedOut'; notice?: SignedOutNotice } | { status: 'signedIn'; user: CurrentUser };

export interface Credentials {
  email: string;
  password: string;
}

interface AuthContextValue {
  state: AuthState;
  signIn: (credentials: Credentials) => Promise<void>;
  signInDemo: () => Promise<void>;
  signOut: () => Promise<void>;
  setUser: (user: CurrentUser) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

interface TokenResponse {
  token: string;
  expires_in: number;
}

function isUser(value: unknown): value is CurrentUser {
  return typeof value === 'object' && value !== null && typeof (value as CurrentUser).id === 'string';
}

function deviceName(): string {
  return (Device.modelName ?? 'Phone').slice(0, 100);
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [state, setState] = useState<AuthState>({ status: 'loading' });
  const signingOut = useRef(false);

  const reset = useCallback(
    async (notice?: SignedOutNotice) => {
      disconnectRealtime();
      await clearSession();
      queryClient.clear();
      setState({ status: 'signedOut', ...(notice ? { notice } : {}) });
    },
    [queryClient],
  );

  const revokeAndReset = useCallback(
    async (notice?: SignedOutNotice) => {
      // Revoking the token on the server also unregisters this phone's pushes.
      await api.DELETE('/api/v1/auth/tokens/current').catch(() => undefined);
      await reset(notice);
    },
    [reset],
  );

  const accept = useCallback(
    async (user: CurrentUser) => {
      // The app is for customers; staff work in the web app.
      if (user.role !== 'customer') {
        await revokeAndReset('staff');

        return;
      }

      queryClient.setQueryData(queryKeys.me, user);
      await rememberUser(user);
      setState({ status: 'signedIn', user });
    },
    [queryClient, revokeAndReset],
  );

  const fetchMe = useCallback(async () => unwrap(await api.GET('/api/v1/auth/me')).data, []);

  const startWith = useCallback(
    async (tokens: TokenResponse) => {
      await saveSession(tokens.token, tokens.expires_in);
      await accept(await fetchMe());
    },
    [accept, fetchMe],
  );

  // Restore the session saved in secure storage.
  useEffect(() => {
    // An object, so TypeScript does not narrow the flag the cleanup flips.
    const run = { active: true };

    void (async () => {
      const session = await loadSession();

      if (!session) {
        if (run.active) setState({ status: 'signedOut' });

        return;
      }

      try {
        const user = await fetchMe();
        if (run.active) await accept(user);
      } catch (error) {
        if (!run.active) return;

        if (error instanceof ApiError && error.status === 401) {
          await reset('expired');
        } else if (isUser(session.user)) {
          // Offline start: show what we know; queries refresh once online.
          setState({ status: 'signedIn', user: session.user });
        } else {
          await reset();
        }
      }
    })();

    return () => {
      run.active = false;
    };
  }, [accept, fetchMe, reset]);

  // A token revoked or expired elsewhere signs the app out.
  useEffect(() => {
    setUnauthorizedHandler(() => {
      if (!signingOut.current) {
        void reset('expired');
      }
    });

    return () => setUnauthorizedHandler(null);
  }, [reset]);

  const value = useMemo<AuthContextValue>(
    () => ({
      state,
      signIn: async ({ email, password }) => {
        const { tenant } = requireConfig();
        const result = await api.POST('/api/v1/auth/tokens', {
          body: { email, password, device_name: deviceName() },
          headers: { 'X-Tenant': tenant },
        });
        await startWith(unwrap(result));
      },
      signInDemo: async () => {
        const result = await api.POST('/api/v1/auth/demo/tokens', { body: { device_name: deviceName() } });
        await startWith(unwrap(result));
      },
      signOut: async () => {
        signingOut.current = true;

        try {
          await revokeAndReset();
        } finally {
          signingOut.current = false;
        }
      },
      setUser: (user) => {
        queryClient.setQueryData(queryKeys.me, user);
        void rememberUser(user);
        setState({ status: 'signedIn', user });
      },
    }),
    [state, startWith, revokeAndReset, queryClient],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used inside <AuthProvider>.');
  }

  return context;
}

/** The signed-in customer; only for screens behind the auth guard. */
export function useCurrentUser(): CurrentUser {
  const { state } = useAuth();

  if (state.status !== 'signedIn') {
    throw new Error('useCurrentUser needs a signed-in user.');
  }

  return state.user;
}
