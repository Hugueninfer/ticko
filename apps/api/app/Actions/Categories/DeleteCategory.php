<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCategory
{
    public function handle(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            $category->tickets()->update(['category_id' => null]);
            $category->delete();
        });
    }
}
