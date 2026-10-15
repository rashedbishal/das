<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugGenerator
{
    public static function unique(string $table, string $base, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $slug = Str::slug($base);
        $slug = $slug !== '' ? $slug : Str::random(8);
        $original = $slug;
        $suffix = 1;

        while (
            DB::table($table)
                ->where($column, $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$original}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
