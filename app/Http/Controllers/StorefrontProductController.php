<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The product page, rendered with the product as props. It is only reachable by
 * signed-in users: the route's `auth` middleware sends guests to sign in and
 * remembers the product so they come back to it afterwards.
 */
class StorefrontProductController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly ProductService $products,
    ) {}

    public function __invoke(Request $request, string $slug): Response|HttpResponse
    {
        $product = Product::query()->where('slug', $slug)->first();

        // Unknown, draft and archived products are a real 404 (admins may preview drafts).
        if ($product === null || ! Gate::forUser($request->user())->allows('view', $product)) {
            return Inertia::render('storefront/NotFound')->toResponse($request)->setStatusCode(404);
        }

        $product->load(['brand', 'category', 'subcategory', 'tags', 'media', 'specifications', 'variants']);
        $product->setRelation('relatedProducts', $this->products->relatedFor($product));

        return Inertia::render('storefront/Product', [
            'product' => (new ProductResource($product))->resolve($request),
            'isPreview' => $product->status !== 'published',
            'loginMode' => $this->settings->loginMode(),
        ]);
    }
}
