<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Models\Category;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClassificationRequest extends FormRequest
{
    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('classify', $this->route('ticket')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes', 'nullable', 'ulid',
                Rule::exists(Category::class, 'id')->where('tenant_id', app(TenantContext::class)->id()),
            ],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'sentiment' => ['sometimes', 'nullable', Rule::enum(Sentiment::class)],
        ];
    }

    /**
     * @return array{category_id?: string|null, priority?: string, sentiment?: string|null}
     */
    public function changes(): array
    {
        /** @var array{category_id?: string|null, priority?: string, sentiment?: string|null} */
        return $this->validated();
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    public function bodyParameters(): array
    {
        return [
            'category_id' => ['description' => 'Category ULID (null to clear).', 'example' => null],
            'priority' => ['description' => 'low, normal, high or urgent.', 'example' => 'high'],
            'sentiment' => ['description' => 'negative, neutral or positive (null to clear).', 'example' => 'negative'],
        ];
    }
}
