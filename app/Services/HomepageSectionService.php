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
     * portrait upload produces the mobile (4:5) one. Without a portrait, mobile is cut
     * from the landscape image, and re-cut whenever the landscape image changes.
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

        if ($landscape instanceof UploadedFile) {
            $replaced[] = $section->image;
            $replaced[] = $section->image_tablet;
            $section->image = $this->images->render($landscape, 'desktop', 'landscape_image');
            $section->image_tablet = $this->images->render($landscape, 'tablet', 'landscape_image');
        }

        if ($portrait instanceof UploadedFile) {
            $replaced[] = $section->image_mobile;
            $section->image_mobile = $this->images->render($portrait, 'mobile', 'portrait_image');
            $content['mobile_source'] = 'portrait';
        } elseif ($landscape instanceof UploadedFile && ($content['mobile_source'] ?? 'landscape') !== 'portrait') {
            $replaced[] = $section->image_mobile;
            $section->image_mobile = $this->images->render($landscape, 'mobile', 'landscape_image');
            $content['mobile_source'] = 'landscape';
        }

        $section->content = $content;
        $section->save();

        $this->images->delete(...$replaced);

        return $section;
    }
}
