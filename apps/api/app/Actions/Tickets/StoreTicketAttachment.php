<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ImageSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class StoreTicketAttachment
{
    public function __construct(private ImageSanitizer $sanitizer) {}

    public function handle(User $uploader, Ticket $ticket, UploadedFile $file): Attachment
    {
        $image = $this->sanitizer->sanitize($file);
        $disk = config()->string('ticko.attachments_disk');

        // Random, server-chosen name; the client file name is metadata only.
        $path = sprintf('tenants/%s/tickets/%s/%s.%s', $ticket->tenant_id, $ticket->id, Str::ulid(), $image->extension);

        if (! Storage::disk($disk)->put($path, $image->contents, ['visibility' => 'private'])) {
            throw new RuntimeException('Unable to store attachment.');
        }

        $attachment = new Attachment(['original_name' => $this->cleanName($file->getClientOriginalName(), $image->extension)]);
        $attachment->ticket_id = $ticket->id;
        $attachment->uploader_id = $uploader->id;
        $attachment->disk = $disk;
        $attachment->path = $path;
        $attachment->mime_type = $image->mimeType;
        $attachment->size = $image->size();
        $attachment->width = $image->width;
        $attachment->height = $image->height;
        $attachment->checksum = $image->checksum();
        $attachment->save();

        return $attachment;
    }

    private function cleanName(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = (string) preg_replace('/[^\pL\pN._ -]+/u', '', $base);
        $base = Str::limit(trim($base, " .\t\n\r\0\x0B"), 150, '');

        return ($base !== '' ? $base : 'image').'.'.$extension;
    }
}
