<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports the storefront listing query: published, not soft-deleted, newest first
 * (WHERE status = 'published' AND deleted_at IS NULL ORDER BY created_at DESC).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'deleted_at', 'created_at'], 'products_listing_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_listing_index');
        });
    }
};
