<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoHealthService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin -> SEO Health: storefront pages with content gaps, and site-wide SEO settings
 * still to fill in.
 */
class SeoHealthController extends Controller
{
    public function __invoke(Request $request, SeoHealthService $health): Response
    {
        $user = $request->user();
        abort_unless($user !== null && ($user->hasPermission('seo.manage') || $user->hasPermission('products.view')), 403);

        return Inertia::render('admin/seo/Index', [
            'report' => $health->report(),
            'sitemapUrl' => route('sitemap'),
        ]);
    }
}
