import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type TicketList, type TicketMessage, type TicketStatus } from '@ticko/api-client';

import { api, uploadAttachment, type PhotoUpload } from '@/lib/api';
import { subjectFrom } from '@/lib/format';
import { queryKeys } from '@/lib/query';

export type ListState = 'active' | 'done';

export type TicketListItem = TicketList['data'][number];

/** Conversations longer than this many pages are cut to the most recent ones. */
const MAX_MESSAGE_PAGES = 10;

export function useTickets(state: ListState) {
  return useQuery({
    queryKey: queryKeys.ticketList(state),
    queryFn: async () =>
      unwrap(await api.GET('/api/v1/tickets', { params: { query: { state, sort: '-updated_at', per_page: 50 } } })),
  });
}

export function useTicket(id: string) {
  return useQuery({
    queryKey: queryKeys.ticket(id),
    queryFn: async () => unwrap(await api.GET('/api/v1/tickets/{id}', { params: { path: { id } } })).data,
  });
}

export function useMessages(id: string) {
  return useQuery({
    queryKey: queryKeys.messages(id),
    queryFn: async () => {
      const first = unwrap(await api.GET('/api/v1/tickets/{ticket_id}/messages', { params: { path: { ticket_id: id } } }));
      const pages: TicketMessage[][] = [first.data];

      for (let page = 2; page <= Math.min(first.meta.last_page, MAX_MESSAGE_PAGES); page++) {
        const next = unwrap(
          await api.GET('/api/v1/tickets/{ticket_id}/messages', { params: { path: { ticket_id: id }, query: { page } } }),
        );
        pages.push(next.data);
      }

      return pages.flat();
    },
  });
}

export function useAttachments(id: string) {
  return useQuery({
    queryKey: queryKeys.attachments(id),
    queryFn: async () =>
      unwrap(await api.GET('/api/v1/tickets/{ticket_id}/attachments', { params: { path: { ticket_id: id } } })).data,
    // Signed URLs expire after a few minutes; refresh them before that.
    staleTime: 2 * 60_000,
    refetchInterval: 4 * 60_000,
  });
}

export function useCategories() {
  return useQuery({
    queryKey: queryKeys.categories,
    queryFn: async () => unwrap(await api.GET('/api/v1/categories')).data,
    staleTime: 10 * 60_000,
  });
}

function useRefreshTicket(id: string) {
  const queryClient = useQueryClient();

  return () =>
    Promise.all([
      queryClient.invalidateQueries({ queryKey: queryKeys.ticket(id) }),
      queryClient.invalidateQueries({ queryKey: queryKeys.messages(id) }),
      queryClient.invalidateQueries({ queryKey: queryKeys.ticketLists }),
    ]);
}

export interface NewTicketInput {
  description: string;
  categoryId: string | null;
  photos: PhotoUpload[];
}

/** Opens the ticket, then uploads its photos; reports photos that failed. */
export function useCreateTicket() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ description, categoryId, photos }: NewTicketInput) => {
      const ticket = unwrap(
        await api.POST('/api/v1/tickets', {
          body: { subject: subjectFrom(description), description: description.trim(), category_id: categoryId },
        }),
      ).data;
      let failedPhotos = 0;

      for (const photo of photos) {
        try {
          await uploadAttachment(ticket.id, photo);
        } catch {
          failedPhotos++;
        }
      }

      return { ticket, failedPhotos };
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.ticketLists }),
  });
}

export function useSendMessage(id: string) {
  const refresh = useRefreshTicket(id);

  return useMutation({
    mutationFn: async (body: string) =>
      unwrap(await api.POST('/api/v1/tickets/{ticket_id}/messages', { params: { path: { ticket_id: id } }, body: { body } }))
        .data,
    onSuccess: refresh,
  });
}

export function useUploadPhoto(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (photo: PhotoUpload) => uploadAttachment(id, photo),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: queryKeys.attachments(id) }),
  });
}

export function useChangeStatus(id: string) {
  const refresh = useRefreshTicket(id);

  return useMutation({
    mutationFn: async (status: Extract<TicketStatus, 'open' | 'closed'>) =>
      unwrap(await api.PATCH('/api/v1/tickets/{ticket_id}/status', { params: { path: { ticket_id: id } }, body: { status } }))
        .data,
    onSuccess: refresh,
  });
}
