<?php

namespace App\Services;

use App\Models\Brand;
use App\Support\PublicDiskImage;
use App\Support\SlugGenerator;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrandService
{
    /** Largest logo file an admin may upload. */
    public const MAX_LOGO_UPLOAD_SIZE_KB = 500;

    /** Stored logos are WebP at most this size; larger uploads are compressed down to it. */
    public const TARGET_LOGO_SIZE_KB = 50;

    /** Logos are scaled down (never up) to fit this box, keeping their shape. */
    public const MAX_LOGO_WIDTH = 800;

    public const MAX_LOGO_HEIGHT = 400;

    /** Largest source image accepted, so decoding stays within memory. */
    public const MAX_LOGO_SOURCE_DIMENSION = 4000;

    /** @var list<string> */
    public const LOGO_FORMATS = ['webp', 'png', 'jpg', 'jpeg'];

    /**
     * Upload rules for the admin form.
     *
     * @return array{maxSizeKb: int, targetSizeKb: int, maxWidth: int, maxHeight: int}
     */
    public static function logoLimits(): array
    {
        return [
            'maxSizeKb' => self::MAX_LOGO_UPLOAD_SIZE_KB,
            'targetSizeKb' => self::TARGET_LOGO_SIZE_KB,
            'maxWidth' => self::MAX_LOGO_WIDTH,
            'maxHeight' => self::MAX_LOGO_HEIGHT,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        $logoFile = Arr::pull($data, 'logo_file');
        unset($data['remove_logo']);

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('brands', $data['slug'])
            : SlugGenerator::unique('brands', $data['name']);

        $storedPath = null;

        try {
            return DB::transaction(function () use ($data, $logoFile, &$storedPath) {
                $brand = Brand::query()->create($data);

                if ($logoFile instanceof UploadedFile && $logoFile->isValid()) {
                    $storedPath = $this->storeLogo($brand, $logoFile);
                    $brand->update(['logo' => $storedPath]);
                }

                return $brand;
            });
        } catch (\Throwable $e) {
            $this->deleteStoredLogo($storedPath);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        $logoFile = Arr::pull($data, 'logo_file');
        $removeLogo = (bool) Arr::pull($data, 'remove_logo', false);
        $previousLogo = $brand->getRawOriginal('logo');

        $data['slug'] = ! empty($data['slug'])
            ? SlugGenerator::unique('brands', $data['slug'], $brand->id)
            : $brand->slug;

        $storedPath = null;

        if ($logoFile instanceof UploadedFile && $logoFile->isValid()) {
            $storedPath = $this->storeLogo($brand, $logoFile);
            $data['logo'] = $storedPath;
        } elseif ($removeLogo) {
            $data['logo'] = null;
        }

        try {
            $brand->update($data);
        } catch (\Throwable $e) {
            $this->deleteStoredLogo($storedPath);

            throw $e;
        }

        if ($previousLogo !== $brand->getRawOriginal('logo')) {
            $this->deleteStoredLogo($previousLogo);
        }

        return $brand;
    }

    public function delete(Brand $brand): void
    {
        if ($brand->products()->exists()) {
            throw ValidationException::withMessages(['brand' => 'This brand still has products assigned and cannot be deleted.']);
        }

        // Soft delete: the logo file stays with the (restorable) record.
        $brand->delete();
    }

    /**
     * Stores the logo as WebP within TARGET_LOGO_SIZE_KB. A WebP that already
     * fits the size and the box is kept byte for byte; anything else is scaled
     * to fit the box and re-encoded, stepping quality (then size) down until it
     * fits, so a large upload may lose some quality.
     *
     * @return string The file's path on the public disk.
     */
    private function storeLogo(Brand $brand, UploadedFile $file): string
    {
        $path = "brands/{$brand->id}/".Str::uuid().'.webp';
        $contents = (string) file_get_contents($file->getRealPath());

        if (! $this->logoNeedsProcessing($file)) {
            $data = $contents;
        } else {
            $data = $this->compressLogo($contents);
        }

        if (! Storage::disk('public')->put($path, $data)) {
            throw new \RuntimeException('Failed to store the uploaded brand logo.');
        }

        return $path;
    }

    private function logoNeedsProcessing(UploadedFile $file): bool
    {
        if ($file->getSize() > self::TARGET_LOGO_SIZE_KB * 1024 || $file->getMimeType() !== 'image/webp') {
            return true;
        }

        $size = @getimagesize($file->getRealPath());

        return $size === false || $size[0] > self::MAX_LOGO_WIDTH || $size[1] > self::MAX_LOGO_HEIGHT;
    }

    private function compressLogo(string $contents): string
    {
        $original = @imagecreatefromstring($contents);

        if (! $original instanceof GdImage) {
            throw ValidationException::withMessages([
                'logo_file' => 'This image could not be read. Please upload another image.',
            ]);
        }

        $originalWidth = imagesx($original);
        $originalHeight = imagesy($original);
        $fit = min(1, self::MAX_LOGO_WIDTH / $originalWidth, self::MAX_LOGO_HEIGHT / $originalHeight);
        $targetBytes = self::TARGET_LOGO_SIZE_KB * 1024;
        $data = null;

        foreach ([1, 0.85, 0.7, 0.55, 0.4] as $scale) {
            $width = max(1, (int) round($originalWidth * $fit * $scale));
            $height = max(1, (int) round($originalHeight * $fit * $scale));

            // Keep transparency: logos usually sit on a transparent background.
            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagecopyresampled($canvas, $original, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

            $data = $this->encodeWebpWithin($canvas, $targetBytes);
            imagedestroy($canvas);

            if ($data !== null) {
                break;
            }
        }

        imagedestroy($original);

        if ($data === null) {
            throw ValidationException::withMessages([
                'logo_file' => 'This logo could not be compressed below '.self::TARGET_LOGO_SIZE_KB.' KB. Please upload a simpler or smaller image.',
            ]);
        }

        return $data;
    }

    /** The highest-quality WebP encoding of the image that fits in $maxBytes, if any. */
    private function encodeWebpWithin(GdImage $image, int $maxBytes): ?string
    {
        foreach ([90, 82, 74, 66, 58, 50, 42] as $quality) {
            ob_start();
            imagewebp($image, null, $quality);
            $candidate = (string) ob_get_clean();

            if (strlen($candidate) <= $maxBytes) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Deletes the file behind a stored value, but only if it is an upload we
     * manage (never an older external URL).
     */
    private function deleteStoredLogo(?string $storedValue): void
    {
        if (PublicDiskImage::isStoredPath($storedValue)) {
            Storage::disk('public')->delete((string) $storedValue);
        }
    }
}
