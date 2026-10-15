import { useTranslation } from 'react-i18next';
import { StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import { space, useTheme } from '@/theme';

/** Shown instead of the app when EXPO_PUBLIC_* settings are missing or unsafe. */
export function ConfigErrorScreen({ message }: { message: string }) {
  const { t } = useTranslation();
  const { colors } = useTheme();

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]} accessibilityRole="alert">
      <AppText variant="heading" color="danger">
        {t('config.title')}
      </AppText>
      <AppText>{message}</AppText>
      <AppText variant="small" color="mutedForeground">
        {t('config.hint')}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, justifyContent: 'center', padding: space.xl, gap: space.md },
});
