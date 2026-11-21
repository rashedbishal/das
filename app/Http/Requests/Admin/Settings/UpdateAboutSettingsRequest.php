<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAbout', Setting::class) ?? false;
    }

    /**
     * Every field is required: the limits are sized so the About page layout holds.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'headline_line_1' => ['required', 'string', FieldLimits::max('about.headline_line')],
            'headline_line_2' => ['required', 'string', FieldLimits::max('about.headline_line')],
            'intro' => ['required', 'string', FieldLimits::max('about.intro')],
            'heading' => ['required', 'string', FieldLimits::max('about.heading')],
            'paragraph_1' => ['required', 'string', FieldLimits::max('about.paragraph')],
            'paragraph_2' => ['required', 'string', FieldLimits::max('about.paragraph')],
            'principles' => ['required', 'array', 'size:3'],
            'principles.*.title' => ['required', 'string', FieldLimits::max('about.principle_title')],
            'principles.*.text' => ['required', 'string', FieldLimits::max('about.principle_text')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'headline_line_1' => 'headline line 1',
            'headline_line_2' => 'headline line 2',
            'paragraph_1' => 'paragraph 1',
            'paragraph_2' => 'paragraph 2',
            'principles.*.title' => 'card title',
            'principles.*.text' => 'card text',
        ];
    }
}
