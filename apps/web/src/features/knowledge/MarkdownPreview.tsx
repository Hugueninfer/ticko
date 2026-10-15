import Markdown from 'react-markdown';

/**
 * Safe Markdown rendering: raw HTML is dropped, only text elements are
 * allowed (no images, so no external requests), and links are restricted
 * to safe protocols by react-markdown's default URL transform.
 */
const ALLOWED = [
  'p',
  'h1',
  'h2',
  'h3',
  'h4',
  'ul',
  'ol',
  'li',
  'strong',
  'em',
  'a',
  'code',
  'pre',
  'blockquote',
  'hr',
  'br',
  'del',
];

export function MarkdownPreview({ markdown }: { markdown: string }) {
  return (
    <div className="prose-ticko text-[14px] leading-relaxed">
      <Markdown
        skipHtml
        allowedElements={ALLOWED}
        unwrapDisallowed
        components={{
          h1: ({ children }) => <h2 className="mt-4 mb-2 text-lg font-semibold">{children}</h2>,
          h2: ({ children }) => <h3 className="mt-4 mb-2 text-base font-semibold">{children}</h3>,
          h3: ({ children }) => <h4 className="mt-3 mb-1.5 font-semibold">{children}</h4>,
          p: ({ children }) => <p className="my-2">{children}</p>,
          ul: ({ children }) => <ul className="my-2 list-disc pl-5">{children}</ul>,
          ol: ({ children }) => <ol className="my-2 list-decimal pl-5">{children}</ol>,
          a: ({ children, href }) => (
            <a className="text-primary underline" href={href} target="_blank" rel="noopener noreferrer">
              {children}
            </a>
          ),
          code: ({ children }) => <code className="rounded bg-muted px-1 font-mono text-[13px]">{children}</code>,
          blockquote: ({ children }) => (
            <blockquote className="my-2 border-l-2 pl-3 text-muted-foreground">{children}</blockquote>
          ),
        }}
      >
        {markdown}
      </Markdown>
    </div>
  );
}
