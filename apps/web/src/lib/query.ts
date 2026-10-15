import { QueryClient } from '@tanstack/react-query';
import { ApiError } from '@ticko/api-client';

/**
 * Server errors and rate limits (429) are retried; other client errors
 * (401/403/404/422) will not change on retry.
 */
export function shouldRetry(failureCount: number, error: unknown): boolean {
  if (error instanceof ApiError && error.status < 500 && error.status !== 429) {
    return false;
  }

  return failureCount < 2;
}

/** Rate-limited requests back off longer (2 s, then 4 s) than other failures. */
export function retryDelay(attempt: number, error: unknown): number {
  const base = error instanceof ApiError && error.status === 429 ? 2_000 : 500;

  return Math.min(base * 2 ** attempt, 10_000);
}

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 15_000,
        refetchOnWindowFocus: true,
        retry: shouldRetry,
        retryDelay,
      },
      mutations: { retry: false },
    },
  });
}

/**
 * Resolves once no query is in flight (or after `timeoutMs`).
 *
 * Signing out must wait for requests that started with the old session:
 * one that finishes after the logout would store the old session again and
 * send its cookie back, signing the browser in again.
 */
export function settleQueries(queryClient: QueryClient, timeoutMs = 5_000): Promise<void> {
  if (queryClient.isFetching() === 0) {
    return Promise.resolve();
  }

  return new Promise((resolve) => {
    const done = () => {
      clearTimeout(timer);
      unsubscribe();
      resolve();
    };
    const timer = setTimeout(done, timeoutMs);
    const unsubscribe = queryClient.getQueryCache().subscribe(() => {
      if (queryClient.isFetching() === 0) {
        done();
      }
    });
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
  knowledge: ['knowledge'] as const,
  articles: (filters: object) => ['knowledge', 'articles', filters] as const,
  article: (id: string) => ['knowledge', 'article', id] as const,
  chunks: (id: string) => ['knowledge', 'article', id, 'chunks'] as const,
  knowledgeStats: ['knowledge', 'stats'] as const,
  suggestion: (ticketId: string) => ['tickets', 'detail', ticketId, 'suggestion'] as const,
  team: (filters: object = {}) => ['team', filters] as const,
  metrics: (from: string, to: string) => ['metrics', from, to] as const,
};
