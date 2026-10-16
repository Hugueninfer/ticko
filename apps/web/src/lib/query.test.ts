import { QueryClient } from '@tanstack/react-query';
import { ApiError } from '@ticko/api-client';
import { describe, expect, it, vi } from 'vitest';

import { retryDelay, settleQueries, shouldRetry } from './query';

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

describe('retries', () => {
  const error = (status: number) => new ApiError({ type: 'about:blank', title: 'x', status, detail: 'x' });

  it('retries server errors and rate limits, not other client errors', () => {
    expect(shouldRetry(0, error(500))).toBe(true);
    expect(shouldRetry(0, error(429))).toBe(true);
    expect(shouldRetry(2, error(429))).toBe(false);
    expect(shouldRetry(0, error(404))).toBe(false);
    expect(shouldRetry(0, new TypeError('offline'))).toBe(true);
  });

  it('backs off longer when rate limited', () => {
    expect(retryDelay(0, error(429))).toBe(2_000);
    expect(retryDelay(1, error(429))).toBe(4_000);
    expect(retryDelay(1, error(500))).toBe(1_000);
    expect(retryDelay(10, error(429))).toBe(10_000);
  });
});
