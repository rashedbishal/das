<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ProductCardResource;
use App\Models\Brand;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): JsonResponse
    {
        $brands = Brand::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => BrandResource::collection($brands)->resolve()]);
    }

    public function show(Brand $brand): JsonResponse
    {
        abort_unless($brand->is_active, 404);

        return response()->json([
            'brand' => (new BrandResource($brand))->resolve(),
            'products' => ProductCardResource::collection($this->products->paginate(['brand' => $brand->slug]))
                ->response()
                ->getData(true),
        ]);
    }
}
