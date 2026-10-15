<?php

namespace App\Services;

use App\Models\Subcategory;
use App\Support\SlugGenerator;
use Illuminate\Validation\ValidationException;

class SubcategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Subcategory
    {
        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('subcategories', $data['slug'])
            : SlugGenerator::unique('subcategories', $data['name']);

        return Subcategory::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subcategory $subcategory, array $data): Subcategory
    {
        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('subcategories', $data['slug'], $subcategory->id)
            : $subcategory->slug;

        $subcategory->update($data);

        return $subcategory;
    }

    public function delete(Subcategory $subcategory): void
    {
        if ($subcategory->products()->exists()) {
            throw ValidationException::withMessages(['subcategory' => 'This subcategory still has products assigned and cannot be deleted.']);
        }

        $subcategory->delete();
    }
}
