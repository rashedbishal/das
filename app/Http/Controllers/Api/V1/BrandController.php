<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => Brand::query()->active()->orderBy('name')->get()]);
    }

    public function show(Brand $brand): JsonResponse
    {
        abort_unless($brand->is_active, 404);

        $products = $this->products->paginate(['brand' => $brand->slug]);

        $products->setCollection(
            $products->getCollection()->map(fn (Product $product) => new ProductCardResource($product)),
        );

        return response()->json([
            'brand' => $brand,
            'products' => $products,
        ]);
    }
}
