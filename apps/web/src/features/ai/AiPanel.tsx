import type { AiSuggestion, DiscardReason, Ticket } from '@ticko/api-client';
import { AlertCircle, Check, CircleCheck, ExternalLink, Loader2, Pencil, RefreshCw, Sparkles, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router';

import { useToast } from '@/components/layout/toast-context';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/card';
import { Textarea } from '@/components/ui/input';
import { formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';

import {
  splitMarkers,
  useApproveSuggestion,
  useDiscardSuggestion,
  useReportGap,
  useRequestSuggestion,
  useSuggestion,
  withoutMarkers,
} from './api';

const REASONS: DiscardReason[] = ['wrong_information', 'tone', 'outdated_source', 'other'];

type Citation = AiSuggestion['citations'][number];

function confidenceLevel(value: number): 'high' | 'medium' | 'low' {
  return value >= 0.7 ? 'high' : value >= 0.45 ? 'medium' : 'low';
}

/** Five segments, like the design: how much of the bar the confidence fills. */
function ConfidenceBar({ value }: { value: number }) {
  const { t, i18n } = useTranslation();
  const level = confidenceLevel(value);
  const filled = Math.max(1, Math.round(value * 5));

  return (
    <div className="mt-4">
      <div className="flex items-center justify-between text-xs">
        <span className="text-muted-foreground">{t('ai.confidence')}</span>
        <span className={cn('font-medium', level === 'low' ? 'text-warning' : 'text-ai')}>
          {t(`ai.confidenceLevel.${level}`)} ·{' '}
          <span className="font-mono">
            {value.toLocaleString(i18n.resolvedLanguage, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
          </span>
        </span>
      </div>
      <div
        className="mt-1.5 grid grid-cols-5 gap-1"
        role="meter"
        aria-label={t('ai.confidence')}
        aria-valuemin={0}
        aria-valuemax={1}
        aria-valuenow={value}
      >
        {[0, 1, 2, 3, 4].map((i) => (
          <span
            key={i}
            className={cn('h-1 rounded-full', i < filled ? (level === 'low' ? 'bg-warning' : 'bg-ai') : 'bg-muted')}
          />
        ))}
      </div>
    </div>
  );
}

/** The draft with its source markers rendered as chips that reveal the quoted passage. */
function DraftText({ body, citations }: { body: string; citations: Citation[] }) {
  const { t } = useTranslation();
  const [open, setOpen] = useState<number | null>(null);
  const cited = citations.find((citation) => citation.n === open);

  return (
    <div className="mt-4">
      <div className="rounded-lg border border-ai/30 bg-ai-soft/60 p-3.5 text-[14px] leading-relaxed whitespace-pre-wrap">
        {splitMarkers(body).map((part, index) =>
          part.kind === 'text' ? (
            <span key={index}>{part.value}</span>
          ) : (
            <button
              key={index}
              type="button"
              className={cn(
                'mx-0.5 inline-flex min-w-5 items-center justify-center rounded border border-ai/40 bg-background px-1 font-mono text-[11px] text-ai align-text-top',
                open === part.n && 'bg-ai text-background',
              )}
              aria-label={t('ai.source', { n: part.n })}
              aria-expanded={open === part.n}
              onClick={() => setOpen(open === part.n ? null : part.n)}
            >
              {part.n}
            </button>
          ),
        )}
      </div>
      {cited ? (
        <figure className="mt-2 rounded-lg border bg-background p-3 shadow-sm" aria-label={t('ai.source', { n: cited.n })}>
          <figcaption className="flex items-center gap-2 text-[13px] font-medium">
            <span className="rounded border border-ai/40 px-1 font-mono text-[11px] text-ai">{cited.n}</span>
            <span className="truncate">{cited.title}</span>
            <span className="ml-auto truncate text-xs text-muted-foreground">{cited.heading}</span>
          </figcaption>
          <blockquote className="mt-2 rounded bg-muted p-2.5 font-mono text-[12px] leading-relaxed">“{cited.quote}”</blockquote>
          <Link
            to={`/knowledge/${cited.article_id}`}
            className="mt-2 inline-flex items-center gap-1 text-xs text-primary underline"
          >
            {t('ai.openArticle')} <ExternalLink className="size-3" aria-hidden />
          </Link>
        </figure>
      ) : null}
    </div>
  );
}

function ArticleLinks({ title, articles }: { title: string; articles: AiSuggestion['candidates'] }) {
  const { i18n } = useTranslation();

  if (articles.length === 0) {
    return null;
  }

  return (
    <section className="mt-5">
      <h3 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">{title}</h3>
      <ul className="mt-2 grid grid-cols-1 gap-2">
        {articles.map((article) => (
          <li key={article.article_id} className="min-w-0">
            <Link
              to={`/knowledge/${article.article_id}`}
              className="flex items-center gap-2 rounded-md border px-3 py-2 text-[13px] hover:bg-muted"
            >
              <span className="min-w-0 flex-1 truncate">{article.title}</span>
              <span className="font-mono text-xs text-muted-foreground">
                {article.score.toLocaleString(i18n.resolvedLanguage, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </section>
  );
}

function focusComposer() {
  document.querySelector<HTMLTextAreaElement>('[data-composer] textarea')?.focus();
}

export function AiPanel({ ticket }: { ticket: Ticket }) {
  const { t, i18n } = useTranslation();
  const toast = useToast();
  const suggestion = useSuggestion(ticket.id);
  const request = useRequestSuggestion(ticket.id);
  const approve = useApproveSuggestion(ticket.id);
  const discard = useDiscardSuggestion(ticket.id);
  const gap = useReportGap(ticket.id);
  const [editing, setEditing] = useState<string | null>(null);
  const [discarding, setDiscarding] = useState(false);
  const closed = ticket.status === 'closed';
  const current = suggestion.data ?? null;
  const onError = () => toast.error(t('common.genericError'));

  const generate = () => {
    setEditing(null);
    setDiscarding(false);
    request.mutate(undefined, { onError });
  };

  const send = (draft: AiSuggestion) =>
    approve.mutate(
      { id: draft.id, body: editing ?? withoutMarkers(draft.body ?? '') },
      {
        onSuccess: () => {
          setEditing(null);
          toast.success(t('ai.approved'));
        },
        onError,
      },
    );

  const generateButton = (label: string) => (
    <Button variant="outline" className="w-full" onClick={generate} disabled={request.isPending || closed}>
      <Sparkles className="text-ai" aria-hidden />
      {label}
    </Button>
  );

  let body;

  if (suggestion.isPending) {
    body = <Skeleton className="mt-5 h-32" />;
  } else if (current === null) {
    body = (
      <>
        <p className="mt-4 text-[13px] text-muted-foreground">{closed ? t('ai.closedHint') : t('ai.idleHint')}</p>
        <div className="mt-auto pt-4">{generateButton(t('ai.generate'))}</div>
      </>
    );
  } else if (current.status === 'pending') {
    body = (
      <div className="mt-4" role="status">
        <p className="flex items-center gap-2 text-[13px] text-ai">
          <Loader2 className="size-4 animate-spin" aria-hidden /> {t('ai.generating')}
        </p>
        <div className="mt-4 grid gap-2" aria-hidden>
          <Skeleton className="h-3 w-4/5 bg-ai/10" />
          <Skeleton className="h-3 w-full bg-ai/10" />
          <Skeleton className="h-3 w-2/3 bg-ai/10" />
        </div>
      </div>
    );
  } else if (current.status === 'ready') {
    body = (
      <>
        <ConfidenceBar value={current.confidence ?? 0} />
        {editing === null ? (
          <DraftText body={current.body ?? ''} citations={current.citations} />
        ) : (
          <div className="mt-4">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="ai-draft">
              {t('ai.editing')}
            </label>
            <Textarea
              id="ai-draft"
              className="mt-1.5 min-h-48"
              value={editing}
              onChange={(event) => setEditing(event.target.value)}
            />
          </div>
        )}
        <ArticleLinks
          title={t('ai.sources')}
          articles={current.citations.map((citation) => ({
            article_id: citation.article_id,
            title: `${citation.n} · ${citation.title}`,
            score: citation.score,
          }))}
        />
        {discarding ? (
          <section className="mt-5" aria-label={t('ai.discardWhy')}>
            <h3 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">{t('ai.discardWhy')}</h3>
            <div className="mt-2 flex flex-wrap gap-2">
              {REASONS.map((reason) => (
                <Button
                  key={reason}
                  variant="outline"
                  size="sm"
                  disabled={discard.isPending}
                  onClick={() => discard.mutate({ id: current.id, reason }, { onError, onSuccess: () => setDiscarding(false) })}
                >
                  {t(`ai.reasons.${reason}`)}
                </Button>
              ))}
            </div>
            <p className="mt-2 text-xs text-muted-foreground">{t('ai.discardHint')}</p>
          </section>
        ) : null}
        <div className="mt-auto grid gap-2 pt-4">
          <Button onClick={() => send(current)} disabled={approve.isPending || closed || editing?.trim() === ''}>
            <Check aria-hidden /> {t('ai.approve')}
          </Button>
          <div className="grid grid-cols-2 gap-2">
            <Button
              variant="outline"
              onClick={() => setEditing(editing === null ? withoutMarkers(current.body ?? '') : null)}
              disabled={closed}
            >
              <Pencil aria-hidden /> {t('ai.edit')}
            </Button>
            <Button variant="outline" onClick={() => setDiscarding(!discarding)} disabled={closed}>
              <X aria-hidden /> {t('ai.discard')}
            </Button>
          </div>
        </div>
      </>
    );
  } else if (current.status === 'insufficient') {
    body = (
      <>
        <ConfidenceBar value={current.confidence ?? 0} />
        <div className="mt-4 rounded-lg border border-dashed border-warning/50 p-3.5" role="status">
          <p className="font-medium">{t('ai.insufficientTitle')}</p>
          <p className="mt-1.5 text-[13px] text-muted-foreground">{t('ai.insufficientText')}</p>
        </div>
        <ArticleLinks title={t('ai.nearest')} articles={current.candidates} />
        <div className="mt-auto grid gap-2 pt-4">
          <Button onClick={focusComposer} disabled={closed}>
            {t('ai.writeReply')}
          </Button>
          <Button
            variant="outline"
            disabled={gap.isPending || current.gap_reported_at !== null || closed}
            onClick={() => gap.mutate(current.id, { onError })}
          >
            {current.gap_reported_at !== null ? t('ai.gapReported') : t('ai.reportGap')}
          </Button>
          {generateButton(t('ai.regenerate'))}
        </div>
      </>
    );
  } else if (current.status === 'failed') {
    body = (
      <>
        <div className="mt-4 rounded-lg bg-danger-soft p-3.5" role="alert">
          <p className="flex items-center gap-2 font-medium text-danger">
            <AlertCircle className="size-4" aria-hidden /> {t('ai.failedTitle')}
          </p>
          <p className="mt-1.5 text-[13px] text-muted-foreground">{t('ai.failedText')}</p>
          {current.error_code ? (
            <p className="mt-2 font-mono text-xs text-muted-foreground">
              erro {current.error_code} · {formatTime(current.created_at, i18n.resolvedLanguage ?? 'pt-BR')}
            </p>
          ) : null}
        </div>
        <Button variant="outline" className="mt-3 w-full" onClick={generate} disabled={request.isPending || closed}>
          <RefreshCw aria-hidden /> {t('ai.retry')}
        </Button>
        <ArticleLinks title={t('ai.meanwhile')} articles={current.candidates} />
      </>
    );
  } else {
    const approved = current.status === 'approved';

    body = (
      <>
        <div className={cn('mt-4 rounded-lg p-3.5', approved ? 'bg-success-soft' : 'bg-muted')} role="status">
          <p className={cn('flex items-center gap-2 font-medium', approved ? 'text-success' : 'text-muted-foreground')}>
            {approved ? <CircleCheck className="size-4" aria-hidden /> : <X className="size-4" aria-hidden />}
            {approved
              ? current.edited
                ? t('ai.sentEdited')
                : t('ai.sent')
              : t('ai.discarded', { reason: current.discard_reason ? t(`ai.reasons.${current.discard_reason}`) : '' })}
          </p>
          {current.reviewed_by && current.reviewed_at ? (
            <p className="mt-1 text-[13px] text-muted-foreground">
              {t('ai.sentBy', {
                name: current.reviewed_by.name,
                time: formatTime(current.reviewed_at, i18n.resolvedLanguage ?? 'pt-BR'),
              })}
            </p>
          ) : null}
        </div>
        <div className="mt-auto pt-4">{generateButton(t('ai.regenerate'))}</div>
      </>
    );
  }

  return (
    <aside className="flex w-80 shrink-0 flex-col overflow-x-hidden overflow-y-auto border-l p-5" aria-label={t('ai.title')}>
      <h2 className="flex items-center gap-2 font-semibold">
        <span className="flex size-7 items-center justify-center rounded-md bg-ai-soft text-ai">
          <Sparkles className="size-4" aria-hidden />
        </span>
        {t('ai.title')}
        {current && current.status !== 'pending' && !closed ? (
          <Button
            variant="ghost"
            size="icon"
            className="ml-auto"
            aria-label={t('ai.regenerate')}
            onClick={generate}
            disabled={request.isPending}
          >
            <RefreshCw aria-hidden />
          </Button>
        ) : null}
      </h2>
      {body}
      <p className="pt-4 text-center text-xs text-muted-foreground">{t('ai.rule')}</p>
    </aside>
  );
}
