import { QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook, waitFor } from '@testing-library/react-native';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js/react-native';
import type { ReactNode } from 'react';

import { mockApi, respond } from '@/test/api-mock';
import { testQueryClient } from '@/test/render';

import { queryKeys } from './query';
import {
  authorizeChannel,
  connectRealtime,
  disconnectRealtime,
  socketTarget,
  ticketChannel,
  useEcho,
  useRealtimeConnection,
  useTicketUpdates,
} from './realtime';
import { saveSession } from './session';

const mockListeners = new Map<string, () => void>();
interface MockChannel {
  listen: jest.Mock<MockChannel, [string, () => void]>;
}

const mockChannel: MockChannel = {
  listen: jest.fn((event: string, callback: () => void): MockChannel => {
    mockListeners.set(event, callback);

    return mockChannel;
  }),
};

jest.mock('pusher-js/react-native', () => jest.fn());
jest.mock('laravel-echo', () =>
  jest.fn().mockImplementation(() => ({ private: jest.fn(() => mockChannel), leave: jest.fn(), disconnect: jest.fn() })),
);

describe('realtime', () => {
  afterEach(() => {
    disconnectRealtime();
    mockListeners.clear();
  });

  it('connects to the API origin', () => {
    expect(socketTarget('https://help.example.com')).toEqual({ host: 'help.example.com', port: 443, tls: true });
    expect(socketTarget('http://192.168.0.165:8090')).toEqual({ host: '192.168.0.165', port: 8090, tls: false });
    expect(socketTarget('http://localhost')).toEqual({ host: 'localhost', port: 80, tls: false });
  });

  it('authorizes private channels with the bearer token', async () => {
    const mock = mockApi({ 'POST /api/broadcasting/auth': { auth: 'key:signature' } });
    await saveSession('token-1', 60);
    const callback = jest.fn();

    await authorizeChannel('http://192.168.0.10:8080', '1.2', 'private-tenant.t.tickets.x', callback);

    expect(callback).toHaveBeenCalledWith(null, { auth: 'key:signature' });
    expect(mock.requests[0]?.headers.get('Authorization')).toBe('Bearer token-1');
    expect(mock.requests[0]?.body).toEqual({ socket_id: '1.2', channel_name: 'private-tenant.t.tickets.x' });
  });

  it('reports refused or failed authorizations', async () => {
    mockApi({ 'POST /api/broadcasting/auth': respond(403) });
    const callback = jest.fn();

    await authorizeChannel('http://192.168.0.10:8080', '1.2', 'private-x', callback);
    expect(callback).toHaveBeenCalledWith(expect.any(Error), null);

    globalThis.fetch = jest.fn(() => Promise.reject(new Error('offline')));
    await authorizeChannel('http://192.168.0.10:8080', '1.2', 'private-x', callback);
    expect(callback).toHaveBeenLastCalledWith(new Error('offline'), null);
  });

  it('creates one Reverb connection per key', () => {
    const first = connectRealtime('key-1');

    expect(connectRealtime('key-1')).toBe(first);
    expect(Pusher).toHaveBeenCalledWith(
      'key-1',
      expect.objectContaining({ wsHost: '192.168.0.10', wsPort: 8080, forceTLS: false, enabledTransports: ['ws', 'wss'] }),
    );
    expect(Echo).toHaveBeenCalledWith(expect.objectContaining({ broadcaster: 'reverb', key: 'key-1' }));

    connectRealtime('key-2');
    expect(first?.disconnect).toHaveBeenCalled();
  });

  it('connects only when the API enables realtime', async () => {
    mockApi({ 'GET /api/v1/realtime': { data: { enabled: true, key: 'public-key' } } });
    const queryClient = testQueryClient();
    const wrapper = ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    const { result, unmount } = await renderHook(
      () => {
        useRealtimeConnection(true);

        return useEcho();
      },
      { wrapper },
    );

    await waitFor(() => expect(result.current).not.toBeNull());
    await unmount();
  });

  it('refreshes the ticket when the server announces a change', async () => {
    const queryClient = testQueryClient();
    const invalidate = jest.spyOn(queryClient, 'invalidateQueries');
    const wrapper = ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );
    const connection = connectRealtime('key-1');

    const { unmount } = await renderHook(() => useTicketUpdates('tenant-1', 'ticket-1'), { wrapper });

    expect(connection?.private).toHaveBeenCalledWith(ticketChannel('tenant-1', 'ticket-1'));
    await act(() => mockListeners.get('.message.posted')?.());
    expect(invalidate).toHaveBeenCalledWith({ queryKey: queryKeys.messages('ticket-1') });

    await act(() => mockListeners.get('.status.changed')?.());
    expect(invalidate).toHaveBeenCalledWith({ queryKey: queryKeys.ticket('ticket-1') });

    await unmount();
    expect(connection?.leave).toHaveBeenCalledWith('tenant.tenant-1.tickets.ticket-1');
  });
});
