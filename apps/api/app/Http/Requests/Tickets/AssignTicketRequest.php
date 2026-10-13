<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use App\Enums\Role;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignTicketRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('assign', $this->route('ticket')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => [
                'present', 'nullable', 'ulid',
                Rule::exists(User::class, 'id')
                    ->where('tenant_id', app(TenantContext::class)->id())
                    ->where('is_active', true)
                    ->whereIn('role', [Role::Agent->value, Role::Admin->value]),
            ],
        ];
    }

    public function assignee(): ?User
    {
        $id = $this->input('assignee_id');

        return is_string($id) ? User::query()->findOrFail($id) : null;
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function bodyParameters(): array
    {
        return ['assignee_id' => ['description' => 'ULID of an active agent/admin, or null to unassign.', 'example' => null]];
    }
}
