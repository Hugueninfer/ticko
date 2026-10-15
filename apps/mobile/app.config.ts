import type { ConfigContext, ExpoConfig } from 'expo/config';

/**
 * Only public values belong here and in EXPO_PUBLIC_* variables: they are
 * compiled into the app bundle. The API URL and tenant slug are public;
 * secrets never are (see README).
 */
export default ({ config }: ConfigContext): ExpoConfig => ({
  ...config,
  name: 'Ticko',
  slug: 'ticko',
  scheme: 'ticko',
  version: '0.1.0',
  orientation: 'portrait',
  icon: './assets/icon.png',
  userInterfaceStyle: 'automatic',
  ios: {
    supportsTablet: false,
    bundleIdentifier: 'dev.ticko.app',
  },
  android: {
    package: 'dev.ticko.app',
    adaptiveIcon: {
      backgroundColor: '#0f766e',
      foregroundImage: './assets/android-icon-foreground.png',
      backgroundImage: './assets/android-icon-background.png',
      monochromeImage: './assets/android-icon-monochrome.png',
    },
    predictiveBackGestureEnabled: false,
  },
  plugins: [
    'expo-router',
    'expo-secure-store',
    'expo-font',
    'expo-localization',
    'expo-image',
    ['expo-notifications', { color: '#0f766e' }],
    [
      'expo-image-picker',
      {
        photosPermission: 'Ticko uses your photos only to attach them to a ticket.',
        cameraPermission: 'Ticko uses the camera only to photograph something for a ticket.',
        microphonePermission: false,
      },
    ],
  ],
  experiments: { typedRoutes: true },
  extra: {
    // Needed for push tokens in development/production builds (EAS project id, not a secret).
    ...(process.env.EAS_PROJECT_ID ? { eas: { projectId: process.env.EAS_PROJECT_ID } } : {}),
  },
});
