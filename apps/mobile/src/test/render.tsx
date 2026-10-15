import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { CurrentUser } from '@ticko/api-client';
import { render } from '@testing-library/react-native';
import type { ReactElement, ReactNode } from 'react';
import { Text } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { AuthProvider, useAuth } from '@/features/auth/AuthProvider';
import { saveSession } from '@/lib/session';

export function testQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: { queries: { retry: false, gcTime: Infinity }, mutations: { retry: false, gcTime: Infinity } },
  });
}

const METRICS = { frame: { x: 0, y: 0, width: 390, height: 844 }, insets: { top: 0, left: 0, right: 0, bottom: 0 } };

function SignedIn({ children }: { children: ReactNode }) {
  const { state } = useAuth();

  return state.status === 'signedIn' ? children : <Text>{`auth:${state.status}`}</Text>;
}

/**
 * Renders inside the app's providers. With `user`, a session is stored and
 * /auth/me must be served by the API mock; children render once signed in.
 */
export async function renderApp(ui: ReactElement, options: { user?: CurrentUser; queryClient?: QueryClient } = {}) {
  const queryClient = options.queryClient ?? testQueryClient();

  if (options.user) {
    await saveSession('test-token', 3600);
  }

  const result = await render(
    <SafeAreaProvider initialMetrics={METRICS}>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>{options.user ? <SignedIn>{ui}</SignedIn> : ui}</AuthProvider>
      </QueryClientProvider>
    </SafeAreaProvider>,
  );

  return { ...result, queryClient };
}
