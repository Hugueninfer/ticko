import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type TicketFilters, type TicketStatus } from '@ticko/api-client';

import { api } from '@/lib/api';
import { queryKeys } from '@/lib/query';

export function useTicketList(filters: TicketFilters) {
  return useQuery({
    queryKey: queryKeys.ticketList(filters),
    queryFn: async () => unwrap(await api.GET('/api/v1/tickets', { params: { query: filters } })),
    placeholderData: keepPreviousData,
  });
}

/** Total of a filter, read from the paginator with a one-row page. */
export function useTicketCount(filters: TicketFilters) {
  return useQuery({
    queryKey: queryKeys.ticketCount(filters),
    queryFn: async () => unwrap(await api.GET('/api/v1/tickets', { params: { query: { ...filters, per_page: 1 } } })).meta.total,
  });
}

export function useTicket(id: string) {
  return useQuery({
    queryKey: queryKeys.ticket(id),
    queryFn: async () => unwrap(await api.GET('/api/v1/tickets/{id}', { params: { path: { id } } })).data,
  });
}

export function useMessages(ticketId: string) {
  return useQuery({
    queryKey: queryKeys.messages(ticketId),
    queryFn: async () =>
      unwrap(await api.GET('/api/v1/tickets/{ticket_id}/messages', { params: { path: { ticket_id: ticketId } } })).data,
  });
}

export function useAttachments(ticketId: string) {
  return useQuery({
    queryKey: queryKeys.attachments(ticketId),
    queryFn: async () =>
      unwrap(await api.GET('/api/v1/tickets/{ticket_id}/attachments', { params: { path: { ticket_id: ticketId } } })).data,
    // Signed URLs expire after a few minutes; refresh them before that.
    staleTime: 2 * 60_000,
    refetchInterval: 4 * 60_000,
  });
}

function useInvalidateTicket(ticketId: string) {
  const queryClient = useQueryClient();

  return async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: queryKeys.ticket(ticketId) }),
      queryClient.invalidateQueries({ queryKey: ['tickets', 'list'] }),
      queryClient.invalidateQueries({ queryKey: ['tickets', 'count'] }),
    ]);
  };
}

export function useChangeStatus(ticketId: string) {
  const invalidate = useInvalidateTicket(ticketId);

  return useMutation({
    mutationFn: async (status: TicketStatus) =>
      unwrap(
        await api.PATCH('/api/v1/tickets/{ticket_id}/status', { params: { path: { ticket_id: ticketId } }, body: { status } }),
      ).data,
    onSuccess: invalidate,
  });
}

export function useAssign(ticketId: string) {
  const invalidate = useInvalidateTicket(ticketId);

  return useMutation({
    mutationFn: async (assigneeId: string | null) =>
      unwrap(
        await api.PATCH('/api/v1/tickets/{ticket_id}/assignee', {
          params: { path: { ticket_id: ticketId } },
          body: { assignee_id: assigneeId },
        }),
      ).data,
    onSuccess: invalidate,
  });
}

export function usePostMessage(ticketId: string) {
  const queryClient = useQueryClient();
  const invalidate = useInvalidateTicket(ticketId);

  return useMutation({
    mutationFn: async ({ body, internal }: { body: string; internal: boolean }) =>
      unwrap(
        await api.POST('/api/v1/tickets/{ticket_id}/messages', {
          params: { path: { ticket_id: ticketId } },
          body: { body, is_internal: internal },
        }),
      ).data,
    onSuccess: async () => {
      await Promise.all([queryClient.invalidateQueries({ queryKey: queryKeys.messages(ticketId) }), invalidate()]);
    },
  });
}

export function useUploadAttachment(ticketId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (file: File) => {
      const form = new FormData();
      form.append('file', file);

      return unwrap(
        await api.POST('/api/v1/tickets/{ticket_id}/attachments', {
          params: { path: { ticket_id: ticketId } },
          // The typed body is the multipart form itself.
          body: form as unknown as { file: string },
          bodySerializer: (body) => body as unknown as FormData,
        }),
      ).data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.attachments(ticketId) }),
  });
}
