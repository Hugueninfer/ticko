import type { TicketFilters, TicketPriority, TicketStatus } from '@ticko/api-client';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useSearchParams } from 'react-router';

import { useCurrentUser } from '@/auth/useAuth';
import { EmptyState, ErrorState, PageHeader } from '@/components/layout/States';
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/card';
import { Input, NativeSelect } from '@/components/ui/input';
import { useCategories } from '@/features/categories/api';
import { ticketRef } from '@/lib/format';
import { cn } from '@/lib/utils';

import { useTicketCount, useTicketList } from './api';
import { SORTS, TABS, tabFilters, type QueueTab, type Sort } from './queue';
import { PriorityBadge, SlaIndicator, StatusLabel } from './components/badges';
import { PRIORITIES, STATUSES } from './ticket-meta';

function TabCount({ filters }: { filters: TicketFilters }) {
  const { data } = useTicketCount(filters);

  return data === undefined ? null : (
    <span className="rounded-sm bg-muted px-1.5 font-mono text-[11px] text-muted-foreground">{data}</span>
  );
}

export function QueuePage() {
  const { t } = useTranslation();
  const { data: user } = useCurrentUser();
  const { data: categories } = useCategories();
  const [params, setParams] = useSearchParams();

  const tab: QueueTab = (TABS as readonly string[]).includes(params.get('tab') ?? '') ? (params.get('tab') as QueueTab) : 'mine';
  const status = params.get('status') ?? '';
  const priority = params.get('priority') ?? '';
  const category = params.get('category_id') ?? '';
  const sort: Sort = (SORTS as readonly string[]).includes(params.get('sort') ?? '')
    ? (params.get('sort') as Sort)
    : '-created_at';
  const page = Math.max(1, Number(params.get('page') ?? '1') || 1);
  const q = params.get('q') ?? '';
  const [search, setSearch] = useState(q);

  const update = (changes: Record<string, string | null>) => {
    setParams(
      (current) => {
        const next = new URLSearchParams(current);

        for (const [key, value] of Object.entries(changes)) {
          if (value === null || value === '') {
            next.delete(key);
          } else {
            next.set(key, value);
          }
        }

        if (!('page' in changes)) {
          next.delete('page');
        }

        return next;
      },
      { replace: true },
    );
  };

  // Debounced search box, kept in the URL so the view can be shared.
  useEffect(() => {
    if (search === q) {
      return;
    }

    const timer = window.setTimeout(() => update({ q: search.trim() || null }), 300);

    return () => window.clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- update is recreated every render
  }, [search, q]);

  const filters: TicketFilters = {
    ...tabFilters(tab, user?.id),
    ...(status ? { status: status as TicketStatus } : {}),
    ...(priority ? { priority: priority as TicketPriority } : {}),
    ...(category ? { category_id: category } : {}),
    ...(q ? { q } : {}),
    sort,
    page,
    per_page: 25,
  };

  const { data, isPending, isError, refetch, isPlaceholderData } = useTicketList(filters);
  const hasFilters = Boolean(status || priority || category || q);

  return (
    <div>
      <PageHeader title={t('queue.title')} />

      <div className="px-6">
        <div role="tablist" aria-label={t('queue.title')} className="-mt-px flex gap-1 border-b">
          {TABS.map((name) => (
            <button
              key={name}
              type="button"
              role="tab"
              aria-selected={tab === name}
              onClick={() => update({ tab: name === 'mine' ? null : name })}
              className={cn(
                'flex items-center gap-2 border-b-2 px-2 py-2.5 text-sm',
                tab === name ? 'border-foreground font-medium' : 'border-transparent text-muted-foreground hover:text-foreground',
                name === 'overdue' && 'text-danger',
              )}
            >
              {t(`queue.tabs.${name}`)}
              <TabCount filters={tabFilters(name, user?.id)} />
            </button>
          ))}
        </div>

        <div className="flex flex-wrap items-center gap-2 py-4">
          <div className="relative w-72">
            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
            <Input
              type="search"
              aria-label={t('common.search')}
              placeholder={t('queue.searchPlaceholder')}
              className="pl-8"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
          <NativeSelect
            aria-label={t('queue.columns.status')}
            value={status}
            onChange={(event) => update({ status: event.target.value })}
          >
            <option value="">{t('queue.allStatuses')}</option>
            {STATUSES.map((value) => (
              <option key={value} value={value}>
                {t(`status.${value}`)}
              </option>
            ))}
          </NativeSelect>
          <NativeSelect
            aria-label={t('queue.columns.priority')}
            value={priority}
            onChange={(event) => update({ priority: event.target.value })}
          >
            <option value="">{t('queue.allPriorities')}</option>
            {PRIORITIES.map((value) => (
              <option key={value} value={value}>
                {t(`priority.${value}`)}
              </option>
            ))}
          </NativeSelect>
          <NativeSelect
            aria-label={t('queue.columns.category')}
            value={category}
            onChange={(event) => update({ category_id: event.target.value })}
          >
            <option value="">{t('queue.allCategories')}</option>
            {categories?.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </NativeSelect>
          <NativeSelect aria-label={t('queue.sort')} value={sort} onChange={(event) => update({ sort: event.target.value })}>
            {SORTS.map((value) => (
              <option key={value} value={value}>
                {t(`queue.sortOptions.${value}`)}
              </option>
            ))}
          </NativeSelect>
          {hasFilters ? (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => {
                setSearch('');
                update({ status: null, priority: null, category_id: null, q: null });
              }}
            >
              {t('queue.clear')}
            </Button>
          ) : null}
        </div>

        {isError ? (
          <ErrorState onRetry={() => void refetch()} />
        ) : (
          <div className={cn('overflow-x-auto rounded-lg border', isPlaceholderData && 'opacity-70')}>
            <table className="w-full text-[13px]">
              <thead className="border-b bg-muted/40 text-left text-muted-foreground">
                <tr>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.ref')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.subject')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.requester')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.category')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.priority')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.status')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.assignee')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('queue.columns.sla')}</th>
                </tr>
              </thead>
              <tbody>
                {isPending
                  ? Array.from({ length: 6 }, (_, index) => (
                      <tr key={index} className="border-b last:border-0">
                        <td colSpan={8} className="px-3 py-3">
                          <Skeleton className="h-4 w-full" />
                        </td>
                      </tr>
                    ))
                  : data.data.map((ticket) => (
                      <tr key={ticket.id} className="border-b last:border-0 hover:bg-muted/40">
                        <td className="px-3 py-3 font-mono text-muted-foreground">{ticketRef(ticket.id)}</td>
                        <td className="max-w-96 px-3 py-3">
                          <Link to={`/tickets/${ticket.id}`} className="block truncate font-medium hover:underline">
                            {ticket.subject}
                          </Link>
                        </td>
                        <td className="px-3 py-3 whitespace-nowrap">{ticket.requester.name}</td>
                        <td className="px-3 py-3">
                          {ticket.category ? <Badge variant="primary">{ticket.category.name}</Badge> : t('common.none')}
                        </td>
                        <td className="px-3 py-3">
                          <PriorityBadge priority={ticket.priority} />
                        </td>
                        <td className="px-3 py-3">
                          <StatusLabel status={ticket.status} />
                        </td>
                        <td className="px-3 py-3 whitespace-nowrap">
                          {ticket.assignee ? (
                            <span className="inline-flex items-center gap-2">
                              <Avatar name={ticket.assignee.name} className="size-6 text-[10px]" />
                              {ticket.assignee.id === user?.id ? t('common.you') : ticket.assignee.name}
                            </span>
                          ) : (
                            <span className="text-muted-foreground">{t('queue.unassigned')}</span>
                          )}
                        </td>
                        <td className="px-3 py-3">
                          <SlaIndicator ticket={ticket} compact />
                        </td>
                      </tr>
                    ))}
              </tbody>
            </table>

            {!isPending && data.data.length === 0 ? <EmptyState title={t('queue.empty')} hint={t('queue.emptyHint')} /> : null}

            {!isPending && data.meta.total > 0 ? (
              <div className="flex items-center justify-between border-t px-3 py-2.5 text-muted-foreground">
                <span>
                  {t('common.showing', { count: data.data.length, total: data.meta.total })} ·{' '}
                  {t('common.page', { page: data.meta.current_page, last: data.meta.last_page })}
                </span>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => update({ page: String(page - 1) })}>
                    {t('common.previous')}
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={isPlaceholderData || page >= data.meta.last_page}
                    onClick={() => update({ page: String(page + 1) })}
                  >
                    {t('common.next')}
                  </Button>
                </div>
              </div>
            ) : null}
          </div>
        )}
      </div>
    </div>
  );
}
