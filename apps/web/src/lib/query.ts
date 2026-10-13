import { QueryClient } from '@tanstack/react-query';
import { ApiError } from '@ticko/api-client';

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 15_000,
        refetchOnWindowFocus: true,
        // Client errors (401/403/404/422) will not change on retry.
        retry: (failureCount, error) => !(error instanceof ApiError && error.status < 500) && failureCount < 2,
      },
      mutations: { retry: false },
    },
  });
}

export const queryKeys = {
  me: ['me'] as const,
  demo: ['auth', 'demo'] as const,
  tickets: ['tickets'] as const,
  ticketList: (filters: object) => ['tickets', 'list', filters] as const,
  ticketCount: (filters: object) => ['tickets', 'count', filters] as const,
  ticket: (id: string) => ['tickets', 'detail', id] as const,
  messages: (id: string) => ['tickets', 'detail', id, 'messages'] as const,
  attachments: (id: string) => ['tickets', 'detail', id, 'attachments'] as const,
  categories: ['categories'] as const,
  team: (filters: object = {}) => ['team', filters] as const,
  metrics: (from: string, to: string) => ['metrics', from, to] as const,
};
