<?php

namespace Database\Factories;

use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageSection>
 */
class HomepageSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'banner',
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(),
            'image' => null,
            'link_url' => null,
            'content' => null,
            'sort_order' => fake()->unique()->numberBetween(1, 1_000_000),
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
