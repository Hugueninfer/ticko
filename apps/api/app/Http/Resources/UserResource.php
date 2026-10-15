<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Knuckles\Scribe\Attributes\ResponseField;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[ResponseField('role', 'string', enum: Role::class)]
    #[ResponseField('tenant', 'object', 'Sent by the sign-in endpoints and /auth/me.', required: false)]
    #[ResponseField('open_tickets_count', 'integer', 'Open tickets assigned to the member (team listing only).', required: false)]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'is_active' => $this->is_active,
            'tenant' => $this->whenLoaded('tenant', fn (): array => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ]),
            'notification_preferences' => [
                'push_replies' => $this->push_replies,
                'push_status_changes' => $this->push_status_changes,
            ],
            'open_tickets_count' => $this->whenCounted('open_tickets'),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
