<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class DemoTokenRequest extends FormRequest
{
    /**
     * Public endpoint, only reachable when demo sign-in is enabled (rate limited).
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
        return ['device_name' => ['required', 'string', 'max:100']];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return ['device_name' => ['description' => 'Label for the token, e.g. the phone model.', 'example' => 'Pixel 9']];
    }
}
