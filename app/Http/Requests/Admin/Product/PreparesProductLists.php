<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;
use App\Support\FieldLimits;
use Illuminate\Validation\Rule;

/**
 * Rules for the product form's repeatable lists (highlights, specifications and
 * variants), shared by the create and update requests.
 *
 * The form always offers an empty row to type into, so rows left completely
 * empty are dropped before validation instead of failing it. Row keys are kept,
 * so errors still point at the row the admin sees ("variants.2.name").
 */
trait PreparesProductLists
{
    protected function prepareForValidation(): void
    {
        $filled = fn (mixed $value): bool => trim((string) (is_scalar($value) ? $value : '')) !== '';
        $lists = [];

        if (is_array($highlights = $this->input('highlights'))) {
            $lists['highlights'] = array_filter($highlights, $filled);
        }

        if (is_array($specifications = $this->input('specifications'))) {
            $lists['specifications'] = array_filter(
                $specifications,
                fn ($spec) => ! is_array($spec) || $filled($spec['name'] ?? null) || $filled($spec['value'] ?? null),
            );
        }

        if (is_array($variants = $this->input('variants'))) {
            $lists['variants'] = array_filter(
                $variants,
                fn ($variant) => ! is_array($variant)
                    || $filled($variant['id'] ?? null)
                    || $filled($variant['name'] ?? null)
                    || $filled($variant['sku'] ?? null)
                    || $filled($variant['price'] ?? null),
            );
        }

        $this->merge($lists);
    }

    /**
     * @return array<string, mixed>
     */
    protected function listRules(?Product $product): array
    {
        return [
            'highlights' => ['nullable', 'array', 'max:20'],
            'highlights.*' => ['string', FieldLimits::max('product.highlight')],
            'specifications' => ['nullable', 'array', 'max:50'],
            'specifications.*' => ['array'],
            'specifications.*.name' => ['required', 'string', FieldLimits::max('product.spec_name')],
            'specifications.*.value' => ['required', 'string', FieldLimits::max('product.spec_value')],
            'specifications.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:'.FieldLimits::get('number.sort_order')],
            'variants' => ['nullable', 'array', 'max:50'],
            'variants.*' => ['array'],
            // Only this product's own variants can be updated by id; on create there are none.
            'variants.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id ?? 0),
            ],
            'variants.*.name' => ['required', 'string', FieldLimits::max('product.variant_name')],
            // Unique among other products' variants; this product's own rows may swap or
            // reuse SKUs, which the service handles. "distinct" catches repeats in the form.
            'variants.*.sku' => [
                'nullable',
                'string',
                FieldLimits::max('product.sku'),
                'distinct:ignore_case',
                Rule::unique('product_variants', 'sku')->where(fn ($query) => $query->where('product_id', '!=', $product->id ?? 0)),
            ],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:'.FieldLimits::get('number.price')],
            'variants.*.stock_status' => ['required', Rule::in(['in_stock', 'out_of_stock', 'backorder', 'on_order'])],
            'variants.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Readable names, e.g. "The option name field is required."
     *
     * @return array<string, string>
     */
    protected function listAttributes(): array
    {
        return [
            'highlights.*' => 'highlight',
            'specifications.*.name' => 'specification name',
            'specifications.*.value' => 'specification value',
            'variants.*.name' => 'option name',
            'variants.*.sku' => 'option SKU',
            'variants.*.price' => 'option price',
            'variants.*.stock_status' => 'option stock status',
            'canonical_url' => 'canonical URL',
            'related_product_ids.*' => 'related product',
        ];
    }
}
