<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMedia;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductMediaService
{
    public const MAX_IMAGES = 10;

    public const MAX_VIDEOS = 2;

    /**
     * Largest source image accepted from an admin. Anything above the target
     * size is resized and re-encoded server-side, so this only guards against
     * absurd uploads (e.g. camera originals), not against "large" ones.
     */
    public const MAX_IMAGE_UPLOAD_SIZE_KB = 10240;

    /** Longest side accepted for a source image, to bound GD's memory use when decoding. */
    public const MAX_IMAGE_SOURCE_DIMENSION = 8000;

    /** Target size for images stored and served by the catalog. */
    public const TARGET_IMAGE_SIZE_KB = 50;

    public const MAX_VIDEO_SIZE_KB = 10240;

    public const REQUIRED_IMAGE_DIMENSION = 1200;

    /** @var array<int, string> */
    /** Accepted image formats; anything that isn't already WebP is converted to WebP. */
    public const IMAGE_FORMATS = ['webp', 'jpg', 'jpeg', 'png'];

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
                'maxSizeKb' => self::MAX_IMAGE_UPLOAD_SIZE_KB,
                'targetSizeKb' => self::TARGET_IMAGE_SIZE_KB,
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
     * Adds an upload or URL as product media, enforcing the per-type limit. The product
     * row is locked while counting so simultaneous requests can't both pass the check.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws ValidationException when the product already has the maximum for this type.
     */
    public function add(Product $product, string $type, ?UploadedFile $file, ?string $url, array $meta = []): ProductMedia
    {
        return DB::transaction(function () use ($product, $type, $file, $url, $meta) {
            Product::query()->whereKey($product->id)->lockForUpdate()->first();

            $max = self::limits()[$type]['max'];

            if ($product->media()->where('type', $type)->count() >= $max) {
                throw ValidationException::withMessages([
                    'file' => "This product already has the maximum of {$max} {$type}s.",
                ]);
            }

            return $file !== null
                ? $this->addUpload($product, $file, $type, $meta)
                : $this->addUrl($product, $type, (string) $url, $meta);
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function addUpload(
        Product $product,
        UploadedFile $file,
        string $type,
        array $meta = [],
        string $errorKey = 'file',
    ): ProductMedia {
        $path = $type === 'image' && $this->needsProcessing($file)
            ? $this->storeProcessedImage($product, $file, $errorKey)
            : $file->store("products/{$product->id}", 'public');

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

    /**
     * An image is stored as-is only when it is already a WebP of the required
     * square size within the target file size; anything else is re-encoded.
     */
    private function needsProcessing(UploadedFile $file): bool
    {
        if ($file->getSize() > self::TARGET_IMAGE_SIZE_KB * 1024 || $file->getMimeType() !== 'image/webp') {
            return true;
        }

        $size = @getimagesize($file->getRealPath());

        return $size === false
            || $size[0] !== self::REQUIRED_IMAGE_DIMENSION
            || $size[1] !== self::REQUIRED_IMAGE_DIMENSION;
    }

    /**
     * Fit into the required square (keeping the aspect ratio, padding with
     * transparency), then re-encode as WebP until it fits the target file size.
     */
    private function storeProcessedImage(Product $product, UploadedFile $file, string $errorKey): string
    {
        // GD holds the decoded bitmap in memory (~4 bytes per pixel), so an
        // 8000px photo needs ~256 MB; lift low limits for this request only.
        $limit = ini_get('memory_limit');
        if ($limit !== '-1' && $this->bytes($limit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        $original = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $original instanceof GdImage) {
            throw ValidationException::withMessages([
                $errorKey => 'This image could not be read. Please upload another image.',
            ]);
        }

        $dimension = self::REQUIRED_IMAGE_DIMENSION;
        $originalWidth = imagesx($original);
        $originalHeight = imagesy($original);

        if ($originalWidth === $dimension && $originalHeight === $dimension) {
            $source = $original;
        } else {
            // Fit the whole photo inside the square without distorting it; the unused
            // band (top/bottom or left/right) is left transparent.
            $fit = min($dimension / $originalWidth, $dimension / $originalHeight);
            $fitWidth = max(1, (int) round($originalWidth * $fit));
            $fitHeight = max(1, (int) round($originalHeight * $fit));

            $source = imagecreatetruecolor($dimension, $dimension);
            imagealphablending($source, false);
            imagesavealpha($source, true);
            imagefill($source, 0, 0, (int) imagecolorallocatealpha($source, 0, 0, 0, 127));
            imagecopyresampled(
                $source,
                $original,
                intdiv($dimension - $fitWidth, 2),
                intdiv($dimension - $fitHeight, 2),
                0,
                0,
                $fitWidth,
                $fitHeight,
                $originalWidth,
                $originalHeight,
            );
            imagedestroy($original);
        }

        $targetBytes = self::TARGET_IMAGE_SIZE_KB * 1024;
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $data = null;

        foreach ([1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4] as $scale) {
            $width = max(1, (int) round($sourceWidth * $scale));
            $height = max(1, (int) round($sourceHeight * $scale));
            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

            foreach ([82, 74, 66, 58, 50, 42, 35, 30] as $quality) {
                ob_start();
                imagewebp($canvas, null, $quality);
                $candidate = (string) ob_get_clean();

                if (strlen($candidate) <= $targetBytes) {
                    $data = $candidate;
                    imagedestroy($canvas);
                    break 2;
                }
            }

            imagedestroy($canvas);
        }

        imagedestroy($source);

        if ($data === null) {
            throw ValidationException::withMessages([
                $errorKey => 'This image could not be compressed below '.self::TARGET_IMAGE_SIZE_KB.' KB. Please upload a simpler image.',
            ]);
        }

        $path = "products/{$product->id}/".Str::uuid().'.webp';
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    /** Converts a php.ini size such as "128M" to bytes. */
    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
