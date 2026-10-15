<?php

namespace App\Services;

use App\Models\Category;
use App\Support\SlugGenerator;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('categories', $data['slug'])
            : SlugGenerator::unique('categories', $data['name']);

        return Category::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        if (array_key_exists('parent_id', $data) && $data['parent_id'] === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
        }

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('categories', $data['slug'], $category->id)
            : $category->slug;

        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category still has subcategories and cannot be deleted.']);
        }

        if ($category->products()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category still has products assigned and cannot be deleted.']);
        }

        $category->delete();
    }
}
