import type { KbArticleSummary } from '@ticko/api-client';
import { Check } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Badge } from '@/components/ui/badge';

/** Indexing state of an article, in violet: it is about what the AI can use. */
export function IndexBadge({ index }: { index: KbArticleSummary['index'] }) {
  const { t } = useTranslation();

  switch (index.status) {
    case 'indexed':
      return (
        <Badge variant="ai">
          <Check className="size-3" aria-hidden /> {t('knowledge.index.indexed', { count: index.chunks_count })}
        </Badge>
      );
    case 'indexing':
      return (
        <span className="inline-flex items-center gap-2 text-xs text-ai">
          <span className="h-1 w-16 overflow-hidden rounded-full bg-ai-soft" aria-hidden>
            <span className="block h-full bg-ai" style={{ width: `${index.progress}%` }} />
          </span>
          {t('knowledge.index.indexing', { progress: index.progress })}
        </span>
      );
    case 'queued':
      return <Badge variant="neutral">● {t('knowledge.index.queued')}</Badge>;
    case 'failed':
      return (
        <Badge variant="danger" title={index.error ?? undefined}>
          {t('knowledge.index.failed')}
        </Badge>
      );
    default:
      return <span className="text-xs text-muted-foreground">{t('knowledge.index.none')}</span>;
  }
}

export function StatusBadge({ status }: { status: KbArticleSummary['status'] }) {
  const { t } = useTranslation();

  return <Badge variant={status === 'published' ? 'success' : 'neutral'}>{t(`knowledge.status.${status}`)}</Badge>;
}
