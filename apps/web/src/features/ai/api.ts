import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type AiSuggestion, type DiscardReason } from '@ticko/api-client';

import { api } from '@/lib/api';
import { queryKeys } from '@/lib/query';

/** Latest AI draft of a ticket (null before the first request). Polls while it is being written. */
export function useSuggestion(ticketId: string) {
  return useQuery({
    queryKey: queryKeys.suggestion(ticketId),
    queryFn: async (): Promise<AiSuggestion | null> =>
      (unwrap(await api.GET('/api/v1/tickets/{ticket_id}/suggestions/latest', { params: { path: { ticket_id: ticketId } } }))
        .data as AiSuggestion | null) ?? null,
    // Realtime normally announces the result; polling is the fallback.
    refetchInterval: (query) => (query.state.data?.status === 'pending' ? 2_000 : false),
  });
}

function useSetSuggestion(ticketId: string) {
  const queryClient = useQueryClient();

  return (suggestion: AiSuggestion) => queryClient.setQueryData(queryKeys.suggestion(ticketId), suggestion);
}

export function useRequestSuggestion(ticketId: string) {
  const set = useSetSuggestion(ticketId);

  return useMutation({
    mutationFn: async () =>
      unwrap(await api.POST('/api/v1/tickets/{ticket_id}/suggestions', { params: { path: { ticket_id: ticketId } } })).data,
    onSuccess: set,
  });
}

export function useApproveSuggestion(ticketId: string) {
  const queryClient = useQueryClient();
  const set = useSetSuggestion(ticketId);

  return useMutation({
    mutationFn: async ({ id, body }: { id: string; body: string }) =>
      unwrap(
        await api.POST('/api/v1/suggestions/{suggestion_id}/approve', {
          params: { path: { suggestion_id: id } },
          body: { body },
        }),
      ).data,
    onSuccess: async (suggestion) => {
      set(suggestion);
      await queryClient.invalidateQueries({ queryKey: queryKeys.ticket(ticketId) });
      await queryClient.invalidateQueries({ queryKey: ['tickets', 'list'] });
    },
  });
}

export function useDiscardSuggestion(ticketId: string) {
  const set = useSetSuggestion(ticketId);

  return useMutation({
    mutationFn: async ({ id, reason }: { id: string; reason: DiscardReason }) =>
      unwrap(
        await api.POST('/api/v1/suggestions/{suggestion_id}/discard', {
          params: { path: { suggestion_id: id } },
          body: { reason },
        }),
      ).data,
    onSuccess: set,
  });
}

export function useReportGap(ticketId: string) {
  const set = useSetSuggestion(ticketId);

  return useMutation({
    mutationFn: async (id: string) =>
      unwrap(await api.POST('/api/v1/suggestions/{suggestion_id}/gap', { params: { path: { suggestion_id: id } } })).data,
    onSuccess: set,
  });
}

/** Source markers ("[1]") are for the agent; the customer gets the text without them. */
export function withoutMarkers(text: string): string {
  return text
    .replace(/\s*\[\d{1,2}\]/g, '')
    .replace(/[ \t]{2,}/g, ' ')
    .trim();
}

/** Splits a draft into text and citation markers, for rendering chips. */
export function splitMarkers(text: string): ({ kind: 'text'; value: string } | { kind: 'cite'; n: number })[] {
  return text
    .split(/(\[\d{1,2}\])/)
    .filter((part) => part !== '')
    .map((part) => {
      const match = /^\[(\d{1,2})\]$/.exec(part);

      return match ? { kind: 'cite' as const, n: Number(match[1]) } : { kind: 'text' as const, value: part };
    });
}
