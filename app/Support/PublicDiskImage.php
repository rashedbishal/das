<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * An image column that holds either an uploaded file's path on the public disk
 * (e.g. "categories/3/photo.jpg") or an external/absolute URL typed by an admin.
 *
 * Uploaded files are stored as relative paths so they survive an APP_URL or domain
 * change, and resolved to a full URL when read. Writing back a URL that points at
 * our own public disk (e.g. an edit form resubmitting the resolved value) stores
 * the relative path again.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class PublicDiskImage implements CastsAttributes
{
    /** Directories on the public disk that hold uploaded catalogue images. */
    public const DIRECTORIES = ['categories', 'subcategories', 'brands'];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::url(is_string($value) ? $value : null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::toStoredValue(is_string($value) ? $value : null);
    }

    public static function url(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::isStoredPath($value) ? Storage::disk('public')->url($value) : $value;
    }

    /**
     * Converts a public-disk URL (for any host, so values saved under an old APP_URL
     * are recovered too) into its relative path; anything else is kept as given.
     */
    public static function toStoredValue(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $directories = implode('|', self::DIRECTORIES);

        if (preg_match('#^(?:https?://[^/]+)?/storage/((?:'.$directories.')/.+)$#', $value, $matches) === 1) {
            return $matches[1];
        }

        return $value;
    }

    /** Whether the value is a relative path to an uploaded file we manage. */
    public static function isStoredPath(?string $value): bool
    {
        if ($value === null || str_contains($value, '://') || str_starts_with($value, '/')) {
            return false;
        }

        foreach (self::DIRECTORIES as $directory) {
            if (str_starts_with($value, $directory.'/')) {
                return true;
            }
        }

        return false;
    }
}
