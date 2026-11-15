<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional search-engine title and description for category, subcategory and brand
 * pages (products already have these). Empty values fall back to the name and
 * description on the storefront.
 */
return new class extends Migration
{
    private const TABLES = ['categories', 'subcategories', 'brands'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('seo_title', 70)->nullable();
                $table->string('seo_description', 170)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['seo_title', 'seo_description']);
            });
        }
    }
};
