import Constants from 'expo-constants';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';

import { mockApi } from '@/test/api-mock';

import { openFromNotification, pushSupport, registerForPush } from './push';

const constants = Constants as { executionEnvironment: string; expoConfig: { extra?: unknown } | null };
const TICKET = '01m503y8y87hra2tfv9vqthppt';

function response(data: unknown) {
  return { notification: { request: { content: { data } } } } as unknown as Notifications.NotificationResponse;
}

describe('push notifications', () => {
  afterEach(() => {
    constants.executionEnvironment = 'bare';
    constants.expoConfig = { extra: { eas: { projectId: 'project-id' } } };
  });

  it('knows when remote pushes are impossible', () => {
    expect(pushSupport()).toBe('supported');

    constants.executionEnvironment = 'storeClient';
    expect(pushSupport()).toBe('expo-go');

    constants.executionEnvironment = 'bare';
    constants.expoConfig = { extra: {} };
    expect(pushSupport()).toBe('no-project');
  });

  it('does not ask for permission silently, and never registers without it', async () => {
    const mock = mockApi({});

    await expect(registerForPush('pt-BR', false)).resolves.toBe('denied');
    expect(Notifications.requestPermissionsAsync).not.toHaveBeenCalled();

    await expect(registerForPush('pt-BR', true)).resolves.toBe('denied');
    expect(Notifications.requestPermissionsAsync).toHaveBeenCalled();
    expect(mock.requests).toHaveLength(0);
  });

  it('registers the Expo token with the API in the app language', async () => {
    jest.mocked(Notifications.getPermissionsAsync).mockResolvedValueOnce({ granted: true } as never);
    const mock = mockApi({ 'POST /api/v1/push-devices': () => ({ status: 204 }) });

    await expect(registerForPush('en', false)).resolves.toBe('registered');

    expect(Notifications.getExpoPushTokenAsync).toHaveBeenCalledWith({ projectId: 'project-id' });
    expect(mock.calls('POST /api/v1/push-devices')[0]?.body).toEqual({
      token: 'ExponentPushToken[test]',
      platform: 'ios',
      locale: 'en',
    });
  });

  it('skips registration in Expo Go', async () => {
    constants.executionEnvironment = 'storeClient';

    await expect(registerForPush('pt-BR', true)).resolves.toBe('expo-go');
    expect(Notifications.getPermissionsAsync).not.toHaveBeenCalled();
  });

  it('opens only valid tickets from a tapped notification', () => {
    openFromNotification(response({ ticket_id: TICKET }));
    expect(router.push).toHaveBeenCalledWith(`/tickets/${TICKET}`);

    jest.mocked(router.push).mockClear();
    openFromNotification(response({ ticket_id: '../auth/me' }));
    openFromNotification(null);
    expect(router.push).not.toHaveBeenCalled();
  });
});
