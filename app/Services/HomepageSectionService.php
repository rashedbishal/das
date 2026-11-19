<?php

namespace App\Services;

use App\Models\HomepageSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class HomepageSectionService
{
    public function __construct(private readonly BannerImageService $images) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HomepageSection
    {
        $section = new HomepageSection;

        // Order numbers are unique per type, so an unspecified one goes after the last.
        $data['sort_order'] ??= (int) HomepageSection::query()->where('type', $data['type'])->max('sort_order') + 1;

        return $this->save($section, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(HomepageSection $section, array $data): HomepageSection
    {
        return $this->save($section, $data);
    }

    public function delete(HomepageSection $section): void
    {
        $section->delete();

        $this->images->delete($section->image, $section->image_tablet, $section->image_mobile);
    }

    /**
     * The landscape upload produces the desktop (3:1) and tablet (2:1) renditions; the
     * optional mobile upload (field `portrait_image`) produces the mobile (4:3) one.
     * Without it, mobile is cut from the landscape image, and re-cut whenever the
     * landscape image changes.
     *
     * @param  array<string, mixed>  $data
     */
    private function save(HomepageSection $section, array $data): HomepageSection
    {
        $landscape = $data['landscape_image'] ?? null;
        $portrait = $data['portrait_image'] ?? null;
        $content = (array) ($section->content ?? []);
        $content['show_text'] = (bool) ($data['show_text'] ?? $content['show_text'] ?? true);

        $section->fill(Arr::except($data, ['landscape_image', 'portrait_image', 'show_text', 'content']));

        $replaced = [];
        $written = [];

        try {
            if ($landscape instanceof UploadedFile) {
                $cutMobile = ! $portrait instanceof UploadedFile
                    && ($content['mobile_source'] ?? 'landscape') !== 'portrait';
                $paths = $this->images->renderAll(
                    $landscape,
                    $cutMobile ? ['desktop', 'tablet', 'mobile'] : ['desktop', 'tablet'],
                    'landscape_image',
                );
                $written = [...$written, ...array_values($paths)];

                $replaced[] = $section->image;
                $replaced[] = $section->image_tablet;
                $section->image = $paths['desktop'];
                $section->image_tablet = $paths['tablet'];

                if ($cutMobile) {
                    $replaced[] = $section->image_mobile;
                    $section->image_mobile = $paths['mobile'];
                    $content['mobile_source'] = 'landscape';
                }
            }

            if ($portrait instanceof UploadedFile) {
                $paths = $this->images->renderAll($portrait, ['mobile'], 'portrait_image');
                $written = [...$written, ...array_values($paths)];

                $replaced[] = $section->image_mobile;
                $section->image_mobile = $paths['mobile'];
                $content['mobile_source'] = 'portrait';
            }

            $section->content = $content;
            $section->save();
        } catch (\Throwable $e) {
            // Nothing was saved, so remove the renditions written for this attempt.
            $this->images->delete(...$written);

            throw $e;
        }

        $this->images->delete(...$replaced);

        return $section;
    }
}
