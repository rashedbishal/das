<?php

namespace App\Services;

use App\Models\Subcategory;
use App\Support\SlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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

        $subcategory = Subcategory::query()->create($data);

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $subcategory->update(['image' => $this->storeImage($subcategory, $imageFile)]);
        }

        return $subcategory;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subcategory $subcategory, array $data): Subcategory
    {
        $imageFile = Arr::pull($data, 'image_file');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('subcategories', $data['slug'], $subcategory->id)
            : $subcategory->slug;

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $this->deleteStoredImage($subcategory->image);
            $data['image'] = $this->storeImage($subcategory, $imageFile);
        }

        $subcategory->update($data);

        return $subcategory;
    }

    public function delete(Subcategory $subcategory): void
    {
        if ($subcategory->products()->exists()) {
            throw ValidationException::withMessages(['subcategory' => 'This subcategory still has products assigned and cannot be deleted.']);
        }

        $this->deleteStoredImage($subcategory->image);

        $subcategory->delete();
    }

    private function storeImage(Subcategory $subcategory, UploadedFile $file): string
    {
        $path = $file->store("subcategories/{$subcategory->id}", 'public');

        if ($path === false) {
            throw new \RuntimeException('Failed to store the uploaded subcategory image.');
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
