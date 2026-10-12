<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\Role;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreTeamMemberRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:254',
                Rule::unique(User::class, 'email')->where('tenant_id', app(TenantContext::class)->id()),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    /**
     * @return array{name: string, email: string, password: string, role: string}
     */
    public function payload(): array
    {
        /** @var array{name: string, email: string, password: string, role: string} */
        return $this->validated();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Full name.', 'example' => 'Ana Souza'],
            'email' => ['description' => 'E-mail, unique within the tenant.', 'example' => 'ana@acme.test'],
            'password' => ['description' => 'Initial password (min 12 chars, not found in known breaches).', 'example' => 'a-long-unique-passphrase'],
            'role' => ['description' => 'customer, agent or admin.', 'example' => 'agent'],
        ];
    }
}
