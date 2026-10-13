<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin Attachment
 */
final class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ttl = config()->integer('ticko.attachment_url_ttl_minutes');

        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            // Short-lived signed link; the file itself lives on a private disk.
            'url' => URL::temporarySignedRoute(
                'api.v1.attachments.content',
                now()->addMinutes($ttl),
                ['attachment' => $this->id, 'tenant' => $this->tenant_id],
            ),
            'url_expires_in' => $ttl * 60,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
