import { zodResolver } from '@hookform/resolvers/zod';
import { useQuery } from '@tanstack/react-query';
import { ApiError } from '@ticko/api-client';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { z } from 'zod';

import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { OfflineBanner } from '@/components/States';
import { TextField } from '@/components/TextField';
import { api } from '@/lib/api';
import { queryKeys } from '@/lib/query';
import { radius, space, useTheme } from '@/theme';

import { useAuth } from './AuthProvider';

const schema = z.object({
  email: z.string().trim().max(254).pipe(z.email()),
  password: z.string().min(1).max(255),
});

type FormValues = z.infer<typeof schema>;

/** Demo sign-in exists only where the API enables it (dev/demo, never production). */
function useDemoAvailable(): boolean {
  const demo = useQuery({
    queryKey: queryKeys.demo,
    queryFn: async () => (await api.GET('/api/v1/auth/demo')).response.ok,
    retry: false,
    staleTime: Infinity,
  });

  return demo.data === true;
}

export function SignInScreen() {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const { state, signIn, signInDemo } = useAuth();
  const demoAvailable = useDemoAvailable();
  const [error, setError] = useState<string | null>(null);
  const [demoLoading, setDemoLoading] = useState(false);
  const notice = state.status === 'signedOut' ? state.notice : undefined;

  const form = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { email: '', password: '' } });

  const describe = (cause: unknown): string => {
    if (cause instanceof ApiError && cause.status === 422) return t('auth.invalid');
    if (cause instanceof ApiError && cause.status === 429) return t('auth.tooMany');

    return t('common.genericError');
  };

  const submit = form.handleSubmit(async (values) => {
    setError(null);

    try {
      await signIn({ email: values.email.trim(), password: values.password });
    } catch (cause) {
      setError(describe(cause));
    }
  });

  const demo = async () => {
    setError(null);
    setDemoLoading(true);

    try {
      await signInDemo();
    } catch (cause) {
      setError(describe(cause));
    } finally {
      setDemoLoading(false);
    }
  };

  const message = error ?? (notice ? t(notice === 'staff' ? 'auth.staffOnly' : 'auth.sessionExpired') : null);

  return (
    <SafeAreaView style={[styles.safe, { backgroundColor: colors.background }]}>
      <OfflineBanner />
      <KeyboardAvoidingView style={styles.safe} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={[styles.logo, { backgroundColor: colors.primary }]}>
            <AppText variant="heading" weight="bold" style={{ color: colors.primaryForeground }}>
              T
            </AppText>
          </View>
          <View style={styles.header}>
            <AppText variant="title" accessibilityRole="header">
              {t('auth.title')}
            </AppText>
            <AppText color="mutedForeground">{t('auth.subtitle')}</AppText>
          </View>

          <Controller
            control={form.control}
            name="email"
            render={({ field, fieldState }) => (
              <TextField
                label={t('auth.email')}
                placeholder={t('auth.emailPlaceholder')}
                autoCapitalize="none"
                autoComplete="email"
                keyboardType="email-address"
                textContentType="username"
                value={field.value}
                onChangeText={field.onChange}
                onBlur={field.onBlur}
                error={fieldState.error ? t('auth.validation.email') : undefined}
              />
            )}
          />
          <Controller
            control={form.control}
            name="password"
            render={({ field, fieldState }) => (
              <TextField
                label={t('auth.password')}
                secureTextEntry
                autoComplete="current-password"
                textContentType="password"
                value={field.value}
                onChangeText={field.onChange}
                onBlur={field.onBlur}
                onSubmitEditing={() => void submit()}
                error={fieldState.error ? t('auth.validation.password') : undefined}
              />
            )}
          />

          {message ? (
            <View style={[styles.alert, { backgroundColor: colors.dangerSoft }]} accessibilityRole="alert">
              <AppText variant="small" color="danger">
                {message}
              </AppText>
            </View>
          ) : null}

          <Button
            label={form.formState.isSubmitting ? t('auth.submitting') : t('auth.submit')}
            loading={form.formState.isSubmitting}
            onPress={() => void submit()}
          />

          {demoAvailable ? (
            <View style={styles.demo}>
              <Button label={t('auth.demo')} variant="outline" loading={demoLoading} onPress={() => void demo()} />
              <AppText variant="small" color="mutedForeground" style={styles.center}>
                {t('auth.demoHint')}
              </AppText>
            </View>
          ) : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1 },
  content: { padding: space.xl, gap: space.lg, flexGrow: 1, justifyContent: 'center' },
  logo: { width: 48, height: 48, borderRadius: radius.mobile, alignItems: 'center', justifyContent: 'center' },
  header: { gap: space.sm, marginBottom: space.sm },
  alert: { borderRadius: radius.base, padding: space.md },
  demo: { gap: space.sm, marginTop: space.xl },
  center: { textAlign: 'center' },
});
