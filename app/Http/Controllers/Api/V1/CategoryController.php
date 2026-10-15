<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function show(Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $category->load(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')]);

        return response()->json(['data' => $category]);
    }
}
