import type { Ticket, TicketPriority, TicketStatus } from '@ticko/api-client';
import { Clock } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Badge, type BadgeVariant } from '@/components/ui/badge';
import { formatMinutes } from '@/lib/format';
import { cn } from '@/lib/utils';

import { slaState } from '../ticket-meta';

const priorityVariant: Record<TicketPriority, BadgeVariant> = {
  urgent: 'danger',
  high: 'warning',
  normal: 'neutral',
  low: 'neutral',
};

const statusDot: Record<TicketStatus, string> = {
  open: 'bg-muted-foreground',
  pending: 'bg-warning',
  resolved: 'bg-success',
  closed: 'bg-border',
};

export function PriorityBadge({ priority }: { priority: TicketPriority }) {
  const { t } = useTranslation();

  return <Badge variant={priorityVariant[priority]}>{t(`priority.${priority}`)}</Badge>;
}

export function StatusLabel({ status }: { status: TicketStatus }) {
  const { t } = useTranslation();

  return (
    <span className="inline-flex items-center gap-1.5 whitespace-nowrap">
      <span aria-hidden className={cn('size-1.5 rounded-full', statusDot[status])} />
      {t(`status.${status}`)}
    </span>
  );
}

export function SlaIndicator({ ticket, compact = false }: { ticket: Pick<Ticket, 'status' | 'sla'>; compact?: boolean }) {
  const { t, i18n } = useTranslation();
  const state = slaState(ticket);
  const locale = i18n.resolvedLanguage ?? 'pt-BR';

  if (state.kind === 'done') {
    return <span className="text-xs text-muted-foreground">{t('sla.done')}</span>;
  }

  if (state.kind === 'none') {
    return <span className="text-xs text-muted-foreground">{t('sla.noDeadline')}</span>;
  }

  const text =
    state.kind === 'overdue'
      ? t('sla.overdueBy', { time: formatMinutes(state.minutes, locale) })
      : t(`sla.${state.label}`, { time: formatMinutes(state.minutes, locale) });
  const variant: BadgeVariant = state.kind === 'overdue' ? 'danger' : state.tone === 'warn' ? 'warning' : 'muted';

  return (
    <Badge variant={variant} className={cn(compact && 'font-mono')}>
      <Clock className="size-3" aria-hidden />
      {text}
    </Badge>
  );
}
