<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product detail. Public for published products (the storefront product page);
 * prices are only included for signed-in users.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Prices are for signed-in customers only; guests are asked to sign in.
        $priceVisible = $this->show_price && $request->user() !== null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'model_number' => $this->model_number,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'show_price' => $priceVisible,
            'price_login_required' => $this->show_price && $request->user() === null,
            'show_stock' => $this->show_stock,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'subcategory' => $this->whenLoaded('subcategory', fn () => $this->subcategory ? [
                'name' => $this->subcategory->name,
                'slug' => $this->subcategory->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'short_description' => $this->short_description,
            'full_description' => $this->full_description,
            'highlights' => $this->highlights,
            'specifications' => $this->whenLoaded('specifications', fn () => $this->specifications->map(fn ($spec) => [
                'name' => $spec->name,
                'value' => $spec->value,
            ])),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media) => [
                'type' => $media->type,
                'url' => $media->resolvedUrl(),
                'is_main' => $media->is_main,
                'alt_text' => $media->alt_text,
            ])),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants
                ->where('is_active', true)
                ->values()
                ->map(fn ($variant) => [
                    'name' => $variant->name,
                    'price' => $priceVisible && $variant->price !== null ? (string) $variant->price : null,
                    'stock_status' => $this->show_stock ? $variant->stock_status : null,
                ])),
            'regular_price' => $priceVisible && $this->regular_price !== null ? (string) $this->regular_price : null,
            'sale_price' => $priceVisible && $this->sale_price !== null ? (string) $this->sale_price : null,
            'currency' => $this->currency,
            'stock_quantity' => $this->show_stock ? $this->stock_quantity : null,
            'stock_status' => $this->show_stock ? $this->stock_status : null,
            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
                'keywords' => $this->seo_keywords,
                'canonical_url' => $this->canonical_url,
            ],
            // Resolved to a plain list so it has the same shape in JSON responses and in
            // Inertia props (where a nested collection would be wrapped in "data").
            'related_products' => $this->whenLoaded('relatedProducts', fn () => ProductCardResource::collection($this->relatedProducts)->resolve($request)),
        ];
    }
}
