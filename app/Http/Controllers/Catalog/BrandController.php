<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): Response
    {
        return Inertia::render('catalog/BrandIndex', [
            'brands' => Brand::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Brand $brand): Response
    {
        abort_unless($brand->is_active, 404);

        $products = $this->products->paginate(['brand' => $brand->slug]);
        $products->through(fn (Product $product) => (new ProductCardResource($product))->toArray($request));

        return Inertia::render('catalog/BrandShow', [
            'brand' => $brand,
            'products' => $products,
        ]);
    }
}
