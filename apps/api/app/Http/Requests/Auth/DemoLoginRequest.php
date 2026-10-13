<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DemoLoginRequest extends FormRequest
{
    /**
     * Public endpoint, only reachable when demo sign-in is enabled (rate limited).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['role' => ['required', Rule::enum(Role::class)->only([Role::Agent, Role::Admin])]];
    }

    public function role(): Role
    {
        return Role::from($this->string('role')->toString());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return ['role' => ['description' => 'agent or admin.', 'example' => 'agent']];
    }
}
