<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PushPlatform;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PushDeviceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A phone that receives push notifications through the Expo push service.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property int $personal_access_token_id
 * @property string $token
 * @property PushPlatform $platform
 * @property string $locale
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class PushDevice extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PushDeviceFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['token', 'platform', 'locale'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['platform' => PushPlatform::class];
    }
}
