import { Pressable, StyleSheet } from 'react-native';

import { radius, space, useTheme } from '@/theme';

import { AppText } from './AppText';

export function Chip({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  const { colors } = useTheme();

  return (
    <Pressable
      accessibilityRole="radio"
      accessibilityState={{ checked: selected }}
      onPress={onPress}
      style={[
        styles.chip,
        {
          borderColor: selected ? colors.primary : colors.border,
          backgroundColor: selected ? colors.primarySoft : colors.background,
        },
      ]}
    >
      <AppText variant="label" style={{ color: selected ? colors.primary : colors.foreground }}>
        {label}
      </AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  chip: { borderWidth: 1, borderRadius: radius.pill, paddingHorizontal: space.lg, paddingVertical: space.sm + 2 },
});
