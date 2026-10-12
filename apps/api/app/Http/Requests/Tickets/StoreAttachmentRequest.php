<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

final class StoreAttachmentRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('reply', $this->route('ticket')) ?? false;
    }

    /**
     * First-line checks; the content is verified again and re-encoded by ImageSanitizer.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.config()->integer('ticko.attachments_max_kb'), 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    public function uploadedImage(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return ['file' => ['description' => 'JPEG, PNG or WebP image (max 5 MB). Metadata is stripped.']];
    }
}
