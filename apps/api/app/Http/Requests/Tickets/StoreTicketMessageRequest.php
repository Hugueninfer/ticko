<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTicketMessageRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can($this->boolean('is_internal') ? 'addInternalNote' : 'reply', $this->route('ticket')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'is_internal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, string|bool>>
     */
    public function bodyParameters(): array
    {
        return [
            'body' => ['description' => 'Message text.', 'example' => 'Could you try restarting it?'],
            'is_internal' => ['description' => 'Internal note visible to staff only.', 'example' => false],
        ];
    }
}
