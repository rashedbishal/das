<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): Response
    {
        $products = $this->products->paginate($request->only([
            'search', 'category', 'brand', 'min_price', 'max_price', 'stock_status', 'sort', 'per_page',
        ]));

        $products->through(fn (Product $product) => (new ProductCardResource($product))->toArray($request));

        return Inertia::render('catalog/ProductIndex', [
            'products' => $products,
            'filters' => $request->only(['search', 'category', 'brand', 'min_price', 'max_price', 'stock_status', 'sort']),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless(Gate::allows('view', $product), 404);

        $product->load(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications', 'relatedProducts.brand', 'relatedProducts.media']);

        return Inertia::render('catalog/ProductShow', [
            'product' => new ProductResource($product),
        ]);
    }
}
