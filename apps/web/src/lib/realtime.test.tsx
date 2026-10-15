import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { mockApi } from '@/test/api-mock';

import {
  authorizeChannel,
  connectRealtime,
  disconnectRealtime,
  resumeRealtime,
  suspendRealtime,
  useEcho,
  useRealtimeConnection,
  useStaffUpdates,
} from './realtime';

const mocks = vi.hoisted(() => {
  const listeners = new Map<string, (event: { ticket_id: string }) => void>();
  const channel = {
    listen: vi.fn((event: string, callback: (event: { ticket_id: string }) => void) => {
      listeners.set(event, callback);

      return channel;
    }),
  };

  return { listeners, channel, Pusher: vi.fn(), Echo: vi.fn() };
});

vi.mock('pusher-js', () => ({ default: mocks.Pusher }));
vi.mock('laravel-echo', () => ({ default: mocks.Echo }));

mocks.Echo.mockImplementation(function () {
  return { private: vi.fn(() => mocks.channel), leave: vi.fn(), disconnect: vi.fn() };
});

function wrapper(queryClient: QueryClient) {
  return ({ children }: { children: ReactNode }) => <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}

describe('realtime', () => {
  afterEach(() => {
    disconnectRealtime();
    mocks.listeners.clear();
    document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
  });

  it('authorizes channels with the session cookie and the CSRF header', async () => {
    document.cookie = 'XSRF-TOKEN=abc%3D';
    const { requests } = mockApi({ 'POST /api/broadcasting/auth': { auth: 'key:sig' } });
    const callback = vi.fn();

    await authorizeChannel('1.2', 'private-tenant.t.staff', callback);

    expect(callback).toHaveBeenCalledWith(null, { auth: 'key:sig' });
    expect(requests[0]?.headers.get('X-XSRF-TOKEN')).toBe('abc=');
    expect(requests[0]?.body).toEqual({ socket_id: '1.2', channel_name: 'private-tenant.t.staff' });
  });

  it('reports refused authorizations', async () => {
    mockApi({ 'POST /api/broadcasting/auth': { status: 403 } });
    const callback = vi.fn();

    await authorizeChannel('1.2', 'private-x', callback);

    expect(callback).toHaveBeenCalledWith(expect.any(Error), null);
  });

  it('connects to Reverb on the page origin', () => {
    connectRealtime('key-1');

    expect(mocks.Pusher).toHaveBeenCalledWith(
      'key-1',
      expect.objectContaining({ wsHost: window.location.hostname, forceTLS: false }),
    );
    expect(mocks.Echo).toHaveBeenCalledWith(expect.objectContaining({ broadcaster: 'reverb', key: 'key-1' }));
  });

  it('connects when the API enables realtime, and refreshes the queue on events', async () => {
    mockApi({ 'GET /api/v1/realtime': { data: { enabled: true, key: 'public' } } });
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    const invalidate = vi.spyOn(queryClient, 'invalidateQueries');

    const { result } = renderHook(
      () => {
        useRealtimeConnection(true);
        useStaffUpdates('tenant-1');

        return useEcho();
      },
      { wrapper: wrapper(queryClient) },
    );

    await waitFor(() => expect(result.current).not.toBeNull());
    expect(result.current?.private).toHaveBeenCalledWith('tenant.tenant-1.staff');

    act(() => mocks.listeners.get('.message.posted')?.({ ticket_id: 'tk-1' }));

    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['tickets', 'list'] });
    expect(invalidate).toHaveBeenCalledWith({ queryKey: ['tickets', 'detail', 'tk-1'] });
  });

  it('waits for channel authorizations in flight before signing out', async () => {
    let release: (response: Response) => void = () => undefined;
    vi.stubGlobal(
      'fetch',
      vi.fn(() => new Promise<Response>((resolve) => (release = resolve))),
    );
    connectRealtime('key-1');
    const options = mocks.Pusher.mock.lastCall?.[1] as {
      channelAuthorization: { customHandler: (params: { socketId: string; channelName: string }, callback: () => void) => void };
    };
    options.channelAuthorization.customHandler({ socketId: '1.2', channelName: 'private-x' }, vi.fn());
    const settled = vi.fn();

    const waiting = suspendRealtime().then(settled);
    await Promise.resolve();
    expect(settled).not.toHaveBeenCalled();

    release(new Response(JSON.stringify({ auth: 'k:s' }), { status: 200 }));
    await waiting;
    expect(settled).toHaveBeenCalled();

    // While signing out nothing connects or authorizes; afterwards it can again.
    const refused = vi.fn();
    options.channelAuthorization.customHandler({ socketId: '1.3', channelName: 'private-x' }, refused);
    expect(refused).toHaveBeenCalledWith(expect.any(Error), null);
    expect(connectRealtime('key-1')).toBeNull();

    resumeRealtime();
    expect(connectRealtime('key-1')).not.toBeNull();
  });
});
