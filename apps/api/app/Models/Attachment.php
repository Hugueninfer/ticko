<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $ticket_id
 * @property string $uploader_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int $width
 * @property int $height
 * @property string $checksum
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Ticket $ticket
 * @property-read User $uploader
 */
class Attachment extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * Storage details are set by the upload action, never from request input.
     *
     * @var list<string>
     */
    protected $fillable = ['original_name'];

    /** @var list<string> */
    protected $hidden = ['disk', 'path'];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }
}
