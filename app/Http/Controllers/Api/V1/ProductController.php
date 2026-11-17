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
        $canSeePrices = $request->user()?->canSeePrices() === true;
        $filters = ProductService::withoutPriceFiltersForGuests($request->only([
            'search', 'category', 'subcategory', 'brand', 'min_price', 'max_price', 'stock_status', 'sort', 'per_page',
        ]), $canSeePrices);
        $products = $this->products->paginate($filters);

        return response()->json([
            ...ProductCardResource::collection($products)->response()->getData(true),
            // Bounds for the storefront's price slider, only for users who may see prices.
            'price_range' => $canSeePrices ? $this->products->priceRange($filters) : null,
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless(Gate::allows('view', $product), 404);

        $product->load(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications', 'variants']);
        // Same suggestions as the storefront page: hand-picked, then shared tags, subcategory, category.
        $product->setRelation('relatedProducts', $this->products->relatedFor($product));

        return response()->json(['data' => new ProductResource($product)]);
    }
}
