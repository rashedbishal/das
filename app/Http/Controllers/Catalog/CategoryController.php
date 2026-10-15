<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): Response
    {
        $categories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('catalog/CategoryIndex', [
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $products = $this->products->paginate(['category' => $category->slug]);
        $products->through(fn (Product $product) => (new ProductCardResource($product))->toArray($request));

        return Inertia::render('catalog/CategoryShow', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
