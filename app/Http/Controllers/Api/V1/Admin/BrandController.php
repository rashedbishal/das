<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Brand\StoreBrandRequest;
use App\Http\Requests\Admin\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brands) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        return response()->json(['data' => Brand::query()->withCount('products')->orderBy('name')->get()]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->brands->create($request->validated())], 201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        return response()->json(['data' => $this->brands->update($brand, $request->validated())]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);

        $this->brands->delete($brand);

        return response()->json(status: 204);
    }
}
