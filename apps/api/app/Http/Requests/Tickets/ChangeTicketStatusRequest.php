<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeTicketStatusRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('ticket')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(TicketStatus::class)]];
    }

    public function status(): TicketStatus
    {
        return TicketStatus::from($this->string('status')->toString());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return ['status' => ['description' => 'open, pending, resolved or closed. Customers may only use open or closed.', 'example' => 'resolved']];
    }
}
