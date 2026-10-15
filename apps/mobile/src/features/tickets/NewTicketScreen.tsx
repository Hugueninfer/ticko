import { zodResolver } from '@hookform/resolvers/zod';
import { router } from 'expo-router';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { z } from 'zod';

import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Chip } from '@/components/Chip';
import { OfflineBanner } from '@/components/States';
import { TextField } from '@/components/TextField';
import type { PhotoUpload } from '@/lib/api';
import { pickPhoto } from '@/lib/images';
import { useOnline } from '@/lib/network';
import { space, useTheme } from '@/theme';

import { useCategories, useCreateTicket } from './api';
import { PhotoStrip } from './PhotoStrip';

export const MAX_PHOTOS = 3;

const schema = z.object({
  description: z.string().trim().min(10, 'short').max(20000, 'long'),
  categoryId: z.string().nullable(),
});

type FormValues = z.infer<typeof schema>;

export function NewTicketScreen() {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const online = useOnline();
  const categories = useCategories();
  const create = useCreateTicket();
  const [photos, setPhotos] = useState<PhotoUpload[]>([]);
  const [error, setError] = useState<string | null>(null);

  const form = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { description: '', categoryId: null } });

  const addPhoto = async (source: 'camera' | 'gallery') => {
    if (photos.length >= MAX_PHOTOS) {
      setError(t('newTicket.tooManyPhotos', { max: MAX_PHOTOS }));

      return;
    }

    try {
      const result = await pickPhoto(source);

      if (result.kind === 'photo') {
        setPhotos((current) => [...current, result.photo].slice(0, MAX_PHOTOS));
      } else if (result.kind === 'denied') {
        setError(t('newTicket.permissionDenied'));
      }
    } catch {
      setError(t('common.genericError'));
    }
  };

  const submit = form.handleSubmit(async (values) => {
    setError(null);

    try {
      const { ticket, failedPhotos } = await create.mutateAsync({ ...values, photos });

      if (failedPhotos > 0) {
        Alert.alert(t('newTicket.photoFailed'));
      }

      router.replace(`/tickets/${ticket.id}`);
    } catch {
      setError(t('common.genericError'));
    }
  });

  return (
    <SafeAreaView style={[styles.safe, { backgroundColor: colors.background }]}>
      <OfflineBanner />
      <View style={styles.header}>
        <Pressable accessibilityRole="button" onPress={() => router.back()} hitSlop={8}>
          <AppText color="primary">{t('common.cancel')}</AppText>
        </Pressable>
        <AppText variant="body" weight="semibold" accessibilityRole="header">
          {t('newTicket.title')}
        </AppText>
        <View style={styles.spacer} />
      </View>
      <KeyboardAvoidingView style={styles.safe} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <Controller
            control={form.control}
            name="description"
            render={({ field, fieldState }) => (
              <TextField
                label={t('newTicket.description')}
                hint={t('newTicket.descriptionHint')}
                multiline
                value={field.value}
                onChangeText={field.onChange}
                onBlur={field.onBlur}
                maxLength={20000}
                error={
                  fieldState.error
                    ? t(
                        fieldState.error.message === 'long'
                          ? 'newTicket.validation.descriptionLong'
                          : 'newTicket.validation.descriptionShort',
                      )
                    : undefined
                }
              />
            )}
          />

          <View style={styles.section}>
            <AppText variant="label" weight="semibold">
              {t('newTicket.photos')}{' '}
              <AppText variant="label" color="mutedForeground">
                {t('common.optional')}
              </AppText>
            </AppText>
            <PhotoStrip
              photos={photos}
              max={MAX_PHOTOS}
              onAdd={(source) => void addPhoto(source)}
              onRemove={(index) => setPhotos((current) => current.filter((_, i) => i !== index))}
            />
          </View>

          {categories.data && categories.data.length > 0 ? (
            <View style={styles.section}>
              <AppText variant="label" weight="semibold">
                {t('newTicket.category')}{' '}
                <AppText variant="label" color="mutedForeground">
                  {t('common.optional')}
                </AppText>
              </AppText>
              <Controller
                control={form.control}
                name="categoryId"
                render={({ field }) => (
                  <View style={styles.chips} accessibilityRole="radiogroup">
                    {categories.data.map((category) => (
                      <Chip
                        key={category.id}
                        label={category.name}
                        selected={field.value === category.id}
                        onPress={() => field.onChange(field.value === category.id ? null : category.id)}
                      />
                    ))}
                  </View>
                )}
              />
            </View>
          ) : null}

          {(error ?? (!online ? t('offline.cannotSend') : null)) ? (
            <AppText variant="small" color="danger" accessibilityRole="alert">
              {error ?? t('offline.cannotSend')}
            </AppText>
          ) : null}

          <Button
            label={create.isPending ? t('newTicket.submitting') : t('newTicket.submit')}
            loading={create.isPending}
            disabled={!online}
            onPress={() => void submit()}
          />
          <AppText variant="small" color="mutedForeground" style={styles.center}>
            {t('newTicket.responseTime')}
          </AppText>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: space.xl,
    paddingVertical: space.md,
  },
  spacer: { width: 60 },
  content: { padding: space.xl, gap: space.xl },
  section: { gap: space.md },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  center: { textAlign: 'center' },
});
