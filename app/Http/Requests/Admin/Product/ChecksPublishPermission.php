<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product;
use Illuminate\Validation\Validator;

/**
 * Changing a product's status (publishing, archiving or moving it back to draft) needs
 * products.publish, the same ability bulk publish/draft actions check. Without it an
 * editor can still save a product as long as its status stays what it was (or draft,
 * for a new product).
 */
trait ChecksPublishPermission
{
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');
                $currentStatus = $product instanceof Product ? $product->status : 'draft';
                $status = $this->input('status');

                if (! is_string($status) || $status === $currentStatus) {
                    return;
                }

                $allowed = $product instanceof Product
                    ? $this->user()?->can('publish', $product)
                    : $this->user()?->hasPermission('products.publish');

                if (! $allowed) {
                    $validator->errors()->add('status', 'You do not have permission to change a product\'s publish status.');
                }
            },
        ];
    }
}
