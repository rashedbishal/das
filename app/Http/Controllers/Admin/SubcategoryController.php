<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Admin\Subcategory\UpdateSubcategoryRequest;
use App\Models\Subcategory;
use App\Services\SubcategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SubcategoryController extends Controller
{
    public function __construct(private readonly SubcategoryService $subcategories) {}

    public function store(StoreSubcategoryRequest $request): RedirectResponse
    {
        $this->subcategories->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subcategory created.']);

        return to_route('admin.categories.index');
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): RedirectResponse
    {
        $this->subcategories->update($subcategory, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subcategory updated.']);

        return to_route('admin.categories.index');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('delete', $subcategory);

        $this->subcategories->delete($subcategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subcategory deleted.']);

        return to_route('admin.categories.index');
    }
}
