<?php

declare(strict_types=1);

namespace App\Services;

use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Turns an untrusted upload into a clean image:
 *
 *  - the type is detected from the file contents (magic bytes), never from
 *    the client-supplied name or Content-Type;
 *  - dimensions are checked before decoding, to refuse decompression bombs;
 *  - the pixels are decoded and re-encoded, which drops EXIF/XMP/ICC metadata
 *    (GPS position, device data) and any payload appended to the file.
 */
final class ImageSanitizer
{
    /** @var array<string, string> */
    public const array ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public const int MAX_PIXELS = 40_000_000;

    public const int MAX_SIDE = 10_000;

    public function __construct(private readonly ImageManager $images) {}

    public function sanitize(UploadedFile $file, string $field = 'file'): SanitizedImage
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            throw $this->invalid($field, 'The file could not be read.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (! is_string($mimeType) || ! array_key_exists($mimeType, self::ALLOWED_TYPES)) {
            throw $this->invalid($field, 'The file must be a JPEG, PNG or WebP image.');
        }

        $info = @getimagesize($path);

        if ($info === false || $info['mime'] !== $mimeType) {
            throw $this->invalid($field, 'The file is not a valid image.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_SIDE || $height > self::MAX_SIDE
            || $width * $height > self::MAX_PIXELS) {
            throw $this->invalid($field, 'The image dimensions are not allowed.');
        }

        try {
            $image = $this->images->decodePath($path)->orient();

            $encoded = $image->encode(match ($mimeType) {
                'image/jpeg' => new JpegEncoder(quality: 85, strip: true),
                'image/png' => new PngEncoder,
                default => new WebpEncoder(quality: 85, strip: true),
            });
        } catch (Throwable) {
            throw $this->invalid($field, 'The file is not a valid image.');
        }

        return new SanitizedImage(
            contents: $encoded->toString(),
            mimeType: $mimeType,
            extension: self::ALLOWED_TYPES[$mimeType],
            width: $image->width(),
            height: $image->height(),
        );
    }

    private function invalid(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([$field => $message]);
    }
}
