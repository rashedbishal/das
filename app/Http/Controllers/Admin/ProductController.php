<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->products->paginate(
            $request->only(['search', 'status', 'brand', 'category', 'stock_status', 'sort', 'per_page']),
            includeAllStatuses: true,
        );

        return Inertia::render('admin/products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'status', 'brand', 'category', 'stock_status', 'sort']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('admin/products/Form', [
            'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => $this->subcategoryOptions(),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product created.']);

        return to_route('admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('manage', $product);

        $product->load(['brand', 'categories', 'tags', 'media', 'specifications', 'relatedProducts']);

        return Inertia::render('admin/products/Form', [
            'product' => $product,
            'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => $this->subcategoryOptions(),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($request->user(), $product, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product updated.']);

        return to_route('admin.products.edit', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product deleted.']);

        return to_route('admin.products.index');
    }

    /**
     * Products can only be assigned to subcategories, so the picker only ever offers those,
     * labelled with their parent so admins can tell them apart.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    private function subcategoryOptions(): Collection
    {
        return Category::query()
            ->active()
            ->whereNotNull('parent_id')
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->parent !== null
                    ? "{$category->parent->name} \u{203A} {$category->name}"
                    : $category->name,
            ]);
    }
}
