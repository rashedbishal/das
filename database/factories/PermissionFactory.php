<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $group = fake()->randomElement(['products', 'categories', 'brands', 'tags']);
        $name = fake()->unique()->word();

        return [
            'name' => "{$group}.{$name}",
            'slug' => Str::slug("{$group}-{$name}"),
            'group' => $group,
        ];
    }
}
