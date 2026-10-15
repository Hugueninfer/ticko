import { ApiError } from '@ticko/api-client';
import { Paperclip, Send } from 'lucide-react';
import { useId, useRef, useState, type KeyboardEvent } from 'react';
import { useTranslation } from 'react-i18next';

import { useToast } from '@/components/layout/toast-context';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/input';
import { cn } from '@/lib/utils';

import { usePostMessage, useUploadAttachment } from '../api';

type Mode = 'reply' | 'note';

const ACCEPTED_IMAGES = 'image/jpeg,image/png,image/webp';

export function Composer({ ticketId, disabled }: { ticketId: string; disabled: boolean }) {
  const { t } = useTranslation();
  const toast = useToast();
  const [mode, setMode] = useState<Mode>('reply');
  const [body, setBody] = useState('');
  const [error, setError] = useState<string | null>(null);
  const fileInput = useRef<HTMLInputElement>(null);
  const textareaId = useId();
  const post = usePostMessage(ticketId);
  const upload = useUploadAttachment(ticketId);

  const errorMessage = (cause: unknown) =>
    cause instanceof ApiError ? (Object.values(cause.fieldErrors())[0] ?? cause.problem.detail) : t('common.genericError');

  const send = () => {
    const text = body.trim();

    if (!text || post.isPending) {
      return;
    }

    setError(null);
    post.mutate(
      { body: text, internal: mode === 'note' },
      {
        onSuccess: () => setBody(''),
        onError: (cause) => setError(errorMessage(cause)),
      },
    );
  };

  const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
      event.preventDefault();
      send();
    }
  };

  const onFile = (file: File | undefined) => {
    if (!file) {
      return;
    }

    upload.mutate(file, {
      onSuccess: () => toast.success(t('ticket.uploaded')),
      onError: (cause) => toast.error(errorMessage(cause)),
      onSettled: () => {
        if (fileInput.current) {
          fileInput.current.value = '';
        }
      },
    });
  };

  if (disabled) {
    return <p className="rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground">{t('ticket.closedHint')}</p>;
  }

  return (
    <form
      data-composer
      className={cn('rounded-lg border bg-background', mode === 'note' && 'border-note-border bg-note')}
      onSubmit={(event) => {
        event.preventDefault();
        send();
      }}
    >
      <div role="tablist" className="flex gap-1 px-2 pt-2">
        {(['reply', 'note'] as const).map((value) => (
          <button
            key={value}
            type="button"
            role="tab"
            aria-selected={mode === value}
            aria-controls={textareaId}
            onClick={() => setMode(value)}
            className={cn(
              'rounded-md px-3 py-1.5 text-[13px]',
              mode === value ? 'bg-muted font-medium' : 'text-muted-foreground',
            )}
          >
            {t(value === 'reply' ? 'ticket.reply' : 'ticket.note')}
          </button>
        ))}
      </div>
      <Textarea
        id={textareaId}
        aria-label={t(mode === 'reply' ? 'ticket.reply' : 'ticket.note')}
        className="min-h-24 resize-y border-0 bg-transparent focus-visible:outline-none"
        placeholder={t(mode === 'reply' ? 'ticket.replyPlaceholder' : 'ticket.notePlaceholder')}
        value={body}
        maxLength={10_000}
        onChange={(event) => setBody(event.target.value)}
        onKeyDown={onKeyDown}
      />
      {error ? (
        <p role="alert" className="px-3 text-[13px] text-danger">
          {error}
        </p>
      ) : null}
      <div className="flex items-center gap-2 px-2 pb-2">
        <input
          ref={fileInput}
          type="file"
          accept={ACCEPTED_IMAGES}
          className="sr-only"
          tabIndex={-1}
          aria-label={t('ticket.attach')}
          onChange={(event) => onFile(event.target.files?.[0])}
        />
        <Button
          variant="ghost"
          size="icon"
          aria-label={upload.isPending ? t('ticket.uploading') : t('ticket.attach')}
          disabled={upload.isPending}
          onClick={() => fileInput.current?.click()}
        >
          <Paperclip />
        </Button>
        <span className="ml-auto text-xs text-muted-foreground">{t('ticket.sendHint')}</span>
        <Button type="submit" size="sm" disabled={post.isPending || body.trim() === ''}>
          {post.isPending ? t('ticket.sending') : t('ticket.send')}
          <Send />
        </Button>
      </div>
    </form>
  );
}
