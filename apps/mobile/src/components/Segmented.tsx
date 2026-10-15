import { Pressable, StyleSheet, View } from 'react-native';

import { radius, space, useTheme } from '@/theme';

import { AppText } from './AppText';

export interface Segment<T extends string> {
  value: T;
  label: string;
}

export function Segmented<T extends string>({
  segments,
  value,
  onChange,
}: {
  segments: Segment<T>[];
  value: T;
  onChange: (value: T) => void;
}) {
  const { colors } = useTheme();

  return (
    <View style={[styles.track, { backgroundColor: colors.muted }]} accessibilityRole="tablist">
      {segments.map((segment) => {
        const selected = segment.value === value;

        return (
          <Pressable
            key={segment.value}
            accessibilityRole="tab"
            accessibilityState={{ selected }}
            onPress={() => onChange(segment.value)}
            style={[styles.segment, selected ? { backgroundColor: colors.background, borderColor: colors.border } : null]}
          >
            <AppText
              variant="label"
              weight={selected ? 'semibold' : 'medium'}
              color={selected ? 'foreground' : 'mutedForeground'}
            >
              {segment.label}
            </AppText>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  track: { flexDirection: 'row', borderRadius: radius.mobile, padding: 3 },
  segment: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: space.sm + 2,
    borderRadius: radius.mobile - 2,
    borderWidth: 1,
    borderColor: 'transparent',
  },
});
