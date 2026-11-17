<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $defaults = [
        'general' => [
            'site_name' => 'DAS',
            'logo' => null,
            'favicon' => null,
            'contact_email' => 'afazad@gmail.com',
            'contact_phone' => '01761158577',
            'address' => null,
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'social_links' => [],
        ],
        'auth' => [
            'login_mode' => 'both',
            'registration_enabled' => true,
        ],
        'whatsapp' => [
            'enabled' => false,
            'phone' => null,
            'message' => "Hello, I'm interested in your products.",
        ],
        'messenger' => [
            'enabled' => false,
            'page_url' => null,
        ],
        'catalog' => [
            'page_size' => 24,
            'featured_limit' => 8,
            'delivery_note' => 'Through courier all over Bangladesh',
        ],
        'seo' => [
            'default_title' => 'DAS - Dental Machine & Accessories Source',
            'default_description' => 'Browse our catalog of dental equipment, instruments, and supplies.',
        ],
    ];

    public function run(): void
    {
        foreach ($this->defaults as $group => $keys) {
            foreach ($keys as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value],
                );
            }
        }
    }
}
