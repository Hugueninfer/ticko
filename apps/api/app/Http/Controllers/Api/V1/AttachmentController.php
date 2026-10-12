<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\StoreTicketAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Attachments', 'Images attached to tickets. Files are stored privately and served through short-lived signed URLs.')]
final class AttachmentController extends Controller
{
    /**
     * List attachments of a ticket
     */
    #[ResponseFromApiResource(AttachmentResource::class, Attachment::class, collection: true)]
    public function index(Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('view', $ticket);

        return AttachmentResource::collection($ticket->attachments()->latest()->get());
    }

    /**
     * Upload an image
     *
     * The image type is detected from its content; the image is re-encoded,
     * which removes EXIF metadata such as GPS position.
     */
    #[ResponseFromApiResource(AttachmentResource::class, Attachment::class, 201)]
    public function store(StoreAttachmentRequest $request, Ticket $ticket, StoreTicketAttachment $store): AttachmentResource
    {
        $this->authorize('reply', $ticket);

        return AttachmentResource::make($store->handle($this->currentUser($request), $ticket, $request->uploadedImage()));
    }

    /**
     * Show an attachment
     *
     * Returns metadata with a fresh signed download URL.
     */
    #[ResponseFromApiResource(AttachmentResource::class, Attachment::class)]
    public function show(Attachment $attachment): AttachmentResource
    {
        $this->authorize('view', $attachment);

        return AttachmentResource::make($attachment);
    }

    /**
     * Download attachment content
     *
     * Only reachable through the signed `url` returned by the other attachment
     * endpoints; the signature carries the authorization and expires quickly.
     */
    #[Unauthenticated]
    public function content(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=300',
        ], 'inline');
    }
}
