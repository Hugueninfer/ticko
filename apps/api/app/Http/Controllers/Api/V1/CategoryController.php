<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\SaveCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\SaveCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Categories', 'Everyone in the tenant can list categories; only admins manage them.')]
final class CategoryController extends Controller
{
    /**
     * List categories
     */
    #[ResponseFromApiResource(CategoryResource::class, Category::class, collection: true)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Category::class);

        return CategoryResource::collection(Category::query()->withCount('tickets')->orderBy('name')->get());
    }

    /**
     * Create a category
     */
    #[ResponseFromApiResource(CategoryResource::class, Category::class, 201)]
    public function store(SaveCategoryRequest $request, SaveCategory $save): CategoryResource
    {
        Gate::authorize('create', Category::class);

        return CategoryResource::make($save->handle(new Category, $request->payload()));
    }

    /**
     * Show a category
     */
    #[ResponseFromApiResource(CategoryResource::class, Category::class)]
    public function show(Category $category): CategoryResource
    {
        Gate::authorize('view', $category);

        return CategoryResource::make($category);
    }

    /**
     * Update a category
     */
    #[ResponseFromApiResource(CategoryResource::class, Category::class)]
    public function update(SaveCategoryRequest $request, Category $category, SaveCategory $save): CategoryResource
    {
        Gate::authorize('update', $category);

        return CategoryResource::make($save->handle($category, $request->payload()));
    }

    /**
     * Delete a category
     *
     * Tickets in the category are kept and become uncategorized.
     */
    public function destroy(Category $category, DeleteCategory $delete): Response
    {
        Gate::authorize('delete', $category);

        $delete->handle($category);

        return response()->noContent();
    }
}
