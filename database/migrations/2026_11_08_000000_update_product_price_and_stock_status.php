<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('regular_price', 12, 2)->nullable()->change();
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder', 'on_order'])->default('in_stock')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('regular_price', 12, 2)->nullable(false)->change();
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder'])->default('in_stock')->change();
        });
    }
};
