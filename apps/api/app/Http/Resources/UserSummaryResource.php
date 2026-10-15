<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal public view of a user, embedded in tickets and messages.
 *
 * @mixin User
 */
final class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            // Staff need the requester's e-mail; customers never see staff addresses.
            'email' => $request->user() instanceof User && $request->user()->isStaff() ? $this->email : null,
        ];
    }
}
