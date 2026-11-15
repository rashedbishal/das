<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\FieldLimits;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds and removes product stock from Admin -> Stock Management, keeping a
 * per-product history in stock_movements.
 */
class StockService
{
    public function __construct(private readonly ActivityLogService $activity) {}

    /**
     * Applies one stock change under a row lock, so two admins adjusting the same
     * product at once cannot lose an update or push the quantity below zero.
     *
     * The stock status follows the quantity between "in stock" and "out of stock"
     * only: reaching 0 marks an in-stock product out of stock, and adding to an
     * out-of-stock product marks it in stock. Backorder and on-order are kept.
     *
     * @param  'add'|'remove'  $type
     *
     * @throws ValidationException when removing more than is in stock, or adding past the maximum
     */
    public function adjust(User $actor, Product $product, string $type, int $quantity, ?string $note = null): StockMovement
    {
        return DB::transaction(function () use ($actor, $product, $type, $quantity, $note) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = $locked->stock_quantity;

            if ($type === 'remove' && $quantity > $before) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$before} in stock; you cannot remove {$quantity}.",
                ]);
            }

            $after = $type === 'add' ? $before + $quantity : $before - $quantity;
            $maximum = FieldLimits::get('number.stock_quantity');

            if ($after > $maximum) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock cannot exceed '.number_format($maximum).'.',
                ]);
            }

            $status = match (true) {
                $after === 0 && $locked->stock_status === 'in_stock' => 'out_of_stock',
                $after > 0 && $locked->stock_status === 'out_of_stock' => 'in_stock',
                default => $locked->stock_status,
            };

            // Logged once below as a stock change, not also as a generic product update.
            activity()->withoutLogs(fn () => $locked->forceFill([
                'stock_quantity' => $after,
                'stock_status' => $status,
                'updated_by' => $actor->id,
            ])->save());

            $movement = $locked->stockMovements()->create([
                'user_id' => $actor->id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'note' => $note,
            ]);

            $verb = $type === 'add' ? 'Added' : 'Removed';
            $this->activity->log(
                $type === 'add' ? 'product.stock_added' : 'product.stock_removed',
                $locked,
                "{$verb} {$quantity} stock for {$locked->name} ({$before} → {$after})",
                ['quantity' => $quantity, 'before' => $before, 'after' => $after, 'note' => $note, 'stock_status' => $status],
            );

            $product->setRawAttributes($locked->getAttributes(), true);

            return $movement;
        });
    }
}
