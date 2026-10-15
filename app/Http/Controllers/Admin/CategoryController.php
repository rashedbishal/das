<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        return Inertia::render('admin/categories/Index', [
            'categories' => Category::query()
                ->with('parent')
                ->withCount(['children', 'products'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created.']);

        return to_route('admin.categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return to_route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return to_route('admin.categories.index');
    }
}
