import type { TicketFilters } from '@ticko/api-client';

export const TABS = ['mine', 'unassigned', 'all', 'overdue'] as const;
export type QueueTab = (typeof TABS)[number];

export const SORTS = ['-created_at', 'created_at', 'first_response_due_at', 'resolution_due_at', '-priority'] as const;
export type Sort = (typeof SORTS)[number];

export function tabFilters(tab: QueueTab, userId: string | undefined): TicketFilters {
  switch (tab) {
    case 'mine':
      return userId ? { assignee_id: userId } : {};
    case 'unassigned':
      return { unassigned: true };
    case 'overdue':
      return { overdue: true };
    default:
      return {};
  }
}
