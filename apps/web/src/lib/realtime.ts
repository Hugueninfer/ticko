import { useQuery, useQueryClient } from '@tanstack/react-query';
import { readDocumentCookie, unwrap } from '@ticko/api-client';
import Echo from 'laravel-echo';
import Pusher, { type ChannelAuthorizationCallback } from 'pusher-js';
import { useEffect, useSyncExternalStore } from 'react';

import { api } from './api';
import { queryKeys } from './query';

/**
 * Live updates over Laravel Reverb on the page's own origin (/app, proxied
 * by Caddy or Vite). Events carry ids only; the queries refetch through
 * the API. Channel authorization uses the session cookie and CSRF header.
 */

let echo: Echo<'reverb'> | null = null;
const listeners = new Set<() => void>();

function setEcho(next: Echo<'reverb'> | null): void {
  echo = next;
  listeners.forEach((listener) => {
    listener();
  });
}

export function useEcho(): Echo<'reverb'> | null {
  return useSyncExternalStore(
    (onChange) => {
      listeners.add(onChange);

      return () => listeners.delete(onChange);
    },
    () => echo,
  );
}

export async function authorizeChannel(
  socketId: string,
  channelName: string,
  callback: ChannelAuthorizationCallback,
): Promise<void> {
  try {
    const xsrf = readDocumentCookie('XSRF-TOKEN');
    const response = await globalThis.fetch(`${window.location.origin}/api/broadcasting/auth`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
      },
      body: JSON.stringify({ socket_id: socketId, channel_name: channelName }),
    });

    if (!response.ok) {
      callback(new Error(`Channel authorization failed (${response.status})`), null);

      return;
    }

    callback(null, (await response.json()) as { auth: string });
  } catch (error) {
    callback(error instanceof Error ? error : new Error('Channel authorization failed'), null);
  }
}

export function connectRealtime(key: string): Echo<'reverb'> {
  disconnectRealtime();

  const tls = window.location.protocol === 'https:';
  const port = window.location.port ? Number(window.location.port) : tls ? 443 : 80;
  const client = new Pusher(key, {
    cluster: '',
    wsHost: window.location.hostname,
    wsPort: port,
    wssPort: port,
    forceTLS: tls,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    channelAuthorization: {
      customHandler: ({ socketId, channelName }, callback) => void authorizeChannel(socketId, channelName, callback),
    },
  });
  const instance = new Echo({ broadcaster: 'reverb', key, client });
  setEcho(instance);

  return instance;
}

export function disconnectRealtime(): void {
  echo?.disconnect();
  setEcho(null);
}

/** Connects while a staff member is signed in and the API enables realtime. */
export function useRealtimeConnection(enabled: boolean): void {
  const settings = useQuery({
    queryKey: ['realtime'],
    queryFn: async () => unwrap(await api.GET('/api/v1/realtime')).data,
    enabled,
    staleTime: Infinity,
    retry: false,
  });
  const key = settings.data?.enabled ? settings.data.key : null;

  useEffect(() => {
    if (!enabled || !key) {
      return;
    }

    connectRealtime(key);

    return () => {
      disconnectRealtime();
    };
  }, [enabled, key]);
}

/** Refreshes the queue and counters when any ticket of the tenant changes. */
export function useStaffUpdates(tenantId: string | undefined): void {
  const queryClient = useQueryClient();
  const connection = useEcho();

  useEffect(() => {
    if (!connection || !tenantId) {
      return;
    }

    const name = `tenant.${tenantId}.staff`;
    const refresh = (event: { ticket_id: string }) => {
      void queryClient.invalidateQueries({ queryKey: ['tickets', 'list'] });
      void queryClient.invalidateQueries({ queryKey: ['tickets', 'count'] });
      void queryClient.invalidateQueries({ queryKey: queryKeys.ticket(event.ticket_id) });
    };

    connection
      .private(name)
      .listen('.ticket.created', refresh)
      .listen('.message.posted', refresh)
      .listen('.status.changed', refresh);

    return () => {
      connection.leave(name);
    };
  }, [connection, tenantId, queryClient]);
}
