import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';
import * as ImagePicker from 'expo-image-picker';

import type { PhotoUpload } from './api';

/** Longest side sent to the API: plenty for support, well under the upload limit. */
const MAX_SIDE = 2048;

export type PickResult = { kind: 'photo'; photo: PhotoUpload } | { kind: 'cancelled' } | { kind: 'denied' };

/**
 * Re-encodes the photo as a fresh JPEG: drops EXIF (GPS, device data)
 * before it leaves the phone and keeps the file small. The API strips
 * metadata again on its side.
 */
export async function prepareForUpload(asset: ImagePicker.ImagePickerAsset, index = 0): Promise<PhotoUpload> {
  const context = ImageManipulator.manipulate(asset.uri);

  if (Math.max(asset.width, asset.height) > MAX_SIDE) {
    context.resize(asset.width >= asset.height ? { width: MAX_SIDE } : { height: MAX_SIDE });
  }

  const image = await context.renderAsync();
  const saved = await image.saveAsync({ format: SaveFormat.JPEG, compress: 0.8 });

  return { uri: saved.uri, name: `photo-${Date.now()}-${index}.jpg`, mimeType: 'image/jpeg' };
}

export async function pickPhoto(source: 'camera' | 'gallery'): Promise<PickResult> {
  // The system photo picker needs no permission; the camera does.
  if (source === 'camera' && !(await ImagePicker.requestCameraPermissionsAsync()).granted) {
    return { kind: 'denied' };
  }

  const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 1, exif: false };
  const result =
    source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
  const asset = result.canceled ? undefined : result.assets[0];

  return asset ? { kind: 'photo', photo: await prepareForUpload(asset) } : { kind: 'cancelled' };
}
