<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * These columns were VARCHAR(255) while their form requests accept up to 500 or 2048
 * characters, so valid admin input failed on MySQL with "Data too long" (a 500 error).
 * Widen each column to the length its validation already allows.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, int>>
     */
    private array $columns = [
        'products' => ['short_description' => 500, 'seo_description' => 500, 'canonical_url' => 2048],
        'product_media' => ['url' => 2048],
        'homepage_sections' => ['link_url' => 2048],
        'brands' => ['logo' => 2048, 'website_url' => 2048],
        'categories' => ['image' => 2048],
        'subcategories' => ['image' => 2048],
    ];

    public function up(): void
    {
        $this->resize(fn (int $length) => $length);
    }

    /**
     * Rolling back fails on MySQL if any row holds a value longer than 255 characters.
     */
    public function down(): void
    {
        $this->resize(fn () => 255);
    }

    /**
     * @param  callable(int): int  $length
     */
    private function resize(callable $length): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $length) {
                foreach ($columns as $column => $size) {
                    $blueprint->string($column, $length($size))->nullable()->change();
                }
            });
        }
    }
};
