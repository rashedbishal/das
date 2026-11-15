<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Public, indexable pages only. Product detail pages are left out on purpose:
     * they require login, so search engines can't see their content.
     */
    public function sitemap(): Response
    {
        /** @var array<int, array{loc: string, lastmod: string|null}> $urls */
        $urls = [];

        foreach (['home', 'catalogue', 'categories.index', 'brands', 'about', 'contact'] as $route) {
            $urls[] = ['loc' => route($route), 'lastmod' => null];
        }

        $categories = Category::query()->active()->orderBy('sort_order')->get(['slug', 'updated_at']);

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => route('categories.show', $category->slug),
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        $entries = implode("\n", array_map(function (array $url): string {
            $lastmod = $url['lastmod'] !== null ? '<lastmod>'.e($url['lastmod']).'</lastmod>' : '';

            return '<url><loc>'.e($url['loc']).'</loc>'.$lastmod.'</url>';
        }, $urls));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$entries."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /api/',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
