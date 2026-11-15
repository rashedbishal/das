<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->published()])
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => CategoryResource::collection($categories)->resolve()]);
    }

    public function show(Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $category->loadCount(['products' => fn ($query) => $query->published()]);
        $category->load(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')]);

        return response()->json(['data' => (new CategoryResource($category))->resolve()]);
    }
}
