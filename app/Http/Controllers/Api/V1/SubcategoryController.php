<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Models\Subcategory;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class SubcategoryController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function show(Subcategory $subcategory): JsonResponse
    {
        abort_unless($subcategory->is_active, 404);

        $subcategory->load('category');

        $products = $this->products->paginate(['subcategory' => $subcategory->slug]);

        $products->setCollection(
            $products->getCollection()->map(fn (Product $product) => new ProductCardResource($product)),
        );

        return response()->json([
            'subcategory' => $subcategory,
            'products' => $products,
        ]);
    }
}
