<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\SubcategoryResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Renders the catalogue, category, subcategory and brand pages (and the category
 * and brand directories) with their data as props, so the content and links are in
 * the server-rendered HTML for search engines and a faster first paint. Later filter
 * changes are fetched client-side from /api/v1.
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

    public function categories(): Response
    {
        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->published()])
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('storefront/Categories', [
            'categories' => CategoryResource::collection($categories)->resolve(),
        ]);
    }

    public function category(Request $request, string $slug): Response|HttpResponse
    {
        $category = $this->activeCategory($slug);

        if ($category === null) {
            return $this->notFound($request);
        }

        return Inertia::render('storefront/Category', [
            'category' => (new CategoryResource($category))->resolve(),
            'initialProducts' => $this->firstPage($request, ['category' => $category->slug]),
        ]);
    }

    public function subcategory(Request $request, string $categorySlug, string $slug): Response|HttpResponse
    {
        $category = $this->activeCategory($categorySlug);
        $subcategory = $category?->subcategories->firstWhere('slug', $slug);

        if ($category === null || ! $subcategory instanceof Subcategory) {
            return $this->notFound($request);
        }

        return Inertia::render('storefront/Subcategory', [
            'category' => (new CategoryResource($category))->resolve(),
            'subcategory' => (new SubcategoryResource($subcategory))->resolve(),
            'initialProducts' => $this->firstPage($request, [
                'category' => $category->slug,
                'subcategory' => $subcategory->slug,
            ]),
        ]);
    }

    public function brands(): Response
    {
        $brands = Brand::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();

        return Inertia::render('storefront/Brands', [
            'brands' => BrandResource::collection($brands)->resolve(),
        ]);
    }

    public function brand(Request $request, string $slug): Response|HttpResponse
    {
        $brand = Brand::query()
            ->active()
            ->where('slug', $slug)
            ->withCount(['products' => fn ($query) => $query->published()])
            ->first();

        if ($brand === null) {
            return $this->notFound($request);
        }

        return Inertia::render('storefront/Brand', [
            'brand' => (new BrandResource($brand))->resolve(),
            'initialProducts' => $this->firstPage($request, ['brand' => $brand->slug]),
        ]);
    }

    private function activeCategory(string $slug): ?Category
    {
        return Category::query()
            ->active()
            ->where('slug', $slug)
            ->withCount(['products' => fn ($query) => $query->published()])
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->first();
    }

    private function notFound(Request $request): HttpResponse
    {
        return Inertia::render('storefront/NotFound')->toResponse($request)->setStatusCode(404);
    }

    /**
     * The first page of products for the URL's filters, with the page's own
     * category/subcategory/brand fixed.
     *
     * @param  array<string, string>  $fixed
     * @return array<string, mixed>
     */
    private function firstPage(Request $request, array $fixed = []): array
    {
        $text = fn (string $key): ?string => is_string($request->query($key)) && $request->query($key) !== ''
            ? (string) $request->query($key)
            : null;
        $maxPrice = (float) $text('maxPrice');

        $filters = ProductService::withoutPriceFiltersForGuests([
            'search' => $text('q'),
            'category' => $text('category'),
            'subcategory' => $text('subcategory'),
            'brand' => $text('brand'),
            'stock_status' => $text('stockStatus'),
            'max_price' => $maxPrice > 0 ? $maxPrice : null,
            'sort' => self::SORTS[$text('sort') ?? 'featured'] ?? 'newest',
            'per_page' => self::PER_PAGE,
            ...$fixed,
        ], $request->user()?->canSeePrices() === true);

        return ProductCardResource::collection($this->products->paginate($filters))->response()->getData(true);
    }
}
