<?php

namespace App\Services;

use App\Models\Subcategory;
use App\Support\PublicDiskImage;
use App\Support\SlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubcategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Subcategory
    {
        $imageFile = Arr::pull($data, 'image_file');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('subcategories', $data['slug'])
            : SlugGenerator::unique('subcategories', $data['name']);

        $storedPath = null;

        try {
            return DB::transaction(function () use ($data, $imageFile, &$storedPath) {
                $subcategory = Subcategory::query()->create($data);

                if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                    $storedPath = $this->storeImage($subcategory, $imageFile);
                    $subcategory->update(['image' => $storedPath]);
                }

                return $subcategory;
            });
        } catch (\Throwable $e) {
            $this->deleteStoredImage($storedPath);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subcategory $subcategory, array $data): Subcategory
    {
        $imageFile = Arr::pull($data, 'image_file');
        $previousImage = $subcategory->getRawOriginal('image');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('subcategories', $data['slug'], $subcategory->id)
            : $subcategory->slug;

        $storedPath = null;

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $storedPath = $this->storeImage($subcategory, $imageFile);
            $data['image'] = $storedPath;
        }

        try {
            $subcategory->update($data);
        } catch (\Throwable $e) {
            $this->deleteStoredImage($storedPath);

            throw $e;
        }

        if ($previousImage !== $subcategory->getRawOriginal('image')) {
            $this->deleteStoredImage($previousImage);
        }

        return $subcategory;
    }

    public function delete(Subcategory $subcategory): void
    {
        if ($subcategory->products()->exists()) {
            throw ValidationException::withMessages(['subcategory' => 'This subcategory still has products assigned and cannot be deleted.']);
        }

        $this->deleteStoredImage($subcategory->getRawOriginal('image'));

        $subcategory->delete();
    }

    /**
     * @return string The file's path on the public disk.
     */
    private function storeImage(Subcategory $subcategory, UploadedFile $file): string
    {
        $path = $file->store("subcategories/{$subcategory->id}", 'public');

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded subcategory image.');
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
