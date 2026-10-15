import { initI18n } from '@/i18n';
import type * as Session from '@/lib/session';

type SessionModule = typeof Session;

// In-memory secure storage: the real one is the platform keychain.
jest.mock('expo-secure-store', () => {
  const store = new Map<string, string>();

  return {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: 'WHEN_UNLOCKED_THIS_DEVICE_ONLY',
    getItemAsync: jest.fn((key: string) => Promise.resolve(store.get(key) ?? null)),
    setItemAsync: jest.fn((key: string, value: string) => {
      store.set(key, value);

      return Promise.resolve();
    }),
    deleteItemAsync: jest.fn((key: string) => {
      store.delete(key);

      return Promise.resolve();
    }),
    __store: store,
  };
});

jest.mock('@react-native-community/netinfo', () => ({
  __esModule: true,
  default: {
    addEventListener: jest.fn(() => jest.fn()),
    fetch: jest.fn(() => Promise.resolve({ isConnected: true })),
  },
}));

// The WebSocket client is exercised in realtime.test.tsx; elsewhere it stays inert.
jest.mock('pusher-js/react-native', () => jest.fn());

jest.mock('expo-router', () => ({
  router: { push: jest.fn(), replace: jest.fn(), back: jest.fn() },
  useLocalSearchParams: jest.fn(() => ({})),
}));

jest.mock('expo-localization', () => ({ getLocales: jest.fn(() => [{ languageTag: 'pt-BR' }]) }));

jest.mock('expo-device', () => ({ modelName: 'Pixel Test', isDevice: true }));

jest.mock('expo-image-picker', () => ({
  requestCameraPermissionsAsync: jest.fn(() => Promise.resolve({ granted: true })),
  launchCameraAsync: jest.fn(() => Promise.resolve({ canceled: true, assets: null })),
  launchImageLibraryAsync: jest.fn(() => Promise.resolve({ canceled: true, assets: null })),
}));

jest.mock('expo-image-manipulator', () => {
  const context = {
    resize: jest.fn(),
    renderAsync: jest.fn(() => Promise.resolve({ saveAsync: jest.fn(() => Promise.resolve({ uri: 'file:///clean.jpg' })) })),
  };

  return { ImageManipulator: { manipulate: jest.fn(() => context) }, SaveFormat: { JPEG: 'jpeg' }, __context: context };
});

jest.mock('expo-notifications', () => ({
  setNotificationHandler: jest.fn(),
  setNotificationChannelAsync: jest.fn(() => Promise.resolve()),
  getPermissionsAsync: jest.fn(() => Promise.resolve({ granted: false })),
  requestPermissionsAsync: jest.fn(() => Promise.resolve({ granted: false })),
  getExpoPushTokenAsync: jest.fn(() => Promise.resolve({ data: 'ExponentPushToken[test]' })),
  getLastNotificationResponse: jest.fn(() => null),
  addNotificationResponseReceivedListener: jest.fn(() => ({ remove: jest.fn() })),
  AndroidImportance: { HIGH: 4 },
}));

jest.mock('expo-constants', () => ({
  __esModule: true,
  default: { executionEnvironment: 'bare', expoConfig: { extra: { eas: { projectId: 'project-id' } } }, easConfig: null },
  ExecutionEnvironment: { Bare: 'bare', Standalone: 'standalone', StoreClient: 'storeClient' },
}));

process.env.EXPO_PUBLIC_API_URL = 'http://192.168.0.10:8080';
process.env.EXPO_PUBLIC_TENANT = 'acme';

initI18n('pt-BR');

afterEach(async () => {
  const { clearSession } = jest.requireActual<SessionModule>('@/lib/session');
  await clearSession();
  jest.requireMock<{ __store: Map<string, string> }>('expo-secure-store').__store.clear();
  jest.clearAllMocks();
});
