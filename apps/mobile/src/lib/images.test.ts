import * as ImagePicker from 'expo-image-picker';
import { ImageManipulator } from 'expo-image-manipulator';

import { pickPhoto, prepareForUpload } from './images';

const context = jest.requireMock<{ __context: { resize: jest.Mock } }>('expo-image-manipulator').__context;

function asset(width: number, height: number): ImagePicker.ImagePickerAsset {
  return { uri: 'file:///raw.heic', width, height };
}

describe('photos', () => {
  it('re-encodes photos as JPEG and shrinks large ones', async () => {
    await expect(prepareForUpload(asset(4000, 3000), 2)).resolves.toMatchObject({
      uri: 'file:///clean.jpg',
      mimeType: 'image/jpeg',
    });
    expect(ImageManipulator.manipulate).toHaveBeenCalledWith('file:///raw.heic');
    expect(context.resize).toHaveBeenCalledWith({ width: 2048 });

    await prepareForUpload(asset(1000, 3000));
    expect(context.resize).toHaveBeenLastCalledWith({ height: 2048 });

    context.resize.mockClear();
    await prepareForUpload(asset(800, 600));
    expect(context.resize).not.toHaveBeenCalled();
  });

  it('asks for camera permission and reports a refusal', async () => {
    jest.mocked(ImagePicker.requestCameraPermissionsAsync).mockResolvedValueOnce({ granted: false } as never);

    await expect(pickPhoto('camera')).resolves.toEqual({ kind: 'denied' });
    expect(ImagePicker.launchCameraAsync).not.toHaveBeenCalled();
  });

  it('returns a cleaned photo, or nothing when cancelled', async () => {
    jest.mocked(ImagePicker.launchImageLibraryAsync).mockResolvedValueOnce({ canceled: false, assets: [asset(100, 100)] });

    await expect(pickPhoto('gallery')).resolves.toMatchObject({ kind: 'photo', photo: { mimeType: 'image/jpeg' } });
    expect(ImagePicker.launchImageLibraryAsync).toHaveBeenCalledWith({ mediaTypes: ['images'], quality: 1, exif: false });
    expect(ImagePicker.requestCameraPermissionsAsync).not.toHaveBeenCalled();

    await expect(pickPhoto('camera')).resolves.toEqual({ kind: 'cancelled' });
  });
});
