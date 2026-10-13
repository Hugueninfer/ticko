import { CheckCircle2, CircleAlert, X } from 'lucide-react';
import { useCallback, useMemo, useRef, useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/lib/utils';

import { ToastContext, type ToastApi } from './toast-context';

type Tone = 'success' | 'error';

interface Toast {
  id: number;
  tone: Tone;
  message: string;
}

/** Minimal toasts: plain DOM and Tailwind, no runtime-injected styles (strict CSP). */
export function ToastProvider({ children }: { children: ReactNode }) {
  const { t } = useTranslation();
  const [toasts, setToasts] = useState<Toast[]>([]);
  const nextId = useRef(1);

  const dismiss = useCallback((id: number) => {
    setToasts((current) => current.filter((toast) => toast.id !== id));
  }, []);

  const push = useCallback(
    (tone: Tone, message: string) => {
      const id = nextId.current++;
      setToasts((current) => [...current.slice(-2), { id, tone, message }]);
      window.setTimeout(() => dismiss(id), 4000);
    },
    [dismiss],
  );

  const api = useMemo<ToastApi>(
    () => ({ success: (message) => push('success', message), error: (message) => push('error', message) }),
    [push],
  );

  return (
    <ToastContext.Provider value={api}>
      {children}
      <div aria-live="polite" className="pointer-events-none fixed right-4 bottom-4 z-[60] flex w-80 flex-col gap-2">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            role={toast.tone === 'error' ? 'alert' : 'status'}
            className={cn(
              'pointer-events-auto flex items-start gap-2 rounded-lg border bg-popover p-3 text-sm shadow-lg',
              toast.tone === 'error' && 'border-danger/30',
            )}
          >
            {toast.tone === 'success' ? (
              <CheckCircle2 className="mt-0.5 size-4 text-success" aria-hidden />
            ) : (
              <CircleAlert className="mt-0.5 size-4 text-danger" aria-hidden />
            )}
            <p className="flex-1">{toast.message}</p>
            <button type="button" onClick={() => dismiss(toast.id)} aria-label={t('common.close')}>
              <X className="size-4 text-muted-foreground" aria-hidden />
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}
