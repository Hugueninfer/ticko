import { Inbox, LoaderCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';

export function Spinner({ label }: { label?: string }) {
  const { t } = useTranslation();

  return (
    <span role="status" className="inline-flex items-center gap-2 text-muted-foreground">
      <LoaderCircle className="size-4 animate-spin" aria-hidden />
      <span className="sr-only">{label ?? t('common.loading')}</span>
    </span>
  );
}

export function FullPageSpinner() {
  return (
    <div className="flex h-full items-center justify-center">
      <Spinner />
    </div>
  );
}

export function ErrorState({ title, onRetry }: { title?: string; onRetry?: () => void }) {
  const { t } = useTranslation();

  return (
    <div role="alert" className="m-6 rounded-lg border border-danger/20 bg-danger-soft p-4">
      <p className="font-semibold text-danger">{title ?? t('common.loadError')}</p>
      {onRetry ? (
        <>
          <p className="mt-1 text-sm text-muted-foreground">{t('common.loadErrorHint')}</p>
          <Button variant="outline" size="sm" className="mt-3" onClick={onRetry}>
            {t('common.retry')}
          </Button>
        </>
      ) : null}
    </div>
  );
}

export function EmptyState({ title, hint, icon }: { title: string; hint?: string; icon?: ReactNode }) {
  return (
    <div className="flex flex-col items-center justify-center gap-1 p-10 text-center">
      {icon ?? <Inbox className="mb-2 size-5 text-muted-foreground" aria-hidden />}
      <p className="font-semibold">{title}</p>
      {hint ? <p className="text-sm text-muted-foreground">{hint}</p> : null}
    </div>
  );
}

export function PageHeader({ title, subtitle, actions }: { title: string; subtitle?: string; actions?: ReactNode }) {
  return (
    <header className="flex flex-wrap items-start justify-between gap-3 border-b px-6 py-5">
      <div>
        <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
        {subtitle ? <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p> : null}
      </div>
      {actions ? <div className="flex items-center gap-2">{actions}</div> : null}
    </header>
  );
}
