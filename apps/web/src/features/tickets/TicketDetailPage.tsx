import type { Ticket, TicketStatus } from '@ticko/api-client';
import { ApiError } from '@ticko/api-client';
import { ChevronLeft } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Link, useParams } from 'react-router';

import { useCurrentUser } from '@/auth/useAuth';
import { ErrorState, FullPageSpinner } from '@/components/layout/States';
import { useToast } from '@/components/layout/toast-context';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/card';
import { NativeSelect } from '@/components/ui/input';
import { AiPanel } from '@/features/ai/AiPanel';
import { useStaff } from '@/features/team/api';
import { formatDateTime, ticketRef } from '@/lib/format';

import { useAssign, useAttachments, useChangeStatus, useMessages, useTicket, useTicketList } from './api';
import { PriorityBadge, SlaIndicator, StatusLabel } from './components/badges';
import { STATUSES } from './ticket-meta';
import { Composer } from './components/Composer';
import { AttachmentGrid, Conversation } from './components/Conversation';

function CustomerColumn({ ticket }: { ticket: Ticket }) {
  const { t, i18n } = useTranslation();
  const history = useTicketList({ requester_id: ticket.requester.id, per_page: 6, sort: '-created_at' });
  const others = history.data?.data.filter((item) => item.id !== ticket.id) ?? [];

  return (
    <aside className="flex w-72 shrink-0 flex-col gap-5 overflow-y-auto border-r p-5" aria-label={t('ticket.requester')}>
      <div className="flex items-center gap-3">
        <Avatar name={ticket.requester.name} className="size-10 text-xs" />
        <div className="min-w-0">
          <p className="truncate font-semibold">{ticket.requester.name}</p>
          {ticket.requester.email ? <p className="truncate text-[13px] text-muted-foreground">{ticket.requester.email}</p> : null}
        </div>
      </div>

      <dl className="grid grid-cols-[auto_1fr] items-center gap-x-4 gap-y-2.5 rounded-lg border p-3.5 text-[13px]">
        <dt className="text-muted-foreground">{t('ticket.category')}</dt>
        <dd>{ticket.category ? <Badge variant="primary">{ticket.category.name}</Badge> : t('ticket.noCategory')}</dd>
        <dt className="text-muted-foreground">{t('ticket.priority')}</dt>
        <dd>
          <PriorityBadge priority={ticket.priority} />
        </dd>
        <dt className="text-muted-foreground">{t('ticket.status')}</dt>
        <dd>
          <StatusLabel status={ticket.status} />
        </dd>
        <dt className="text-muted-foreground">{t('ticket.openedAt')}</dt>
        <dd>{formatDateTime(ticket.created_at, i18n.resolvedLanguage ?? 'pt-BR')}</dd>
      </dl>

      <section>
        <h2 className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">{t('ticket.history')}</h2>
        {history.isPending ? (
          <Skeleton className="h-10" />
        ) : others.length === 0 ? (
          <p className="text-[13px] text-muted-foreground">{t('ticket.noHistory')}</p>
        ) : (
          <ul className="divide-y">
            {others.map((item) => (
              <li key={item.id} className="py-2.5">
                <Link to={`/tickets/${item.id}`} className="block truncate text-[13px] font-medium hover:underline">
                  {item.subject}
                </Link>
                <p className="text-xs text-muted-foreground">
                  {ticketRef(item.id)} · {t(`status.${item.status}`)}
                </p>
              </li>
            ))}
          </ul>
        )}
      </section>
    </aside>
  );
}

export function TicketDetailPage() {
  const { t } = useTranslation();
  const toast = useToast();
  const { id = '' } = useParams();
  const { data: user } = useCurrentUser();
  const ticket = useTicket(id);
  const messages = useMessages(id);
  const attachments = useAttachments(id);
  const staff = useStaff();
  const changeStatus = useChangeStatus(id);
  const assign = useAssign(id);

  if (ticket.isPending) {
    return <FullPageSpinner />;
  }

  if (ticket.isError) {
    const notFound = ticket.error instanceof ApiError && ticket.error.status === 404;

    return (
      <ErrorState
        title={notFound ? t('ticket.notFound') : undefined}
        onRetry={notFound ? undefined : () => void ticket.refetch()}
      />
    );
  }

  const current = ticket.data;
  const finished = current.status === 'resolved' || current.status === 'closed';
  const onError = () => toast.error(t('common.genericError'));
  const setStatus = (status: TicketStatus) =>
    changeStatus.mutate(status, { onSuccess: () => toast.success(t('ticket.statusChanged')), onError });
  const setAssignee = (assigneeId: string | null) =>
    assign.mutate(assigneeId, { onSuccess: () => toast.success(t('ticket.assigned')), onError });

  return (
    <div className="flex h-full flex-col">
      <header className="flex flex-wrap items-center gap-3 border-b px-4 py-3">
        <Button variant="ghost" size="icon" asChild>
          <Link to="/tickets" aria-label={t('ticket.back')}>
            <ChevronLeft />
          </Link>
        </Button>
        <span className="font-mono text-[13px] text-muted-foreground">{ticketRef(current.id)}</span>
        <h1 className="min-w-0 flex-1 truncate text-base font-semibold">{current.subject}</h1>
        <SlaIndicator ticket={current} />
        <NativeSelect
          aria-label={t('ticket.status')}
          value={current.status}
          disabled={changeStatus.isPending}
          onChange={(event) => setStatus(event.target.value as TicketStatus)}
        >
          {STATUSES.map((status) => (
            <option key={status} value={status}>
              {t(`status.${status}`)}
            </option>
          ))}
        </NativeSelect>
        <NativeSelect
          aria-label={t('ticket.assignee')}
          value={current.assignee?.id ?? ''}
          disabled={assign.isPending}
          onChange={(event) => setAssignee(event.target.value || null)}
        >
          <option value="">{t('queue.unassigned')}</option>
          {staff.data?.map((member) => (
            <option key={member.id} value={member.id}>
              {member.id === user?.id ? `${member.name} (${t('team.self')})` : member.name}
            </option>
          ))}
        </NativeSelect>
        {current.assignee?.id !== user?.id && user ? (
          <Button variant="outline" size="sm" onClick={() => setAssignee(user.id)} disabled={assign.isPending}>
            {t('ticket.assignToMe')}
          </Button>
        ) : null}
        <Button
          variant={finished ? 'outline' : 'default'}
          size="sm"
          disabled={changeStatus.isPending}
          onClick={() => setStatus(finished ? 'open' : 'resolved')}
        >
          {finished ? t('ticket.reopen') : t('ticket.resolve')}
        </Button>
      </header>

      <div className="flex min-h-0 flex-1">
        <CustomerColumn ticket={current} />

        <section className="flex min-w-0 flex-1 flex-col" aria-label={t('ticket.conversation')}>
          <div className="flex-1 overflow-y-auto px-8 py-6">
            {messages.isPending ? (
              <Skeleton className="h-24" />
            ) : messages.isError ? (
              <ErrorState onRetry={() => void messages.refetch()} />
            ) : (
              <Conversation ticket={current} messages={messages.data} />
            )}
            <AttachmentGrid attachments={attachments.data ?? []} />
          </div>
          <div className="border-t p-4">
            <Composer ticketId={current.id} disabled={current.status === 'closed'} />
          </div>
        </section>

        <AiPanel ticket={current} />
      </div>
    </div>
  );
}
