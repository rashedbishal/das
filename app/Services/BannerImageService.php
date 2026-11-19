<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns a raw uploaded image into a banner rendition: centre-cropped to the
 * device's aspect ratio, resized to its exact pixel size and encoded as WebP.
 */
class BannerImageService
{
    public const DISK = 'public';

    public const DIRECTORY = 'banners';

    public const QUALITY = 82;

    /** Longest side accepted for an upload, to bound GD's memory use when decoding. */
    public const MAX_SOURCE_DIMENSION = 8000;

    /** @var array<string, array{0: int, 1: int}> */
    public const SIZES = [
        'desktop' => [1920, 640],
        'tablet' => [1536, 768],
        'mobile' => [1080, 810],
    ];

    /**
     * Decodes the upload once and writes one rendition per device. If any rendition
     * fails, the ones already written are deleted before the error is rethrown.
     *
     * @param  array<int, 'desktop'|'tablet'|'mobile'>  $devices
     * @return array<string, string> Stored path keyed by device.
     */
    public function renderAll(UploadedFile $file, array $devices, string $field): array
    {
        // GD holds the decoded bitmap in memory (~4 bytes per pixel), so an 8000px
        // image needs ~256 MB; lift low limits for this request only.
        $limit = (string) ini_get('memory_limit');
        if ($limit !== '-1' && $this->bytes($limit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages([$field => 'This image could not be read. Upload a JPG, PNG or WebP file.']);
        }

        $paths = [];

        try {
            foreach ($devices as $device) {
                $paths[$device] = $this->renderFrom($source, $device);
            }
        } catch (\Throwable $e) {
            $this->delete(...array_values($paths));

            throw $e;
        } finally {
            imagedestroy($source);
        }

        return $paths;
    }

    /**
     * @param  'desktop'|'tablet'|'mobile'  $device
     */
    public function render(UploadedFile $file, string $device, string $field): string
    {
        return $this->renderAll($file, [$device], $field)[$device];
    }

    /**
     * Renders a new rendition from an already stored image, e.g. to re-cut
     * existing banners after a device size changes. Returns the new path.
     *
     * @param  'desktop'|'tablet'|'mobile'  $device
     */
    public function rerender(string $path, string $device): string
    {
        $source = @imagecreatefromstring((string) Storage::disk(self::DISK)->get($path));

        if (! $source instanceof GdImage) {
            throw new \RuntimeException("Banner image [{$path}] could not be read.");
        }

        try {
            return $this->renderFrom($source, $device);
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * @param  'desktop'|'tablet'|'mobile'  $device
     */
    private function renderFrom(GdImage $source, string $device): string
    {
        [$width, $height] = self::SIZES[$device];

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $ratio = $width / $height;

        if ($sourceWidth / $sourceHeight > $ratio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $ratio);
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $ratio);
        }

        $canvas = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            intdiv($sourceWidth - $cropWidth, 2),
            intdiv($sourceHeight - $cropHeight, 2),
            $width,
            $height,
            $cropWidth,
            $cropHeight,
        );

        ob_start();
        imagewebp($canvas, null, self::QUALITY);
        $data = (string) ob_get_clean();
        imagedestroy($canvas);

        $path = self::DIRECTORY.'/'.Str::uuid()."-{$device}.webp";
        Storage::disk(self::DISK)->put($path, $data);

        return $path;
    }

    public function delete(?string ...$paths): void
    {
        $paths = array_filter($paths, fn (?string $path) => $path !== null && str_starts_with($path, self::DIRECTORY.'/'));

        if ($paths !== []) {
            Storage::disk(self::DISK)->delete(array_values($paths));
        }
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

    public static function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
