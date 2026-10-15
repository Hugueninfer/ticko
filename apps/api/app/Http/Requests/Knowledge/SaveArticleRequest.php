<?php

declare(strict_types=1);

namespace App\Http\Requests\Knowledge;

use App\Enums\ArticleVisibility;
use App\Models\Category;
use App\Models\KbArticle;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveArticleRequest extends FormRequest
{
    public const int MAX_BODY = 100_000;

    /**
     * Authorized before validation, so validation errors never leak data to callers without access.
     */
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof KbArticle
            ? ($this->user()?->can('update', $article) ?? false)
            : ($this->user()?->can('create', KbArticle::class) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'title' => [$required, 'string', 'min:3', 'max:200'],
            'body' => [$required, 'string', 'max:'.self::MAX_BODY],
            'visibility' => [$required, Rule::enum(ArticleVisibility::class)],
            'tags' => ['sometimes', 'array', 'max:10'],
            'tags.*' => ['string', 'min:1', 'max:40'],
            'category_id' => [
                'sometimes', 'nullable', 'ulid',
                Rule::exists(Category::class, 'id')->where('tenant_id', app(TenantContext::class)->id()),
            ],
        ];
    }

    /**
     * @return array{title?: string, body?: string, visibility?: string, tags?: list<string>, category_id?: string|null}
     */
    public function payload(): array
    {
        /** @var array{title?: string, body?: string, visibility?: string, tags?: list<string>, category_id?: string|null} $data */
        $data = $this->validated();

        if (isset($data['tags'])) {
            $data['tags'] = array_values(array_unique(array_map('trim', $data['tags'])));
        }

        return $data;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Article title.', 'example' => 'Política de entregas e prazos'],
            'body' => ['description' => 'Markdown. Only the published version is indexed and cited.', 'example' => "## Prazos\n\nSul e Sudeste: 3 a 5 dias úteis."],
            'visibility' => ['description' => 'customers (staff and customers) or internal (staff only, never used in replies).', 'example' => 'customers'],
            'tags' => ['description' => 'Up to 10 tags.', 'example' => ['rastreio', 'prazo']],
            'category_id' => ['description' => 'Category ULID.', 'example' => null],
        ];
    }
}
