<?php

use App\Support\PublicDiskImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Uploaded category/subcategory images used to be saved as absolute URLs that
 * embedded APP_URL (e.g. "http://192.168.0.111:8000/storage/categories/3/a.jpg"),
 * which break when the domain changes. Rewrite them to relative public-disk paths;
 * external URLs typed in by admins are left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'subcategories'] as $table) {
            DB::table($table)
                ->where('image', 'like', '%/storage/%')
                ->orderBy('id')
                ->each(function (object $row) use ($table) {
                    $relative = PublicDiskImage::toStoredValue($row->image);

                    if ($relative !== $row->image) {
                        DB::table($table)->where('id', $row->id)->update(['image' => $relative]);
                    }
                });
        }
    }

    /**
     * Relative paths still resolve correctly, so there is nothing to undo.
     */
    public function down(): void {}
};
