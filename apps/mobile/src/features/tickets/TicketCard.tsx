import { useTranslation } from 'react-i18next';
import { Pressable, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import { StatusBadge } from '@/components/StatusBadge';
import { formatListDate } from '@/lib/format';
import { radius, space, useTheme } from '@/theme';

import type { TicketListItem } from './api';

export function TicketCard({ ticket, onPress }: { ticket: TicketListItem; onPress: () => void }) {
  const { t, i18n } = useTranslation();
  const { colors } = useTheme();
  const last = ticket.last_message ?? null;
  // The ball is in the customer's court: highlight the card.
  const highlighted = ticket.status === 'pending';
  const author = last ? (last.author_role === 'customer' ? t('common.you') : last.author_name.split(' ')[0]) : null;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`${ticket.subject}, ${t(`status.${ticket.status}`)}`}
      onPress={onPress}
      style={({ pressed }) => [
        styles.card,
        { borderColor: highlighted ? colors.primary : colors.border, backgroundColor: colors.card, opacity: pressed ? 0.85 : 1 },
      ]}
    >
      <View style={styles.row}>
        <StatusBadge status={ticket.status} />
        <AppText variant="small" color="mutedForeground">
          {formatListDate(last?.created_at ?? ticket.updated_at, i18n.language, t('common.yesterday'))}
        </AppText>
      </View>
      <AppText variant="body" weight="semibold" numberOfLines={2}>
        {ticket.subject}
      </AppText>
      <AppText variant="small" color="mutedForeground" numberOfLines={2}>
        {last ? `${author ?? ''}: ${last.excerpt}` : t('tickets.noMessages')}
      </AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: { borderWidth: 1, borderRadius: radius.mobile, padding: space.lg, gap: space.sm },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
});
