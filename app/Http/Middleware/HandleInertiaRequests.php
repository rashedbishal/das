<?php

namespace App\Http\Middleware;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Permission;
use App\Models\User;
use App\Services\GoogleSignInService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
                'permissions' => $this->permissionsFor($request->user()),
            ],
            'googleSignIn' => app(GoogleSignInService::class)->isEnabled(),
            // Labels the storefront login modal's identifier field (email, phone or both).
            'loginMode' => $settings->loginMode(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'contactWidgets' => [
                'whatsapp' => $settings->whatsappWidget(),
                'messenger' => $settings->messengerWidget(),
            ],
            'siteSettings' => [
                'name' => $settings->get('general', 'site_name', config('app.name')),
                'logo' => $settings->get('general', 'logo'),
                // Business details come only from Admin → Settings (nothing is seeded or
                // hard-coded); the storefront hides each one that has not been set.
                'contactPhone' => $settings->get('general', 'contact_phone'),
                'contactEmail' => $settings->get('general', 'contact_email'),
                'address' => $settings->get('general', 'address'),
                'addressCity' => $settings->get('general', 'address_city'),
                'addressRegion' => $settings->get('general', 'address_region'),
                'postalCode' => $settings->get('general', 'postal_code'),
                'mapUrl' => $settings->get('general', 'map_url'),
                'latitude' => $settings->get('general', 'latitude'),
                'longitude' => $settings->get('general', 'longitude'),
                'openingHours' => $settings->get('general', 'opening_hours', []),
                'socialLinks' => array_filter((array) $settings->get('general', 'social_links', [])),
                'currency' => $settings->get('general', 'currency', 'BDT'),
                'deliveryNote' => $settings->get('catalog', 'delivery_note'),
            ],
            // Category links for the storefront header and footer, so they are in the
            // server-rendered HTML; sent once and kept across page visits.
            ...($request->is('admin', 'admin/*', 'dashboard', 'dashboard/*') ? [] : [
                'navCategories' => Inertia::once(fn () => CategoryResource::collection(
                    Category::query()
                        ->active()
                        ->withCount(['products' => fn ($query) => $query->published()])
                        ->with(['subcategories' => fn ($query) => $query->active()->orderBy('sort_order')])
                        ->orderBy('sort_order')
                        ->get(),
                )->resolve()),
            ]),
            'seo' => [
                'defaultTitle' => $settings->get('seo', 'default_title'),
                'defaultDescription' => $settings->get('seo', 'default_description'),
                'canonicalUrl' => $request->url(),
                'appUrl' => rtrim((string) config('app.url'), '/'),
                'googleSiteVerification' => $settings->get('seo', 'google_site_verification'),
                'bingSiteVerification' => $settings->get('seo', 'bing_site_verification'),
            ],
        ];
    }

    /**
     * Permission slugs the signed-in admin holds, so the admin UI can hide actions
     * the policies would refuse. Mother Admin implicitly holds every permission.
     *
     * @return list<string>
     */
    private function permissionsFor(?User $user): array
    {
        if ($user === null || ! $user->isAdmin() || $user->role === null) {
            return [];
        }

        $permissions = $user->isMotherAdmin()
            ? Permission::query()->get()
            : $user->role->permissions;

        return array_values($permissions
            ->map(fn (Permission $permission): string => $permission->slug)
            ->sort()
            ->all());
    }
}
