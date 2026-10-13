<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class SaveCategoryRequest extends FormRequest
{
    /** 30 days. */
    private const int MAX_SLA_MINUTES = 43_200;

    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            ? ($this->user()?->can('update', $category) ?? false)
            : ($this->user()?->can('create', Category::class) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $category = $this->route('category');
        $ignoreId = $category instanceof Category ? $category->id : null;

        $rules = [
            'name' => [$required, 'string', 'min:2', 'max:80', $this->uniqueSlug($ignoreId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            // Overrides of the default SLA, per priority; omitted priorities keep the default.
            'sla_targets' => ['sometimes', 'nullable', 'array:'.implode(',', array_column(TicketPriority::cases(), 'value'))],
        ];

        foreach (TicketPriority::cases() as $priority) {
            $key = "sla_targets.{$priority->value}";
            $rules[$key] = ['sometimes', 'array:first_response_minutes,resolution_minutes'];
            $rules["{$key}.first_response_minutes"] = ["required_with:{$key}", 'integer', 'min:1', 'max:'.self::MAX_SLA_MINUTES];
            $rules["{$key}.resolution_minutes"] = ["required_with:{$key}", 'integer', "gte:{$key}.first_response_minutes", 'max:'.self::MAX_SLA_MINUTES];
        }

        return $rules;
    }

    /**
     * @return array{name?: string, description?: string|null, sla_targets?: array<string, array{first_response_minutes: int, resolution_minutes: int}>|null}
     */
    public function payload(): array
    {
        /** @var array{name?: string, description?: string|null, sla_targets?: array<string, array{first_response_minutes: int, resolution_minutes: int}>|null} */
        return $this->validated();
    }

    private function uniqueSlug(?string $ignoreId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreId): void {
            $exists = Category::query()
                ->where('tenant_id', app(TenantContext::class)->id())
                ->where('slug', Str::slug(is_string($value) ? $value : ''))
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists();

            if ($exists) {
                $fail('A category with this name already exists.');
            }
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Category name, unique per tenant.', 'example' => 'Hardware'],
            'description' => ['description' => 'Optional description.', 'example' => 'Printers, laptops and peripherals'],
            'sla_targets' => ['description' => 'SLA overrides keyed by priority (urgent, high, normal, low), each with first_response_minutes and resolution_minutes. Null resets to the defaults.', 'example' => ['urgent' => ['first_response_minutes' => 15, 'resolution_minutes' => 240]]],
        ];
    }
}
