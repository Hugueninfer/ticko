import { router } from 'expo-router';
import { Plus } from 'lucide-react-native';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { FlatList, Pressable, RefreshControl, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AppText } from '@/components/AppText';
import { Segmented } from '@/components/Segmented';
import { EmptyState, ErrorState, LoadingList, OfflineBanner } from '@/components/States';
import { radius, space, useTheme } from '@/theme';

import { useTickets, type ListState } from './api';
import { TicketCard } from './TicketCard';

export function TicketsScreen() {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const [state, setState] = useState<ListState>('active');
  const tickets = useTickets(state);
  const active = useTickets('active');
  const activeCount = active.data?.meta.total;

  return (
    <SafeAreaView edges={['top']} style={[styles.safe, { backgroundColor: colors.background }]}>
      <OfflineBanner />
      <View style={styles.header}>
        <AppText variant="title" accessibilityRole="header">
          {t('tickets.title')}
        </AppText>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={t('tickets.new')}
          onPress={() => router.push('/tickets/new')}
          style={({ pressed }) => [styles.add, { backgroundColor: colors.primary, opacity: pressed ? 0.85 : 1 }]}
        >
          <Plus color={colors.primaryForeground} size={24} />
        </Pressable>
      </View>
      <View style={styles.tabs}>
        <Segmented
          value={state}
          onChange={setState}
          segments={[
            {
              value: 'active',
              label: activeCount !== undefined ? t('tickets.activeCount', { count: activeCount }) : t('tickets.active'),
            },
            { value: 'done', label: t('tickets.done') },
          ]}
        />
      </View>

      {tickets.isPending ? (
        <View style={styles.body}>
          <LoadingList />
        </View>
      ) : tickets.isError && !tickets.data ? (
        <View style={styles.body}>
          <ErrorState onRetry={() => void tickets.refetch()} />
        </View>
      ) : (
        <FlatList
          data={tickets.data.data}
          keyExtractor={(ticket) => ticket.id}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={tickets.isRefetching}
              onRefresh={() => void tickets.refetch()}
              tintColor={colors.primary}
            />
          }
          renderItem={({ item }) => <TicketCard ticket={item} onPress={() => router.push(`/tickets/${item.id}`)} />}
          ListEmptyComponent={
            state === 'active' ? (
              <EmptyState title={t('tickets.empty')} hint={t('tickets.emptyHint')} />
            ) : (
              <EmptyState title={t('tickets.emptyDone')} hint={t('tickets.emptyDoneHint')} />
            )
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1 },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: space.xl,
    paddingTop: space.lg,
  },
  add: { width: 44, height: 44, borderRadius: radius.mobile, alignItems: 'center', justifyContent: 'center' },
  tabs: { paddingHorizontal: space.xl, paddingVertical: space.lg },
  body: { paddingHorizontal: space.xl },
  list: { paddingHorizontal: space.xl, paddingBottom: space.xl, gap: space.md },
});
