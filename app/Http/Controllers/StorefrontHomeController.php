<?php

namespace App\Http\Controllers;

use App\Http\Resources\HomepageSectionResource;
use App\Models\HomepageSection;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontHomeController extends Controller
{
    /**
     * Banners are passed as page props (not fetched client-side like the rest of the
     * storefront data) so the hero renders on first paint without a layout jump.
     */
    public function __invoke(): Response
    {
        return Inertia::render('storefront/Home', [
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
