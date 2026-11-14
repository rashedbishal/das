<?php

namespace App\Http\Requests\Admin\HomepageSection;

use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('homepage_section')) ?? false;
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
            'landscape_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:min_width=1200,min_height=500'],
            'portrait_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480', 'dimensions:min_width=800,min_height=1000'],
            'link_url' => ['nullable', 'url', FieldLimits::max('banner.link_url')],
            'link_label' => ['nullable', 'string', FieldLimits::max('banner.link_label')],
            'show_text' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:'.FieldLimits::get('number.sort_order')],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
