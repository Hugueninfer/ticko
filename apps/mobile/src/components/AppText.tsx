import { Text, type TextProps } from 'react-native';

import { text, useTheme, type Colors } from '@/theme';

export interface AppTextProps extends TextProps {
  variant?: keyof typeof text;
  color?: keyof Colors;
  weight?: 'regular' | 'medium' | 'semibold' | 'bold';
}

const WEIGHTS = {
  regular: 'Geist_400Regular',
  medium: 'Geist_500Medium',
  semibold: 'Geist_600SemiBold',
  bold: 'Geist_700Bold',
} as const;

export function AppText({ variant = 'body', color = 'foreground', weight, style, ...props }: AppTextProps) {
  const { colors } = useTheme();

  return (
    <Text {...props} style={[text[variant], { color: colors[color] }, weight ? { fontFamily: WEIGHTS[weight] } : null, style]} />
  );
}
