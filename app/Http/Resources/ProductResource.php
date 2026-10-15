<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product detail — only ever returned to authenticated requests
 * that passed ProductPolicy::view.
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'model_number' => $this->model_number,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
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
            'regular_price' => (string) $this->regular_price,
            'sale_price' => $this->sale_price !== null ? (string) $this->sale_price : null,
            'currency' => $this->currency,
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status,
            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
                'keywords' => $this->seo_keywords,
                'canonical_url' => $this->canonical_url,
            ],
            'related_products' => $this->whenLoaded('relatedProducts', fn () => ProductCardResource::collection($this->relatedProducts)),
        ];
    }
}
