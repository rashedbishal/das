<?php

namespace App\Services;

use App\Models\Category;
use App\Support\SlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $imageFile = Arr::pull($data, 'image_file');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('categories', $data['slug'])
            : SlugGenerator::unique('categories', $data['name']);

        $category = Category::query()->create($data);

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $category->update(['image' => $this->storeImage($category, $imageFile)]);
        }

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $imageFile = Arr::pull($data, 'image_file');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('categories', $data['slug'], $category->id)
            : $category->slug;

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $this->deleteStoredImage($category->image);
            $data['image'] = $this->storeImage($category, $imageFile);
        }

        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->subcategories()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category still has subcategories and cannot be deleted.']);
        }

        if ($category->products()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category still has products assigned and cannot be deleted.']);
        }

        $this->deleteStoredImage($category->image);

        $category->delete();
    }

    private function storeImage(Category $category, UploadedFile $file): string
    {
        $path = $file->store("categories/{$category->id}", 'public');

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded category image.');
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredImage(?string $image): void
    {
        $prefix = Storage::disk('public')->url('');

        if ($image && str_starts_with($image, $prefix)) {
            Storage::disk('public')->delete(substr($image, strlen($prefix)));
        }
    }
}
