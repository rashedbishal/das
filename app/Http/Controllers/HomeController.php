<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCardResource;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\SettingsService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function __invoke(): Response
    {
        $featuredLimit = (int) $this->settings->get('catalog', 'featured_limit', 8);

        return Inertia::render('Welcome', [
            'sections' => HomepageSection::query()->visible()->orderBy('sort_order')->get(),
            'featuredProducts' => ProductCardResource::collection(
                Product::query()->published()->featured()->with(['brand', 'media'])->limit($featuredLimit)->get(),
            ),
        ]);
    }
}
