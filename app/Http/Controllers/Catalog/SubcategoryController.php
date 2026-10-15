<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Product;
use App\Models\Subcategory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubcategoryController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function show(Request $request, Subcategory $subcategory): Response
    {
        abort_unless($subcategory->is_active, 404);

        $subcategory->load('category');

        $products = $this->products->paginate(['subcategory' => $subcategory->slug]);
        $products->through(fn (Product $product) => (new ProductCardResource($product))->toArray($request));

        return Inertia::render('catalog/SubcategoryShow', [
            'subcategory' => $subcategory,
            'products' => $products,
        ]);
    }
}
