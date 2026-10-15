<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'settings.all';

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

    public function loginMode(): string
    {
        return (string) $this->get('auth', 'login_mode', 'both');
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
