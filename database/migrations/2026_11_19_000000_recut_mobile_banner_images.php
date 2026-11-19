<?php

use App\Services\BannerImageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The phone hero went from 4:5 portrait to 4:3 landscape, so existing mobile
 * renditions are re-cut to the new size. The original uploads aren't kept: a
 * mobile image from its own upload is cropped from the old 4:5 rendition (no
 * upscaling), and one cut from the landscape upload is re-cut from the 2:1
 * tablet rendition, which holds more of the picture.
 */
return new class extends Migration
{
    public function up(): void
    {
        $images = app(BannerImageService::class);
        $disk = Storage::disk(BannerImageService::DISK);

        DB::table('homepage_sections')
            ->whereNotNull('image_mobile')
            ->orderBy('id')
            ->each(function (object $section) use ($images, $disk): void {
                $content = json_decode((string) $section->content, true);
                $fromOwnUpload = is_array($content) && ($content['mobile_source'] ?? null) === 'portrait';
                $source = $fromOwnUpload ? $section->image_mobile : ($section->image_tablet ?? $section->image_mobile);

                if (! $disk->exists($source)) {
                    return;
                }

                // A banner that can't be re-cut keeps its current mobile image (it
                // still shows, just cropped), so one bad file never stalls a deploy.
                try {
                    $path = $images->rerender($source, 'mobile');
                } catch (Throwable $e) {
                    Log::warning("Could not re-cut the mobile image of banner {$section->id}: {$e->getMessage()}");

                    return;
                }

                DB::table('homepage_sections')->where('id', $section->id)->update(['image_mobile' => $path]);
                $images->delete($section->image_mobile);
            });
    }

    /** The 4:5 renditions were replaced, so there is nothing to restore. */
    public function down(): void {}
};
