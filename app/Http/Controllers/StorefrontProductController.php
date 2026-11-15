<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The public product page, rendered with the product as props so its name,
 * description, images and specifications are in the server-rendered HTML for
 * search engines. Prices are left out for guests (see ProductResource).
 */
class StorefrontProductController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function __invoke(Request $request, string $slug): Response|HttpResponse
    {
        $product = Product::query()->where('slug', $slug)->first();

        // Unknown, draft and archived products are a real 404 (admins may preview drafts).
        if ($product === null || ! Gate::forUser($request->user())->allows('view', $product)) {
            return Inertia::render('storefront/NotFound')->toResponse($request)->setStatusCode(404);
        }

        $product->load([
            'brand', 'category', 'subcategory', 'tags', 'media', 'specifications', 'variants',
            'relatedProducts' => fn ($query) => $query->published()->with(['brand', 'media'])->limit(8),
        ]);

        // A guest signing in from this page ("Sign in to see price") comes back here.
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::render('storefront/Product', [
            'product' => (new ProductResource($product))->resolve($request),
            'isPreview' => $product->status !== 'published',
            'loginMode' => $this->settings->loginMode(),
        ]);
    }
}
