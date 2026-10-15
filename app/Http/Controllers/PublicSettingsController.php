<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function widgets(): JsonResponse
    {
        return response()->json([
            'whatsapp' => $this->settings->whatsappWidget(),
            'messenger' => $this->settings->messengerWidget(),
        ]);
    }
}
