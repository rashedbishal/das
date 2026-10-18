<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomepageSectionResource;
use App\Http\Resources\ProductCardResource;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function __invoke(): JsonResponse
    {
        $featuredLimit = (int) $this->settings->get('catalog', 'featured_limit', 8);

        return response()->json([
            'sections' => HomepageSectionResource::collection(HomepageSection::query()->visible()->orderBy('sort_order')->get()),
            'featured_products' => ProductCardResource::collection(
                Product::query()->published()->featured()->with(['brand', 'media'])->limit($featuredLimit)->get(),
            ),
        ]);
    }
}
