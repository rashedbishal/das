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

    /** @var array<string, array{0: int, 1: int}> */
    public const SIZES = [
        'desktop' => [1920, 640],
        'tablet' => [1536, 768],
        'mobile' => [1080, 1350],
    ];

    /**
     * @param  'desktop'|'tablet'|'mobile'  $device
     */
    public function render(UploadedFile $file, string $device, string $field): string
    {
        [$width, $height] = self::SIZES[$device];

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source instanceof GdImage) {
            throw ValidationException::withMessages([$field => 'This image could not be read. Upload a JPG, PNG or WebP file.']);
        }

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

    public static function url(?string $path): ?string
    {
        return $path === null ? null : Storage::disk(self::DISK)->url($path);
    }
}
