<?php

declare(strict_types=1);

namespace App\Http\Requests\Knowledge;

use App\Enums\ArticleStatus;
use App\Enums\IndexStatus;
use App\Models\KbArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', KbArticle::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', Rule::enum(ArticleStatus::class)],
            'index_status' => ['sometimes', Rule::enum(IndexStatus::class)],
            'category_id' => ['sometimes', 'ulid'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'q' => ['description' => 'Search titles.', 'example' => 'entrega'],
            'status' => ['description' => 'draft, published or archived.', 'example' => 'published'],
            'index_status' => ['description' => 'none, queued, indexing, indexed or failed.', 'example' => ''],
            'category_id' => ['description' => 'Category ULID.', 'example' => ''],
            'per_page' => ['description' => 'Page size (1-100).', 'example' => '25'],
            'page' => ['description' => 'Page number.', 'example' => '1'],
        ];
    }
}
