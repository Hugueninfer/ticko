<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Support\Str;

final readonly class SaveCategory
{
    /**
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Category $category, array $data): Category
    {
        $category->fill($data);

        if (isset($data['name'])) {
            $category->slug = Str::slug($data['name']);
        }

        $category->save();

        return $category;
    }
}
