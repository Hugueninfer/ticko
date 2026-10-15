import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Input({ className, ...props }: ComponentProps<'input'>) {
  return (
    <input
      data-slot="input"
      className={cn(
        'h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground disabled:opacity-50 aria-invalid:border-danger',
        className,
      )}
      {...props}
    />
  );
}

export function Textarea({ className, ...props }: ComponentProps<'textarea'>) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        'min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground disabled:opacity-50 aria-invalid:border-danger',
        className,
      )}
      {...props}
    />
  );
}

/** Native select, styled like the inputs; accessible and CSP-friendly. */
export function NativeSelect({ className, ...props }: ComponentProps<'select'>) {
  return (
    <select
      data-slot="select"
      className={cn('h-9 rounded-md border border-input bg-background px-2 text-sm disabled:opacity-50', className)}
      {...props}
    />
  );
}

export function Label({ className, ...props }: ComponentProps<'label'>) {
  return <label data-slot="label" className={cn('text-[13px] font-medium', className)} {...props} />;
}

export function FieldError({ children, id }: { children?: string | undefined; id?: string }) {
  if (!children) {
    return null;
  }

  return (
    <p id={id} role="alert" className="text-[13px] text-danger">
      {children}
    </p>
  );
}
