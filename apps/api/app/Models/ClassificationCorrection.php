<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An agent changing a field the AI had set: the ground truth for
 * measuring and retraining the classifier.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $ticket_id
 * @property string|null $classification_id
 * @property string $user_id
 * @property string $field
 * @property string|null $ai_value
 * @property string|null $corrected_value
 * @property Carbon $created_at
 */
class ClassificationCorrection extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['field', 'ai_value', 'corrected_value'];
}
