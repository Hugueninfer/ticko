import type { KbArticleFilters } from '@ticko/api-client';
import { Download, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router';

import { EmptyState, ErrorState, PageHeader } from '@/components/layout/States';
import { useToast } from '@/components/layout/toast-context';
import { Button } from '@/components/ui/button';
import { Card, Skeleton } from '@/components/ui/card';
import { Input, NativeSelect } from '@/components/ui/input';
import { useCategories } from '@/features/categories/api';
import { formatDateTime, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';

import { useArticleAction, useArticles, useImportArticle, useKnowledgeStats } from './api';
import { IndexBadge, StatusBadge } from './IndexBadge';

const STATUSES = ['draft', 'published', 'archived'] as const;
const INDEX_STATUSES = ['none', 'queued', 'indexing', 'indexed', 'failed'] as const;

function Stats() {
  const { t, i18n } = useTranslation();
  const stats = useKnowledgeStats();
  const items = [
    ['published', stats.data?.published],
    ['chunks', stats.data?.chunks],
    ['queued', stats.data?.queued],
    ['failed', stats.data?.failed],
  ] as const;

  return (
    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
      {items.map(([key, value]) => (
        <Card key={key} className="p-4">
          <p className="text-[13px] text-muted-foreground">{t(`knowledge.stats.${key}`)}</p>
          {value === undefined ? (
            <Skeleton className="mt-2 h-7 w-12" />
          ) : (
            <p className={cn('mt-1 text-2xl font-semibold', key === 'failed' && value > 0 && 'text-danger')}>
              {formatNumber(value, i18n.resolvedLanguage ?? 'pt-BR')}
            </p>
          )}
        </Card>
      ))}
    </div>
  );
}

export function KnowledgePage() {
  const { t, i18n } = useTranslation();
  const toast = useToast();
  const navigate = useNavigate();
  const categories = useCategories();
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState<KbArticleFilters>({ per_page: 50 });
  const articles = useArticles(filters);
  const action = useArticleAction();
  const importArticle = useImportArticle();
  const fileInput = useRef<HTMLInputElement>(null);
  const locale = i18n.resolvedLanguage ?? 'pt-BR';

  useEffect(() => {
    const timer = window.setTimeout(() => setFilters((current) => ({ ...current, q: search.trim() || undefined })), 300);

    return () => window.clearTimeout(timer);
  }, [search]);

  const setFilter = (key: 'status' | 'index_status' | 'category_id', value: string) =>
    setFilters((current) => ({ ...current, [key]: value || undefined }));

  const onFile = (file: File | undefined) => {
    if (!file) return;

    importArticle.mutate(file, {
      onSuccess: (article) => {
        toast.success(t('knowledge.imported'));
        void navigate(`/knowledge/${article.id}`);
      },
      onError: () => toast.error(t('common.genericError')),
      onSettled: () => {
        if (fileInput.current) fileInput.current.value = '';
      },
    });
  };

  return (
    <div>
      <PageHeader
        title={t('knowledge.title')}
        subtitle={t('knowledge.subtitle')}
        actions={
          <>
            <input
              ref={fileInput}
              type="file"
              accept=".md,.markdown,.txt,text/markdown,text/plain"
              className="sr-only"
              aria-label={t('knowledge.import')}
              onChange={(event) => onFile(event.target.files?.[0])}
            />
            <Button variant="outline" onClick={() => fileInput.current?.click()} disabled={importArticle.isPending}>
              <Download aria-hidden /> {t('knowledge.import')}
            </Button>
            <Button asChild>
              <Link to="/knowledge/new">
                <Plus aria-hidden /> {t('knowledge.newArticle')}
              </Link>
            </Button>
          </>
        }
      />
      <div className="grid gap-4 p-6">
        <Stats />

        <div className="flex flex-wrap items-center gap-2">
          <div className="relative w-72">
            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden />
            <Input
              type="search"
              className="pl-8"
              aria-label={t('common.search')}
              placeholder={t('knowledge.searchPlaceholder')}
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
          <NativeSelect
            aria-label={t('knowledge.allCategories')}
            value={filters.category_id ?? ''}
            onChange={(e) => setFilter('category_id', e.target.value)}
          >
            <option value="">{t('knowledge.allCategories')}</option>
            {categories.data?.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </NativeSelect>
          <NativeSelect
            aria-label={t('knowledge.allStatuses')}
            value={filters.status ?? ''}
            onChange={(e) => setFilter('status', e.target.value)}
          >
            <option value="">{t('knowledge.allStatuses')}</option>
            {STATUSES.map((status) => (
              <option key={status} value={status}>
                {t(`knowledge.status.${status}`)}
              </option>
            ))}
          </NativeSelect>
          <NativeSelect
            aria-label={t('knowledge.allIndex')}
            value={filters.index_status ?? ''}
            onChange={(e) => setFilter('index_status', e.target.value)}
          >
            <option value="">{t('knowledge.allIndex')}</option>
            {INDEX_STATUSES.map((status) => (
              <option key={status} value={status}>
                {t(`knowledge.index.${status}`, { count: 0, progress: 0 }).replace(/ · .*$/, '')}
              </option>
            ))}
          </NativeSelect>
        </div>

        {articles.isError ? (
          <ErrorState onRetry={() => void articles.refetch()} />
        ) : (
          <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-[13px]">
              <thead className="border-b bg-muted/40 text-left text-muted-foreground">
                <tr>
                  <th className="px-3 py-2.5 font-medium">{t('knowledge.columns.title')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('knowledge.columns.category')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('knowledge.columns.status')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('knowledge.columns.index')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('knowledge.columns.cited')}</th>
                  <th className="px-3 py-2.5 font-medium">{t('knowledge.columns.updated')}</th>
                </tr>
              </thead>
              <tbody>
                {articles.isPending ? (
                  <tr>
                    <td colSpan={6} className="p-3">
                      <Skeleton className="h-8" />
                    </td>
                  </tr>
                ) : (
                  articles.data.data.map((article) => (
                    <tr key={article.id} className="border-b last:border-0 hover:bg-muted/40">
                      <td className="px-3 py-3 font-medium">
                        <Link to={`/knowledge/${article.id}`} className="hover:underline">
                          {article.title}
                        </Link>
                        {article.has_unpublished_changes ? (
                          <span className="ml-2 text-xs font-normal text-warning">{t('knowledge.unpublishedChanges')}</span>
                        ) : null}
                      </td>
                      <td className="px-3 py-3 text-muted-foreground">{article.category?.name ?? t('knowledge.noCategory')}</td>
                      <td className="px-3 py-3">
                        <StatusBadge status={article.status} />
                      </td>
                      <td className="px-3 py-3">
                        <span className="inline-flex items-center gap-3">
                          <IndexBadge index={article.index} />
                          {article.index.status === 'failed' ? (
                            <button
                              type="button"
                              className="text-xs font-medium text-primary hover:underline"
                              disabled={action.isPending}
                              onClick={() =>
                                action.mutate(
                                  { id: article.id, action: 'reindex' },
                                  { onError: () => toast.error(t('common.genericError')) },
                                )
                              }
                            >
                              {t('knowledge.retry')}
                            </button>
                          ) : null}
                        </span>
                      </td>
                      <td className="px-3 py-3 font-mono">
                        {article.status === 'published' ? `${article.citations_count}×` : t('common.none')}
                      </td>
                      <td className="px-3 py-3 text-muted-foreground">
                        {t('knowledge.updatedBy', {
                          when: formatDateTime(article.updated_at, locale),
                          name: article.updated_by.name.split(' ')[0] ?? '',
                        })}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
            {!articles.isPending && articles.data.data.length === 0 ? (
              <EmptyState title={t('knowledge.empty')} hint={t('knowledge.emptyHint')} />
            ) : null}
          </div>
        )}
      </div>
    </div>
  );
}
