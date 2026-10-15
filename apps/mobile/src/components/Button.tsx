import { ActivityIndicator, Pressable, StyleSheet, View, type PressableProps } from 'react-native';
import type { ReactNode } from 'react';

import { radius, space, useTheme } from '@/theme';

import { AppText } from './AppText';

type Variant = 'primary' | 'outline' | 'ghost' | 'dark';

export interface ButtonProps extends Omit<PressableProps, 'children'> {
  label: string;
  variant?: Variant;
  loading?: boolean;
  icon?: ReactNode;
}

export function Button({ label, variant = 'primary', loading = false, disabled, icon, style, ...props }: ButtonProps) {
  const { colors } = useTheme();
  const inactive = disabled === true || loading;

  const palette = {
    primary: { background: colors.primary, border: colors.primary, text: colors.primaryForeground },
    outline: { background: colors.background, border: colors.border, text: colors.foreground },
    ghost: { background: 'transparent', border: 'transparent', text: colors.primary },
    dark: { background: colors.foreground, border: colors.foreground, text: colors.background },
  }[variant];

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: inactive, busy: loading }}
      disabled={inactive}
      {...props}
      style={(state) => [
        styles.base,
        { backgroundColor: palette.background, borderColor: palette.border, opacity: inactive ? 0.6 : state.pressed ? 0.85 : 1 },
        typeof style === 'function' ? style(state) : style,
      ]}
    >
      <View style={styles.content}>
        {loading ? <ActivityIndicator color={palette.text} size="small" /> : icon}
        <AppText variant="label" weight="semibold" style={{ color: palette.text, fontSize: 16 }}>
          {label}
        </AppText>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 48,
    borderRadius: radius.mobile,
    borderWidth: 1,
    paddingHorizontal: space.lg,
    justifyContent: 'center',
  },
  content: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: space.sm },
});
