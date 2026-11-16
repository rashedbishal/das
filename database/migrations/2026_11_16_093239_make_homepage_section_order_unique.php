<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each banner (and each section) gets its own order number. Existing duplicates
     * are resolved by moving the later-created one to the next free number for its
     * type; every other row keeps the number an admin gave it.
     */
    public function up(): void
    {
        foreach (DB::table('homepage_sections')->distinct()->pluck('type') as $type) {
            $rows = DB::table('homepage_sections')->where('type', $type)->orderBy('sort_order')->orderBy('id')->get(['id', 'sort_order']);
            $taken = [];
            $next = (int) $rows->max('sort_order') + 1;

            foreach ($rows as $row) {
                if (isset($taken[$row->sort_order])) {
                    DB::table('homepage_sections')->where('id', $row->id)->update(['sort_order' => $next]);
                    $taken[$next++] = true;

                    continue;
                }

                $taken[$row->sort_order] = true;
            }
        }

        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->unique(['type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropUnique(['type', 'sort_order']);
        });
    }
};
