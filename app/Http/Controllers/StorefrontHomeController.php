<?php

namespace App\Http\Controllers;

use App\Http\Resources\HomepageSectionResource;
use App\Http\Resources\ProductCardResource;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontHomeController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Banners and featured products are passed as page props so the hero renders on
     * first paint and the product links are in the server-rendered HTML.
     */
    public function __invoke(Request $request): Response
    {
        $featuredLimit = (int) $this->settings->get('catalog', 'featured_limit', 8);

        return Inertia::render('storefront/Home', [
            'featuredProducts' => ProductCardResource::collection(
                Product::query()->published()->featured()->with(['brand', 'media'])->latest()->limit($featuredLimit)->get(),
            )->resolve($request),
            'banners' => HomepageSectionResource::collection(
                HomepageSection::query()
                    ->visible()
                    ->where('type', 'banner')
                    ->whereNotNull('image')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(),
            )->resolve(),
        ]);
    }
}
