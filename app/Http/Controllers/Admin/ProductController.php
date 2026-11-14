<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Services\ProductMediaService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductMediaService $media,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->products->paginate(
            $request->only(['search', 'status', 'brand', 'category', 'subcategory', 'stock_status', 'sort', 'per_page']),
            includeAllStatuses: true,
        );

        $products->getCollection()->each(
            fn (Product $product) => $product->media->each(
                fn (ProductMedia $media) => $media->url = $media->resolvedUrl(),
            ),
        );

        return Inertia::render('admin/products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'status', 'brand', 'category', 'subcategory', 'stock_status', 'sort']),
            'mediaLimits' => ProductMediaService::limits(),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['images']);

        DB::transaction(function () use ($request, $data) {
            $product = $this->products->create($request->user(), $data);

            try {
                // Empty file slots (an untouched picker) arrive as nulls.
                $images = array_values(array_filter((array) $request->file('images', [])));

                foreach ($images as $index => $image) {
                    $this->media->addUpload($product, $image, 'image', [
                        'is_main' => $index === 0,
                        'sort_order' => $index,
                    ], "images.{$index}");
                }
            } catch (\Throwable $e) {
                // The product row is rolled back; remove any files already stored for it.
                Storage::disk('public')->deleteDirectory("products/{$product->id}");

                throw $e;
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product created.']);

        return to_route('admin.products.index');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($request->user(), $product, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product updated.']);

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product deleted.']);

        return to_route('admin.products.index');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'action' => ['required', Rule::in([
                'delete',
                'publish',
                'draft',
                'show_price',
                'hide_price',
                'show_stock',
                'hide_stock',
            ])],
        ]);

        $products = Product::query()->whereKey($data['ids'])->get();
        if ($products->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => 'One or more selected products are unavailable.']);
        }

        $ability = match ($data['action']) {
            'delete' => 'delete',
            'publish', 'draft' => 'publish',
            default => 'update',
        };
        foreach ($products as $product) {
            $this->authorize($ability, $product);
        }

        if ($data['action'] === 'delete') {
            $products->each(fn (Product $product) => $this->products->delete($product));
        } else {
            $updates = match ($data['action']) {
                'publish' => ['status' => 'published'],
                'draft' => ['status' => 'draft'],
                'show_price' => ['show_price' => true],
                'hide_price' => ['show_price' => false],
                'show_stock' => ['show_stock' => true],
                'hide_stock' => ['show_stock' => false],
                default => throw new \LogicException('Unsupported bulk product action.'),
            };
            $products->each(fn (Product $product) => $this->products->update(
                $request->user(),
                $product,
                $updates,
            ));
        }

        $message = match ($data['action']) {
            'delete' => 'deleted.',
            'publish' => 'published.',
            'draft' => 'moved to draft.',
            'show_price' => 'updated to show price.',
            'hide_price' => 'updated to hide price.',
            'show_stock' => 'updated to show stock.',
            'hide_stock' => 'updated to hide stock.',
        };

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => count($products).' products '.$message,
        ]);

        return to_route('admin.products.index');
    }

    /**
     * @return array{brands: Collection<int, Brand>, categories: Collection<int, Category>, subcategories: Collection<int, Subcategory>, tags: Collection<int, Tag>}
     */
    private function formOptions(): array
    {
        return [
            'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->active()->orderBy('name')->get(['id', 'name']),
            'subcategories' => Subcategory::query()->active()->orderBy('name')->get(['id', 'name', 'category_id']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
