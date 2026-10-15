<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function show(Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $products = $this->products->paginate(['category' => $category->slug]);

        $products->setCollection(
            $products->getCollection()->map(fn (Product $product) => new ProductCardResource($product)),
        );

        return response()->json([
            'category' => $category,
            'products' => $products,
        ]);
    }
}
