<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

use App\Models\Category;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class SaveCategoryRequest extends FormRequest
{
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

        return [
            'name' => [$required, 'string', 'min:2', 'max:80', $this->uniqueSlug($ignoreId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array{name?: string, description?: string|null}
     */
    public function payload(): array
    {
        /** @var array{name?: string, description?: string|null} */
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
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Category name, unique per tenant.', 'example' => 'Hardware'],
            'description' => ['description' => 'Optional description.', 'example' => 'Printers, laptops and peripherals'],
        ];
    }
}
