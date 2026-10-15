<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video']);
            $table->enum('source', ['upload', 'url']);
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_main')->default(false);
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
