import { useQuery } from '@tanstack/react-query';
import { unwrap, type Metrics } from '@ticko/api-client';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { ErrorState, PageHeader } from '@/components/layout/States';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, Skeleton } from '@/components/ui/card';
import { api } from '@/lib/api';
import { formatDate, formatNumber } from '@/lib/format';
import { queryKeys } from '@/lib/query';
import { cn } from '@/lib/utils';

import { delta, periodFor, RANGES, type Range } from './metrics';

function useMetrics(from: string, to: string) {
  return useQuery({
    queryKey: queryKeys.metrics(from, to),
    queryFn: async () => unwrap(await api.GET('/api/v1/metrics', { params: { query: { from, to } } })).data,
  });
}

function Kpi({ label, value, change }: { label: string; value: string; change: { text: string; good: boolean | null } | null }) {
  const { t } = useTranslation();

  return (
    <Card>
      <CardContent>
        <p className="text-[13px] text-muted-foreground">{label}</p>
        <p className="mt-2 font-mono text-3xl font-medium tracking-tight">{value}</p>
        <p
          className={cn(
            'mt-2 text-xs',
            change?.good === true && 'text-success',
            change?.good === false && 'text-danger',
            (change?.good ?? null) === null && 'text-muted-foreground',
          )}
        >
          {change ? t('dashboard.vsPrevious', { delta: change.text }) : t('dashboard.noPrevious')}
        </p>
      </CardContent>
    </Card>
  );
}

function VolumeChart({ data }: { data: Metrics['volume_by_day'] }) {
  const { t, i18n } = useTranslation();
  const locale = i18n.resolvedLanguage ?? 'pt-BR';
  const max = Math.max(1, ...data.map((day) => day.count));
  const labelEvery = Math.ceil(data.length / 14);

  return (
    <figure>
      <div className="flex h-48 items-end gap-1" role="list" aria-label={t('dashboard.volume')}>
        {data.map((day, index) => {
          const label = t('dashboard.dayCount', {
            date: formatDate(day.date, locale, { day: '2-digit', month: 'short' }),
            count: day.count,
          });

          return (
            <div
              key={day.date}
              role="listitem"
              aria-label={label}
              title={label}
              className="flex h-full flex-1 flex-col justify-end"
            >
              <svg viewBox="0 0 10 100" preserveAspectRatio="none" className="h-full w-full" aria-hidden>
                <rect
                  x="1"
                  width="8"
                  y={100 - (day.count / max) * 100}
                  height={(day.count / max) * 100}
                  rx="1"
                  className="fill-primary"
                />
              </svg>
              <span className="mt-1 h-4 text-center font-mono text-[10px] text-muted-foreground">
                {index % labelEvery === 0 ? new Date(`${day.date}T00:00:00`).getDate() : ''}
              </span>
            </div>
          );
        })}
      </div>
      <figcaption className="sr-only">{t('dashboard.volumeHint')}</figcaption>
    </figure>
  );
}

function CategoryBars({ data }: { data: Metrics['by_category'] }) {
  const { t } = useTranslation();
  const max = Math.max(1, ...data.map((row) => row.count));

  if (data.length === 0) {
    return <p className="text-muted-foreground">{t('dashboard.noData')}</p>;
  }

  return (
    <ul className="grid gap-2.5">
      {data.map((row) => (
        <li key={row.category_id ?? 'none'} className="grid grid-cols-[9rem_1fr_3rem] items-center gap-3 text-[13px]">
          <span className="truncate">{row.name ?? t('dashboard.uncategorized')}</span>
          <svg viewBox="0 0 100 8" preserveAspectRatio="none" className="h-2.5 w-full" aria-hidden>
            <rect width={(row.count / max) * 100} height="8" rx="2" className="fill-primary" />
          </svg>
          <span className="text-right font-mono">{row.count}</span>
        </li>
      ))}
    </ul>
  );
}

