<?php

namespace App\Http\Requests\Admin\HomepageSection;

use App\Models\HomepageSection;
use App\Services\BannerImageService;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HomepageSection::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['banner', 'section'])],
            'title' => ['required', 'string', FieldLimits::max('banner.title')],
            'subtitle' => ['nullable', 'string', FieldLimits::max('banner.subtitle')],
            // Raw uploads (e.g. straight from an image generator); BannerImageService crops
            // and converts them to the desktop, tablet and mobile WebP renditions.
            'landscape_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:min_width=1200,min_height=500,max_width='.BannerImageService::MAX_SOURCE_DIMENSION.',max_height='.BannerImageService::MAX_SOURCE_DIMENSION],
            'portrait_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:min_width=800,min_height=1000,max_width='.BannerImageService::MAX_SOURCE_DIMENSION.',max_height='.BannerImageService::MAX_SOURCE_DIMENSION],
            'link_url' => ['nullable', 'url:http,https', FieldLimits::max('banner.link_url')],
            'link_label' => ['nullable', 'string', FieldLimits::max('banner.link_label')],
            'show_text' => ['boolean'],
            // Each banner (and each section) needs its own position in the order.
            'sort_order' => [
                'integer', 'min:0', 'max:'.FieldLimits::get('number.sort_order'),
                Rule::unique('homepage_sections', 'sort_order')->where('type', $this->input('type')),
            ],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $kind = $this->input('type') === 'section' ? 'section' : 'banner';

        return [
            'sort_order.unique' => "Another {$kind} already uses order {$this->input('sort_order')}. Choose a different number.",
        ];
    }
}
