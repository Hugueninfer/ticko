import { StyleSheet, TextInput, View, type TextInputProps } from 'react-native';

import { fonts, radius, space, useTheme } from '@/theme';

import { AppText } from './AppText';

export interface TextFieldProps extends TextInputProps {
  label: string;
  error?: string | undefined;
  hint?: string;
}

export function TextField({ label, error, hint, style, multiline, ...props }: TextFieldProps) {
  const { colors } = useTheme();

  return (
    <View style={styles.field}>
      <AppText variant="label" weight="semibold">
        {label}
      </AppText>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor={colors.mutedForeground}
        multiline={multiline}
        {...props}
        style={[
          styles.input,
          multiline ? styles.multiline : null,
          { color: colors.foreground, borderColor: error ? colors.danger : colors.border, backgroundColor: colors.background },
          style,
        ]}
      />
      {error ? (
        <AppText variant="small" color="danger" accessibilityRole="alert">
          {error}
        </AppText>
      ) : hint ? (
        <AppText variant="small" color="mutedForeground">
          {hint}
        </AppText>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  field: { gap: space.sm },
  input: {
    minHeight: 48,
    borderWidth: 1,
    borderRadius: radius.mobile,
    paddingHorizontal: space.md,
    fontFamily: fonts.regular,
    fontSize: 16,
  },
  multiline: { minHeight: 120, paddingTop: space.md, textAlignVertical: 'top' },
});