export function DashboardPage() {
  const { t, i18n } = useTranslation();
  const locale = i18n.resolvedLanguage ?? 'pt-BR';
  const [range, setRange] = useState<Range>('30');
  const { from, to } = periodFor(range);
  const metrics = useMetrics(from, to);

  const number = (value: number, options?: Intl.NumberFormatOptions) => formatNumber(value, locale, options);
  const signed = (value: number, digits = 0) =>
    `${value > 0 ? '+' : value < 0 ? '−' : ''}${number(Math.abs(value), { maximumFractionDigits: digits })}`;

  return (
    <div>
      <PageHeader
        title={t('dashboard.title')}
        actions={
          <div role="group" aria-label={t('dashboard.title')} className="flex rounded-lg border p-0.5">
            {RANGES.map((value) => (
              <button
                key={value}
                type="button"
                aria-pressed={range === value}
                onClick={() => setRange(value)}
                className={cn(
                  'rounded-md px-3 py-1 text-[13px]',
                  range === value ? 'bg-muted font-medium' : 'text-muted-foreground',
                )}
              >
                {t(`dashboard.range.${value}`)}
              </button>
            ))}
          </div>
        }
      />

      {metrics.isError ? (
        <ErrorState onRetry={() => void metrics.refetch()} />
      ) : metrics.isPending ? (
        <div className="grid gap-4 p-6 md:grid-cols-4">
          {Array.from({ length: 4 }, (_, index) => (
            <Skeleton key={index} className="h-32" />
          ))}
        </div>
      ) : (
        <div className="grid gap-4 p-6">
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {(() => {
              const m = metrics.data;
              const received = delta(m.tickets_received.value, m.tickets_received.previous || null);
              const response = delta(m.first_response_median_minutes.value, m.first_response_median_minutes.previous, true);
              const sla = delta(m.sla_first_response_percent.value, m.sla_first_response_percent.previous);

              return (
                <>
                  <Kpi
                    label={t('dashboard.received')}
                    value={number(m.tickets_received.value)}
                    change={
                      received && m.tickets_received.previous
                        ? { text: `${signed((received.diff / m.tickets_received.previous) * 100)}%`, good: null }
                        : null
                    }
                  />
                  <Kpi
                    label={t('dashboard.firstResponse')}
                    value={
                      m.first_response_median_minutes.value === null
                        ? t('common.none')
                        : t('dashboard.minutesValue', {
                            value: number(m.first_response_median_minutes.value, { maximumFractionDigits: 0 }),
                          })
                    }
                    change={
                      response
                        ? { text: t('dashboard.minutesValue', { value: signed(response.diff) }), good: response.good }
                        : null
                    }
                  />
                  <Kpi
                    label={t('dashboard.sla')}
                    value={
                      m.sla_first_response_percent.value === null
                        ? t('common.none')
                        : t('dashboard.percentValue', {
                            value: number(m.sla_first_response_percent.value, { maximumFractionDigits: 1 }),
                          })
                    }
                    change={sla ? { text: t('dashboard.pointsValue', { value: signed(sla.diff, 1) }), good: sla.good } : null}
                  />
                  <Card className="border-ai/30 bg-ai-soft">
                    <CardContent>
                      <p className="flex items-center gap-1.5 text-[13px] text-ai">
                        <Sparkles className="size-3.5" aria-hidden />
                        {t('dashboard.aiUsed')}
                      </p>
                      <p className="mt-2 font-mono text-3xl font-medium text-muted-foreground">{t('common.none')}</p>
                      <Badge variant="ai" className="mt-2">
                        {t('common.comingSoon')}
                      </Badge>
                    </CardContent>
                  </Card>
                </>
              );
            })()}
          </div>

          <Card>
            <CardHeader>
              <CardTitle>{t('dashboard.volume')}</CardTitle>
              <span className="text-xs text-muted-foreground">{t('dashboard.volumeHint')}</span>
            </CardHeader>
            <CardContent>
              <VolumeChart data={metrics.data.volume_by_day} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>{t('dashboard.byCategory')}</CardTitle>
            </CardHeader>
            <CardContent>
              <CategoryBars data={metrics.data.by_category} />
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
