<?php

namespace App\Http\Requests\Admin\Product;

use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use ChecksPublishPermission;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', FieldLimits::max('product.name')],
            'slug' => ['nullable', 'string', FieldLimits::max('product.slug')],
            'sku' => ['required', 'string', FieldLimits::max('product.sku'), Rule::unique('products', 'sku')->ignore($product)],
            'model_number' => ['nullable', 'string', FieldLimits::max('product.model_number')],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'short_description' => ['nullable', 'string', FieldLimits::max('product.short_description')],
            'full_description' => ['nullable', 'string', FieldLimits::max('product.full_description')],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string', FieldLimits::max('product.highlight')],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => ['boolean'],
            'show_price' => ['sometimes', 'boolean'],
            'show_stock' => ['sometimes', 'boolean'],
            'regular_price' => ['nullable', 'numeric', 'min:0', 'max:'.FieldLimits::get('number.price')],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:'.FieldLimits::get('number.price'), 'lt:regular_price'],
            'currency' => ['nullable', 'string', 'size:3'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0', 'max:'.FieldLimits::get('number.stock_quantity')],
            'stock_status' => ['required', Rule::in(['in_stock', 'out_of_stock', 'backorder', 'on_order'])],
            'seo_title' => ['nullable', 'string', FieldLimits::max('product.seo_title')],
            'seo_description' => ['nullable', 'string', FieldLimits::max('product.seo_description')],
            'seo_keywords' => ['nullable', 'string', FieldLimits::max('product.seo_keywords')],
            'canonical_url' => ['nullable', 'url:http,https', FieldLimits::max('product.canonical_url')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', Rule::exists('subcategories', 'id')->where('category_id', $this->input('category_id'))],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'related_product_ids' => ['nullable', 'array'],
            'related_product_ids.*' => ['integer', 'exists:products,id'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.name' => ['required_with:specifications', 'string', FieldLimits::max('product.spec_name')],
            'specifications.*.value' => ['required_with:specifications', 'string', FieldLimits::max('product.spec_value')],
            'specifications.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:'.FieldLimits::get('number.sort_order')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already used by another product. If you deleted that product, restore it or delete it permanently from the trash.',
        ];
    }

    /**
     * Readable field names for messages, e.g. "The SKU field is required."
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'sku' => 'SKU',
            'category_id' => 'category',
            'subcategory_id' => 'subcategory',
            'brand_id' => 'brand',
            'stock_status' => 'stock status',
            'status' => 'status',
            'regular_price' => 'regular price',
            'sale_price' => 'sale price',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
        ];
    }
}
