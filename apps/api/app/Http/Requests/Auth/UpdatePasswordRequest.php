<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends FormRequest
{
    /**
     * Any authenticated user may change their own password.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'current_password' => ['description' => 'The current password.', 'example' => 'correct-horse-battery-staple'],
            'password' => ['description' => 'New password (min 12 chars, not found in known breaches).', 'example' => 'a-new-long-unique-passphrase'],
            'password_confirmation' => ['description' => 'Repeat the new password.', 'example' => 'a-new-long-unique-passphrase'],
        ];
    }
}
