import type { TicketStatus } from '@ticko/api-client';
import { useTranslation } from 'react-i18next';
import { StyleSheet, View } from 'react-native';

import { radius, space, useTheme, type Colors } from '@/theme';

import { AppText } from './AppText';

/** Customer-facing status colours: amber when the ball is in their court. */
export const STATUS_TONE: Record<TicketStatus, { fg: keyof Colors; bg: keyof Colors }> = {
  open: { fg: 'mutedForeground', bg: 'muted' },
  pending: { fg: 'warning', bg: 'warningSoft' },
  resolved: { fg: 'success', bg: 'successSoft' },
  closed: { fg: 'mutedForeground', bg: 'muted' },
};

export function StatusBadge({ status }: { status: TicketStatus }) {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const tone = STATUS_TONE[status];

  return (
    <View style={[styles.badge, { backgroundColor: colors[tone.bg] }]} testID={`status-${status}`}>
      <View style={[styles.dot, { backgroundColor: colors[tone.fg] }]} />
      <AppText variant="small" weight="medium" style={{ color: colors[tone.fg] }}>
        {t(`status.${status}`)}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: space.xs + 2,
    paddingHorizontal: space.sm,
    paddingVertical: 2,
    borderRadius: radius.badge,
  },
  dot: { width: 6, height: 6, borderRadius: 3 },
});
