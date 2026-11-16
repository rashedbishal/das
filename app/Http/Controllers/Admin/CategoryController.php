<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                ->withCount(['subcategories', 'products'])
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

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'action' => ['required', Rule::in(['delete', 'activate', 'deactivate'])],
        ]);

        $categories = Category::query()->whereKey($data['ids'])->get();
        if ($categories->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'One or more selected categories are unavailable.']);
        }

        foreach ($categories as $category) {
            $this->authorize($data['action'] === 'delete' ? 'delete' : 'update', $category);
        }

        if ($data['action'] === 'delete') {
            if ($categories->contains(fn (Category $category) => $category->subcategories()->exists() || $category->products()->exists())) {
                throw ValidationException::withMessages([
                    'ids' => 'Categories with subcategories or assigned products cannot be deleted.',
                ]);
            }

            $categories->each(fn (Category $category) => $this->categories->delete($category));
        } else {
            $categories->each(fn (Category $category) => $category->update([
                'is_active' => $data['action'] === 'activate',
            ]));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($categories).' categories '.($data['action'] === 'delete' ? 'deleted.' : 'updated.'),
        ]);

        return to_route('admin.categories.index');
    }
}
