import { Image } from 'expo-image';
import { Camera, ImageIcon, X } from 'lucide-react-native';
import { useTranslation } from 'react-i18next';
import { Pressable, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import type { PhotoUpload } from '@/lib/api';
import { radius, space, useTheme } from '@/theme';

interface PhotoStripProps {
  photos: PhotoUpload[];
  max: number;
  onAdd: (source: 'camera' | 'gallery') => void;
  onRemove: (index: number) => void;
}

export function PhotoStrip({ photos, max, onAdd, onRemove }: PhotoStripProps) {
  const { t } = useTranslation();
  const { colors } = useTheme();
  const full = photos.length >= max;

  const tile = [styles.tile, { borderColor: colors.border }];

  return (
    <View style={styles.row}>
      {photos.map((photo, index) => (
        <View key={photo.uri} style={tile}>
          <Image source={{ uri: photo.uri }} style={styles.image} contentFit="cover" accessibilityIgnoresInvertColors />
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={t('newTicket.removePhoto')}
            onPress={() => onRemove(index)}
            style={[styles.remove, { backgroundColor: colors.foreground }]}
            hitSlop={8}
          >
            <X color={colors.background} size={14} />
          </Pressable>
        </View>
      ))}
      {full ? null : (
        <>
          <Pressable accessibilityRole="button" onPress={() => onAdd('camera')} style={[tile, styles.dashed]}>
            <Camera color={colors.mutedForeground} size={22} />
            <AppText variant="small" color="mutedForeground">
              {t('newTicket.camera')}
            </AppText>
          </Pressable>
          <Pressable accessibilityRole="button" onPress={() => onAdd('gallery')} style={[tile, styles.dashed]}>
            <ImageIcon color={colors.mutedForeground} size={22} />
            <AppText variant="small" color="mutedForeground">
              {t('newTicket.gallery')}
            </AppText>
          </Pressable>
        </>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  tile: {
    width: 80,
    height: 80,
    borderWidth: 1,
    borderRadius: radius.mobile,
    overflow: 'hidden',
    alignItems: 'center',
    justifyContent: 'center',
    gap: space.xs,
  },
  dashed: { borderStyle: 'dashed' },
  image: { width: '100%', height: '100%' },
  remove: { position: 'absolute', top: 4, right: 4, borderRadius: 10, padding: 3 },
});
