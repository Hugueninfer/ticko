<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * Users only ever change their own preferences.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'push_replies' => ['required', 'boolean'],
            'push_status_changes' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'push_replies' => ['description' => 'Push when the team replies to one of your tickets.', 'example' => true],
            'push_status_changes' => ['description' => 'Push when the status of one of your tickets changes.', 'example' => false],
        ];
    }
}
