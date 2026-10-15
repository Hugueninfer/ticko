import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { unwrap, type NotificationPreferences } from '@ticko/api-client';
import { BellOff } from 'lucide-react-native';
import { useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Linking, ScrollView, StyleSheet, Switch, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Chip } from '@/components/Chip';
import { OfflineBanner } from '@/components/States';
import { useAuth, useCurrentUser } from '@/features/auth/AuthProvider';
import { LANGUAGES, setLanguage, type Language } from '@/i18n';
import { api } from '@/lib/api';
import { initials } from '@/lib/format';
import { permissionGranted, pushSupport, registerForPush } from '@/lib/push';
import { radius, space, useTheme } from '@/theme';

function usePushPermission() {
  return useQuery({ queryKey: ['push', 'permission'], queryFn: permissionGranted, enabled: pushSupport() === 'supported' });
}

export function ProfileScreen() {
  const { t, i18n } = useTranslation();
  const { colors } = useTheme();
  const user = useCurrentUser();
  const { signOut, setUser } = useAuth();
  const queryClient = useQueryClient();
  const permission = usePushPermission();
  const support = pushSupport();
  const [signingOut, setSigningOut] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const preferences = useMutation({
    mutationFn: async (next: NotificationPreferences) =>
      unwrap(await api.PUT('/api/v1/auth/notification-preferences', { body: next })).data,
    // Optimistic: flip the switch now, put it back if the API refuses.
    onMutate: (next) => {
      const previous = user;
      setUser({ ...user, notification_preferences: next });

      return { previous };
    },
    onSuccess: (updated) => setUser(updated),
    onError: (_error, _next, context) => {
      if (context) setUser(context.previous);
      setError(t('profile.saveFailed'));
    },
  });

  const enablePush = async () => {
    const result = await registerForPush(i18n.language, true).catch(() => 'denied' as const);
    await queryClient.invalidateQueries({ queryKey: ['push', 'permission'] });

    if (result === 'denied') {
      await Linking.openSettings();
    }
  };

  const toggle = (key: keyof NotificationPreferences) => (value: boolean) => {
    setError(null);
    preferences.mutate({ ...user.notification_preferences, [key]: value });
  };

  const changeLanguage = async (language: Language) => {
    await setLanguage(language);
    // Pushes are written in the phone's language: tell the API.
    void registerForPush(language, false).catch(() => undefined);
  };

  return (
    <SafeAreaView edges={['top']} style={[styles.safe, { backgroundColor: colors.background }]}>
      <OfflineBanner />
      <ScrollView contentContainerStyle={styles.content}>
        <AppText variant="title" accessibilityRole="header">
          {t('profile.title')}
        </AppText>

        <View style={styles.identity}>
          <View style={[styles.avatar, { backgroundColor: colors.muted }]}>
            <AppText variant="body" weight="semibold" color="mutedForeground">
              {initials(user.name)}
            </AppText>
          </View>
          <View style={styles.flex}>
            <AppText variant="body" weight="semibold">
              {user.name}
            </AppText>
            <AppText variant="small" color="mutedForeground">
              {user.email}
            </AppText>
          </View>
        </View>

        {support === 'supported' && permission.data === false ? (
          <View style={[styles.card, { backgroundColor: colors.warningSoft, borderColor: colors.warningSoft }]}>
            <View style={styles.cardTitle}>
              <BellOff color={colors.warning} size={20} />
              <AppText variant="label" weight="semibold" color="warning">
                {t('profile.notificationsOff')}
              </AppText>
            </View>
            <AppText variant="small" color="mutedForeground">
              {t('profile.notificationsOffHint')}
            </AppText>
            <Button label={t('profile.enable')} variant="dark" onPress={() => void enablePush()} />
          </View>
        ) : null}

        {support === 'expo-go' ? (
          <View style={[styles.card, { backgroundColor: colors.muted, borderColor: colors.border }]}>
            <AppText variant="small" color="mutedForeground">
              {t('profile.expoGo')}
            </AppText>
          </View>
        ) : null}

        <View style={styles.section}>
          <AppText variant="small" weight="semibold" color="mutedForeground" style={styles.caps}>
            {t('profile.notifyWhen')}
          </AppText>
          <View style={[styles.group, { borderColor: colors.border }]}>
            <Row label={t('profile.replies')}>
              <Switch
                accessibilityLabel={t('profile.replies')}
                value={user.notification_preferences.push_replies}
                onValueChange={toggle('push_replies')}
                trackColor={{ true: colors.primary, false: colors.border }}
              />
            </Row>
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <Row label={t('profile.statusChanges')}>
              <Switch
                accessibilityLabel={t('profile.statusChanges')}
                value={user.notification_preferences.push_status_changes}
                onValueChange={toggle('push_status_changes')}
                trackColor={{ true: colors.primary, false: colors.border }}
              />
            </Row>
          </View>
          {error ? (
            <AppText variant="small" color="danger" accessibilityRole="alert">
              {error}
            </AppText>
          ) : null}
        </View>

        <View style={styles.section}>
          <AppText variant="small" weight="semibold" color="mutedForeground" style={styles.caps}>
            {t('profile.language')}
          </AppText>
          <View style={styles.chips} accessibilityRole="radiogroup">
            {LANGUAGES.map((language) => (
              <Chip
                key={language}
                label={t(`language.${language}`)}
                selected={i18n.language === language}
                onPress={() => void changeLanguage(language)}
              />
            ))}
          </View>
        </View>

        <Button
          label={signingOut ? t('profile.signingOut') : t('profile.signOut')}
          variant="outline"
          loading={signingOut}
          onPress={() => {
            setSigningOut(true);
            void signOut().finally(() => setSigningOut(false));
          }}
        />
      </ScrollView>
    </SafeAreaView>
  );
}

function Row({ label, children }: { label: string; children: ReactNode }) {
  return (
    <View style={styles.row}>
      <AppText style={styles.flex}>{label}</AppText>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1 },
  flex: { flex: 1 },
  content: { padding: space.xl, gap: space.xl },
  identity: { flexDirection: 'row', alignItems: 'center', gap: space.lg },
  avatar: { width: 48, height: 48, borderRadius: 24, alignItems: 'center', justifyContent: 'center' },
  card: { borderWidth: 1, borderRadius: radius.mobile, padding: space.lg, gap: space.md },
  cardTitle: { flexDirection: 'row', alignItems: 'center', gap: space.sm },
  section: { gap: space.sm },
  caps: { textTransform: 'uppercase', letterSpacing: 0.5 },
  group: { borderWidth: 1, borderRadius: radius.mobile },
  row: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: space.lg, minHeight: 56 },
  divider: { height: 1 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
});
