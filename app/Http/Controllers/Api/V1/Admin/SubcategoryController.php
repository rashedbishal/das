<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Admin\Subcategory\UpdateSubcategoryRequest;
use App\Models\Subcategory;
use App\Services\SubcategoryService;
use Illuminate\Http\JsonResponse;

class SubcategoryController extends Controller
{
    public function __construct(private readonly SubcategoryService $subcategories) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Subcategory::class);

        return response()->json([
            'data' => Subcategory::query()->with('category:id,name')->withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->subcategories->create($request->validated())], 201);
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): JsonResponse
    {
        return response()->json(['data' => $this->subcategories->update($subcategory, $request->validated())]);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $this->authorize('delete', $subcategory);

        $this->subcategories->delete($subcategory);

        return response()->json(status: 204);
    }
}
