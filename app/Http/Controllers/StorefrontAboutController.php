<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontAboutController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * The copy is a page prop so it is in the server-rendered HTML on first paint.
     */
    public function __invoke(): Response
    {
        return Inertia::render('storefront/About', [
            'about' => $this->settings->aboutPage(),
        ]);
    }
}
