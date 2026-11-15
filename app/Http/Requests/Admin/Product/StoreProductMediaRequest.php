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
                        'max:'.ProductMediaService::MAX_IMAGE_UPLOAD_SIZE_KB,
                        'dimensions:max_width='.ProductMediaService::MAX_IMAGE_SOURCE_DIMENSION
                            .',max_height='.ProductMediaService::MAX_IMAGE_SOURCE_DIMENSION,
                    ],
                ),
            ],
            'url' => ['required_if:source,url', 'nullable', 'url:http,https', 'max:2048'],
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
        return [
            'file.mimes' => $this->input('type') === 'video'
                ? 'Videos must be '.implode(' or ', ProductMediaService::VIDEO_FORMATS).'.'
                : 'Images must be WebP, JPG or PNG.',
            'file.max' => $this->input('type') === 'video'
                ? 'Videos must be '.(ProductMediaService::MAX_VIDEO_SIZE_KB / 1024).' MB or smaller.'
                : 'Images must be '.(ProductMediaService::MAX_IMAGE_UPLOAD_SIZE_KB / 1024).' MB or smaller.',
            'file.dimensions' => 'Images can be at most '.ProductMediaService::MAX_IMAGE_SOURCE_DIMENSION.'px on either side.',
        ];
    }
}
