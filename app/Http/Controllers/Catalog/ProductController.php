<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly SettingsService $settings,
    ) {}

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

    public function show(Request $request, string $slug): Response
    {
        if (! $request->user()) {
            // Never resolve the product for guests — a 404 here vs. a locked page there
            // would leak which slugs exist/are published. The intended URL is stashed so
            // Fortify's LoginResponse (redirect()->intended()) returns the visitor to this
            // exact page once they authenticate through the inline login modal.
            $request->session()->put('url.intended', $request->fullUrl());

            return Inertia::render('catalog/ProductShow', [
                'product' => null,
                'authRequired' => true,
                'loginMode' => $this->settings->loginMode(),
            ]);
        }

        $product = Product::query()->where('slug', $slug)->firstOrFail();

        abort_unless(Gate::allows('view', $product), 404);

        $product->load(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications', 'variants', 'relatedProducts.brand', 'relatedProducts.media']);

        return Inertia::render('catalog/ProductShow', [
            'product' => new ProductResource($product),
            'authRequired' => false,
        ]);
    }
}
