<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\StoreBrandRequest;
use App\Http\Requests\Admin\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
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
}
