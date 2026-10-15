<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return response()->json([
            'data' => Category::query()->with('parent')->withCount(['children', 'products'])->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->categories->create($request->validated())], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        return response()->json(['data' => $this->categories->update($category, $request->validated())]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        return response()->json(status: 204);
    }
}
