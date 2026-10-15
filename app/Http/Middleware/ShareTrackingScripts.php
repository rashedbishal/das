<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareTrackingScripts
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('headScripts', (string) $this->settings->get('tracking', 'head_scripts', ''));
        View::share('bodyScripts', (string) $this->settings->get('tracking', 'body_scripts', ''));

        return $next($request);
    }
}
