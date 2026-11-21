<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /** Default About page copy, from the client's brief. */
    private const ABOUT_DEFAULTS = [
        'headline_line_1' => 'Only by focusing',
        'headline_line_2' => 'can we become professional.',
        'intro' => 'And only by being professional can we better assist you, from the first enquiry to after-sales service.',
        'heading' => 'Supporting Bangladesh’s dental lab industry.',
        'paragraph_1' => 'DAS (Dental Accessories Source), registered in 2023 and a member of the Bangladesh Dental Merchant Traders Association (BDMTA), is a leading supplier of dental materials and equipment, as well as after-sales service.',
        'paragraph_2' => 'DAS has supported the development of Bangladesh’s dental lab industry with new, high-quality products and services in the prosthodontic field, especially crowns, bridges and dentures.',
        'principles' => [
            [
                'title' => 'A high-quality supply chain',
                'text' => 'Reliable materials and equipment for clinics and laboratories, sourced through a supply chain built on quality.',
            ],
            [
                'title' => 'A professional sales team',
                'text' => 'Convenient, knowledgeable pre-sales guidance, so you can choose the right product with confidence.',
            ],
            [
                'title' => 'Dedicated after-sales service',
                'text' => 'Faster, more convenient after-sales support that keeps your practice running long after delivery.',
            ],
        ],
    ];

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return $this->all()->get("{$group}.{$key}", $default);
    }

    public function set(string $group, string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        return $this->all()
            ->filter(fn (mixed $value, string $key) => str_starts_with($key, "{$group}."))
            ->mapWithKeys(fn (mixed $value, string $key) => [substr($key, strlen($group) + 1) => $value])
            ->all();
    }

    /**
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        // Cache a plain array, never a Collection instance: this cache store
        // does not reliably round-trip objects through serialize/unserialize
        // (observed as __PHP_Incomplete_Class on read). Arrays are always safe.
        $settings = Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()->get()->mapWithKeys(
                fn (Setting $setting) => ["{$setting->group}.{$setting->key}" => $setting->value],
            )->all();
        });

        return collect($settings);
    }

    /**
     * The About page copy. Each field falls back to its default until an admin
     * saves it, so the page always has complete text.
     *
     * @return array{headline_line_1: string, headline_line_2: string, intro: string, heading: string, paragraph_1: string, paragraph_2: string, principles: list<array{title: string, text: string}>}
     */
    public function aboutPage(): array
    {
        $saved = $this->group('about');
        $defaults = self::ABOUT_DEFAULTS;

        $content = [];
        foreach ($defaults as $key => $default) {
            if ($key === 'principles') {
                continue;
            }
            $value = $saved[$key] ?? null;
            $content[$key] = is_string($value) && trim($value) !== '' ? $value : $default;
        }

        $principles = $defaults['principles'];
        if (is_array($saved['principles'] ?? null)) {
            foreach ($principles as $i => $principle) {
                foreach (['title', 'text'] as $field) {
                    $value = $saved['principles'][$i][$field] ?? null;
                    if (is_string($value) && trim($value) !== '') {
                        $principles[$i][$field] = $value;
                    }
                }
            }
        }
        $content['principles'] = $principles;

        return $content;
    }

    public function registrationEnabled(): bool
    {
        return (bool) $this->get('auth', 'registration_enabled', true);
    }

    /**
     * @return array{enabled: bool, phone: string|null, message: string|null}
     */
    public function whatsappWidget(): array
    {
        $whatsapp = $this->group('whatsapp');
        $enabled = (bool) ($whatsapp['enabled'] ?? false);

        return [
            'enabled' => $enabled,
            'phone' => $enabled ? $this->toInternational(is_string($whatsapp['phone'] ?? null) ? $whatsapp['phone'] : null) : null,
            'message' => $whatsapp['message'] ?? null,
        ];
    }

    /**
     * @return array{enabled: bool, page_url: string|null}
     */
    public function messengerWidget(): array
    {
        $messenger = $this->group('messenger');
        $enabled = (bool) ($messenger['enabled'] ?? false);

        return [
            'enabled' => $enabled,
            'page_url' => $enabled ? ($messenger['page_url'] ?? null) : null,
        ];
    }

    private function toInternational(?string $localPhone): ?string
    {
        if ($localPhone === null) {
            return null;
        }

        return '880'.substr($localPhone, 1);
    }
}
