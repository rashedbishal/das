<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $before = fake()->numberBetween(0, 100);
        $quantity = fake()->numberBetween(1, 20);

        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'type' => 'add',
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $before + $quantity,
            'note' => null,
        ];
    }
}
