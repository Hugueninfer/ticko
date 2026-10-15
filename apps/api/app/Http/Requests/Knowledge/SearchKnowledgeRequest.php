<?php

declare(strict_types=1);

namespace App\Http\Requests\Knowledge;

use App\Models\KbArticle;
use App\Services\Knowledge\SearchOptions;
use Illuminate\Foundation\Http\FormRequest;

final class SearchKnowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', KbArticle::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:500'],
            'text_weight' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'top_k' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'rerank' => ['sometimes', 'boolean'],
            'category_id' => ['sometimes', 'nullable', 'ulid'],
            'customer_visible_only' => ['sometimes', 'boolean'],
        ];
    }

    public function options(): SearchOptions
    {
        $category = $this->input('category_id');

        return new SearchOptions(
            textWeight: $this->has('text_weight') ? $this->float('text_weight') : config()->float('ticko.ai.text_weight'),
            topK: $this->has('top_k') ? $this->integer('top_k') : config()->integer('ticko.ai.top_k'),
            rerank: $this->boolean('rerank'),
            categoryId: is_string($category) && $category !== '' ? $category : null,
            customerVisibleOnly: $this->boolean('customer_visible_only'),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'query' => ['description' => 'The question, as a customer would ask it.', 'example' => 'o rastreio do meu pedido parou, o que faço?'],
            'text_weight' => ['description' => 'Weight of the full-text score, 0 to 1 (the rest is semantic).', 'example' => 0.4],
            'top_k' => ['description' => 'Number of passages (1-10).', 'example' => 5],
            'rerank' => ['description' => 'Combine both rankings with reciprocal rank fusion.', 'example' => false],
            'category_id' => ['description' => 'Only articles of this category.', 'example' => null],
            'customer_visible_only' => ['description' => 'Only articles the AI may use in replies.', 'example' => false],
        ];
    }
}
