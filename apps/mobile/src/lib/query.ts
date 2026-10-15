import NetInfo from '@react-native-community/netinfo';
import { focusManager, onlineManager, QueryClient } from '@tanstack/react-query';
import { ApiError } from '@ticko/api-client';
import { AppState, Platform, type AppStateStatus } from 'react-native';

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 15_000,
        // Keep what was loaded so the app still shows it while offline.
        gcTime: 24 * 60 * 60_000,
        // Client errors (401/403/404/422) will not change on retry.
        retry: (failureCount, error) => !(error instanceof ApiError && error.status < 500) && failureCount < 2,
      },
      mutations: { retry: false },
    },
  });
}

/** Wires TanStack Query to the phone: refetch on foreground and when back online. */
export function connectQueryToDevice(): () => void {
  onlineManager.setEventListener((setOnline) =>
    NetInfo.addEventListener((state) => {
      setOnline(state.isConnected !== false);
    }),
  );

  const subscription = AppState.addEventListener('change', (status: AppStateStatus) => {
    if (Platform.OS !== 'web') {
      focusManager.setFocused(status === 'active');
    }
  });

  return () => subscription.remove();
}

export const queryKeys = {
  me: ['me'] as const,
  demo: ['auth', 'demo'] as const,
  realtime: ['realtime'] as const,
  ticketLists: ['tickets', 'list'] as const,
  ticketList: (state: 'active' | 'done') => ['tickets', 'list', state] as const,
  ticket: (id: string) => ['tickets', 'detail', id] as const,
  messages: (id: string) => ['tickets', 'detail', id, 'messages'] as const,
  attachments: (id: string) => ['tickets', 'detail', id, 'attachments'] as const,
  categories: ['categories'] as const,
};
