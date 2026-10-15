<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateAuthSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateCatalogSeoSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateMessengerSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateWhatsappSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function edit(): Response
    {
        $this->authorize('view', Setting::class);

        return Inertia::render('admin/settings/Index', [
            'general' => $this->settings->group('general'),
            'auth' => $this->settings->group('auth'),
            'whatsapp' => $this->settings->group('whatsapp'),
            'messenger' => $this->settings->group('messenger'),
            'catalog' => $this->settings->group('catalog'),
            'seo' => $this->settings->group('seo'),
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->persist('general', $request->validated());

        return back();
    }

    public function updateAuth(UpdateAuthSettingsRequest $request): RedirectResponse
    {
        $this->persist('auth', $request->validated());

        return back();
    }

    public function updateWhatsapp(UpdateWhatsappSettingsRequest $request): RedirectResponse
    {
        $this->persist('whatsapp', $request->validated());

        return back();
    }

    public function updateMessenger(UpdateMessengerSettingsRequest $request): RedirectResponse
    {
        $this->persist('messenger', $request->validated());

        return back();
    }

    public function updateCatalogSeo(UpdateCatalogSeoSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->settings->set('catalog', 'page_size', $data['page_size']);
        $this->settings->set('catalog', 'featured_limit', $data['featured_limit']);
        $this->settings->set('seo', 'default_title', $data['default_title'] ?? null);
        $this->settings->set('seo', 'default_description', $data['default_description'] ?? null);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            $this->settings->set($group, $key, $value);
        }
    }
}
