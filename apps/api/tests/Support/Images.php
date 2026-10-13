<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Builds real image files for upload tests.
 */
final class Images
{
    public static function png(int $width = 20, int $height = 10, string $appended = ''): UploadedFile
    {
        return self::fromGd('png', $width, $height, $appended);
    }

    public static function webp(int $width = 20, int $height = 10): UploadedFile
    {
        return self::fromGd('webp', $width, $height);
    }

    /**
     * A JPEG carrying an EXIF (APP1) segment with a fake GPS marker.
     */
    public static function jpegWithExif(): UploadedFile
    {
        $jpeg = self::render('jpeg', 32, 16);

        $exif = "Exif\0\0".'II*'."\0".pack('V', 8)."\0\0".'GPSLatitude=-23.5505;GPSLongitude=-46.6333';
        $segment = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        // Insert APP1 right after the SOI marker.
        return self::file('photo.jpg', substr($jpeg, 0, 2).$segment.substr($jpeg, 2));
    }

    public static function file(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ticko-test-');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    private static function fromGd(string $type, int $width, int $height, string $appended = ''): UploadedFile
    {
        return self::file('image.'.$type, self::render($type, $width, $height).$appended);
    }

    private static function render(string $type, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 200, 30, 30));

        ob_start();
        match ($type) {
            'png' => imagepng($image),
            'webp' => imagewebp($image),
            default => imagejpeg($image),
        };

        return (string) ob_get_clean();
    }
}
