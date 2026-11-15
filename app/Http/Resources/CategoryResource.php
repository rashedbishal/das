<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public category shape for the storefront.
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
            'description' => $this->description,
            'image' => $this->image,
            'products_count' => $this->whenCounted('products'),
            // Resolved to a plain list so it has the same shape in JSON responses and in
            // Inertia props (where a nested collection would be wrapped in "data").
            'subcategories' => $this->whenLoaded(
                'subcategories',
                fn () => SubcategoryResource::collection($this->subcategories)->resolve(),
            ),
        ];
    }
}
