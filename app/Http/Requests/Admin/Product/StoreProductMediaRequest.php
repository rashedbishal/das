<?php

namespace App\Http\Requests\Admin\Product;

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
                'max:10240',
                Rule::when($this->input('type') === 'video', ['mimes:mp4,mov,webm'], ['image', 'mimes:jpg,jpeg,png,webp']),
            ],
            'url' => ['required_if:source,url', 'nullable', 'url', 'max:2048'],
            'is_main' => ['boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
