<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTicketRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Ticket::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:3', 'max:200'],
            'description' => ['required', 'string', 'max:20000'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'category_id' => [
                'nullable', 'ulid',
                Rule::exists(Category::class, 'id')->where('tenant_id', app(TenantContext::class)->id()),
            ],
        ];
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function bodyParameters(): array
    {
        return [
            'subject' => ['description' => 'Short summary.', 'example' => 'Printer on 3rd floor is jammed'],
            'description' => ['description' => 'Full description of the problem.', 'example' => 'It shows error E-42 since this morning.'],
            'priority' => ['description' => 'low, normal, high or urgent.', 'example' => 'normal'],
            'category_id' => ['description' => 'Category ULID of the same tenant.', 'example' => null],
        ];
    }
}
