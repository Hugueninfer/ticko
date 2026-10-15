import { Stack } from 'expo-router';
import { useEffect } from 'react';

import { useCurrentUser } from '@/features/auth/AuthProvider';
import { currentLanguage } from '@/i18n';
import { registerForPush, useNotificationNavigation } from '@/lib/push';
import { useRealtimeConnection } from '@/lib/realtime';
import { useTheme } from '@/theme';

export default function AppLayout() {
  const { colors } = useTheme();
  const user = useCurrentUser();

  useRealtimeConnection(true);
  useNotificationNavigation(true);

  // Re-register silently on every sign-in when permission was already granted.
  useEffect(() => {
    void registerForPush(currentLanguage(), false).catch(() => undefined);
  }, [user.id]);

  return (
    <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.background } }}>
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="tickets/new" options={{ presentation: 'modal' }} />
      <Stack.Screen name="tickets/[id]" />
    </Stack>
  );
}
