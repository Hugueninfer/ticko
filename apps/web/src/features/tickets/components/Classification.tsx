import type { ClassificationInput, FieldSource, Sentiment, Ticket, TicketPriority } from '@ticko/api-client';
import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useToast } from '@/components/layout/toast-context';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label, NativeSelect } from '@/components/ui/input';
import { useCategories } from '@/features/categories/api';

import { useCorrectClassification } from '../api';
import { PRIORITIES, SENTIMENTS } from '../ticket-meta';

type Field = 'category' | 'priority' | 'sentiment';

function percent(value: number, language: string | undefined): string {
  return value.toLocaleString(language, { style: 'percent', maximumFractionDigits: 0 });
}

/** "AI" chip next to a field the classifier set; its confidence in the tooltip. */
export function AiBadge({ ticket, field }: { ticket: Ticket; field: Field }) {
  const { t, i18n } = useTranslation();
  const source: FieldSource | null | undefined = ticket.sources?.[field];
  const confidence = ticket.classification?.[field]?.confidence;

  if (source !== 'ai') {
    return null;
  }

  const label =
    confidence === undefined
      ? t('classification.aiBadge')
      : t('classification.aiSet', { confidence: percent(confidence, i18n.resolvedLanguage) });

  return (
    <Badge variant="ai" title={label} aria-label={label} data-testid={`ai-badge-${field}`}>
      <Sparkles aria-hidden className="size-3" />
      {t('classification.aiBadge')}
    </Badge>
  );
}

export function SentimentLabel({ sentiment }: { sentiment: Sentiment | null | undefined }) {
  const { t } = useTranslation();

  return <span>{sentiment ? t(`sentiment.${sentiment}`) : '—'}</span>;
}

function CorrectionForm({ ticket, onDone }: { ticket: Ticket; onDone: () => void }) {
  const { t } = useTranslation();
  const toast = useToast();
  const categories = useCategories();
  const correct = useCorrectClassification(ticket.id);
  const [categoryId, setCategoryId] = useState(ticket.category?.id ?? '');
  const [priority, setPriority] = useState<TicketPriority>(ticket.priority);
  const [sentiment, setSentiment] = useState<Sentiment | ''>(ticket.sentiment ?? '');

  const submit = (event: { preventDefault: () => void }) => {
    event.preventDefault();
    // Only what changed: unchanged fields keep their source.
    const input: ClassificationInput = {};
    if (categoryId !== (ticket.category?.id ?? '')) input.category_id = categoryId || null;
    if (priority !== ticket.priority) input.priority = priority;
    if (sentiment !== (ticket.sentiment ?? '')) input.sentiment = sentiment || null;

    if (Object.keys(input).length === 0) {
      onDone();

      return;
    }

    correct.mutate(input, {
      onSuccess: () => {
        toast.success(t('classification.saved'));
        onDone();
      },
      onError: () => toast.error(t('common.genericError')),
    });
  };

  return (
    <form className="mt-3 grid gap-2.5" onSubmit={submit}>
      <Label htmlFor="correct-category">{t('ticket.category')}</Label>
      <NativeSelect id="correct-category" value={categoryId} onChange={(event) => setCategoryId(event.target.value)}>
        <option value="">{t('ticket.noCategory')}</option>
        {(categories.data ?? []).map((category) => (
          <option key={category.id} value={category.id}>
            {category.name}
          </option>
        ))}
      </NativeSelect>
      <Label htmlFor="correct-priority">{t('ticket.priority')}</Label>
      <NativeSelect
        id="correct-priority"
        value={priority}
        onChange={(event) => setPriority(event.target.value as TicketPriority)}
      >
        {PRIORITIES.map((value) => (
          <option key={value} value={value}>
            {t(`priority.${value}`)}
          </option>
        ))}
      </NativeSelect>
      <Label htmlFor="correct-sentiment">{t('classification.sentiment')}</Label>
      <NativeSelect
        id="correct-sentiment"
        value={sentiment}
        onChange={(event) => setSentiment(event.target.value as Sentiment | '')}
      >
        <option value="">{t('classification.none')}</option>
        {SENTIMENTS.map((value) => (
          <option key={value} value={value}>
            {t(`sentiment.${value}`)}
          </option>
        ))}
      </NativeSelect>
      <div className="mt-1 flex gap-2">
        <Button type="submit" size="sm" variant="ai" disabled={correct.isPending}>
          {t('classification.save')}
        </Button>
        <Button type="button" size="sm" variant="ghost" onClick={onDone}>
          {t('classification.cancel')}
        </Button>
      </div>
    </form>
  );
}

/**
 * What the classifier suggested for this ticket and what it applied, with a
 * form to correct it. Labels come from a closed set and render as text only.
 */
export function ClassificationCard({ ticket }: { ticket: Ticket }) {
  const { t, i18n } = useTranslation();
  const [editing, setEditing] = useState(false);
  const result = ticket.classification;

  // Staff only: customers get no sources.
  if (!ticket.sources) {
    return null;
  }

  const suggestion = (field: Field, text: string | null) => {
    const value = result?.[field];

    if (!value || text === null) {
      return null;
    }

    const applied = result.applied.includes(field);

    return (
      <li key={field} className="flex items-baseline justify-between gap-2">
        <span>
          <span className="text-muted-foreground">
            {t(field === 'sentiment' ? 'classification.sentiment' : `ticket.${field}`)}:
          </span>{' '}
          {text}
          {applied ? null : <span className="block text-xs text-muted-foreground">{t('classification.notApplied')}</span>}
        </span>
        <span className="font-mono text-xs text-ai">{percent(value.confidence, i18n.resolvedLanguage)}</span>
      </li>
    );
  };

  return (
    <section className="rounded-lg border border-ai/30 bg-ai-soft/40 p-3.5 text-[13px]" aria-labelledby="classification-title">
      <div className="flex items-center justify-between gap-2">
        <h2 id="classification-title" className="flex items-center gap-1.5 font-semibold text-ai">
          <Sparkles aria-hidden className="size-4" />
          {t('classification.title')}
        </h2>
        {editing ? null : (
          <Button size="sm" variant="ghost" className="h-7 px-2 text-ai" onClick={() => setEditing(true)}>
            {t('classification.correct')}
          </Button>
        )}
      </div>

      {result?.status === 'failed' ? <p className="mt-2 text-muted-foreground">{t('classification.failed')}</p> : null}

      {result?.status === 'classified' ? (
        <>
          <ul className="mt-2 grid gap-1.5" aria-label={t('classification.suggested')}>
            {suggestion('category', result.category ? t(`classification.labels.${result.category.label}`) : null)}
            {suggestion('priority', result.priority ? t(`priority.${result.priority.label}`) : null)}
            {suggestion('sentiment', result.sentiment ? t(`sentiment.${result.sentiment.label}`) : null)}
          </ul>
          {result.model ? (
            <p className="mt-2 font-mono text-[11px] text-muted-foreground">
              {t('classification.model', { model: result.model })}
            </p>
          ) : null}
        </>
      ) : null}

      {editing ? (
        <CorrectionForm ticket={ticket} onDone={() => setEditing(false)} />
      ) : (
        <p className="mt-2 text-xs text-muted-foreground">{t('classification.rule')}</p>
      )}
    </section>
  );
}
