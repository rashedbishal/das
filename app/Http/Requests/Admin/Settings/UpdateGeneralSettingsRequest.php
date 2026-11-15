<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public const SOCIAL_NETWORKS = ['facebook', 'instagram', 'youtube', 'linkedin', 'x'];

    public function authorize(): bool
    {
        return $this->user()?->can('manageGeneral', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', FieldLimits::max('settings.site_name')],
            'logo' => ['nullable', 'string', FieldLimits::max('settings.logo')],
            'favicon' => ['nullable', 'string', FieldLimits::max('settings.favicon')],
            'contact_email' => ['nullable', 'email', FieldLimits::max('settings.contact_email')],
            'contact_phone' => ['nullable', 'string', FieldLimits::max('settings.contact_phone')],
            'address' => ['nullable', 'string', FieldLimits::max('settings.address')],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', FieldLimits::max('settings.timezone'), 'timezone'],
            'address_city' => ['nullable', 'string', FieldLimits::max('settings.address_city')],
            'address_region' => ['nullable', 'string', FieldLimits::max('settings.address_region')],
            'postal_code' => ['nullable', 'string', FieldLimits::max('settings.postal_code')],
            'map_url' => ['nullable', 'url:http,https', FieldLimits::max('settings.map_url')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'opening_hours' => ['nullable', 'array', 'size:7'],
            'opening_hours.*.day' => ['required', 'distinct', Rule::in(self::DAYS)],
            'opening_hours.*.closed' => ['required', 'boolean'],
            'opening_hours.*.opens' => ['nullable', 'required_if_declined:opening_hours.*.closed', 'date_format:H:i'],
            'opening_hours.*.closes' => ['nullable', 'required_if_declined:opening_hours.*.closed', 'date_format:H:i', 'after:opening_hours.*.opens'],
            'social_links' => ['nullable', 'array:'.implode(',', self::SOCIAL_NETWORKS)],
            'social_links.*' => ['nullable', 'url:http,https', FieldLimits::max('settings.social_link')],
        ];
    }

    /**
     * Stores each day as {day, closed: bool, opens, closes}, with no times on closed days.
     *
     * @param  array<array-key, mixed>|int|string|null  $key
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();

        if (isset($data['opening_hours']) && is_array($data['opening_hours'])) {
            $data['opening_hours'] = array_map(function (array $day) {
                $closed = filter_var($day['closed'], FILTER_VALIDATE_BOOLEAN);

                return [
                    'day' => $day['day'],
                    'closed' => $closed,
                    'opens' => $closed ? null : ($day['opens'] ?? null),
                    'closes' => $closed ? null : ($day['closes'] ?? null),
                ];
            }, array_values($data['opening_hours']));
        }

        return data_get($data, $key, $default);
    }
}
