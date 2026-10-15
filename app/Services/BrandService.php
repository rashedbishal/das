<?php

namespace App\Services;

use App\Models\Brand;
use App\Support\SlugGenerator;
use Illuminate\Validation\ValidationException;

class BrandService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('brands', $data['slug'])
            : SlugGenerator::unique('brands', $data['name']);

        return Brand::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('brands', $data['slug'], $brand->id)
            : $brand->slug;

        $brand->update($data);

        return $brand;
    }

    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw ValidationException::withMessages(['brand' => 'This brand still has products assigned and cannot be deleted.']);
        }

        $brand->delete();
    }
}
