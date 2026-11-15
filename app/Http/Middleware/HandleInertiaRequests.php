<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = app(SettingsService::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'contactWidgets' => [
                'whatsapp' => $settings->whatsappWidget(),
                'messenger' => $settings->messengerWidget(),
            ],
            'siteSettings' => [
                'name' => $settings->get('general', 'site_name', config('app.name')),
                'logo' => $settings->get('general', 'logo'),
                // Contact details come from the CMS (seeded by SettingsSeeder); the storefront
                // has its own fallback in resources/js/lib/contact.ts when they are unset.
                'contactPhone' => $settings->get('general', 'contact_phone'),
                'contactEmail' => $settings->get('general', 'contact_email'),
                'address' => $settings->get('general', 'address'),
                'currency' => $settings->get('general', 'currency', 'BDT'),
                'deliveryNote' => $settings->get('catalog', 'delivery_note'),
            ],
            'seo' => [
                'defaultTitle' => $settings->get('seo', 'default_title'),
                'defaultDescription' => $settings->get('seo', 'default_description'),
                'canonicalUrl' => $request->url(),
                'appUrl' => rtrim((string) config('app.url'), '/'),
            ],
        ];
    }
}
