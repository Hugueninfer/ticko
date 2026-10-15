import { ApiError, type KbArticle, type KbArticleInput } from '@ticko/api-client';
import { AlertCircle, ChevronLeft, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate, useParams } from 'react-router';

import { useCurrentUser } from '@/auth/useAuth';
import { ErrorState, FullPageSpinner } from '@/components/layout/States';
import { useToast } from '@/components/layout/toast-context';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/card';
import { FieldError, Input, Label, NativeSelect, Textarea } from '@/components/ui/input';
import { useCategories } from '@/features/categories/api';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';

import { useArticle, useArticleAction, useChunks, useDeleteArticle, useSaveArticle } from './api';
import { IndexBadge, StatusBadge } from './IndexBadge';
import { MarkdownPreview } from './MarkdownPreview';

interface Draft {
  title: string;
  category_id: string;
  visibility: 'customers' | 'internal';
  tags: string;
  markdown: string;
}

const EMPTY: Draft = { title: '', category_id: '', visibility: 'customers', tags: '', markdown: '' };

function toDraft(article: KbArticle): Draft {
  return {
    title: article.title,
    category_id: article.category?.id ?? '',
    visibility: article.visibility,
    tags: article.tags.join(', '),
    markdown: article.markdown ?? '',
  };
}

function toInput(draft: Draft): KbArticleInput {
  return {
    title: draft.title.trim(),
    markdown: draft.markdown,
    visibility: draft.visibility,
    tags: draft.tags
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean),
    category_id: draft.category_id || null,
  };
}

function AiView({ article }: { article: KbArticle }) {
  const { t, i18n } = useTranslation();
  const chunks = useChunks(article.id, article.status === 'published');
  const locale = i18n.resolvedLanguage ?? 'pt-BR';

  if (article.status !== 'published') {
    return <p className="p-5 text-sm text-muted-foreground">{t('editor.notIndexed')}</p>;
  }

  if (chunks.isPending) {
    return <Skeleton className="m-5 h-40" />;
  }

  if (chunks.isError) {
    return <ErrorState onRetry={() => void chunks.refetch()} />;
  }

  return (
    <div className="grid gap-3 p-5">
      <p className="text-xs text-muted-foreground">
        {t('editor.chunksMeta', {
          count: chunks.data.data.length,
          target: chunks.data.meta.target_tokens,
          overlap: chunks.data.meta.overlap_tokens,
          model: chunks.data.meta.embedding_model,
        })}
      </p>
      {chunks.data.data.map((chunk) => (
        <article key={chunk.id} className="rounded-lg border">
          <header className="flex items-center gap-2 border-b bg-muted/40 px-3 py-2 text-[13px]">
            <span className="font-mono text-ai">#{chunk.ordinal}</span>
            <span className="min-w-0 flex-1 truncate">{chunk.heading}</span>
            <span className="font-mono text-xs text-muted-foreground">{t('editor.tokens', { count: chunk.tokens })}</span>
            <Badge variant={chunk.citations_count > 0 ? 'ai' : 'neutral'}>
              {t('editor.cited', { count: chunk.citations_count })}
            </Badge>
          </header>
          <p className="px-3 py-2.5 font-mono text-[12px] leading-relaxed whitespace-pre-wrap">
            {article.title} › {chunk.heading} — {chunk.content}
          </p>
        </article>
      ))}
      {article.has_unpublished_changes ? (
        <p className="flex items-center gap-2 rounded-lg bg-warning-soft p-3 text-[13px] text-warning">
          <AlertCircle className="size-4 shrink-0" aria-hidden /> {t('editor.staleWarning')}
        </p>
      ) : null}
      <p className="sr-only">{formatNumber(chunks.data.data.length, locale)}</p>
    </div>
  );
}

