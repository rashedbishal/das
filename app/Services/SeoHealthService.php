<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Subcategory;
use Illuminate\Support\Str;

/**
 * Finds content gaps that hold storefront pages back in search results: missing or
 * thin descriptions, missing images or alt text, over-long titles, and site-wide
 * settings (Search Console, business address) that are not filled in yet.
 */
class SeoHealthService
{
    /** Google shows roughly this many characters of a title. */
    private const MAX_TITLE = 60;

    private const MIN_META_DESCRIPTION = 70;

    private const MIN_PRODUCT_DESCRIPTION = 300;

    private const MIN_PAGE_DESCRIPTION = 120;

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array{
     *     score: int,
     *     pages: int,
     *     pagesWithIssues: int,
     *     items: array<int, array{type: string, id: int, name: string, url: string, issues: array<int, string>}>,
     *     site: array<int, array{label: string, ok: bool, hint: string}>
     * }
     */
    public function report(): array
    {
        $items = [
            ...$this->products(),
            ...$this->categories(),
            ...$this->subcategories(),
            ...$this->brands(),
        ];

        $withIssues = array_values(array_filter($items, fn (array $item) => $item['issues'] !== []));
        $pages = count($items);

        return [
            'score' => $pages === 0 ? 100 : (int) round(100 * ($pages - count($withIssues)) / $pages),
            'pages' => $pages,
            'pagesWithIssues' => count($withIssues),
            'items' => $withIssues,
            'site' => $this->site(),
        ];
    }

    /**
     * @return array<int, array{type: string, id: int, name: string, url: string, issues: array<int, string>}>
     */
    private function products(): array
    {
        return Product::query()
            ->published()
            ->with(['media' => fn ($query) => $query->where('type', 'image')])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $issues = [];
                $title = trim((string) ($product->seo_title ?: $product->name));
                $metaDescription = trim((string) ($product->seo_description ?: $product->short_description ?: $product->full_description));

                if (Str::length($title) > self::MAX_TITLE) {
                    $issues[] = 'Title is over '.self::MAX_TITLE.' characters (add a shorter SEO title)';
                }

                if (Str::length($metaDescription) < self::MIN_META_DESCRIPTION) {
                    $issues[] = 'No useful search description (add a short description or SEO description)';
                }

                if (Str::length(trim((string) $product->full_description)) < self::MIN_PRODUCT_DESCRIPTION) {
                    $issues[] = 'Full description is missing or under '.self::MIN_PRODUCT_DESCRIPTION.' characters';
                }

                if ($product->media->isEmpty()) {
                    $issues[] = 'No product image';
                } elseif ($product->media->contains(fn (ProductMedia $media) => trim((string) $media->alt_text) === '')) {
                    $issues[] = 'Some images have no alt text';
                }

                return $this->item('product', $product->id, $product->name, route('products.show', $product->slug), $issues);
            })
            ->all();
    }

    /**
     * @return array<int, array{type: string, id: int, name: string, url: string, issues: array<int, string>}>
     */
    private function categories(): array
    {
        return Category::query()->active()->orderBy('sort_order')->get()
            ->map(fn (Category $category) => $this->item(
                'category',
                $category->id,
                $category->name,
                route('categories.show', $category->slug),
                $this->pageIssues($category->seo_title ?: $category->name, $category->description, $category->image !== null, 'image'),
            ))
            ->all();
    }

    /**
     * @return array<int, array{type: string, id: int, name: string, url: string, issues: array<int, string>}>
     */
    private function subcategories(): array
    {
        return Subcategory::query()
            ->active()
            ->whereHas('category', fn ($query) => $query->active())
            ->with('category:id,slug')
            ->orderBy('name')
            ->get()
            ->map(fn (Subcategory $subcategory) => $this->item(
                'subcategory',
                $subcategory->id,
                $subcategory->name,
                route('categories.subcategory', [$subcategory->category->slug, $subcategory->slug]),
                $this->pageIssues($subcategory->seo_title ?: $subcategory->name, $subcategory->description, $subcategory->image !== null, 'image'),
            ))
            ->all();
    }

    /**
     * @return array<int, array{type: string, id: int, name: string, url: string, issues: array<int, string>}>
     */
    private function brands(): array
    {
        return Brand::query()->active()->orderBy('name')->get()
            ->map(fn (Brand $brand) => $this->item(
                'brand',
                $brand->id,
                $brand->name,
                route('brands.show', $brand->slug),
                $this->pageIssues($brand->seo_title ?: $brand->name, $brand->description, filled($brand->logo), 'logo'),
            ))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function pageIssues(string $title, ?string $description, bool $hasImage, string $imageWord): array
    {
        $issues = [];

        if (Str::length(trim($title)) > self::MAX_TITLE) {
            $issues[] = 'Title is over '.self::MAX_TITLE.' characters (add a shorter SEO title)';
        }

        if (Str::length(trim((string) $description)) < self::MIN_PAGE_DESCRIPTION) {
            $issues[] = 'Description is missing or under '.self::MIN_PAGE_DESCRIPTION.' characters';
        }

        if (! $hasImage) {
            $issues[] = "No {$imageWord}";
        }

        return $issues;
    }

    /**
     * @param  array<int, string>  $issues
     * @return array{type: string, id: int, name: string, url: string, issues: array<int, string>}
     */
    private function item(string $type, int $id, string $name, string $url, array $issues): array
    {
        return compact('type', 'id', 'name', 'url', 'issues');
    }

    /**
     * @return array<int, array{label: string, ok: bool, hint: string}>
     */
    private function site(): array
    {
        $general = $this->settings->group('general');
        $seo = $this->settings->group('seo');
        $hours = array_filter((array) ($general['opening_hours'] ?? []), fn ($day) => is_array($day) && empty($day['closed']));
        $appUrl = (string) config('app.url');

        return [
            [
                'label' => 'Google Search Console connected',
                'ok' => filled($seo['google_site_verification'] ?? null),
                'hint' => 'Settings → Catalog & SEO: paste the verification tag, then submit the sitemap in Search Console.',
            ],
            [
                'label' => 'Default site description set',
                'ok' => Str::length(trim((string) ($seo['default_description'] ?? ''))) >= self::MIN_META_DESCRIPTION,
                'hint' => 'Settings → Catalog & SEO: used on pages that have no description of their own.',
            ],
            [
                'label' => 'Business address and city set',
                'ok' => filled($general['address'] ?? null) && filled($general['address_city'] ?? null),
                'hint' => 'Settings → General: needed for Google Maps and local search.',
            ],
            [
                'label' => 'Opening hours set',
                'ok' => $hours !== [],
                'hint' => 'Settings → General: shown on the Contact page and to Google.',
            ],
            [
                'label' => 'Google Maps link set',
                'ok' => filled($general['map_url'] ?? null),
                'hint' => 'Settings → General: links the Contact page to your shop on the map.',
            ],
            [
                'label' => 'Site address uses https',
                'ok' => str_starts_with($appUrl, 'https://'),
                'hint' => 'APP_URL in .env on the server must be the final https:// address (it is used in the sitemap and canonical links).',
            ],
        ];
    }
}
