<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateAuthSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateCatalogSeoSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateMessengerSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateWhatsappSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function show(): JsonResponse
    {
        $this->authorize('view', Setting::class);

        return response()->json([
            'general' => $this->settings->group('general'),
            'auth' => $this->settings->group('auth'),
            'whatsapp' => $this->settings->group('whatsapp'),
            'messenger' => $this->settings->group('messenger'),
            'catalog' => $this->settings->group('catalog'),
            'seo' => $this->settings->group('seo'),
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        return $this->persist('general', $request->validated());
    }

    public function updateAuth(UpdateAuthSettingsRequest $request): JsonResponse
    {
        return $this->persist('auth', $request->validated());
    }

    public function updateWhatsapp(UpdateWhatsappSettingsRequest $request): JsonResponse
    {
        return $this->persist('whatsapp', $request->validated());
    }

    public function updateMessenger(UpdateMessengerSettingsRequest $request): JsonResponse
    {
        return $this->persist('messenger', $request->validated());
    }

    public function updateCatalogSeo(UpdateCatalogSeoSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->settings->set('catalog', 'page_size', $data['page_size']);
        $this->settings->set('catalog', 'featured_limit', $data['featured_limit']);
        if (isset($data['delivery_note'])) {
            $this->settings->set('catalog', 'delivery_note', $data['delivery_note']);
        }
        $this->settings->set('seo', 'default_title', $data['default_title'] ?? null);
        $this->settings->set('seo', 'default_description', $data['default_description'] ?? null);

        return response()->json(['catalog' => $this->settings->group('catalog'), 'seo' => $this->settings->group('seo')]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(string $group, array $data): JsonResponse
    {
        foreach ($data as $key => $value) {
            $this->settings->set($group, $key, $value);
        }

        return response()->json([$group => $this->settings->group($group)]);
    }
}
