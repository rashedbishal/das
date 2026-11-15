<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per day (in the site's timezone) holding the number of storefront page
 * views, incremented by App\Http\Middleware\CountPageViews.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_page_views', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_page_views');
    }
};
