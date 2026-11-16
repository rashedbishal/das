<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Every public, indexable page: the fixed storefront pages and active categories,
     * subcategories and brands. Product pages need a signed-in user, so they are left out.
     */
    public function sitemap(): Response
    {
        /** @var array<int, array{loc: string, lastmod: string|null, images?: array<int, array{loc: string, title: string}>}> $urls */
        $urls = [];

        foreach (['home', 'catalogue', 'categories.index', 'brands', 'about', 'contact'] as $route) {
            $urls[] = ['loc' => route($route), 'lastmod' => null];
        }

        $categories = Category::query()
            ->active()
            ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'updated_at']);

        foreach ($categories as $category) {
            $urls[] = ['loc' => route('categories.show', $category->slug), 'lastmod' => $category->updated_at?->toAtomString()];

            foreach ($category->subcategories as $subcategory) {
                $urls[] = [
                    'loc' => route('categories.subcategory', [$category->slug, $subcategory->slug]),
                    'lastmod' => $subcategory->updated_at?->toAtomString(),
                ];
            }
        }

        foreach (Brand::query()->active()->orderBy('name')->get(['slug', 'updated_at']) as $brand) {
            $urls[] = ['loc' => route('brands.show', $brand->slug), 'lastmod' => $brand->updated_at?->toAtomString()];
        }

        $entries = implode("\n", array_map(function (array $url): string {
            $lastmod = $url['lastmod'] !== null ? '<lastmod>'.e($url['lastmod']).'</lastmod>' : '';
            $images = implode('', array_map(
                fn (array $image) => '<image:image><image:loc>'.e($image['loc']).'</image:loc><image:title>'.e($image['title']).'</image:title></image:image>',
                $url['images'] ?? [],
            ));

            return '<url><loc>'.e($url['loc']).'</loc>'.$lastmod.$images.'</url>';
        }, $urls));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n"
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
