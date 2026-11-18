<?php

namespace App\Support;

class RobotsTxt
{
    /**
     * The robots.txt body: keeps crawlers out of the admin and account areas and points them at the sitemap.
     */
    public static function contents(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /api/',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ]);
    }
}
