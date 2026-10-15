import type { Attachment, Ticket, TicketMessage } from '@ticko/api-client';
import { ImageIcon, Lock } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { formatDate, formatFileSize, formatTime } from '@/lib/format';
import { cn } from '@/lib/utils';

/**
 * Message bodies are user content: always rendered as plain text (React
 * escapes it), never as HTML or Markdown.
 */
function MessageBubble({
  author,
  role,
  body,
  createdAt,
  internal,
}: {
  author: string;
  role: string;
  body: string;
  createdAt: string;
  internal: boolean;
}) {
  const { t, i18n } = useTranslation();
  const staff = role !== 'customer';

  return (
    <article className="flex gap-3" aria-label={`${author}, ${formatTime(createdAt, i18n.resolvedLanguage ?? 'pt-BR')}`}>
      <Avatar name={author} className={cn(staff && 'bg-primary-soft text-primary')} />
      <div className="min-w-0 flex-1">
        <header className="flex flex-wrap items-center gap-2 text-[13px]">
          <span className="font-semibold">{author}</span>
          {internal ? (
            <Badge variant="warning" className="border-note-border">
              <Lock className="size-3" aria-hidden />
              {t('ticket.internalNote')}
            </Badge>
          ) : null}
          <time dateTime={createdAt} className="text-muted-foreground">
            {formatTime(createdAt, i18n.resolvedLanguage ?? 'pt-BR')}
          </time>
        </header>
        <p
          className={cn(
            'mt-1.5 max-w-2xl rounded-lg px-3.5 py-2.5 break-words whitespace-pre-wrap',
            internal ? 'border border-dashed border-note-border bg-note' : staff ? 'border bg-background' : 'bg-muted',
          )}
        >
          {body}
        </p>
      </div>
    </article>
  );
}

export function Conversation({ ticket, messages }: { ticket: Ticket; messages: TicketMessage[] }) {
  const { i18n } = useTranslation();
  const locale = i18n.resolvedLanguage ?? 'pt-BR';

  return (
    <div className="flex flex-col gap-5">
      <p className="flex items-center gap-3 text-xs text-muted-foreground">
        <span className="h-px flex-1 bg-border" />
        {formatDate(ticket.created_at, locale, { dateStyle: 'full' })}
        <span className="h-px flex-1 bg-border" />
      </p>
      <MessageBubble
        author={ticket.requester.name}
        role="customer"
        body={ticket.description}
        createdAt={ticket.created_at}
        internal={false}
      />
      {messages.map((message) => (
        <MessageBubble
          key={message.id}
          author={message.author.name}
          role={message.author.role}
          body={message.body}
          createdAt={message.created_at}
          internal={message.is_internal}
        />
      ))}
    </div>
  );
}

export function AttachmentGrid({ attachments }: { attachments: Attachment[] }) {
  const { t, i18n } = useTranslation();

  if (attachments.length === 0) {
    return null;
  }

  return (
    <section aria-label={t('ticket.attachments')} className="mt-6">
      <h2 className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">{t('ticket.attachments')}</h2>
      <ul className="flex flex-wrap gap-3">
        {attachments.map((attachment) => (
          <li key={attachment.id} className="w-48 overflow-hidden rounded-lg border">
            <a
              href={attachment.url}
              target="_blank"
              rel="noopener noreferrer"
              aria-label={t('ticket.openImage', { name: attachment.original_name })}
            >
              <img src={attachment.url} alt="" loading="lazy" className="h-28 w-full bg-muted object-cover" />
            </a>
            <p className="flex items-center gap-1.5 truncate px-2.5 py-2 text-xs text-muted-foreground">
              <ImageIcon className="size-3.5 shrink-0" aria-hidden />
              <span className="truncate">{attachment.original_name}</span>
              <span className="shrink-0">· {formatFileSize(attachment.size, i18n.resolvedLanguage ?? 'pt-BR')}</span>
            </p>
          </li>
        ))}
      </ul>
    </section>
  );
}
