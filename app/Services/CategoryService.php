<?php

namespace App\Services;

use App\Models\Category;
use App\Support\PublicDiskImage;
use App\Support\SlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

        $storedPath = null;

        try {
            return DB::transaction(function () use ($data, $imageFile, &$storedPath) {
                $category = Category::query()->create($data);

                if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                    $storedPath = $this->storeImage($category, $imageFile);
                    $category->update(['image' => $storedPath]);
                }

                return $category;
            });
        } catch (\Throwable $e) {
            $this->deleteStoredImage($storedPath);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $imageFile = Arr::pull($data, 'image_file');
        $previousImage = $category->getRawOriginal('image');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('categories', $data['slug'], $category->id)
            : $category->slug;

        $storedPath = null;

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $storedPath = $this->storeImage($category, $imageFile);
            $data['image'] = $storedPath;
        }

        try {
            $category->update($data);
        } catch (\Throwable $e) {
            $this->deleteStoredImage($storedPath);

            throw $e;
        }

        if ($previousImage !== $category->getRawOriginal('image')) {
            $this->deleteStoredImage($previousImage);
        }

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

        $this->deleteStoredImage($category->getRawOriginal('image'));

        $category->delete();
    }

    /**
     * @return string The file's path on the public disk.
     */
    private function storeImage(Category $category, UploadedFile $file): string
    {
        $path = $file->store("categories/{$category->id}", 'public');

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded category image.');
        }

        return $path;
    }

    /**
     * Deletes the file behind a stored value, but only if it is an upload we manage
     * (never an external URL an admin typed in).
     */
    private function deleteStoredImage(?string $storedValue): void
    {
        if (PublicDiskImage::isStoredPath($storedValue)) {
            Storage::disk('public')->delete((string) $storedValue);
        }
    }
}
