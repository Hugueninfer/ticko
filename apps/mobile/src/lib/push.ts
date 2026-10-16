import Constants, { ExecutionEnvironment } from 'expo-constants';
import * as Device from 'expo-device';
import type * as NotificationsModule from 'expo-notifications';
import { router } from 'expo-router';
import { useEffect } from 'react';
import { Platform } from 'react-native';

import { api } from './api';
import { ticketIdFromNotification, ticketPath } from './deeplink';

/** Android channel; the API sends pushes to it (channelId "tickets"). */
export const CHANNEL_ID = 'tickets';

export type PushSupport = 'supported' | 'expo-go' | 'simulator' | 'no-project';

export type PushRegistration = 'registered' | 'denied' | PushSupport;

type Notifications = typeof NotificationsModule;

let loaded: Notifications | null = null;

/**
 * expo-notifications, loaded only where remote pushes can work: since SDK 53,
 * merely importing it in Expo Go on Android throws, so the app would not open
 * there at all. Elsewhere it is loaded once, with the foreground handler.
 */
function notifications(): Notifications | null {
  if (Constants.executionEnvironment === ExecutionEnvironment.StoreClient) {
    return null;
  }

  if (loaded === null) {
    // eslint-disable-next-line @typescript-eslint/no-require-imports -- deliberately lazy, see above
    loaded = require('expo-notifications') as Notifications;
    // Foreground pushes still show a banner; realtime already refreshes the screen.
    loaded.setNotificationHandler({
      handleNotification: () =>
        Promise.resolve({ shouldShowBanner: true, shouldShowList: true, shouldPlaySound: false, shouldSetBadge: false }),
    });
  }

  return loaded;
}

function projectId(): string | undefined {
  const extra = Constants.expoConfig?.extra as { eas?: { projectId?: unknown } } | undefined;
  const eas = Constants.easConfig as { projectId?: unknown } | null | undefined;
  const id = extra?.eas?.projectId ?? eas?.projectId;

  return typeof id === 'string' && id !== '' ? id : undefined;
}

/**
 * Remote pushes need a development or store build with an EAS project id;
 * Expo Go (SDK 53+) and simulators cannot receive them.
 */
export function pushSupport(): PushSupport {
  if (Constants.executionEnvironment === ExecutionEnvironment.StoreClient) {
    return 'expo-go';
  }

  if (!Device.isDevice) {
    return 'simulator';
  }

  return projectId() ? 'supported' : 'no-project';
}

export async function permissionGranted(): Promise<boolean> {
  const module = notifications();

  return module === null ? false : (await module.getPermissionsAsync()).granted;
}

/**
 * Asks for permission (when `ask`) and registers this phone's Expo push
 * token with the API, tied to the current sign-in.
 */
export async function registerForPush(locale: string, ask: boolean): Promise<PushRegistration> {
  const support = pushSupport();
  const Notifications = notifications();

  if (support !== 'supported' || Notifications === null) {
    return support;
  }

  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync(CHANNEL_ID, {
      name: 'Tickets',
      importance: Notifications.AndroidImportance.HIGH,
    });
  }

  let granted = await permissionGranted();

  if (!granted && ask) {
    granted = (await Notifications.requestPermissionsAsync()).granted;
  }

  if (!granted) {
    return 'denied';
  }

  const token = (await Notifications.getExpoPushTokenAsync({ projectId: projectId() })).data;
  const { response } = await api.POST('/api/v1/push-devices', {
    body: { token, platform: Platform.OS === 'ios' ? 'ios' : 'android', locale: locale === 'en' ? 'en' : 'pt-BR' },
  });

  return response.ok ? 'registered' : 'denied';
}

/** Opens the ticket of a tapped notification (validated; the API re-checks access). */
export function openFromNotification(response: NotificationsModule.NotificationResponse | null): void {
  const path = ticketPath(ticketIdFromNotification(response?.notification.request.content.data));

  if (path) {
    router.push(path);
  }
}

/** Handles taps on notifications, including the one that launched the app. */
export function useNotificationNavigation(enabled: boolean): void {
  useEffect(() => {
    const Notifications = notifications();

    if (!enabled || Notifications === null) {
      return;
    }

    openFromNotification(Notifications.getLastNotificationResponse());
    const subscription = Notifications.addNotificationResponseReceivedListener(openFromNotification);

    return () => subscription.remove();
  }, [enabled]);
}
