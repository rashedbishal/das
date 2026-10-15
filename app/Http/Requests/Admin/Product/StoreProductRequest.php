<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')],
            'model_number' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'full_description' => ['nullable', 'string'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => ['boolean'],
            'regular_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:regular_price'],
            'currency' => ['nullable', 'string', 'size:3'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'stock_status' => ['required', Rule::in(['in_stock', 'out_of_stock', 'backorder'])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'related_product_ids' => ['nullable', 'array'],
            'related_product_ids.*' => ['integer', 'exists:products,id'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.name' => ['required_with:specifications', 'string', 'max:255'],
            'specifications.*.value' => ['required_with:specifications', 'string', 'max:255'],
            'specifications.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}
