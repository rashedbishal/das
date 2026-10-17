<?php

namespace App\Http\Requests\Admin\Product;

use App\Services\ProductMediaService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', Rule::in(['upload', 'url'])],
            'type' => ['required', Rule::in(['image', 'video'])],
            'file' => [
                'required_if:source,upload',
                'file',
                Rule::when(
                    $this->input('type') === 'video',
                    ['mimes:'.implode(',', ProductMediaService::VIDEO_FORMATS), 'max:'.ProductMediaService::MAX_VIDEO_SIZE_KB],
                    [
                        'image',
                        'mimes:'.implode(',', ProductMediaService::IMAGE_FORMATS),
                        'max:'.ProductMediaService::MAX_IMAGE_SIZE_KB,
                        'dimensions:width='.ProductMediaService::REQUIRED_IMAGE_DIMENSION.',height='.ProductMediaService::REQUIRED_IMAGE_DIMENSION,
                    ],
                ),
            ],
            'url' => ['required_if:source,url', 'nullable', 'url', 'max:2048'],
            'is_main' => ['boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $size = ProductMediaService::REQUIRED_IMAGE_DIMENSION;

        return [
            'file.dimensions' => "Images must be exactly {$size}×{$size}px.",
            'file.mimes' => $this->input('type') === 'video'
                ? 'Videos must be '.implode(' or ', ProductMediaService::VIDEO_FORMATS).'.'
                : 'Images must be WebP.',
        ];
    }
}
