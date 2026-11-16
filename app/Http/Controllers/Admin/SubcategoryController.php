<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Admin\Subcategory\UpdateSubcategoryRequest;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\SubcategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubcategoryController extends Controller
{
    public function __construct(private readonly SubcategoryService $subcategories) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Subcategory::class);

        return Inertia::render('admin/subcategories/Index', [
            // Parent options for the create/edit forms and the public URL preview.
            'categories' => Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'subcategories' => Subcategory::query()
                ->with('category:id,name,slug')
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreSubcategoryRequest $request): RedirectResponse
    {
        $this->subcategories->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subcategory created.']);

        return to_route('admin.subcategories.index');
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): RedirectResponse
    {
        $this->subcategories->update($subcategory, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subcategory updated.']);

        return to_route('admin.subcategories.index');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('delete', $subcategory);

        $this->subcategories->delete($subcategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Subcategory deleted.']);

        return to_route('admin.subcategories.index');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:subcategories,id'],
            'action' => ['required', Rule::in(['delete', 'activate', 'deactivate'])],
        ]);

        $subcategories = Subcategory::query()->whereKey($data['ids'])->get();
        if ($subcategories->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'One or more selected subcategories are unavailable.']);
        }

        foreach ($subcategories as $subcategory) {
            $this->authorize($data['action'] === 'delete' ? 'delete' : 'update', $subcategory);
        }

        if ($data['action'] === 'delete') {
            if ($subcategories->contains(fn (Subcategory $subcategory) => $subcategory->products()->exists())) {
                throw ValidationException::withMessages([
                    'ids' => 'Subcategories with assigned products cannot be deleted.',
                ]);
            }

            $subcategories->each(fn (Subcategory $subcategory) => $this->subcategories->delete($subcategory));
        } else {
            $subcategories->each(fn (Subcategory $subcategory) => $subcategory->update([
                'is_active' => $data['action'] === 'activate',
            ]));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($subcategories).' subcategories '.($data['action'] === 'delete' ? 'deleted.' : 'updated.'),
        ]);

        return to_route('admin.subcategories.index');
    }
}
