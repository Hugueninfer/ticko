import { Tabs } from 'expo-router';
import { MessageSquare, User } from 'lucide-react-native';
import { useTranslation } from 'react-i18next';

import { fonts, useTheme } from '@/theme';

export default function TabsLayout() {
  const { t } = useTranslation();
  const { colors } = useTheme();

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.mutedForeground,
        tabBarStyle: { backgroundColor: colors.background, borderTopColor: colors.border },
        tabBarLabelStyle: { fontFamily: fonts.medium, fontSize: 12 },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{ title: t('tabs.tickets'), tabBarIcon: ({ color, size }) => <MessageSquare color={color} size={size} /> }}
      />
      <Tabs.Screen
        name="profile"
        options={{ title: t('tabs.profile'), tabBarIcon: ({ color, size }) => <User color={color} size={size} /> }}
      />
    </Tabs>
  );
}
