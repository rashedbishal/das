<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->string('image_tablet')->nullable()->after('image');
            $table->string('image_mobile')->nullable()->after('image_tablet');
            $table->string('link_label')->nullable()->after('link_url');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropColumn(['image_tablet', 'image_mobile', 'link_label']);
        });
    }
};
