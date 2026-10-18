<?php

namespace App\Http\Resources;

use App\Models\HomepageSection;
use App\Services\BannerImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HomepageSection
 */
class HomepageSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'link_url' => $this->link_url,
            'link_label' => $this->link_label,
            'show_text' => (bool) ($this->content['show_text'] ?? true),
            'images' => [
                'desktop' => BannerImageService::url($this->image),
                'tablet' => BannerImageService::url($this->image_tablet),
                'mobile' => BannerImageService::url($this->image_mobile),
            ],
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
        ];
    }
}