export function ArticleEditorPage() {
  const { t } = useTranslation();
  const { id: routeId } = useParams();
  const id = routeId && routeId !== 'new' ? routeId : null;
  const article = useArticle(id);

  if (id !== null && article.isPending) {
    return <FullPageSpinner />;
  }

  if (id !== null && article.isError) {
    const notFound = article.error instanceof ApiError && article.error.status === 404;

    return (
      <ErrorState
        title={notFound ? t('editor.notFound') : undefined}
        onRetry={notFound ? undefined : () => void article.refetch()}
      />
    );
  }

  // Keyed by article: the form starts from the loaded article and keeps the
  // agent's edits across background refreshes (indexing progress, etc.).
  return <ArticleForm key={id ?? 'new'} id={id} current={article.data ?? null} />;
}

function ArticleForm({ id, current }: { id: string | null; current: KbArticle | null }) {
  const { t, i18n } = useTranslation();
  const toast = useToast();
  const navigate = useNavigate();
  const { data: user } = useCurrentUser();
  const categories = useCategories();
  const save = useSaveArticle();
  const action = useArticleAction();
  const remove = useDeleteArticle();
  const [draft, setDraft] = useState<Draft>(() => (current ? toDraft(current) : EMPTY));
  const [tab, setTab] = useState<'preview' | 'ai'>('preview');
  const [errors, setErrors] = useState<Record<string, string>>({});

  const busy = save.isPending || action.isPending || remove.isPending;
  const words = draft.markdown.trim() === '' ? 0 : draft.markdown.trim().split(/\s+/).length;
  const set = (key: keyof Draft) => (value: string) => setDraft((previous) => ({ ...previous, [key]: value }));

  const onError = (cause: unknown) => {
    if (cause instanceof ApiError && cause.status === 422) {
      setErrors(cause.fieldErrors());
    } else {
      toast.error(t('common.genericError'));
    }
  };

  const persist = async (): Promise<KbArticle | null> => {
    setErrors({});

    try {
      return await save.mutateAsync({ id, input: toInput(draft) });
    } catch (cause) {
      onError(cause);

      return null;
    }
  };

  const saveDraft = async () => {
    const saved = await persist();

    if (saved) {
      toast.success(t('editor.saved'));

      if (id === null) void navigate(`/knowledge/${saved.id}`, { replace: true });
    }
  };

  const publish = async () => {
    const saved = await persist();

    if (saved) {
      action.mutate(
        { id: saved.id, action: 'publish' },
        {
          onSuccess: () => {
            toast.success(t('editor.published'));
            if (id === null) void navigate(`/knowledge/${saved.id}`, { replace: true });
          },
          onError,
        },
      );
    }
  };

  const unpublish = () => {
    if (current)
      action.mutate(
        { id: current.id, action: 'unpublish' },
        { onSuccess: () => toast.success(t('editor.unpublished')), onError },
      );
  };

  const destroy = () => {
    if (current && window.confirm(t('editor.deleteConfirm'))) {
      remove.mutate(current.id, {
        onSuccess: () => {
          toast.success(t('editor.deleted'));
          void navigate('/knowledge', { replace: true });
        },
        onError,
      });
    }
  };

  return (
    <div className="flex h-full flex-col">
      <header className="flex flex-wrap items-center gap-3 border-b px-6 py-3">
        <Link to="/knowledge" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
          <ChevronLeft className="size-4" aria-hidden /> {t('editor.back')}
        </Link>
        <span className="text-muted-foreground">/</span>
        <h1 className="min-w-0 flex-1 truncate font-semibold">{current?.title ?? t('editor.newTitle')}</h1>
        {current ? (
          <>
            <StatusBadge status={current.status} />
            <IndexBadge index={current.index} />
          </>
        ) : null}
        {current?.status === 'published' ? (
          <Button variant="ghost" size="sm" onClick={unpublish} disabled={busy}>
            {t('editor.unpublish')}
          </Button>
        ) : null}
        {current && user?.role === 'admin' ? (
          <Button variant="ghost" size="sm" className="text-danger" onClick={destroy} disabled={busy}>
            {t('editor.delete')}
          </Button>
        ) : null}
        <Button variant="outline" size="sm" onClick={() => void saveDraft()} disabled={busy}>
          {t('editor.save')}
        </Button>
        <Button size="sm" onClick={() => void publish()} disabled={busy}>
          {current?.status === 'published' ? t('editor.publishChanges') : t('editor.publish')}
        </Button>
      </header>

      <div className="grid gap-4 border-b px-6 py-4 md:grid-cols-[2fr_1fr_1fr_1.5fr]">
        <div>
          <Label htmlFor="kb-title">{t('editor.title')}</Label>
          <Input
            id="kb-title"
            value={draft.title}
            onChange={(e) => set('title')(e.target.value)}
            aria-invalid={errors.title ? true : undefined}
          />
          <FieldError>{errors.title}</FieldError>
        </div>
        <div>
          <Label htmlFor="kb-category">{t('editor.category')}</Label>
          <NativeSelect
            id="kb-category"
            className="w-full"
            value={draft.category_id}
            onChange={(e) => set('category_id')(e.target.value)}
          >
            <option value="">{t('knowledge.noCategory')}</option>
            {categories.data?.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </NativeSelect>
        </div>
        <div>
          <Label htmlFor="kb-visibility">{t('editor.visibility')}</Label>
          <NativeSelect
            id="kb-visibility"
            className="w-full"
            value={draft.visibility}
            onChange={(e) => set('visibility')(e.target.value)}
            title={t('editor.visibilityHint')}
          >
            <option value="customers">{t('editor.visibilities.customers')}</option>
            <option value="internal">{t('editor.visibilities.internal')}</option>
          </NativeSelect>
        </div>
        <div>
          <Label htmlFor="kb-tags">{t('editor.tags')}</Label>
          <Input
            id="kb-tags"
            placeholder={t('editor.tagsHint')}
            value={draft.tags}
            onChange={(e) => set('tags')(e.target.value)}
          />
          <FieldError>{errors.tags}</FieldError>
        </div>
      </div>

      <div className="grid min-h-0 flex-1 md:grid-cols-2">
        <div className="flex min-h-0 flex-col border-r">
          <div className="flex items-center justify-between border-b px-6 py-2">
            <Label htmlFor="kb-markdown" className="mb-0">
              {t('editor.markdown')}
            </Label>
            <span className="font-mono text-xs text-muted-foreground">
              {t('editor.words', { count: words, formattedCount: formatNumber(words, i18n.resolvedLanguage ?? 'pt-BR') })}
            </span>
          </div>
          <Textarea
            id="kb-markdown"
            className="min-h-96 flex-1 resize-none rounded-none border-0 px-6 py-4 font-mono text-[13px] leading-relaxed shadow-none focus-visible:ring-0"
            value={draft.markdown}
            onChange={(e) => set('markdown')(e.target.value)}
            aria-invalid={errors.markdown ? true : undefined}
          />
          <FieldError>{errors.markdown}</FieldError>
        </div>
        <div className="flex min-h-0 flex-col">
          <div className="flex gap-1 border-b px-4" role="tablist">
            {(['preview', 'ai'] as const).map((value) => (
              <button
                key={value}
                type="button"
                role="tab"
                aria-selected={tab === value}
                className={cn(
                  'inline-flex items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm',
                  tab === value
                    ? value === 'ai'
                      ? 'border-ai text-ai'
                      : 'border-foreground font-medium'
                    : 'border-transparent text-muted-foreground',
                )}
                onClick={() => setTab(value)}
              >
                {value === 'ai' ? <Sparkles className="size-3.5" aria-hidden /> : null}
                {t(value === 'ai' ? 'editor.aiView' : 'editor.preview')}
              </button>
            ))}
          </div>
          <div className="min-h-0 flex-1 overflow-y-auto" role="tabpanel">
            {tab === 'preview' ? (
              <div className="px-6 py-4">
                <MarkdownPreview markdown={draft.markdown} />
              </div>
            ) : current ? (
              <AiView article={current} />
            ) : (
              <p className="p-5 text-sm text-muted-foreground">{t('editor.notIndexed')}</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
