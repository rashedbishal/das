<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->active()
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('catalog/CategoryIndex', [
            'categories' => $categories,
        ]);
    }

    public function show(Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $category->load(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name')]);

        return Inertia::render('catalog/CategoryShow', [
            'category' => $category,
            'subcategories' => $category->subcategories,
        ]);
    }
}
