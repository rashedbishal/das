<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductMedia>
 */
class ProductMediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => 'image',
            'source' => 'upload',
            'disk' => 'public',
            'path' => 'products/'.fake()->uuid().'.jpg',
            'url' => null,
            'is_main' => false,
            'alt_text' => fake()->sentence(3),
            'sort_order' => 0,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
            'sort_order' => 0,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'source' => 'url',
            'disk' => null,
            'path' => null,
            'url' => 'https://www.youtube.com/watch?v='.fake()->lexify('???????????'),
        ]);
    }
}
