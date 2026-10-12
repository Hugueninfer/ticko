<?php

declare(strict_types=1);

use App\Services\ImageSanitizer;
use Illuminate\Validation\ValidationException;
use Tests\Support\Images;

it('re-encodes images and reports their real properties', function (): void {
    $image = app(ImageSanitizer::class)->sanitize(Images::png(7, 3));

    expect($image->mimeType)->toBe('image/png')
        ->and($image->extension)->toBe('png')
        ->and([$image->width, $image->height])->toBe([7, 3])
        ->and($image->size())->toBe(strlen($image->contents))
        ->and($image->checksum())->toBe(hash('sha256', $image->contents));
});

it('refuses unreadable uploads', function (): void {
    $file = Images::png();
    unlink($file->getPathname());

    expect(fn () => app(ImageSanitizer::class)->sanitize($file))->toThrow(ValidationException::class);
});

it('reports errors on the given field', function (): void {
    try {
        app(ImageSanitizer::class)->sanitize(Images::file('x.png', 'nope'), 'avatar');
        $this->fail('Expected a validation exception.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('avatar');
    }
});
