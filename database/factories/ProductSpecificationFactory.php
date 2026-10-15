<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpecification>
 */
class ProductSpecificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Material', 'Weight', 'Dimensions', 'Power Supply', 'Sterilization Method']),
            'value' => fake()->words(2, true),
            'sort_order' => 0,
        ];
    }
}
