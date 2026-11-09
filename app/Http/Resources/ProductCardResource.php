<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Restricted public summary — never includes full description,
 * specifications, SEO fields, or the full media gallery.
 *
 * @mixin Product
 */
class ProductCardResource extends JsonResource
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
            'short_description' => $this->short_description,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            'main_image' => $this->mainImage()?->resolvedUrl(),
            'regular_price' => $this->regular_price !== null ? (string) $this->regular_price : null,
            'sale_price' => $this->sale_price !== null ? (string) $this->sale_price : null,
            'currency' => $this->currency,
            'stock_status' => $this->stock_status,
            'is_featured' => $this->is_featured,
        ];
    }
}
