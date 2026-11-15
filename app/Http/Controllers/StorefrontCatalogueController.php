<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Renders the catalogue and category pages with their first page of products as
 * props, so the products are in the server-rendered HTML (for search engines and a
 * faster first paint). Later filter changes are fetched client-side from /api/v1.
 */
class StorefrontCatalogueController extends Controller
{
    /** Matches the page size the storefront requests client-side (catalogue.ts). */
    private const PER_PAGE = 12;

    /** Storefront sort values mapped to ProductService sort keys. */
    private const SORTS = [
        'featured' => 'newest',
        'name' => 'name',
        'price-asc' => 'price_asc',
        'price-desc' => 'price_desc',
    ];

    public function __construct(private readonly ProductService $products) {}

    public function catalogue(Request $request): Response
    {
        return Inertia::render('storefront/Catalogue', [
            'initialProducts' => $this->firstPage($request),
        ]);
    }

    public function category(Request $request, string $slug): Response|HttpResponse
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->withCount(['products' => fn ($query) => $query->published()])
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->first();

        if ($category === null) {
            return Inertia::render('storefront/NotFound')->toResponse($request)->setStatusCode(404);
        }

        return Inertia::render('storefront/Category', [
            'category' => (new CategoryResource($category))->resolve(),
            'initialProducts' => $this->firstPage($request, $category->slug),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function firstPage(Request $request, ?string $category = null): array
    {
        $text = fn (string $key): ?string => is_string($request->query($key)) && $request->query($key) !== ''
            ? (string) $request->query($key)
            : null;
        $maxPrice = (float) $text('maxPrice');

        $products = $this->products->paginate([
            'search' => $text('q'),
            'category' => $category ?? $text('category'),
            'subcategory' => $text('subcategory'),
            'brand' => $text('brand'),
            'stock_status' => $text('stockStatus'),
            'max_price' => $maxPrice > 0 ? $maxPrice : null,
            'sort' => self::SORTS[$text('sort') ?? 'featured'] ?? 'newest',
            'per_page' => self::PER_PAGE,
        ]);

        return ProductCardResource::collection($products)->response()->getData(true);
    }
}
