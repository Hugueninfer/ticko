<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleVisibility;
use App\Models\KbArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KbArticle>
 */
class KbArticleFactory extends Factory
{
    /**
     * @return array<model-property<KbArticle>, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => "## Introdução\n\n".fake()->paragraph()."\n\n## Detalhes\n\n".fake()->paragraph(),
            'visibility' => ArticleVisibility::Customers,
            'tags' => ['entrega'],
            'author_id' => User::factory(),
            'updated_by_id' => fn (array $attributes) => $attributes['author_id'],
        ];
    }
}
