<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Maximum lengths (and a few numeric maxima) for CMS fields.
 *
 * The values live in resources/js/lib/field-limits.json so the admin forms
 * (maxlength + character counters) and the server-side validation read the
 * same numbers. They are sized for how the storefront displays each field.
 */
class FieldLimits
{
    /** @var array<string, array<string, int>>|null */
    private static ?array $limits = null;

    /** e.g. FieldLimits::get('product.name') */
    public static function get(string $key): int
    {
        [$group, $field] = explode('.', $key, 2) + [1 => ''];

        return self::all()[$group][$field]
            ?? throw new InvalidArgumentException("Unknown field limit [{$key}].");
    }

    /** The `max:N` validation rule for a field. */
    public static function max(string $key): string
    {
        return 'max:'.self::get($key);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public static function all(): array
    {
        if (self::$limits === null) {
            /** @var array<string, array<string, int>> $limits */
            $limits = json_decode(
                (string) file_get_contents(resource_path('js/lib/field-limits.json')),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            self::$limits = $limits;
        }

        return self::$limits;
    }
}
