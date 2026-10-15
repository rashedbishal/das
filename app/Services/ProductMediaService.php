<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductMediaService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function addUpload(Product $product, UploadedFile $file, string $type, array $meta = []): ProductMedia
    {
        $path = $file->store("products/{$product->id}", 'public');

        $media = $product->media()->create([
            'type' => $type,
            'source' => 'upload',
            'disk' => 'public',
            'path' => $path,
            'is_main' => (bool) ($meta['is_main'] ?? false),
            'alt_text' => $meta['alt_text'] ?? null,
            'sort_order' => $meta['sort_order'] ?? ((int) $product->media()->max('sort_order') + 1),
        ]);

        $this->enforceSingleMainImage($product, $media);

        return $media;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function addUrl(Product $product, string $type, string $url, array $meta = []): ProductMedia
    {
        $media = $product->media()->create([
            'type' => $type,
            'source' => 'url',
            'url' => $url,
            'is_main' => (bool) ($meta['is_main'] ?? false),
            'alt_text' => $meta['alt_text'] ?? null,
            'sort_order' => $meta['sort_order'] ?? ((int) $product->media()->max('sort_order') + 1),
        ]);

        $this->enforceSingleMainImage($product, $media);

        return $media;
    }

    public function delete(ProductMedia $media): void
    {
        if ($media->source === 'upload' && $media->disk && $media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }

    private function enforceSingleMainImage(Product $product, ProductMedia $media): void
    {
        if ($media->is_main && $media->type === 'image') {
            $product->media()
                ->whereKeyNot($media->id)
                ->where('type', 'image')
                ->update(['is_main' => false]);
        }
    }
}
