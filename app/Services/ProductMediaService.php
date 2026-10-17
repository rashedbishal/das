<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductMediaService
{
    public const MAX_IMAGES = 10;

    public const MAX_VIDEOS = 2;

    public const MAX_IMAGE_SIZE_KB = 500;

    public const MAX_VIDEO_SIZE_KB = 20480;

    public const REQUIRED_IMAGE_DIMENSION = 1200;

    /** @var array<int, string> */
    public const IMAGE_FORMATS = ['webp'];

    /** @var array<int, string> */
    public const VIDEO_FORMATS = ['mp4', 'mov'];

    public const MAX_VIDEO_SECONDS = 60;

    /**
     * Limits and format rules shared by the upload form (client-side hints)
     * and the server-side validation that actually enforces them.
     *
     * @return array<string, mixed>
     */
    public static function limits(): array
    {
        return [
            'image' => [
                'max' => self::MAX_IMAGES,
                'maxSizeKb' => self::MAX_IMAGE_SIZE_KB,
                'requiredDimension' => self::REQUIRED_IMAGE_DIMENSION,
                'formats' => self::IMAGE_FORMATS,
            ],
            'video' => [
                'max' => self::MAX_VIDEOS,
                'maxSizeKb' => self::MAX_VIDEO_SIZE_KB,
                'maxSeconds' => self::MAX_VIDEO_SECONDS,
                'formats' => self::VIDEO_FORMATS,
            ],
        ];
    }

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

        $this->applyMainImageRules($product, $media);

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

        $this->applyMainImageRules($product, $media);

        return $media;
    }

    public function setMain(ProductMedia $media): void
    {
        if ($media->type !== 'image') {
            return;
        }

        $media->product->media()
            ->where('type', 'image')
            ->whereKeyNot($media->id)
            ->update(['is_main' => false]);

        $media->update(['is_main' => true]);
    }

    public function delete(ProductMedia $media): void
    {
        if ($media->source === 'upload' && $media->disk && $media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }

    /**
     * The requested image becomes main if flagged explicitly, or automatically
     * when it's the product's first image — otherwise every image stays a
     * gallery item until someone picks a main image.
     */
    private function applyMainImageRules(Product $product, ProductMedia $media): void
    {
        if ($media->type !== 'image') {
            return;
        }

        $isFirstImage = $product->media()->where('type', 'image')->count() === 1;

        if (! $media->is_main && ! $isFirstImage) {
            return;
        }

        $product->media()
            ->whereKeyNot($media->id)
            ->where('type', 'image')
            ->update(['is_main' => false]);

        if (! $media->is_main) {
            $media->update(['is_main' => true]);
        }
    }
}
