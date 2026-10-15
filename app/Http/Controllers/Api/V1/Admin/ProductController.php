<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->products->paginate(
            $request->only(['search', 'status', 'brand', 'category', 'stock_status', 'sort', 'per_page']),
            includeAllStatuses: true,
        );

        $products->setCollection($products->getCollection()->map(fn (Product $product) => new ProductResource($product)));

        return response()->json($products->toArray());
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->user(), $request->validated());

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('manage', $product);

        $product->load(['brand', 'categories', 'tags', 'media', 'specifications', 'relatedProducts']);

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $updated = $this->products->update($request->user(), $product, $request->validated());

        return response()->json(['data' => new ProductResource($updated)]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return response()->json(status: 204);
    }
}
