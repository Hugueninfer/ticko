import { QueryClient } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';

import { settleQueries } from './query';

describe('settleQueries', () => {
  it('resolves at once when nothing is loading', async () => {
    await expect(settleQueries(new QueryClient())).resolves.toBeUndefined();
  });

  it('waits for queries in flight', async () => {
    const queryClient = new QueryClient();
    let finish: (value: string) => void = () => undefined;
    void queryClient.query({ queryKey: ['slow'], queryFn: () => new Promise<string>((resolve) => (finish = resolve)) });
    const settled = vi.fn();

    const waiting = settleQueries(queryClient).then(settled);
    await Promise.resolve();
    expect(settled).not.toHaveBeenCalled();

    finish('done');
    await waiting;
    expect(settled).toHaveBeenCalled();
  });

  it('gives up after the timeout', async () => {
    vi.useFakeTimers();
    const queryClient = new QueryClient();
    void queryClient.query({ queryKey: ['stuck'], queryFn: () => new Promise<string>(() => undefined) });

    const waiting = settleQueries(queryClient, 1_000);
    vi.advanceTimersByTime(1_000);

    await expect(waiting).resolves.toBeUndefined();
    vi.useRealTimers();
  });
});
