import { Geist_400Regular, Geist_500Medium, Geist_600SemiBold, Geist_700Bold, useFonts } from '@expo-google-fonts/geist';
import { QueryClientProvider } from '@tanstack/react-query';
import { SplashScreen, Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { ConfigErrorScreen } from '@/features/auth/ConfigErrorScreen';
import { AuthProvider, useAuth } from '@/features/auth/AuthProvider';
import { initI18n, savedLanguage } from '@/i18n';
import { ConfigError, getConfig } from '@/lib/config';
import { connectQueryToDevice, createQueryClient } from '@/lib/query';
import { useTheme } from '@/theme';

void SplashScreen.preventAutoHideAsync();
initI18n();

function RootNavigator() {
  const { state } = useAuth();
  const { colors, dark } = useTheme();
  const signedIn = state.status === 'signedIn';

  useEffect(() => {
    if (state.status !== 'loading') {
      SplashScreen.hide();
    }
  }, [state.status]);

  if (state.status === 'loading') {
    return null;
  }

  return (
    <>
      <StatusBar style={dark ? 'light' : 'dark'} />
      <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.background } }}>
        <Stack.Protected guard={signedIn}>
          <Stack.Screen name="(app)" />
        </Stack.Protected>
        <Stack.Protected guard={!signedIn}>
          <Stack.Screen name="sign-in" />
        </Stack.Protected>
      </Stack>
    </>
  );
}

export default function RootLayout() {
  const [queryClient] = useState(createQueryClient);
  const [fontsLoaded] = useFonts({ Geist_400Regular, Geist_500Medium, Geist_600SemiBold, Geist_700Bold });
  const [languageReady, setLanguageReady] = useState(false);
  const config = getConfig();

  useEffect(() => connectQueryToDevice(), []);

  useEffect(() => {
    void savedLanguage()
      .then((language) => (language ? initI18n().changeLanguage(language) : undefined))
      .finally(() => setLanguageReady(true));
  }, []);

  if (!fontsLoaded || !languageReady) {
    return null;
  }

  if (config instanceof ConfigError) {
    SplashScreen.hide();

    return <ConfigErrorScreen message={config.message} />;
  }

  return (
    <SafeAreaProvider>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <RootNavigator />
        </AuthProvider>
      </QueryClientProvider>
    </SafeAreaProvider>
  );
}
