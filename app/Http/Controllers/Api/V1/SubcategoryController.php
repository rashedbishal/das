<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\SubcategoryResource;
use App\Models\Subcategory;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    /**
     * Subcategories holding at least one published product of the given brand(s),
     * optionally narrowed to some categories - feeds the storefront's brand-scoped
     * subcategory filter. `brand` and `category` take comma-separated slugs.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:2000'],
        ]);

        $brands = ProductService::slugList($validated['brand']);
        $categories = ProductService::slugList($validated['category'] ?? null);

        // Untyped like the other relation-constraint closures here (see
        // CategoryController), so the Product-only published() scope resolves.
        $brandProducts = fn ($query) => $query->published()
            ->whereHas('brand', fn ($inner) => $inner->whereIn('brands.slug', $brands));

        $subcategories = Subcategory::query()
            ->active()
            ->whereHas('category', function (Builder $query) use ($categories): void {
                $query->active()->when($categories !== [], fn (Builder $inner) => $inner->whereIn('slug', $categories));
            })
            ->whereHas('products', $brandProducts)
            ->withCount(['products' => $brandProducts])
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => SubcategoryResource::collection($subcategories)->resolve()]);
    }

    public function show(Subcategory $subcategory): JsonResponse
    {
        abort_unless($subcategory->is_active, 404);

        $subcategory->load('category');

        return response()->json([
            'subcategory' => (new SubcategoryResource($subcategory))->resolve(),
            'products' => ProductCardResource::collection($this->products->paginate(['subcategory' => $subcategory->slug]))
                ->response()
                ->getData(true),
        ]);
    }
}
