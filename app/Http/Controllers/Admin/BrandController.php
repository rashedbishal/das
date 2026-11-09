<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\StoreBrandRequest;
use App\Http\Requests\Admin\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brands) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Brand::class);

        return Inertia::render('admin/brands/Index', [
            'brands' => Brand::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->brands->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand created.']);

        return to_route('admin.brands.index');
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brands->update($brand, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand updated.']);

        return to_route('admin.brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $this->brands->delete($brand);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand deleted.']);

        return to_route('admin.brands.index');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:brands,id'],
            'action' => ['required', Rule::in(['delete', 'activate', 'deactivate'])],
        ]);

        $brands = Brand::query()->whereKey($data['ids'])->get();
        if ($brands->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'One or more selected brands are unavailable.']);
        }

        foreach ($brands as $brand) {
            $this->authorize($data['action'] === 'delete' ? 'delete' : 'update', $brand);
        }

        if ($data['action'] === 'delete') {
            if ($brands->contains(fn (Brand $brand) => $brand->products()->exists())) {
                throw ValidationException::withMessages([
                    'ids' => 'Brands with assigned products cannot be deleted.',
                ]);
            }

            $brands->each(fn (Brand $brand) => $this->brands->delete($brand));
        } else {
            $brands->each(fn (Brand $brand) => $brand->update([
                'is_active' => $data['action'] === 'activate',
            ]));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($brands).' brands '.($data['action'] === 'delete' ? 'deleted.' : 'updated.'),
        ]);

        return to_route('admin.brands.index');
    }
}
