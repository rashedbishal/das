<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = implode(' ', (array) fake()->unique()->words(3));
        $regularPrice = fake()->randomFloat(2, 500, 50000);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-???')),
            'model_number' => strtoupper(fake()->bothify('MDL-###')),
            'brand_id' => Brand::factory(),
            'short_description' => fake()->sentence(),
            'full_description' => fake()->paragraphs(3, true),
            'highlights' => fake()->sentences(3),
            'status' => 'draft',
            'is_featured' => false,
            'regular_price' => $regularPrice,
            'sale_price' => null,
            'currency' => 'BDT',
            'stock_quantity' => fake()->numberBetween(0, 500),
            'stock_status' => 'in_stock',
            'seo_title' => $name,
            'seo_description' => fake()->sentence(),
            'seo_keywords' => implode(', ', (array) fake()->words(5)),
            'canonical_url' => null,
            'created_by' => null,
            'updated_by' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
        ]);
    }
}
