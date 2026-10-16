import { ApiError, type KnowledgeSearchHit } from '@ticko/api-client';
import { ChevronLeft, MoveDown, MoveUp } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router';

import { EmptyState } from '@/components/layout/States';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input, Label, NativeSelect } from '@/components/ui/input';
import { useCategories } from '@/features/categories/api';
import { cn } from '@/lib/utils';

import { useKnowledgeSearch } from './api';

function Score({ label, value, tone }: { label: string; value: number; tone: 'text' | 'semantic' | 'final' }) {
  const { i18n } = useTranslation();

  return (
    <div className="grid grid-cols-[5.5rem_1fr_2.5rem] items-center gap-2 text-xs">
      <span className={cn(tone === 'final' ? 'font-medium' : 'text-muted-foreground')}>{label}</span>
      <span className="h-1.5 overflow-hidden rounded-full bg-muted" aria-hidden>
        <span
          className={cn('block h-full', tone === 'text' ? 'bg-muted-foreground/60' : tone === 'semantic' ? 'bg-ai/40' : 'bg-ai')}
          style={{ width: `${Math.round(value * 100)}%` }}
        />
      </span>
      <span className="text-right font-mono">
        {value.toLocaleString(i18n.resolvedLanguage, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
      </span>
    </div>
  );
}

function Result({ hit, rank }: { hit: KnowledgeSearchHit; rank: number }) {
  const { t } = useTranslation();

  return (
    <Card className="grid gap-4 p-4 md:grid-cols-[2.5rem_1fr_16rem]">
      <div className="text-center">
        <p className="text-lg font-semibold">{rank}</p>
        {hit.movement !== 0 ? (
          <p className={cn('inline-flex items-center text-xs', hit.movement > 0 ? 'text-success' : 'text-danger')}>
            {hit.movement > 0 ? <MoveUp className="size-3" aria-hidden /> : <MoveDown className="size-3" aria-hidden />}
            <span className="sr-only">
              {t(hit.movement > 0 ? 'playground.up' : 'playground.down', { n: Math.abs(hit.movement) })}
            </span>
            <span aria-hidden>{Math.abs(hit.movement)}</span>
          </p>
        ) : null}
      </div>
      <div className="min-w-0">
        <p className="font-semibold">
          <Link to={`/knowledge/${hit.article_id}`} className="hover:underline">
            {hit.title}
          </Link>{' '}
          <span className="font-mono text-xs font-normal text-muted-foreground">{t('playground.chunk', { n: hit.ordinal })}</span>
        </p>
        <p className="mt-0.5 text-xs text-muted-foreground">{hit.heading}</p>
        <p className="mt-2 line-clamp-3 font-mono text-[12px] leading-relaxed">{hit.content}</p>
      </div>
      <div className="grid content-center gap-1.5">
        <Score label={t('playground.scores.text')} value={hit.text_score} tone="text" />
        <Score label={t('playground.scores.semantic')} value={hit.semantic_score} tone="semantic" />
        <Score label={t('playground.scores.final')} value={hit.score} tone="final" />
      </div>
    </Card>
  );
}

export function PlaygroundPage() {
  const { t } = useTranslation();
  const categories = useCategories();
  const search = useKnowledgeSearch();
  const [query, setQuery] = useState('');
  const [weight, setWeight] = useState(0.4);
  const [topK, setTopK] = useState(5);
  const [rerank, setRerank] = useState(false);
  const [categoryId, setCategoryId] = useState('');
  const [customersOnly, setCustomersOnly] = useState(true);

  const submit = (event?: { preventDefault: () => void }) => {
    event?.preventDefault();

    if (query.trim().length < 2) return;

    search.mutate({
      query: query.trim(),
      text_weight: weight,
      top_k: topK,
      rerank,
      category_id: categoryId || null,
      customer_visible_only: customersOnly,
    });
  };

  const unavailable = search.error instanceof ApiError && search.error.status === 503;

  return (
    <div className="flex h-full flex-col">
      <header className="flex items-center gap-3 border-b px-6 py-3">
        <Link to="/knowledge" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
          <ChevronLeft className="size-4" aria-hidden /> {t('editor.back')}
        </Link>
        <h1 className="flex-1 font-semibold">{t('playground.title')}</h1>
        <p className="text-xs text-muted-foreground">{t('playground.subtitle')}</p>
      </header>
      <div className="grid min-h-0 flex-1 gap-6 overflow-y-auto p-6 lg:grid-cols-[18rem_1fr]">
        <Card className="h-fit p-4">
          <h2 className="font-semibold">{t('playground.parameters')}</h2>
          <div className="mt-4 grid gap-4 text-[13px]">
            <div>
              <Label htmlFor="pg-weight">{t('playground.weight')}</Label>
              <input
                id="pg-weight"
                type="range"
                min={0}
                max={1}
                step={0.1}
                value={weight}
                disabled={rerank}
                onChange={(event) => setWeight(Number(event.target.value))}
                className="w-full accent-[var(--primary)]"
              />
              <div className="flex justify-between font-mono text-xs text-muted-foreground">
                <span>
                  {t('playground.text')} {weight.toFixed(1)}
                </span>
                <span>
                  {t('playground.semantic')} {(1 - weight).toFixed(1)}
                </span>
              </div>
            </div>
            <div>
              <Label htmlFor="pg-topk">{t('playground.topK')}</Label>
              <NativeSelect
                id="pg-topk"
                className="w-full"
                value={topK}
                onChange={(event) => setTopK(Number(event.target.value))}
              >
                {[3, 5, 8, 10].map((k) => (
                  <option key={k} value={k}>
                    {k}
                  </option>
                ))}
              </NativeSelect>
            </div>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={rerank}
                onChange={(event) => setRerank(event.target.checked)}
                className="accent-[var(--primary)]"
              />
              {t('playground.rerank')}
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={customersOnly}
                onChange={(event) => setCustomersOnly(event.target.checked)}
                className="accent-[var(--primary)]"
              />
              {t('playground.customersOnly')}
            </label>
            <div>
              <Label htmlFor="pg-category">{t('playground.category')}</Label>
              <NativeSelect
                id="pg-category"
                className="w-full"
                value={categoryId}
                onChange={(event) => setCategoryId(event.target.value)}
              >
                <option value="">{t('playground.allCategories')}</option>
                {categories.data?.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </NativeSelect>
            </div>
            {search.data ? (
              <p className="border-t pt-3 font-mono text-xs text-muted-foreground">
                {t('playground.meta', { latency: search.data.meta.latency_ms })}
              </p>
            ) : null}
          </div>
        </Card>

        <section className="min-w-0" aria-label={t('playground.title')}>
          <form className="flex gap-2" onSubmit={submit}>
            <Input
              aria-label={t('playground.placeholder')}
              placeholder={t('playground.placeholder')}
              value={query}
              onChange={(event) => setQuery(event.target.value)}
            />
            <Button type="submit" disabled={search.isPending || query.trim().length < 2}>
              {t('playground.search')}
            </Button>
          </form>
          <p className="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
            <span className="inline-flex items-center gap-1.5">
              <span className="size-2 rounded-full bg-muted-foreground/60" aria-hidden /> {t('playground.legend.text')}
            </span>
            <span className="inline-flex items-center gap-1.5">
              <span className="size-2 rounded-full bg-ai/40" aria-hidden /> {t('playground.legend.semantic')}
            </span>
            <span className="inline-flex items-center gap-1.5">
              <span className="size-2 rounded-full bg-ai" aria-hidden /> {t('playground.legend.final')}
            </span>
          </p>

          <div className="mt-4 grid gap-3">
            {search.isError ? (
              <p role="alert" className="rounded-lg bg-danger-soft p-4 text-sm text-danger">
                {unavailable ? t('playground.error') : t('common.genericError')}
              </p>
            ) : null}
            {search.data?.data.length === 0 ? (
              <EmptyState title={t('playground.empty')} hint={t('playground.emptyHint')} />
            ) : null}
            {search.data?.data.map((hit, index) => (
              <Result key={hit.chunk_id} hit={hit} rank={index + 1} />
            ))}
          </div>
        </section>
      </div>
    </div>
  );
}
