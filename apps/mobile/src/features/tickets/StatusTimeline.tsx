import { useTranslation } from 'react-i18next';
import { StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import { space, useTheme } from '@/theme';

import type { TimelineStep } from './timeline';

const STEPS: TimelineStep[] = ['open', 'progress', 'resolved'];

export function StatusTimeline({ step }: { step: TimelineStep }) {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const current = STEPS.indexOf(step);

  return (
    <View
      style={[styles.row, { borderColor: colors.border, backgroundColor: colors.muted }]}
      accessible
      accessibilityRole="progressbar"
      accessibilityLabel={t(`conversation.timeline.${step}`)}
      accessibilityValue={{ min: 1, max: STEPS.length, now: current + 1 }}
    >
      {STEPS.map((item, index) => {
        const done = index < current;
        const active = index === current;

        return (
          <View key={item} style={styles.step}>
            <View style={styles.track}>
              <View
                style={[
                  styles.line,
                  { backgroundColor: index === 0 ? 'transparent' : index <= current ? colors.primary : colors.border },
                ]}
              />
              <View
                style={[
                  styles.dot,
                  {
                    borderColor: index <= current ? colors.primary : colors.border,
                    backgroundColor: done || (active && item === 'resolved') ? colors.primary : colors.background,
                  },
                ]}
              />
              <View
                style={[
                  styles.line,
                  {
                    backgroundColor:
                      index === STEPS.length - 1 ? 'transparent' : index < current ? colors.primary : colors.border,
                  },
                ]}
              />
            </View>
            <AppText variant="small" weight={active ? 'semibold' : 'regular'} color={active ? 'foreground' : 'mutedForeground'}>
              {t(`conversation.timeline.${item}`)}
            </AppText>
          </View>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', paddingVertical: space.md, borderBottomWidth: 1 },
  step: { flex: 1, alignItems: 'center', gap: space.xs },
  track: { flexDirection: 'row', alignItems: 'center', width: '100%' },
  line: { flex: 1, height: 2 },
  dot: { width: 12, height: 12, borderRadius: 6, borderWidth: 2 },
});
