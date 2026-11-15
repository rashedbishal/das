<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateAuthSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateCatalogSeoSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateMessengerSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateTrackingSettingsRequest;
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
            'authSettings' => $this->settings->group('auth'),
            'whatsapp' => $this->settings->group('whatsapp'),
            'messenger' => $this->settings->group('messenger'),
            'catalog' => $this->settings->group('catalog'),
            'seo' => $this->settings->group('seo'),
            'tracking' => $this->settings->group('tracking'),
            'canManageTracking' => request()->user()?->can('manageTracking', Setting::class) ?? false,
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->persist('general', $request->validated());

        return $this->backWithToast('General settings saved.');
    }

    public function updateAuth(UpdateAuthSettingsRequest $request): RedirectResponse
    {
        $this->persist('auth', $request->validated());

        return $this->backWithToast('Authentication settings saved.');
    }

    public function updateWhatsapp(UpdateWhatsappSettingsRequest $request): RedirectResponse
    {
        $this->persist('whatsapp', $request->validated());

        return $this->backWithToast('WhatsApp settings saved.');
    }

    public function updateMessenger(UpdateMessengerSettingsRequest $request): RedirectResponse
    {
        $this->persist('messenger', $request->validated());

        return $this->backWithToast('Messenger settings saved.');
    }

    public function updateCatalogSeo(UpdateCatalogSeoSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->settings->set('catalog', 'page_size', $data['page_size']);
        $this->settings->set('catalog', 'featured_limit', $data['featured_limit']);
        if (isset($data['delivery_note'])) {
            $this->settings->set('catalog', 'delivery_note', $data['delivery_note']);
        }
        $this->settings->set('seo', 'default_title', $data['default_title'] ?? null);
        $this->settings->set('seo', 'default_description', $data['default_description'] ?? null);

        return $this->backWithToast('Catalog & SEO settings saved.');
    }

    public function updateTracking(UpdateTrackingSettingsRequest $request): RedirectResponse
    {
        $this->persist('tracking', $request->validated());

        return $this->backWithToast('Tracking scripts saved.');
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

    private function backWithToast(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
