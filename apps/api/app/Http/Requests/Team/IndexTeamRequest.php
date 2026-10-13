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
        return [
            'role' => ['sometimes', Rule::enum(Role::class)],
            'q' => ['sometimes', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * LIKE pattern for the name/e-mail search, with wildcards in the input escaped.
     */
    public function search(): ?string
    {
        return $this->filled('q') ? '%'.addcslashes($this->string('q')->toString(), '%_\\').'%' : null;
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
        return [
            'role' => ['description' => 'Filter by role: customer, agent or admin.', 'example' => 'agent'],
            'q' => ['description' => 'Search name and e-mail.', 'example' => 'ana'],
            'page' => ['description' => 'Page number.', 'example' => '1'],
        ];
    }
}
