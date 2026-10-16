import { useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap } from '@ticko/api-client';
import Echo from 'laravel-echo';
import type PusherClient from 'pusher-js/react-native';
import * as PusherModule from 'pusher-js/react-native';
import type { ChannelAuthorizationCallback } from 'pusher-js/react-native';
import { useEffect, useSyncExternalStore } from 'react';

import { api } from './api';
import { getConfig, ConfigError } from './config';
import { queryKeys } from './query';
import { currentToken } from './session';

/**
 * pusher-js's React Native build is CommonJS and exports the class as `Pusher`
 * (`module.exports.Pusher`), not as `default`: a default import gets the module
 * object and `new` throws on the device. Accept both shapes.
 */
export function pusherClass(module: unknown = PusherModule): typeof PusherClient {
  const candidate = module as { Pusher?: unknown; default?: unknown };

  for (const value of [candidate.Pusher, candidate.default, module]) {
    if (typeof value === 'function') {
      return value as typeof PusherClient;
    }
  }

  throw new TypeError('pusher-js did not export a constructor.');
}

/**
 * Realtime updates over Laravel Reverb (Pusher protocol), on the API's own
 * origin at /app. Events only say *what* changed; the app then refetches
 * through the API, which applies the usual authorization.
 */

let echo: Echo<'reverb'> | null = null;
let echoKey: string | null = null;
const listeners = new Set<() => void>();

function setEcho(next: Echo<'reverb'> | null, key: string | null): void {
  echo = next;
  echoKey = key;
  listeners.forEach((listener) => listener());
}

/** The live connection, or null; re-renders when it connects or disconnects. */
export function useEcho(): Echo<'reverb'> | null {
  return useSyncExternalStore(
    (onChange) => {
      listeners.add(onChange);

      return () => listeners.delete(onChange);
    },
    () => echo,
  );
}

export interface SocketTarget {
  host: string;
  port: number;
  tls: boolean;
}

export function socketTarget(apiUrl: string): SocketTarget {
  const url = new URL(apiUrl);
  const tls = url.protocol === 'https:';

  return { host: url.hostname, port: url.port ? Number(url.port) : tls ? 443 : 80, tls };
}

/** Private channels are authorized by the API with the same bearer token. */
export async function authorizeChannel(
  apiUrl: string,
  socketId: string,
  channelName: string,
  callback: ChannelAuthorizationCallback,
) {
  try {
    const token = currentToken();
    const response = await fetch(`${apiUrl}/api/broadcasting/auth`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
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

export function connectRealtime(key: string): Echo<'reverb'> | null {
  const config = getConfig();

  if (config instanceof ConfigError) {
    return null;
  }

  if (echo && echoKey === key) {
    return echo;
  }

  disconnectRealtime();

  const target = socketTarget(config.apiUrl);
  const Pusher = pusherClass();
  const client = new Pusher(key, {
    cluster: '',
    wsHost: target.host,
    wsPort: target.port,
    wssPort: target.port,
    forceTLS: target.tls,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    channelAuthorization: {
      customHandler: ({ socketId, channelName }, callback) =>
        void authorizeChannel(config.apiUrl, socketId, channelName, callback),
    },
  });

  const instance = new Echo({ broadcaster: 'reverb', key, client });
  setEcho(instance, key);

  return instance;
}

export function disconnectRealtime(): void {
  echo?.disconnect();
  setEcho(null, null);
}

/** Connects once the API says realtime is on; disconnects on sign-out. */
export function useRealtimeConnection(enabled: boolean): void {
  const settings = useQuery({
    queryKey: queryKeys.realtime,
    queryFn: async () => unwrap(await api.GET('/api/v1/realtime')).data,
    enabled,
    staleTime: Infinity,
  });
  const key = settings.data?.enabled ? settings.data.key : null;

  useEffect(() => {
    if (!enabled || !key) {
      return;
    }

    connectRealtime(key);

    return () => disconnectRealtime();
  }, [enabled, key]);
}

export function ticketChannel(tenantId: string, ticketId: string): string {
  return `tenant.${tenantId}.tickets.${ticketId}`;
}

/** Refreshes a ticket's conversation and status as soon as they change. */
export function useTicketUpdates(tenantId: string | undefined, ticketId: string): void {
  const queryClient = useQueryClient();
  const connection = useEcho();

  useEffect(() => {
    if (!connection || !tenantId) {
      return;
    }

    const name = ticketChannel(tenantId, ticketId);
    const refresh = (keys: (readonly string[])[]) => {
      for (const queryKey of keys) {
        void queryClient.invalidateQueries({ queryKey });
      }
    };

    connection
      .private(name)
      .listen('.message.posted', () =>
        refresh([
          queryKeys.messages(ticketId),
          queryKeys.ticket(ticketId),
          queryKeys.attachments(ticketId),
          queryKeys.ticketLists,
        ]),
      )
      .listen('.status.changed', () => refresh([queryKeys.ticket(ticketId), queryKeys.ticketLists]));

    return () => connection.leave(name);
  }, [connection, tenantId, ticketId, queryClient]);
}
