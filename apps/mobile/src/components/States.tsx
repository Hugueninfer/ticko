import { Inbox, WifiOff } from 'lucide-react-native';
import { useTranslation } from 'react-i18next';
import { StyleSheet, View } from 'react-native';

import { useOnline } from '@/lib/network';
import { radius, space, useTheme } from '@/theme';

import { AppText } from './AppText';
import { Button } from './Button';

export function EmptyState({ title, hint }: { title: string; hint: string }) {
  const { colors } = useTheme();

  return (
    <View style={[styles.box, { borderColor: colors.border }]}>
      <Inbox color={colors.mutedForeground} size={24} />
      <AppText variant="label" weight="semibold">
        {title}
      </AppText>
      <AppText variant="small" color="mutedForeground" style={styles.center}>
        {hint}
      </AppText>
    </View>
  );
}

export function ErrorState({ onRetry, title, hint }: { onRetry?: () => void; title?: string; hint?: string }) {
  const { t } = useTranslation();
  const { colors } = useTheme();

  return (
    <View
      style={[styles.box, styles.left, { backgroundColor: colors.dangerSoft, borderColor: colors.dangerSoft }]}
      accessibilityRole="alert"
    >
      <AppText variant="label" weight="semibold" color="danger">
        {title ?? t('common.loadError')}
      </AppText>
      <AppText variant="small" color="mutedForeground">
        {hint ?? t('common.loadErrorHint')}
      </AppText>
      {onRetry ? <Button label={t('common.retry')} variant="outline" onPress={onRetry} style={styles.retry} /> : null}
    </View>
  );
}

/** Placeholder rows while a list loads. */
export function LoadingList({ rows = 3 }: { rows?: number }) {
  const { colors } = useTheme();

  return (
    <View style={styles.list} accessibilityLabel="loading" accessibilityRole="progressbar">
      {Array.from({ length: rows }, (_, index) => (
        <View key={index} style={[styles.skeleton, { borderColor: colors.border }]}>
          <View style={[styles.bar, { width: '35%', backgroundColor: colors.muted }]} />
          <View style={[styles.bar, { width: '80%', backgroundColor: colors.muted }]} />
          <View style={[styles.bar, { width: '60%', backgroundColor: colors.muted }]} />
        </View>
      ))}
    </View>
  );
}

/** Shown on every screen while the phone is offline; cached data stays visible. */
export function OfflineBanner() {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const online = useOnline();

  if (online) {
    return null;
  }

  return (
    <View style={[styles.banner, { backgroundColor: colors.warningSoft }]} accessibilityRole="alert">
      <WifiOff color={colors.warning} size={18} />
      <AppText variant="small" weight="medium" color="warning">
        {t('offline.banner')}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  box: { borderWidth: 1, borderRadius: radius.mobile, padding: space.xl, gap: space.sm, alignItems: 'center' },
  left: { alignItems: 'flex-start' },
  center: { textAlign: 'center' },
  retry: { marginTop: space.sm, alignSelf: 'flex-start', minHeight: 40 },
  list: { gap: space.md },
  skeleton: { borderWidth: 1, borderRadius: radius.mobile, padding: space.lg, gap: space.sm },
  bar: { height: 12, borderRadius: 6 },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.sm,
    paddingHorizontal: space.lg,
    paddingVertical: space.sm + 2,
  },
});
