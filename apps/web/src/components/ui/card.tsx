import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Card({ className, ...props }: ComponentProps<'div'>) {
  return <div data-slot="card" className={cn('rounded-lg border bg-card', className)} {...props} />;
}

export function CardHeader({ className, ...props }: ComponentProps<'div'>) {
  return <div className={cn('flex items-center justify-between gap-2 px-4 pt-4', className)} {...props} />;
}

export function CardTitle({ className, children, ...props }: ComponentProps<'h2'>) {
  return (
    <h2 className={cn('text-sm font-semibold', className)} {...props}>
      {children}
    </h2>
  );
}

export function CardContent({ className, ...props }: ComponentProps<'div'>) {
  return <div className={cn('p-4', className)} {...props} />;
}

export function Skeleton({ className, ...props }: ComponentProps<'div'>) {
  return <div aria-hidden className={cn('animate-pulse rounded-md bg-muted', className)} {...props} />;
}
