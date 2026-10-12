<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexTeamRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['role' => ['sometimes', Rule::enum(Role::class)]];
    }

    public function role(): ?Role
    {
        return $this->filled('role') ? Role::from($this->string('role')->toString()) : null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return ['role' => ['description' => 'Filter by role: customer, agent or admin.', 'example' => 'agent']];
    }
}
