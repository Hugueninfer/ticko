<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTeamMemberRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('member')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'role' => ['sometimes', Rule::enum(Role::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name?: string, role?: string, is_active?: bool}
     */
    public function payload(): array
    {
        /** @var array{name?: string, role?: string, is_active?: bool} */
        return $this->validated();
    }

    /**
     * @return array<string, array<string, string|bool>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Full name.', 'example' => 'Ana Souza'],
            'role' => ['description' => 'customer, agent or admin.', 'example' => 'admin'],
            'is_active' => ['description' => 'Deactivated users cannot sign in; their tokens are revoked.', 'example' => true],
        ];
    }
}
