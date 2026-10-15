<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->products->paginate($request->only([
            'search', 'category', 'brand', 'min_price', 'max_price', 'stock_status', 'sort', 'per_page',
        ]));

        return response()->json(ProductCardResource::collection($products)->response()->getData(true));
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless(Gate::allows('view', $product), 404);

        $product->load(['brand', 'categories', 'tags', 'media', 'specifications', 'relatedProducts.brand', 'relatedProducts.media']);

        return response()->json(['data' => new ProductResource($product)]);
    }
}
